<?php
// admin/config_izipay.php
// Configuración IZIPAY: modo (Test/Producción) + credenciales duales.

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('config_pagos');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
require_once __DIR__ . '/../includes/izipay_helper.php';

// ── Migración idempotente: crear claves dual si no existen ──
$claves_nuevas = [
    'izipay_modo'            => 'TEST',
    'izipay_username_test'   => '',
    'izipay_password_test'   => '',
    'izipay_public_key_test' => '',
    'izipay_hmac_test'       => '',
    'izipay_username_prod'   => '',
    'izipay_password_prod'   => '',
    'izipay_public_key_prod' => '',
    'izipay_hmac_prod'       => '',
];
$existentes = [];
$stmt_ex = $db->query("SELECT clave FROM config_pagos WHERE clave LIKE 'izipay%'");
foreach ($stmt_ex->fetchAll(PDO::FETCH_ASSOC) as $r) $existentes[] = $r['clave'];
$stmt_ex->closeCursor();

foreach ($claves_nuevas as $k => $default) {
    if (!in_array($k, $existentes)) {
        $campo = [
            'izipay_modo'            => 'Modo Test/Producción',
            'izipay_username_test'   => 'User ID (Test)',
            'izipay_password_test'   => 'Password (Test)',
            'izipay_public_key_test' => 'Public Key (Test)',
            'izipay_hmac_test'       => 'HMAC SHA-256 (Test)',
            'izipay_username_prod'   => 'User ID (Producción)',
            'izipay_password_prod'   => 'Password (Producción)',
            'izipay_public_key_prod' => 'Public Key (Producción)',
            'izipay_hmac_prod'       => 'HMAC SHA-256 (Producción)',
        ][$k];
        $db->prepare("INSERT INTO config_pagos (clave, valor, campo) VALUES (?, ?, ?)")
           ->execute([$k, $default, $campo]);
    }
}

// Si hay credenciales antiguas y las nuevas están vacías, migrarlas como TEST
$antiguas = ['izipay_username', 'izipay_password', 'izipay_public_key', 'izipay_hmac'];
foreach ($antiguas as $ak) {
    $vk = $db->prepare("SELECT valor FROM config_pagos WHERE clave = ?");
    $vk->execute([$ak]); $old = trim($vk->fetchColumn() ?: ''); $vk->closeCursor();
    $nk = $ak . '_test';
    $vk2 = $db->prepare("SELECT valor FROM config_pagos WHERE clave = ?");
    $vk2->execute([$nk]); $new = trim($vk2->fetchColumn() ?: ''); $vk2->closeCursor();
    if ($old !== '' && $new === '') {
        $db->prepare("UPDATE config_pagos SET valor = ? WHERE clave = ?")->execute([$old, $nk]);
    }
}

// ── Cargar valores actuales ──
$CLAVES = ['izipay_modo',
    'izipay_username_test', 'izipay_password_test', 'izipay_public_key_test', 'izipay_hmac_test',
    'izipay_username_prod', 'izipay_password_prod', 'izipay_public_key_prod', 'izipay_hmac_prod',
    'izipay_moneda'];
$valores = [];
$stmt = $db->query("SELECT clave, valor FROM config_pagos WHERE clave IN ('" . implode("','", $CLAVES) . "')");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $valores[$fila['clave']] = $fila['valor'];
$stmt->closeCursor();

$mensaje = '';
$resultado_prueba = null;

