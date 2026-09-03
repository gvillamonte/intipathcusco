<?php
// includes/izipay_helper.php
// Helpers de integración con IZIPAY (Perú): creación de pagos, verificación de firma
// y registro idempotente de pagos en la BD.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/izipay.php';
require_once __DIR__ . '/pago_helper.php';
require_once __DIR__ . '/tipo_cambio_helper.php';

use GuzzleHttp\Client;

/**
 * Busca la reserva por su token público (URL segura).
 */
function izipayObtenerReservaPorToken($db, $token) {
    $stmt = $db->prepare("SELECT r.*, t.titulo, t.titulo_en, t.duracion, t.imagen_principal
                          FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id
                          WHERE r.token = ? LIMIT 1");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Monto a cobrar: primero el adelanto pendiente; luego el saldo restante.
 */
function izipayMontoACobrar($db, $reserva) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
    $stmt->execute([$reserva['id']]);
    $total_pagado = (float)$stmt->fetchColumn();

    $monto_total = (float)$reserva['monto_total'];
    $adelanto = (float)$reserva['adelanto'];

    if ($total_pagado >= $adelanto) {
        return max(0, $monto_total - $total_pagado);
    }
    return max(0, $adelanto - $total_pagado);
}

/**
 * Convierte un monto USD a PEN usando el tipo de cambio automático (API SUNAT + cache).
 */
function izipayConvertirMoneda($db, $monto_usd, $moneda) {
    $moneda = strtoupper($moneda);
    if ($moneda === 'PEN') {
        return convertirUsdAPen($db, $monto_usd);
    }
    return round($monto_usd, 2);
}

/**
 * Crea el pago en IZIPAY y devuelve ['formToken', 'publicKey', 'moneda', 'monto'] o lanza excepción.
 *
 * Soporta PEN y USD en paralelo: intenta primero la moneda elegida por el cliente
 * (o la configurada si no se pasa). Si IZIPAY rechaza esa moneda por falta de
 * "acuerdo de aceptación" (p.ej. error PSP_610), reintenta automáticamente con la
 * otra moneda convirtiendo el monto según el tipo de cambio. Devuelve la moneda y
 * el monto realmente usados para que la pasarela muestre el cobro correcto.
 */
function izipayCrearPago($db, $reserva, $monto, $moneda, $monto_convertido = null) {
    $moneda = strtoupper($moneda);
    if (!in_array($moneda, ['PEN', 'USD'])) $moneda = IZIPAY_MONEDA;

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = $protocol . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

    $client = new Client(['timeout' => 30]);
    $auth = 'Basic ' . base64_encode(IZIPAY_USERNAME . ':' . IZIPAY_PASSWORD);

    // Intentos: primero la moneda elegida; si falla por rechazo de moneda, la otra.
    $intentos_moneda = [$moneda];
    $otra = ($moneda === 'USD') ? 'PEN' : 'USD';
    $intentos_moneda[] = $otra;

    $ultimo_error = '';
    $ultima_respuesta = null;

    foreach ($intentos_moneda as $moneda_intento) {
        // Convertir el monto base USD a la moneda del intento
        if ($moneda_intento === $moneda && $monto_convertido !== null) {
            $monto_intento = (float)$monto_convertido;
        } elseif ($moneda_intento === 'PEN') {
            $monto_intento = izipayConvertirMoneda($db, (float)$monto, 'PEN');
        } else {
            $monto_intento = round((float)$monto, 2);
        }
        $monto_centimos = (int)round($monto_intento * 100);

        $data = [
            'amount'   => $monto_centimos,
            'currency' => $moneda_intento,
            'orderId'  => 'RES-' . $reserva['codigo'],
            'customer' => [
                'email'          => $reserva['email'],
                'billingDetails' => [
                    'firstName'   => $reserva['nombre'] ?? 'Cliente',
                    'lastName'    => $reserva['apellido'] ?? '',
                    'phoneNumber' => $reserva['telefono'] ?? '',
                    'country'     => 'PE',
                    'city'        => 'Cusco',
                ]
            ],
            'urlReturn' => $base . 'retorno_izipay.php',
            'urlIpn'    => $base . 'ipn_izipay.php',
            'metadata'  => [
                'id_reserva'     => $reserva['id'],
                'codigo_reserva' => $reserva['codigo']
            ]
        ];

        try {
            $resp = $client->post(IZIPAY_API_URL, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => $auth
                ],
                'json' => $data
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = json_decode((string)$e->getResponse()->getBody(), true);
            $ultimo_error = $body['answer']['errorMessage'] ?? $body['errorMessage'] ?? $e->getMessage();
            $code = $body['answer']['errorCode'] ?? '';
            $ultima_respuesta = ['status' => 'ERROR', 'errorCode' => $code, 'errorMessage' => $ultimo_error];
            if (!esErrorDeMoneda($code, $ultimo_error)) {
                throw new Exception('IZIPAY: ' . $ultimo_error);
            }
            continue;
        }

        $respuesta = json_decode((string)$resp->getBody(), true);
        $ultima_respuesta = $respuesta;

        if (!isset($respuesta['status']) || $respuesta['status'] !== 'SUCCESS') {
            $ultimo_error = $respuesta['answer']['errorMessage'] ?? json_encode($respuesta);
            $code = $respuesta['answer']['errorCode'] ?? '';
            if (esErrorDeMoneda($code, $ultimo_error)) {
                continue; // la moneda no fue aceptada -> probar la otra
            }
            throw new Exception('IZIPAY: ' . $ultimo_error);
        }

        $formToken = $respuesta['answer']['formToken'] ?? '';
        if (empty($formToken)) {
            throw new Exception('IZIPAY: No se obtuvo el formToken');
        }

        // Éxito: devolver la moneda y monto realmente usados
        return [
            'formToken'  => $formToken,
            'publicKey'  => IZIPAY_PUBLIC_KEY,
            'moneda'     => $moneda_intento,
            'monto'      => $monto_intento
        ];
    }

    // Se agotaron los intentos (ambas monedas rechazadas)
    $msg = $ultima_respuesta['answer']['errorMessage'] ?? $ultimo_error;
    throw new Exception('IZIPAY: ' . $msg);
}

/**
 * Indica si un error de IZIPAY corresponde al rechazo de una moneda no aceptada
 * (p.ej. "No merchant acceptance agreement" / PSP_610), caso en el que se
 * reintenta automáticamente con la otra moneda.
 */
function esErrorDeMoneda($errorCode, $errorMessage) {
    $code = strtoupper((string)$errorCode);
    if ($code === 'PSP_610') return true;
    $msg = strtolower((string)$errorMessage);
    return (strpos($msg, 'acceptance agreement') !== false)
        || (strpos($msg, 'merchant does not accept') !== false)
        || (strpos($msg, 'currency not accepted') !== false)
        || (strpos($msg, 'currency not supported') !== false);
}

/**
 * Prueba de conexión con IZIPAY: crea un formToken de prueba (NO cobra nada).
 * Lee las credenciales directamente de la BD para que funcione en la misma
 * petición en que se guardan. Devuelve el formToken o lanza excepción.
 * En modo TEST, si USD falla, reintenta con PEN (IZIPAY test solo acepta PEN).
 */
function izipayProbarConexion($db, $monto = 1.00) {
    $stmt = $db->query("SELECT clave, valor FROM config_pagos WHERE clave LIKE 'izipay%'");
    $c = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) $c[$f['clave']] = trim($f['valor']);
    $stmt->closeCursor();

    // Modo activo
    $modo = strtoupper($c['izipay_modo'] ?? 'TEST');
    if (!in_array($modo, ['TEST', 'PRODUCTION'])) $modo = 'TEST';
    $prefijo = ($modo === 'PRODUCTION') ? 'prod' : 'test';

    // Cargar credenciales del modo activo (con fallback a claves antiguas)
    function izipayCfgTest($all, $nueva, $antigua) {
        $v = $all[$nueva] ?? '';
        if ($v === '') $v = $all[$antigua] ?? '';
        return trim($v);
    }
    $u = izipayCfgTest($c, 'izipay_username_' . $prefijo, 'izipay_username');
    $p = izipayCfgTest($c, 'izipay_password_' . $prefijo, 'izipay_password');
    $pk = izipayCfgTest($c, 'izipay_public_key_' . $prefijo, 'izipay_public_key');
    $h = izipayCfgTest($c, 'izipay_hmac_' . $prefijo, 'izipay_hmac');

    if ($u === '' || $p === '' || $pk === '' || $h === '') {
        throw new Exception('IZIPAY: credenciales del modo ' . $modo . ' no configuradas. Configúralas en Admin > Config. IZIPAY.');
    }

    $moneda = !empty($c['izipay_moneda']) ? strtoupper($c['izipay_moneda']) : 'USD';
    if (!in_array($moneda, ['PEN', 'USD'])) $moneda = 'USD';
    $monto_centimos = (int)round($monto * 100);

    $data = [
        'amount'   => $monto_centimos,
        'currency' => $moneda,
        'orderId'  => 'TEST-' . date('YmdHis'),
        'customer' => [
            'email'          => 'test@intipath.com',
            'billingDetails' => [
                'firstName' => 'Prueba',
                'lastName'  => 'Conexion',
                'country'   => 'PE',
                'city'      => 'Cusco',
            ]
        ],
        'metadata' => ['modo' => 'prueba_conexion_admin']
    ];

    $client = new Client(['timeout' => 30]);
    $auth = 'Basic ' . base64_encode($u . ':' . $p);

    try {
        $resp = $client->post(IZIPAY_API_URL, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => $auth
            ],
            'json' => $data
        ]);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $body = json_decode((string)$e->getResponse()->getBody(), true);
        $msg = $body['answer']['errorMessage'] ?? $body['errorMessage'] ?? $e->getMessage();
        throw new Exception('IZIPAY: ' . $msg);
    }

    $respuesta = json_decode((string)$resp->getBody(), true);

    // Si USD falla por "No merchant acceptance agreement", reintentar con PEN
    if (!isset($respuesta['status']) || $respuesta['status'] !== 'SUCCESS') {
        $code = $respuesta['answer']['errorCode'] ?? '';
        if ($code === 'PSP_610' && $moneda === 'USD') {
            $data['currency'] = 'PEN';
            $data['orderId'] = 'TEST-PEN-' . date('YmdHis');
            try {
                $resp2 = $client->post(IZIPAY_API_URL, [
                    'headers' => ['Content-Type' => 'application/json', 'Authorization' => $auth],
                    'json' => $data
                ]);
                $respuesta = json_decode((string)$resp2->getBody(), true);
                $moneda = 'PEN';
            } catch (\GuzzleHttp\Exception\ClientException $e2) {
                $body2 = json_decode((string)$e2->getResponse()->getBody(), true);
                $msg2 = $body2['answer']['errorMessage'] ?? $body2['errorMessage'] ?? $e2->getMessage();
                throw new Exception('IZIPAY: ' . $msg2);
            }
        } else {
            $msg = $respuesta['answer']['errorMessage'] ?? json_encode($respuesta);
            throw new Exception('IZIPAY: ' . $msg);
        }
    }

    $formToken = $respuesta['answer']['formToken'] ?? '';
    if (empty($formToken)) {
        throw new Exception('IZIPAY: No se obtuvo el formToken');
    }

    return ['formToken' => $formToken, 'moneda' => $moneda, 'monto' => $monto];
}

/**
 * Verifica la firma HMAC-SHA256 (hex) de un kr-answer contra el kr-hash recibido.
 */
function izipayVerificarFirma($kr_answer, $kr_hash, $clave) {
    if (empty($kr_answer) || empty($kr_hash) || empty($clave)) return false;
    $calc = hash_hmac('sha256', $kr_answer, $clave);
    return hash_equals(strtolower($calc), strtolower($kr_hash));
}

/**
 * Registra un pago confirmado de IZIPAY (idempotente).
 * Reutiliza el motor común registrarPagoConfirmado() de includes/pago_helper.php.
 *
 * Devuelve ['nuevo_pago'=>bool, 'nuevo_estado'=>string, 'total_pagado'=>float] o null.
 */
function izipayRegistrarPago($db, $id_reserva, $monto, $moneda, $metodo, $transaction_id) {
    return registrarPagoConfirmado($db, $id_reserva, $monto, $moneda, $metodo, $transaction_id);
}