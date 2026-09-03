<?php
/**
 * migrar_izipay.php
 * Migración para la integración IZIPAY + seguridad anti-duplicados.
 * Ejecutar UNA SOLA VEZ en el servidor (https://tudominio/migrar_izipay.php)
 * y luego ELIMINAR este archivo.
 *
 * Es idempotente: si ya se aplicó, no repite nada. No borra datos.
 */
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
if (!$db) {
    die("ERROR: no se pudo conectar a la base de datos.");
}

$ok = 0; $skip = 0; $err = 0;
function columnaExiste($db, $tabla, $columna) {
    $s = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute([$tabla, $columna]);
    return (int)$s->fetchColumn() > 0;
}
function indiceExiste($db, $tabla, $indice) {
    $s = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $s->execute([$tabla, $indice]);
    return (int)$s->fetchColumn() > 0;
}
function paso($db, $nombre, $sql, $condition) {
    global $ok, $skip, $err;
    if ($condition) { echo "[SKIP] $nombre (ya existe)\n"; $skip++; return; }
    try {
        $db->exec($sql);
        echo "[OK]   $nombre\n"; $ok++;
    } catch (Exception $e) {
        echo "[ERROR] $nombre -> " . $e->getMessage() . "\n"; $err++;
    }
}

echo "<pre>\n=== Migración IZIPAY / anti-duplicados ===\n\n";

paso($db, "reservas.token (columna)", "ALTER TABLE reservas ADD COLUMN token VARCHAR(64) NULL AFTER codigo",
    columnaExiste($db, 'reservas', 'token'));

paso($db, "uq_reservas_token (índice único)", "ALTER TABLE reservas ADD UNIQUE INDEX uq_reservas_token (token)",
    indiceExiste($db, 'reservas', 'uq_reservas_token'));

paso($db, "uq_reservas_codigo (índice único)", "ALTER TABLE reservas ADD UNIQUE INDEX uq_reservas_codigo (codigo)",
    indiceExiste($db, 'reservas', 'uq_reservas_codigo'));

paso($db, "reservas.email_pago_enviado (columna)", "ALTER TABLE reservas ADD COLUMN email_pago_enviado TINYINT(1) NOT NULL DEFAULT 0",
    columnaExiste($db, 'reservas', 'email_pago_enviado'));

paso($db, "idx_reservas_created_at (índice)", "ALTER TABLE reservas ADD INDEX idx_reservas_created_at (created_at)",
    indiceExiste($db, 'reservas', 'idx_reservas_created_at'));

paso($db, "pagos.transaction_id (columna)", "ALTER TABLE pagos ADD COLUMN transaction_id VARCHAR(100) NULL",
    columnaExiste($db, 'pagos', 'transaction_id'));

paso($db, "uq_pagos_transaction (índice único)", "ALTER TABLE pagos ADD UNIQUE INDEX uq_pagos_transaction (transaction_id)",
    indiceExiste($db, 'pagos', 'uq_pagos_transaction'));

paso($db, "configuracion.tipo_cambio (columna)", "ALTER TABLE configuracion ADD COLUMN tipo_cambio DECIMAL(10,2) NOT NULL DEFAULT 3.75",
    columnaExiste($db, 'configuracion', 'tipo_cambio'));

$izipay_rows = (int)$db->query("SELECT COUNT(*) FROM config_pagos WHERE clave LIKE 'izipay_%'")->fetchColumn();
paso($db, "config_pagos: filas izipay_*", "INSERT INTO config_pagos (clave, valor, campo) VALUES
    ('izipay_username', '', 'User ID (API REST)'),
    ('izipay_password', '', 'Password (API REST)'),
    ('izipay_public_key', '', 'Public Key'),
    ('izipay_hmac', '', 'HMAC SHA-256'),
    ('izipay_moneda', 'USD', 'Moneda por defecto IZIPAY')
    ON DUPLICATE KEY UPDATE campo = VALUES(campo)", $izipay_rows > 0);

$paypal_rows = (int)$db->query("SELECT COUNT(*) FROM config_pagos WHERE clave LIKE 'paypal_%'")->fetchColumn();
paso($db, "config_pagos: filas paypal_*", "INSERT INTO config_pagos (clave, valor, campo) VALUES
    ('paypal_client_id', '', 'Client ID'),
    ('paypal_client_secret', '', 'Client Secret'),
    ('paypal_mode', 'sandbox', 'Modo PayPal (sandbox/live)'),
    ('paypal_webhook_id', '', 'Webhook ID')
    ON DUPLICATE KEY UPDATE campo = VALUES(campo)", $paypal_rows > 0);

$logo_rows = (int)$db->query("SELECT COUNT(*) FROM config_pagos WHERE clave = 'pago_logo'")->fetchColumn();
paso($db, "config_pagos: fila pago_logo", "INSERT INTO config_pagos (clave, valor, campo) VALUES
    ('pago_logo', '', 'Logo de las paginas de pago')
    ON DUPLICATE KEY UPDATE campo = VALUES(campo)", $logo_rows > 0);

// Backfill: token para reservas existentes (URLs públicas seguras)
$s = $db->query("SELECT COUNT(*) FROM reservas WHERE token IS NULL OR token = ''");
$sin_token = (int)$s->fetchColumn();
if ($sin_token > 0) {
    $db->exec("UPDATE reservas SET token = MD5(UUID()) WHERE token IS NULL OR token = ''");
    echo "[OK]   Backfill de tokens para $sin_token reserva(s)\n";
    $ok++;
} else {
    echo "[SKIP] Backfill de tokens (todas las reservas ya tienen token)\n";
    $skip++;
}

echo "\nResumen: $ok aplicado(s), $skip ya existía(n), $err error(es).\n";
if ($err > 0) echo "Revisa los errores anteriores.\n";
echo "</pre>\n";