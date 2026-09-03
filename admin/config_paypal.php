<?php
// admin/config_paypal.php
// Credenciales REST de PayPal + prueba de conexión desde el panel.

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('config_pagos');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../includes/paypal_helper.php';

$mensaje = '';
$resultado_prueba = null; // ['ok'=>bool, 'texto'=>string, 'detalle'=>string]

$CLAVES = ['paypal_client_id', 'paypal_client_secret', 'paypal_mode', 'paypal_webhook_id'];

// Cargar valores actuales desde BD
$stmt = $db->query("SELECT clave, valor FROM config_pagos WHERE clave IN ('paypal_client_id','paypal_client_secret','paypal_mode','paypal_webhook_id')");
$valores = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $valores[$fila['clave']] = $fila['valor'];
$stmt->closeCursor();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt_up = $db->prepare("INSERT INTO config_pagos (clave, valor, campo)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");

    foreach ($CLAVES as $clave) {
        if ($clave === 'paypal_mode') {
            $modo = strtolower($_POST['paypal_mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
            $stmt_up->execute([$clave, $modo, 'Modo PayPal (sandbox/live)']);
        } else {
            $v = trim($_POST[$clave] ?? '');
            if ($v === '') continue;
            $campo = [
                'paypal_client_id'     => 'Client ID',
                'paypal_client_secret' => 'Client Secret',
                'paypal_webhook_id'    => 'Webhook ID',
            ][$clave];
            $stmt_up->execute([$clave, $v, $campo]);
        }
    }
    $stmt_up->closeCursor();

    // Recargar valores
    $stmt = $db->query("SELECT clave, valor FROM config_pagos WHERE clave IN ('paypal_client_id','paypal_client_secret','paypal_mode','paypal_webhook_id')");
    $valores = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $valores[$fila['clave']] = $fila['valor'];
    $stmt->closeCursor();

    // Probar conexión (requiere client id y secret)
    if (isset($_POST['probar']) && $_POST['probar'] === '1') {
        if (empty($valores['paypal_client_id']) || empty($valores['paypal_client_secret'])) {
            $resultado_prueba = ['ok' => false, 'texto' => 'Completa el Client ID y Client Secret y guarda antes de probar.', 'detalle' => ''];
        } else {
            try {
                $prueba = paypalProbarConexion($db);
                $resultado_prueba = [
                    'ok' => true,
                    'texto' => 'Conexión exitosa. PayPal aceptó las credenciales y emitió un token OAuth.',
                    'detalle' => 'Token: ' . $prueba['token'] . ' | Modo: ' . $prueba['modo'] . ' | No se creó ninguna orden ni cobro.'
                ];
            } catch (Exception $e) {
                $resultado_prueba = [
                    'ok' => false,
                    'texto' => 'La conexión falló. Revisa las credenciales y el modo (sandbox/live).',
                    'detalle' => htmlspecialchars($e->getMessage())
                ];
            }
        }
        $mensaje = 'probado';
    } else {
        $mensaje = 'ok';
    }
}

$usando_respaldo = (defined('PAYPAL_CLIENT_ID') && PAYPAL_CLIENT_ID === 'CHANGE_ME_CLIENT_ID');
$modo_actual = defined('PAYPAL_MODE') ? PAYPAL_MODE : 'sandbox';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar PayPal | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --ip-turq: #0C9A9E; --ip-paypal: #003087; }
        body { background:#f4f7f6;font-family:'Segoe UI',sans-serif; }
        .admin-title { color:var(--admin-blue);font-weight:800;border-bottom:4px solid var(--admin-accent);display:inline-block;padding-bottom:5px;margin-bottom:25px;text-transform:uppercase;font-size:1.4rem; }
        .card { background:#fff;border-radius:12px;padding:25px;margin-bottom:25px;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #e0e0e0; }
        .card h2 { font-size:1.1rem;color:var(--admin-blue);margin:0 0 15px 0;padding-bottom:10px;border-bottom:2px solid var(--admin-accent); }
        .card h2 i { margin-right:8px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block;font-weight:600;font-size:0.85rem;color:#333;margin-bottom:5px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group select { width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:8px;font-size:14px; }
        .btn-admin { background:var(--admin-blue);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-admin:hover { background:#1e3f7a; }
        .btn-test { background:var(--ip-turq);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-test:hover { background:#0a7f83; }
        .estado-llaves { display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-left:10px; }
        .estado-ok { background:#d4edda;color:#155724; }
        .estado-falta { background:#fff3cd;color:#856404; }
        .modo-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800;margin-left:6px; }
        .modo-sandbox { background:#cce5ff;color:#004085; }
        .modo-live { background:#d4edda;color:#155724; }
        .result-box { border-radius:10px;padding:18px;margin-top:20px;font-size:14px;line-height:1.6; }
        .result-ok { background:#d4edda;border:1px solid #a3d9b1;color:#155724; }
        .result-err { background:#f8d7da;border:1px solid #f1b0b7;color:#721c24; }
        .result-box small { display:block;margin-top:6px;opacity:0.85;word-break:break-all; }
        .help-list { font-size:13.5px;color:#444;line-height:1.8;padding-left:20px; }
        .help-list code { background:#f0f0f0;padding:1px 6px;border-radius:4px;font-size:12px; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fab fa-paypal"></i> Configurar PayPal</h1>

        <?php if ($mensaje === 'ok'): ?>
            <script>Swal.fire({icon:'success',title:'Llaves guardadas',timer:1500,showConfirmButton:false});</script>
        <?php endif; ?>

        <div class="card">
            <h2>
                <i class="fas fa-key" style="color:var(--ip-paypal);"></i> Credenciales REST (Checkout v2)
                <?php if (!$usando_respaldo): ?>
                    <span class="estado-llaves estado-ok"><i class="fas fa-check-circle"></i> Configuradas</span>
                    <span class="modo-badge <?= $modo_actual === 'live' ? 'modo-live' : 'modo-sandbox' ?>"><?= strtoupper($modo_actual) ?></span>
                <?php else: ?>
                    <span class="estado-llaves estado-falta"><i class="fas fa-exclamation-triangle"></i> Sin configurar (respaldo CHANGE_ME)</span>
                <?php endif; ?>
            </h2>
            <p style="color:#777;font-size:13.5px;margin-top:-5px;">
                Las llaves se obtienen en <strong>developer.paypal.com</strong> → <strong>Apps &amp; Credentials</strong>
                (app "Default Application"). Se guardan en la base de datos y se usan automáticamente en
                checkout_paypal.php, retorno_paypal.php e ipn_paypal.php.
            </p>

            <form method="POST">
                <div class="form-group">
                    <label>Client ID</label>
                    <input type="text" name="paypal_client_id" value="<?= htmlspecialchars($valores['paypal_client_id'] ?? '') ?>" placeholder="Client ID de tu app en PayPal" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="password" name="paypal_client_secret" value="<?= htmlspecialchars($valores['paypal_client_secret'] ?? '') ?>" placeholder="Client Secret de tu app en PayPal" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Modo de operación</label>
                    <select name="paypal_mode" style="max-width:220px;">
                        <option value="sandbox" <?= ($valores['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (pruebas)</option>
                        <option value="live" <?= ($valores['paypal_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Producción (live)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Webhook ID <small style="color:#888;">(opcional, para notificaciones automáticas)</small></label>
                    <input type="text" name="paypal_webhook_id" value="<?= htmlspecialchars($valores['paypal_webhook_id'] ?? '') ?>" placeholder="ID del webhook creado en el panel de PayPal" autocomplete="off">
                </div>

                <?php if (is_array($resultado_prueba)): ?>
                    <div class="result-box <?= $resultado_prueba['ok'] ? 'result-ok' : 'result-err' ?>">
                        <strong><i class="fas <?= $resultado_prueba['ok'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i> <?= $resultado_prueba['texto'] ?></strong>
                        <?php if (!empty($resultado_prueba['detalle'])): ?>
                            <small><?= $resultado_prueba['detalle'] ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn-admin" name="guardar" value="1"><i class="fas fa-save"></i> Guardar llaves</button>
                    <button type="submit" class="btn-test" name="probar" value="1"><i class="fas fa-plug"></i> Probar conexión</button>
                </div>
                <small style="color:#999;display:block;margin-top:8px;">
                    "Probar conexión" solicita un token OAuth a PayPal con tus credenciales — <strong>no crea órdenes ni cobra nada</strong>.
                </small>
            </form>
        </div>

        <div class="card" style="background:#f8fbff;border:1px solid #d1e3fa;">
            <h2><i class="fas fa-info-circle" style="color:#1a73e8;"></i> Guía rápida</h2>
            <ul class="help-list">
                <li>Regístrate gratis en <strong>developer.paypal.com</strong> → <strong>Apps &amp; Credentials</strong> → copia el <strong>Client ID</strong> y <strong>Client Secret</strong> de la "Default Application" (modo Sandbox).</li>
                <li>En <strong>Testing Tools → Sandbox Accounts</strong> tienes la cuenta <strong>personal</strong> (comprador) y <strong>business</strong> (vendedor) con sus emails/contraseñas para simular pagos en <code>sandbox.paypal.com</code>.</li>
                <li>Para probar: deja el modo en <strong>Sandbox</strong>, guarda y usa "Probar conexión". Luego haz una reserva de prueba y elige PayPal en la página de pago.</li>
                <li>Para producción: cuenta <strong>PayPal Business</strong> real + app en modo Live; en Perú PayPal cobra en <strong>USD</strong>.</li>
                <li>Webhook (opcional): en el panel de PayPal crea un webhook con URL <code>https://intipathtours.com/ipn_paypal.php</code> y evento <code>PAYMENT.CAPTURE.COMPLETED</code>; pega aquí su ID. Sin webhook, el pago se confirma igualmente en el retorno del navegador.</li>
            </ul>
        </div>
    </main>
</div>
</body>
</html>