<?php
// admin/config_logo_pagos.php
// Logo de las páginas de pago (wizard): se sube a assets/img/pagos/ y se
// guarda su nombre en config_pagos (clave pago_logo). Si no hay logo de pagos,
// las páginas usan el logo del sitio (configuracion.logo).

require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';
requierePermiso('config_pagos');

$db = (new Database())->getConnection();

$mensaje = '';
$error = '';

// Valor actual
$stmt = $db->query("SELECT valor FROM config_pagos WHERE clave = 'pago_logo' LIMIT 1");
$logo_actual = trim((string)$stmt->fetchColumn());
$stmt->closeCursor();

$dir = __DIR__ . '/../assets/img/pagos/';
$ext_permitidas = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quitar']) && $_POST['quitar'] === '1') {
        if ($logo_actual !== '' && is_file($dir . $logo_actual)) @unlink($dir . $logo_actual);
        $stmt = $db->prepare("INSERT INTO config_pagos (clave, valor, campo) VALUES ('pago_logo', '', 'Logo de las paginas de pago')
                              ON DUPLICATE KEY UPDATE valor = '', updated_at = NOW()");
        $stmt->execute();
        $logo_actual = '';
        $mensaje = 'ok';
    } elseif (isset($_FILES['pago_logo']) && $_FILES['pago_logo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['pago_logo'];
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_permitidas)) {
            $error = 'Formato no permitido. Usa PNG, JPG, WEBP, SVG o GIF.';
        } elseif ($archivo['size'] > 2 * 1024 * 1024) {
            $error = 'La imagen supera los 2 MB.';
        } else {
            if (!is_dir($dir)) @mkdir($dir, 0755, true);

            $nuevo_nombre = 'pago-logo-' . substr(md5(time() . uniqid()), 0, 8) . '.' . $ext;

            if (move_uploaded_file($archivo['tmp_name'], $dir . $nuevo_nombre)) {
                if ($logo_actual !== '' && $logo_actual !== $nuevo_nombre && is_file($dir . $logo_actual)) {
                    @unlink($dir . $logo_actual);
                }
                $stmt = $db->prepare("INSERT INTO config_pagos (clave, valor, campo) VALUES ('pago_logo', ?, 'Logo de las paginas de pago')
                                      ON DUPLICATE KEY UPDATE valor = ?, updated_at = NOW()");
                $stmt->execute([$nuevo_nombre, $nuevo_nombre]);
                $logo_actual = $nuevo_nombre;
                $mensaje = 'ok';
            } else {
                $error = 'No se pudo guardar la imagen. Verifica permisos de escritura en assets/img/pagos/.';
            }
        }
    } else {
        $error = 'Selecciona una imagen para subir.';
    }
}

