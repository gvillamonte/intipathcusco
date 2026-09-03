<?php
// config/izipay.php
// Credenciales API REST de IZIPAY (Backoffice Vendedor -> Configuración -> API REST).
// Soporta dos modos: TEST y PRODUCTION, cada uno con sus propias credenciales.
// Se cargan desde la BD (tabla config_pagos, pantalla admin/config_izipay.php).

// URLs (las mismas para test y producción — lo que cambia son las credenciales)
if (!defined('IZIPAY_API_URL'))    define('IZIPAY_API_URL', 'https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment');
if (!defined('IZIPAY_STATIC_URL')) define('IZIPAY_STATIC_URL', 'https://static.micuentaweb.pe');

// Conexión BD si no existe
if (!isset($db) || !($db instanceof PDO)) {
    require_once __DIR__ . '/database.php';
    $db = (new Database())->getConnection();
}

// Cargar todas las claves IZIPAY de config_pagos
$izipay_all = [];
if ($db instanceof PDO) {
    try {
        $stmt_iz = $db->query("SELECT clave, valor FROM config_pagos WHERE clave LIKE 'izipay%'");
        foreach ($stmt_iz->fetchAll(PDO::FETCH_ASSOC) as $fila_iz) {
            $izipay_all[$fila_iz['clave']] = trim($fila_iz['valor']);
        }
        $stmt_iz->closeCursor();
    } catch (Exception $e) {
        $izipay_all = [];
    }
}

// Modo activo (TEST por defecto)
$izipay_modo = strtoupper($izipay_all['izipay_modo'] ?? 'TEST');
if (!in_array($izipay_modo, ['TEST', 'PRODUCTION'])) $izipay_modo = 'TEST';

// Moneda (valor por defecto; el flujo de pago respeta la selección del cliente y acepta PEN y USD)
$izipay_moneda = strtoupper($izipay_all['izipay_moneda'] ?? 'USD');
if (!in_array($izipay_moneda, ['PEN', 'USD'])) $izipay_moneda = 'USD';

// Cargar credenciales según el modo activo
$prefijo = ($izipay_modo === 'PRODUCTION') ? 'prod' : 'test';

// Función para obtener valor: primero claves nuevas, luego fallback a claves antiguas
function izipayCfg($all, $nueva, $antigua) {
    $v = $all[$nueva] ?? '';
    if ($v === '') $v = $all[$antigua] ?? '';
    return trim($v);
}

$u = izipayCfg($izipay_all, 'izipay_username_' . $prefijo, 'izipay_username');
$p = izipayCfg($izipay_all, 'izipay_password_' . $prefijo, 'izipay_password');
$pk = izipayCfg($izipay_all, 'izipay_public_key_' . $prefijo, 'izipay_public_key');
$h = izipayCfg($izipay_all, 'izipay_hmac_' . $prefijo, 'izipay_hmac');

if ($u !== '' && $p !== '' && $pk !== '' && $h !== '') {
    define('IZIPAY_USERNAME',    $u);
    define('IZIPAY_PASSWORD',    $p);
    define('IZIPAY_PUBLIC_KEY',  $pk);
    define('IZIPAY_HMAC_SHA256', $h);
    define('IZIPAY_MONEDA',      $izipay_moneda);
    define('IZIPAY_MODO',        $izipay_modo);
} else {
    define('IZIPAY_USERNAME',    'CHANGE_ME_USER_ID');
    define('IZIPAY_PASSWORD',    'CHANGE_ME_PASSWORD');
    define('IZIPAY_PUBLIC_KEY',  'CHANGE_ME_PUBLIC_KEY');
    define('IZIPAY_HMAC_SHA256', 'CHANGE_ME_HMAC_SHA_256');
    define('IZIPAY_MONEDA',      'USD');
    define('IZIPAY_MODO',        $izipay_modo);
}