// ── POST: guardar ──
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt_up = $db->prepare("INSERT INTO config_pagos (clave, valor, campo)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");

    // Modo
    $modo = strtoupper($_POST['izipay_modo'] ?? 'TEST');
    if (!in_array($modo, ['TEST', 'PRODUCTION'])) $modo = 'TEST';
    $stmt_up->execute(['izipay_modo', $modo, 'Modo Test/Producción']);

    // Moneda
    $moneda = strtoupper($_POST['izipay_moneda'] ?? 'USD');
    if (!in_array($moneda, ['PEN', 'USD'])) $moneda = 'USD';
    $stmt_up->execute(['izipay_moneda', $moneda, 'Moneda por defecto IZIPAY']);

    // Credenciales por modo
    foreach (['test', 'prod'] as $m) {
        $campos_modo = [
            'izipay_username_' . $m   => 'User ID (' . ($m === 'test' ? 'Test' : 'Producción') . ')',
            'izipay_password_' . $m   => 'Password (' . ($m === 'test' ? 'Test' : 'Producción') . ')',
            'izipay_public_key_' . $m => 'Public Key (' . ($m === 'test' ? 'Test' : 'Producción') . ')',
            'izipay_hmac_' . $m       => 'HMAC SHA-256 (' . ($m === 'test' ? 'Test' : 'Producción') . ')',
        ];
        foreach ($campos_modo as $clave => $campo) {
            $v = trim($_POST[$clave] ?? '');
            if ($v !== '') {
                $stmt_up->execute([$clave, $v, $campo]);
            }
        }
    }
    $stmt_up->closeCursor();

    // Recargar
    $stmt = $db->query("SELECT clave, valor FROM config_pagos WHERE clave IN ('" . implode("','", $CLAVES) . "')");
    $valores = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $valores[$fila['clave']] = $fila['valor'];
    $stmt->closeCursor();

    // Probar conexión
    if (isset($_POST['probar']) && $_POST['probar'] === '1') {
        $mk = ($modo === 'PRODUCTION') ? 'prod' : 'test';
        $faltan = empty($valores['izipay_username_' . $mk]) || empty($valores['izipay_password_' . $mk])
               || empty($valores['izipay_public_key_' . $mk]) || empty($valores['izipay_hmac_' . $mk]);
        if ($faltan) {
            $resultado_prueba = ['ok' => false, 'texto' => 'Completa las 4 llaves del modo ' . $modo . ' y guarda antes de probar.', 'detalle' => ''];
        } else {
            try {
                $prueba = izipayProbarConexion($db);
                $resultado_prueba = [
                    'ok' => true,
                    'texto' => 'Conexión exitosa (' . $modo . '). IZIPAY aceptó las credenciales.',
                    'detalle' => 'FormToken: ' . substr($prueba['formToken'], 0, 30) . '... | Moneda: ' . $prueba['moneda'] . ' | Monto: ' . $prueba['moneda'] . ' ' . number_format($prueba['monto'], 2) . ' (sin cobro)'
                ];
            } catch (Exception $e) {
                $resultado_prueba = ['ok' => false, 'texto' => 'La conexión falló (' . $modo . '). Revisa las credenciales.', 'detalle' => htmlspecialchars($e->getMessage())];
            }
        }
        $mensaje = 'probado';
    } else {
        $mensaje = 'ok';
    }
}