$usando_logo = ($logo_actual !== '' && is_file($dir . $logo_actual));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo de Pagos | Admin IntiPath</title>
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
        .btn-admin { background:var(--admin-blue);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-admin:hover { background:#1e3f7a; }
        .btn-upload { background:var(--ip-turq);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-upload:hover { background:#0a7f83; }
        .btn-remove { background:#e74c3c;color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-remove:hover { background:#c0392b; }
        .estado-logo { display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-left:10px; }
        .estado-ok { background:#d4edda;color:#155724; }
        .estado-falta { background:#fff3cd;color:#856404; }
        .drop-zone { border:2px dashed #b9c6d8;border-radius:12px;padding:28px 20px;text-align:center;background:#f8fbff;cursor:pointer;transition:0.25s;margin-bottom:16px; }
        .drop-zone:hover, .drop-zone.dragover { border-color:var(--ip-turq);background:#eefbfb; }
        .drop-zone i { font-size:2.2rem;color:var(--ip-turq);display:block;margin-bottom:10px; }
        .drop-zone span { color:#777;font-size:14px; }
        .drop-zone strong { color:var(--admin-blue); }
        .logo-previews { display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:16px 0 20px; }
        .logo-preview-box { border:1px solid #e0e0e0;border-radius:12px;padding:22px;text-align:center;display:flex;align-items:center;justify-content:center;min-height:110px; }
        .logo-preview-box.claro { background:#ffffff; }
        .logo-preview-box.oscuro { background:#15305D; }
        .logo-preview-box img { max-height:64px;max-width:240px;width:auto; }
        .logo-preview-box .sin-logo { color:#999;font-size:13px; }
        .logo-preview-box.oscuro .sin-logo { color:#8fa3c4; }
        .preview-tag { font-size:11px;font-weight:700;letter-spacing:1px;color:#999;text-transform:uppercase;margin-bottom:4px; }
        .result-box { border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:14px; }
        .result-err { background:#f8d7da;border:1px solid #f1b0b7;color:#721c24; }
        .help-list { font-size:13.5px;color:#444;line-height:1.8;padding-left:20px; }
        .help-list code { background:#f0f0f0;padding:1px 6px;border-radius:4px;font-size:12px; }
        @media (max-width:768px) { .logo-previews { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fas fa-image" style="color:var(--ip-turq);"></i> Logo de las Páginas de Pago</h1>

        <?php if ($mensaje === 'ok'): ?>
            <script>Swal.fire({icon:'success',title:'Logo guardado',timer:1500,showConfirmButton:false});</script>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="result-box result-err"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card" style="max-width:760px;">
            <h2>
                <i class="fas fa-image" style="color:var(--ip-turq);"></i> Logo del wizard de pago
                <?php if ($usando_logo): ?>
                    <span class="estado-logo estado-ok"><i class="fas fa-check-circle"></i> Logo configurado</span>
                <?php else: ?>
                    <span class="estado-logo estado-falta"><i class="fas fa-exclamation-triangle"></i> Sin logo: se usa el del sitio</span>
                <?php endif; ?>
            </h2>
            <p style="color:#777;font-size:13.5px;margin-top:-5px;">
                Este logo reemplaza el texto "INTI PATH" en <strong>seleccionar_pago.php</strong>,
                <strong>checkout_izipay.php</strong>, <strong>checkout_paypal.php</strong> y <strong>pago_exitoso.php</strong>.
                Si no subes uno, se usa el logo del sitio (Admin → Configuración General).
            </p>

            <div class="preview-tag">Vista previa (claro / oscuro)</div>
            <div class="logo-previews">
                <div class="logo-preview-box claro">
                    <?php if ($usando_logo): ?>
                        <img src="../assets/img/pagos/<?= htmlspecialchars($logo_actual) ?>?v=<?= time() ?>" alt="Logo de pagos">
                    <?php else: ?>
                        <div class="sin-logo"><i class="fas fa-image mb-2" style="display:block;font-size:1.8rem;"></i>Sin logo de pagos</div>
                    <?php endif; ?>
                </div>
                <div class="logo-preview-box oscuro">
                    <?php if ($usando_logo): ?>
                        <img src="../assets/img/pagos/<?= htmlspecialchars($logo_actual) ?>?v=<?= time() ?>" alt="Logo de pagos">
                    <?php else: ?>
                        <div class="sin-logo"><i class="fas fa-image mb-2" style="display:block;font-size:1.8rem;"></i>Sin logo de pagos</div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="formLogo">
                <div class="form-group">
                    <label><i class="fas fa-upload" style="color:var(--ip-turq);"></i> Subir nuevo logo</label>
                    <label class="drop-zone" for="pago_logo" id="dropZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span><strong>Haz clic</strong> o arrastra la imagen aquí</span><br>
                        <small style="color:#aaa;">PNG, JPG, WEBP, SVG o GIF — máx. 2 MB</small>
                    </label>
                    <input type="file" name="pago_logo" id="pago_logo" accept=".png,.jpg,.jpeg,.webp,.svg,.gif" style="display:none;">
                </div>
                <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn-upload" id="btnSubir" disabled><i class="fas fa-upload"></i> Subir Logo</button>
                    <?php if ($usando_logo): ?>
                        <button type="submit" class="btn-remove" name="quitar" value="1" onclick="return confirm('¿Quitar el logo de pagos? Se usará el logo del sitio.');"><i class="fas fa-trash-alt"></i> Quitar logo</button>
                    <?php endif; ?>
                </div>
                <small style="color:#999;display:block;margin-top:8px;">
                    Al subir un nuevo logo, el anterior se reemplaza automáticamente.
                </small>
            </form>
        </div>

        <div class="card" style="background:#f8fbff;border:1px solid #d1e3fa;max-width:760px;">
            <h2><i class="fas fa-info-circle" style="color:#1a73e8;"></i> ¿Dónde aparece?</h2>
            <ul class="help-list">
                <li>Cabecera del wizard en <code>seleccionar_pago.php</code>, <code>checkout_izipay.php</code>, <code>checkout_paypal.php</code> y <code>pago_exitoso.php</code>.</li>
                <li>También dentro del loader "REDIRIGIENDO A PAYPAL" / "PREPARANDO PAGO SEGURO".</li>
                <li>Prioridad: <strong>logo de pagos</strong> → logo del sitio → texto "INTI PATH".</li>
                <li>Se guarda en <code>assets/img/pagos/</code> y la carpeta se crea automáticamente.</li>
                <li>Para cambiar el logo del sitio usa <strong>Admin → Configuración General</strong>.</li>
            </ul>
        </div>
    </main>
</div>
<script>
    const inputLogo = document.getElementById('pago_logo');
    const btnSubir = document.getElementById('btnSubir');
    const dropZone = document.getElementById('dropZone');

    inputLogo.addEventListener('change', function () {
        btnSubir.disabled = !this.files || this.files.length === 0;
        if (this.files && this.files.length > 0) {
            dropZone.querySelector('span').innerHTML = '<strong>' + this.files[0].name + '</strong> listo para subir';
            btnSubir.innerHTML = '<i class="fas fa-upload"></i> Subir ' + this.files[0].name;
        }
    });

    ['dragenter', 'dragover'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('dragover'); }));
    dropZone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            inputLogo.files = e.dataTransfer.files;
            inputLogo.dispatchEvent(new Event('change'));
        }
    });
    dropZone.addEventListener('click', () => inputLogo.click());
</script>
</body>
</html>