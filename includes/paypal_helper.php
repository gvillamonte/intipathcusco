<?php
// includes/paypal_helper.php
// Integración REST de PayPal (Checkout v2): token OAuth, creación de orden,
// captura, verificación de webhook y prueba de conexión.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/paypal.php';

use GuzzleHttp\Client;

/**
 * Obtiene el access token OAuth2 de PayPal (client_credentials).
 * Convierte errores HTTP en mensajes legibles (error_description).
 */
function paypalLlamarToken($client_id, $client_secret, $api_url) {
    $client = new Client(['timeout' => 30]);
    try {
        $resp = $client->post($api_url . '/v1/oauth2/token', [
            'auth' => [$client_id, $client_secret],
            'form_params' => ['grant_type' => 'client_credentials'],
        ]);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $body = json_decode((string)$e->getResponse()->getBody(), true);
        $msg = $body['error_description'] ?? $body['error'] ?? $e->getMessage();
        throw new Exception('PayPal: ' . $msg);
    }
    $data = json_decode((string)$resp->getBody(), true);
    if (empty($data['access_token'])) {
        throw new Exception('PayPal: no se obtuvo el token OAuth (' . ($data['error_description'] ?? 'error desconocido') . ')');
    }
    return $data['access_token'];
}

/**
 * Obtiene el access token OAuth2 con las credenciales configuradas (constantes).
 */
function paypalGetToken() {
    if (PAYPAL_CLIENT_ID === 'CHANGE_ME_CLIENT_ID' || PAYPAL_CLIENT_SECRET === 'CHANGE_ME_CLIENT_SECRET') {
        throw new Exception('PayPal: credenciales no configuradas. Configúralas en Admin > Config. PayPal.');
    }
    return paypalLlamarToken(PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET, PAYPAL_API_URL);
}

/**
 * Crea una orden de pago (intent CAPTURE) y devuelve ['order_id', 'approve_url'].
 */
function paypalCrearOrden($db, $reserva, $monto, $moneda = 'USD') {
    $moneda = strtoupper($moneda) === 'PEN' ? 'USD' : strtoupper($moneda);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = $protocol . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

    $token_acceso = paypalGetToken();

    $data = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'RES-' . $reserva['codigo'],
            'custom_id'    => 'RES-' . $reserva['codigo'],
            'description'  => mb_substr($reserva['titulo'] ?? 'Reserva IntiPath Tours', 0, 127),
            'amount' => [
                'currency_code' => $moneda,
                'value'         => number_format((float)$monto, 2, '.', ''),
            ],
        ]],
        'application_context' => [
            'return_url'   => $base . 'retorno_paypal.php?t=' . urlencode($reserva['token']),
            'cancel_url'   => $base . 'seleccionar_pago.php?t=' . urlencode($reserva['token']),
            'brand_name'   => 'IntiPath Tours',
            'user_action'  => 'PAY_NOW',
            'locale'       => 'es-PE',
        ],
    ];

    $client = new Client(['timeout' => 30]);
    $resp = $client->post(PAYPAL_API_URL . '/v2/checkout/orders', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token_acceso,
        ],
        'json' => $data,
    ]);
    $orden = json_decode((string)$resp->getBody(), true);

    if (empty($orden['id']) || ($orden['status'] ?? '') !== 'CREATED') {
        $msg = $orden['message'] ?? json_encode($orden);
        throw new Exception('PayPal: ' . $msg);
    }

    $approve_url = '';
    foreach (($orden['links'] ?? []) as $link) {
        if (($link['rel'] ?? '') === 'approve') $approve_url = $link['href'] ?? '';
    }
    if (empty($approve_url)) {
        throw new Exception('PayPal: no se obtuvo la URL de aprobación.');
    }

    return ['order_id' => $orden['id'], 'approve_url' => $approve_url];
}

/**
 * Captura una orden aprobada.
 * Devuelve ['status', 'capture_id', 'amount', 'currency', 'reference_id'] o lanza excepción.
 */
function paypalCapturarOrden($order_id) {
    if (empty($order_id)) throw new Exception('PayPal: orden vacía.');

    $token_acceso = paypalGetToken();
    $client = new Client(['timeout' => 30]);
    $resp = $client->post(PAYPAL_API_URL . '/v2/checkout/orders/' . urlencode($order_id) . '/capture', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token_acceso,
        ],
        'json' => [],
    ]);
    $data = json_decode((string)$resp->getBody(), true);

    $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
    if (($data['status'] ?? '') !== 'COMPLETED' || !$capture || ($capture['status'] ?? '') !== 'COMPLETED') {
        $msg = $data['message'] ?? json_encode($data);
        throw new Exception('PayPal: la captura no se completó (' . $msg . ')');
    }

    return [
        'status'       => 'COMPLETED',
        'capture_id'   => $capture['id'] ?? '',
        'amount'       => (float)($capture['amount']['value'] ?? 0),
        'currency'     => $capture['amount']['currency_code'] ?? 'USD',
        'reference_id' => $data['purchase_units'][0]['reference_id'] ?? '',
    ];
}

/**
 * Prueba de conexión: obtiene el token OAuth con las credenciales de la BD
 * (funciona en la misma petición en que se guardan). No crea órdenes ni cobros.
 */
function paypalProbarConexion($db) {
    $stmt = $db->query("SELECT clave, valor FROM config_pagos WHERE clave IN ('paypal_client_id','paypal_client_secret','paypal_mode')");
    $c = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) $c[$f['clave']] = trim($f['valor']);
    $stmt->closeCursor();

    if (empty($c['paypal_client_id']) || empty($c['paypal_client_secret'])) {
        throw new Exception('PayPal: credenciales no configuradas. Configúralas en Admin > Config. PayPal.');
    }

    $modo = strtolower($c['paypal_mode'] ?? '') === 'live' ? 'live' : 'sandbox';
    $api_url = $modo === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

    $token = paypalLlamarToken($c['paypal_client_id'], $c['paypal_client_secret'], $api_url);
    return ['token' => substr($token, 0, 25) . '...', 'modo' => strtoupper($modo)];
}

/**
 * Verifica la firma de un webhook de PayPal (headers de transmisión + certificado).
 */
function paypalVerificarWebhook($headers, $body_bruto) {
    if (empty(PAYPAL_WEBHOOK_ID)) return false;

    $trans_id   = $headers['paypal-transmission-id'] ?? '';
    $trans_time = $headers['paypal-transmission-time'] ?? '';
    $trans_sig  = $headers['paypal-transmission-sig'] ?? '';
    $cert_url   = $headers['paypal-cert-url'] ?? '';
    $algo       = $headers['paypal-auth-algo'] ?? 'SHA256withRSA';

    if (empty($trans_id) || empty($trans_time) || empty($trans_sig) || empty($cert_url)) return false;
    if (!str_starts_with($cert_url, 'https://')) return false;

    try {
        $client = new Client(['timeout' => 20, 'verify' => true]);
        $resp_cert = $client->get($cert_url);
        $pem = (string)$resp_cert->getBody();
        if (stripos($pem, 'BEGIN PUBLIC KEY') === false) return false;

        $msg = $trans_id . '|' . $trans_time . '|' . PAYPAL_WEBHOOK_ID . '|' . $body_bruto;
        $sig = base64_decode($trans_sig);

        $pubkey = openssl_pkey_get_public($pem);
        if (!$pubkey) return false;

        $ok = openssl_verify($msg, $sig, $pubkey, OPENSSL_ALGO_SHA256);
        openssl_free_key($pubkey);
        return $ok === 1;
    } catch (Exception $e) {
        return false;
    }
}