$modo_actual = strtoupper($valores['izipay_modo'] ?? 'TEST');
$usando_respaldo = (defined('IZIPAY_USERNAME') && IZIPAY_USERNAME === 'CHANGE_ME_USER_ID');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar IZIPAY | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --ip-turq: #0C9A9E; }
        body { background:#f4f7f6;font-family:'Segoe UI',sans-serif; }
        .admin-title { color:var(--admin-blue);font-weight:800;border-bottom:4px solid var(--admin-accent);display:inline-block;padding-bottom:5px;margin-bottom:25px;text-transform:uppercase;font-size:1.4rem; }
        .card { background:#fff;border-radius:12px;padding:25px;margin-bottom:25px;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #e0e0e0; }
        .card h2 { font-size:1.1rem;color:var(--admin-blue);margin:0 0 15px 0;padding-bottom:10px;border-bottom:2px solid var(--admin-accent); }
        .card h2 i { margin-right:8px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block;font-weight:600;font-size:0.85rem;color:#333;margin-bottom:5px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group select { width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:8px;font-size:14px;box-sizing:border-box; }
        .btn-admin { background:var(--admin-blue);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-admin:hover { background:#1e3f7a; }
        .btn-test { background:var(--ip-turq);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-test:hover { background:#0a7f83; }
        .estado-llaves { display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-left:10px; }
        .estado-ok { background:#d4edda;color:#155724; }
        .estado-falta { background:#fff3cd;color:#856404; }
        .result-box { border-radius:10px;padding:18px;margin-top:20px;font-size:14px;line-height:1.6; }
        .result-ok { background:#d4edda;border:1px solid #a3d9b1;color:#155724; }
        .result-err { background:#f8d7da;border:1px solid #f1b0b7;color:#721c24; }
        .result-box small { display:block;margin-top:6px;opacity:0.85;word-break:break-all; }
        .help-list { font-size:13.5px;color:#444;line-height:1.8;padding-left:20px; }
        .help-list code { background:#f0f0f0;padding:1px 6px;border-radius:4px;font-size:12px; }
        .moneda-select { display:flex;gap:12px;align-items:center; }
        .modo-toggle { display:flex;gap:0;border-radius:10px;overflow:hidden;border:2px solid var(--admin-blue);margin-bottom:20px;max-width:400px; }
        .modo-toggle input[type="radio"] { display:none; }
        .modo-toggle label { flex:1;text-align:center;padding:12px 20px;cursor:pointer;font-weight:700;font-size:14px;transition:all 0.3s; }
        .modo-toggle label.modo-test { background:#fff3cd;color:#856404; }
        .modo-toggle label.modo-prod { background:#fff;color:#155724; }
        .modo-toggle input#modo_test:checked ~ label.modo-test { background:#ffc107;color:#000; }
        .modo-toggle input#modo_prod:checked ~ label.modo-prod { background:#28a745;color:#fff; }
        .modo-badge { display:inline-block;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-left:12px; }
        .modo-badge-test { background:#ffc107;color:#000; }
        .modo-badge-prod { background:#28a745;color:#fff; }
        .cred-section { border:1px solid #e0e0e0;border-radius:10px;padding:20px;margin-bottom:15px; }
        .cred-section h3 { font-size:0.95rem;margin:0 0 15px 0;color:#555; }
        .cred-section h3 i { margin-right:6px; }
        .cred-section.active { border-color:var(--admin-blue);box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        .cred-section.inactive { opacity:0.6; }
        @media (max-width:768px) { .moneda-select { flex-direction:column;align-items:flex-start; } }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fas fa-credit-card"></i> Configurar IZIPAY</h1>

        <?php if ($mensaje === 'ok'): ?>
            <script>Swal.fire({icon:'success',title:'Llaves guardadas',timer:1500,showConfirmButton:false});</script>
        <?php endif; ?>

        <?php if ($usando_respaldo): ?>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px 18px;margin-bottom:20px;font-size:13px;">
            <i class="fas fa-exclamation-triangle" style="color:#856404;"></i>
            <strong style="color:#856404;">Credenciales de respaldo activas.</strong>
            <span style="color:#856404;"> Configura las credenciales reales de IZIPAY en las secciones de abajo.</span>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- MODO -->
            <div class="card">
                <h2><i class="fas fa-toggle-on" style="color:var(--ip-turq);"></i> Modo de operación
                    <span class="modo-badge <?= $modo_actual === 'PRODUCTION' ? 'modo-badge-prod' : 'modo-badge-test' ?>">
                        <?= $modo_actual === 'PRODUCTION' ? 'PRODUCCIÓN' : 'PRUEBA' ?>
                    </span>
                </h2>
                <div class="modo-toggle">
                    <input type="radio" name="izipay_modo" id="modo_test" value="TEST" <?= $modo_actual === 'TEST' ? 'checked' : '' ?>>
                    <label for="modo_test" class="modo-test"><i class="fas fa-flask"></i> Prueba (Test)</label>
                    <input type="radio" name="izipay_modo" id="modo_prod" value="PRODUCTION" <?= $modo_actual === 'PRODUCTION' ? 'checked' : '' ?>>
                    <label for="modo_prod" class="modo-prod"><i class="fas fa-rocket"></i> Producción</label>
                </div>
                <p style="color:#777;font-size:13px;margin:0;">
                    <strong>Test:</strong> Usa credenciales de prueba (no cobra dinero real).<br>
                    <strong>Producción:</strong> Usa credenciales reales (cobra dinero real).
                </p>
            </div>

            <!-- MONEDA -->
            <div class="card">
                <h2><i class="fas fa-coins" style="color:var(--admin-accent);"></i> Moneda</h2>
                <div class="moneda-select">
                    <select name="izipay_moneda" style="max-width:200px;">
                        <option value="PEN" <?= ($valores['izipay_moneda'] ?? 'USD') === 'PEN' ? 'selected' : '' ?>>PEN (soles)</option>
                        <option value="USD" <?= ($valores['izipay_moneda'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD (dólares)</option>
                    </select>
                    <small style="color:#888;">Valor por defecto. En la pantalla de pago el cliente elige la moneda (PEN o USD) y el sistema cobra en la seleccionada, con fallback automático si una no está habilitada.</small>
                </div>
            </div>

            <!-- CREDENCIALES TEST -->
            <div class="card cred-section <?= $modo_actual === 'TEST' ? 'active' : 'inactive' ?>">
                <h2><i class="fas fa-flask" style="color:#ffc107;"></i> Credenciales de PRUEBA
                    <?php
                    $test_ok = !empty($valores['izipay_username_test']) && !empty($valores['izipay_password_test'])
                            && !empty($valores['izipay_public_key_test']) && !empty($valores['izipay_hmac_test']);
                    ?>
                    <?php if ($test_ok): ?>
                        <span class="estado-llaves estado-ok"><i class="fas fa-check-circle"></i> Configuradas</span>
                    <?php else: ?>
                        <span class="estado-llaves estado-falta"><i class="fas fa-exclamation-triangle"></i> Incompletas</span>
                    <?php endif; ?>
                </h2>
                <p style="color:#777;font-size:13px;margin-top:-5px;">
                    Credenciales de test del Backoffice IZIPAY → Configuración → API REST (modo prueba).
                </p>
                <div class="form-group">
                    <label>User ID (Test)</label>
                    <input type="text" name="izipay_username_test" value="<?= htmlspecialchars($valores['izipay_username_test'] ?? '') ?>" placeholder="e.g. 83451769" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password (Test)</label>
                    <input type="password" name="izipay_password_test" value="<?= htmlspecialchars($valores['izipay_password_test'] ?? '') ?>" placeholder="Contraseña API REST de prueba" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Public Key (Test)</label>
                    <input type="text" name="izipay_public_key_test" value="<?= htmlspecialchars($valores['izipay_public_key_test'] ?? '') ?>" placeholder="Clave pública de prueba" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>HMAC SHA-256 (Test)</label>
                    <input type="password" name="izipay_hmac_test" value="<?= htmlspecialchars($valores['izipay_hmac_test'] ?? '') ?>" placeholder="Clave HMAC de prueba" autocomplete="off">
                </div>
            </div>

            <!-- CREDENCIALES PRODUCCIÓN -->
            <div class="card cred-section <?= $modo_actual === 'PRODUCTION' ? 'active' : 'inactive' ?>">
                <h2><i class="fas fa-rocket" style="color:#28a745;"></i> Credenciales de PRODUCCIÓN
                    <?php
                    $prod_ok = !empty($valores['izipay_username_prod']) && !empty($valores['izipay_password_prod'])
                            && !empty($valores['izipay_public_key_prod']) && !empty($valores['izipay_hmac_prod']);
                    ?>
                    <?php if ($prod_ok): ?>
                        <span class="estado-llaves estado-ok"><i class="fas fa-check-circle"></i> Configuradas</span>
                    <?php else: ?>
                        <span class="estado-llaves estado-falta"><i class="fas fa-exclamation-triangle"></i> Incompletas</span>
                    <?php endif; ?>
                </h2>
                <p style="color:#777;font-size:13px;margin-top:-5px;">
                    Credenciales de producción del Backoffice IZIPAY → Configuración → API REST (modo producción).
                </p>
                <div class="form-group">
                    <label>User ID (Producción)</label>
                    <input type="text" name="izipay_username_prod" value="<?= htmlspecialchars($valores['izipay_username_prod'] ?? '') ?>" placeholder="e.g. 83451769" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password (Producción)</label>
                    <input type="password" name="izipay_password_prod" value="<?= htmlspecialchars($valores['izipay_password_prod'] ?? '') ?>" placeholder="Contraseña API REST de producción" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Public Key (Producción)</label>
                    <input type="text" name="izipay_public_key_prod" value="<?= htmlspecialchars($valores['izipay_public_key_prod'] ?? '') ?>" placeholder="Clave pública de producción" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>HMAC SHA-256 (Producción)</label>
                    <input type="password" name="izipay_hmac_prod" value="<?= htmlspecialchars($valores['izipay_hmac_prod'] ?? '') ?>" placeholder="Clave HMAC de producción" autocomplete="off">
                </div>
            </div>

            <?php if (is_array($resultado_prueba)): ?>
                <div class="result-box <?= $resultado_prueba['ok'] ? 'result-ok' : 'result-err' ?>">
                    <strong><i class="fas <?= $resultado_prueba['ok'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i> <?= $resultado_prueba['texto'] ?></strong>
                    <?php if (!empty($resultado_prueba['detalle'])): ?>
                        <small><?= $resultado_prueba['detalle'] ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex;gap:12px;margin-top:15px;flex-wrap:wrap;">
                <button type="submit" class="btn-admin" name="guardar" value="1"><i class="fas fa-save"></i> Guardar todo</button>
                <button type="submit" class="btn-test" name="probar" value="1"><i class="fas fa-plug"></i> Probar conexión (<?= $modo_actual === 'PRODUCTION' ? 'PROD' : 'TEST' ?>)</button>
            </div>
            <small style="color:#999;display:block;margin-top:8px;">
                "Probar conexión" crea un formToken de prueba (sin cobro) usando las credenciales del modo seleccionado.
            </small>
        </form>

        <div class="card" style="background:#f8fbff;border:1px solid #d1e3fa;">
            <h2><i class="fas fa-info-circle" style="color:#1a73e8;"></i> Guía rápida</h2>
            <ul class="help-list">
                <li>Ingresa a <strong>mi cuenta IZIPAY</strong> (Backoffice Vendedor) → <strong>Configuración → API REST</strong>.</li>
                <li>Genera credenciales de <strong>Test</strong> y de <strong>Producción</strong> por separado (mismo shop ID, diferentes llaves).</li>
                <li>Copia cada juego de llaves en su sección correspondiente arriba.</li>
                <li>En el backoffice, configura las <strong>URLs de retorno e IPN</strong>:
                    <code>https://intipathtours.com/retorno_izipay.php</code> y <code>https://intipathtours.com/ipn_izipay.php</code>.</li>
                <li>Para probar, usa el <strong>modo Test</strong> con tarjetas de prueba IZIPAY (Visa <code>4970 1000 0000 0054</code>).</li>
                <li>Cuando todo funcione, cambia a <strong>Producción</strong> con las credenciales reales.</li>
            </ul>
        </div>
    </main>
</div>
</body>
</html>
