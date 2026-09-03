<?php
// config/paypal.php
// Credenciales REST de PayPal (developer.paypal.com -> Apps & Credentials).
// Se cargan desde la BD (tabla config_pagos, pantalla admin/config_paypal.php).
// Si la BD no tiene credenciales, se usan estas constantes de respaldo.

// Si el llamador aún no creó la conexión (los flujos públicos incluyen el
// config antes de usar la BD), la creamos aquí para que las constantes
// SIEMPRE reflejen lo guardado en la BD.
if (!isset($db) || !($db instanceof PDO)) {
    require_once __DIR__ . '/database.php';
    $db = (new Database())->getConnection();
}

$paypal_db_config = null;
if ($db instanceof PDO) {
    try {
        $stmt_py = $db->query("SELECT clave, valor FROM config_pagos WHERE clave IN ('paypal_client_id','paypal_client_secret','paypal_mode','paypal_webhook_id')");
        $filas_py = $stmt_py->fetchAll(PDO::FETCH_ASSOC);
        $stmt_py->closeCursor();
        $paypal_db_config = [];
        foreach ($filas_py as $fila_py) $paypal_db_config[$fila_py['clave']] = trim($fila_py['valor']);
    } catch (Exception $e) {
        $paypal_db_config = null;
    }
}

if (is_array($paypal_db_config)
    && !empty($paypal_db_config['paypal_client_id'])
    && !empty($paypal_db_config['paypal_client_secret'])) {
    define('PAYPAL_CLIENT_ID',     $paypal_db_config['paypal_client_id']);
    define('PAYPAL_CLIENT_SECRET', $paypal_db_config['paypal_client_secret']);
    define('PAYPAL_MODE',          strtolower($paypal_db_config['paypal_mode'] ?? '') === 'live' ? 'live' : 'sandbox');
    define('PAYPAL_WEBHOOK_ID',    $paypal_db_config['paypal_webhook_id'] ?? '');
} else {
    define('PAYPAL_CLIENT_ID',     'CHANGE_ME_CLIENT_ID');
    define('PAYPAL_CLIENT_SECRET', 'CHANGE_ME_CLIENT_SECRET');
    define('PAYPAL_MODE',          'sandbox');
    define('PAYPAL_WEBHOOK_ID',    '');
}

// Endpoints según modo (sandbox / live)
if (PAYPAL_MODE === 'live') {
    define('PAYPAL_API_URL', 'https://api-m.paypal.com');
    define('PAYPAL_WEB_URL', 'https://www.paypal.com');
} else {
    define('PAYPAL_API_URL', 'https://api-m.sandbox.paypal.com');
    define('PAYPAL_WEB_URL', 'https://www.sandbox.paypal.com');
}

// PayPal opera solo en USD (para Perú). El equivalente PEN se muestra en la pasarela IZIPAY.
define('PAYPAL_MONEDA', 'USD');