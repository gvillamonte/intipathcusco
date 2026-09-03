<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('config_pagos');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$mensaje = '';

// Cargar config actual
$stmt = $db->query("SELECT clave, valor, campo FROM config_pagos ORDER BY id");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$config = [];
foreach ($configs as $c) $config[$c['clave']] = $c;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $campos_texto = ['yape_numero', 'yape_titular', 'plin_numero', 'plin_titular'];
    $stmt_up = $db->prepare("UPDATE config_pagos SET valor = ?, updated_at = NOW() WHERE clave = ?");

    foreach ($campos_texto as $clave) {
        if (isset($_POST[$clave])) {
            $stmt_up->execute([trim($_POST[$clave]), $clave]);
        }
    }

    // Subida de imagenes QR
    $campos_img = [
        'yape_qr' => 'yape_qr',
        'plin_qr' => 'plin_qr',
    ];
    $upload_dir = __DIR__ . '/../assets/img/';
    foreach ($campos_img as $clave => $filename) {
        if (isset($_FILES[$clave]) && $_FILES[$clave]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$clave]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) continue;
            $destino = $upload_dir . $filename . '.' . $ext;
            if (move_uploaded_file($_FILES[$clave]['tmp_name'], $destino)) {
                $ruta_relativa = 'assets/img/' . $filename . '.' . $ext;
                $stmt_up->execute([$ruta_relativa, $clave]);
                // Borrar imagen anterior si es distinta extension
                $anterior = $config[$clave]['valor'] ?? '';
                if ($anterior && $anterior !== $ruta_relativa && file_exists(__DIR__ . '/../' . $anterior)) {
                    unlink(__DIR__ . '/../' . $anterior);
                }
            }
        }
    }

    // Recargar config
    $stmt = $db->query("SELECT clave, valor, campo FROM config_pagos ORDER BY id");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $config = [];
    foreach ($configs as $c) $config[$c['clave']] = $c;

    $mensaje = 'ok';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Pagos | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; }
        body { background:#f4f7f6;font-family:'Segoe UI',sans-serif; }
        .admin-title { color:var(--admin-blue);font-weight:800;border-bottom:4px solid var(--admin-accent);display:inline-block;padding-bottom:5px;margin-bottom:25px;text-transform:uppercase;font-size:1.4rem; }
        .card { background:#fff;border-radius:12px;padding:25px;margin-bottom:25px;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #e0e0e0; }
        .card h2 { font-size:1.1rem;color:var(--admin-blue);margin:0 0 15px 0;padding-bottom:10px;border-bottom:2px solid var(--admin-accent); }
        .card h2 i { margin-right:8px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block;font-weight:600;font-size:0.85rem;color:#333;margin-bottom:5px; }
        .form-group input[type="text"] { width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:8px;font-size:14px; }
        .form-group input[type="file"] { padding:8px 0; }
        .btn-admin { background:var(--admin-blue);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px; }
        .btn-admin:hover { background:#1e3f7a; }
        .qr-preview { max-width:150px;max-height:150px;border-radius:8px;border:2px solid #eee;margin-top:8px; }
        .dos-columnas { display:grid;grid-template-columns:1fr 1fr;gap:25px; }
        .badge-preview { display:inline-block;background:#f0f8e8;padding:2px 10px;border-radius:4px;font-size:12px;color:#555;margin-left:10px; }
        @media (max-width:768px) { .dos-columnas { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fas fa-cog"></i> Configurar Métodos de Pago</h1>

        <?php if ($mensaje === 'ok'): ?>
            <script>Swal.fire({icon:'success',title:'Configuración guardada',timer:1500,showConfirmButton:false});</script>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="dos-columnas">
                <!-- YAPE -->
                <div class="card">
                    <h2><i class="fas fa-mobile-alt" style="color:#8B1D8B;"></i> Yape</h2>
                    <div class="form-group">
                        <label>Número Yape</label>
                        <input type="text" name="yape_numero" value="<?= htmlspecialchars($config['yape_numero']['valor'] ?? '') ?>" placeholder="999 888 777">
                    </div>
                    <div class="form-group">
                        <label>Titular</label>
                        <input type="text" name="yape_titular" value="<?= htmlspecialchars($config['yape_titular']['valor'] ?? '') ?>" placeholder="Nombre del titular">
                    </div>
                    <div class="form-group">
                        <label>Imagen QR</label>
                        <?php if (!empty($config['yape_qr']['valor'])): ?>
                            <div>
                                <img src="../<?= htmlspecialchars($config['yape_qr']['valor']) ?>" class="qr-preview">
                                <span class="badge-preview">Actual</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="yape_qr" accept="image/png,image/jpeg,image/gif,image/webp" style="margin-top:8px;">
                        <small style="color:#888;display:block;margin-top:3px;">Formatos: PNG, JPG, WEBP. Se redimensionará automáticamente.</small>
                    </div>
                </div>

                <!-- PLIN -->
                <div class="card">
                    <h2><i class="fas fa-mobile-alt" style="color:#E8AC18;"></i> Plin</h2>
                    <div class="form-group">
                        <label>Número Plin</label>
                        <input type="text" name="plin_numero" value="<?= htmlspecialchars($config['plin_numero']['valor'] ?? '') ?>" placeholder="999 888 776">
                    </div>
                    <div class="form-group">
                        <label>Titular</label>
                        <input type="text" name="plin_titular" value="<?= htmlspecialchars($config['plin_titular']['valor'] ?? '') ?>" placeholder="Nombre del titular">
                    </div>
                    <div class="form-group">
                        <label>Imagen QR</label>
                        <?php if (!empty($config['plin_qr']['valor'])): ?>
                            <div>
                                <img src="../<?= htmlspecialchars($config['plin_qr']['valor']) ?>" class="qr-preview">
                                <span class="badge-preview">Actual</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="plin_qr" accept="image/png,image/jpeg,image/gif,image/webp" style="margin-top:8px;">
                        <small style="color:#888;display:block;margin-top:3px;">Formatos: PNG, JPG, WEBP.</small>
                    </div>
                </div>
            </div>

            <div style="text-align:right;margin-top:10px;">
                <button type="submit" class="btn-admin"><i class="fas fa-save"></i> Guardar Configuración</button>
            </div>
        </form>

        <div class="card" style="margin-top:20px; background:#f8fbff; border:1px solid #d1e3fa;">
            <h2><i class="fas fa-info-circle" style="color:#1a73e8;"></i> Nota: pagos con IZIPAY</h2>
            <p style="color:#555;font-size:14px;line-height:1.6;">
                Desde el 17/08/2026 todos los pagos en línea (Tarjeta y Yape) se procesan a través de
                <strong>IZIPAY</strong> en <code>config/izipay.php</code> (usuario, contraseña, clave pública y firma HMAC).
                La página pública de pago ya no usa los datos de Yape/Plin de esta configuración.
                Los datos mostrados aquí (Yape/Plin) quedan solo como referencia histórica.
            </p>
            <p style="font-size:13px;color:#999;margin-top:5px;">
                <i class="fas fa-external-link-alt"></i>
                <a href="../checkout_izipay.php" style="color:var(--admin-blue);">Ver flujo IZIPAY (checkout_izipay.php)</a>
            </p>
        </div>
    </main>
</div>
</body>
</html>
