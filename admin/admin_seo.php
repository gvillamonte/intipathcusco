<?php
// admin/admin_seo.php — Editor de SEO/Metadatos por página (tabla metas_pagina)
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('seo');
require_once '../config/database.php';

$db = (new Database())->getConnection();

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metas = $_POST['metas'] ?? [];
    $actualizadas = 0;
    foreach ($metas as $clave => $m) {
        $stmt = $db->prepare("UPDATE metas_pagina SET meta_title=?, meta_description=?, og_imagen=? WHERE clave=?");
        $stmt->execute([trim($m['meta_title'] ?? ''), trim($m['meta_description'] ?? ''), trim($m['og_imagen'] ?? ''), $clave]);
        $actualizadas++;
    }
    header("Location: admin_seo.php?guardado=1");
    exit;
}

$metas = $db->query("SELECT * FROM metas_pagina ORDER BY clave ASC")->fetchAll(PDO::FETCH_ASSOC);

$etiquetas = [
    'home' => 'Inicio', 'tours' => 'Tours', 'blog' => 'Blog', 'contacto' => 'Contacto',
    'nosotros' => 'Nosotros', 'preguntas' => 'Preguntas Frecuentes', 'reservas_info' => 'Reservas Info',
    'garantia' => 'Garantía', 'seguridad' => 'Seguridad', 'terminos' => 'Términos y Condiciones',
    'privacidad' => 'Política de Privacidad', 'paginas' => 'Páginas libres (CMS)',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SEO / Metadatos | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
<h1 class="admin-title"><i class="fas fa-search"></i> SEO / Metadatos por página</h1>

<div class="admin-contenedor">
    <p style="color:#64748b;">Define el título, descripción e imagen que Google y WhatsApp muestran para cada página.
    Vacío = usa el valor general del sitio. Los tours y entradas de blog generan sus metadatos automáticamente desde su contenido.</p>

    <form method="POST">
    <?php foreach ($metas as $meta): ?>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;margin-bottom:14px;">
            <strong style="color:#15305D;display:block;margin-bottom:8px;">
                <i class="fas fa-file"></i> <?= htmlspecialchars($etiquetas[$meta['clave']] ?? $meta['clave']) ?>
                <code style="font-size:11px;color:#94a3b8;margin-left:8px;"><?= htmlspecialchars($meta['clave']) ?></code>
            </strong>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:12px;color:#64748b;font-weight:600;">Meta título (60-65 caracteres)</label>
                    <input type="text" name="metas[<?= $meta['clave'] ?>][meta_title]" maxlength="200" value="<?= htmlspecialchars($meta['meta_title']) ?>" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:12px;color:#64748b;font-weight:600;">Meta descripción (máx. 160)</label>
                    <input type="text" name="metas[<?= $meta['clave'] ?>][meta_description]" maxlength="300" value="<?= htmlspecialchars($meta['meta_description']) ?>" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;">
                </div>
            </div>
            <div style="margin-top:8px;">
                <label style="font-size:12px;color:#64748b;font-weight:600;">Imagen OG (ruta dentro de assets/img/, p. ej. tours/inca.jpg — vacío = logo del sitio)</label>
                <input type="text" name="metas[<?= $meta['clave'] ?>][og_imagen]" value="<?= htmlspecialchars($meta['og_imagen']) ?>" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;">
            </div>
        </div>
    <?php endforeach; ?>
        <button type="submit" style="background:#15305D;color:#fff;padding:13px 28px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;">
            <i class="fas fa-save"></i> Guardar metadatos
        </button>
    </form>
</div>
</main>
</div>
<?php if (isset($_GET['guardado'])): ?>
<script>Swal.fire('¡Listo!', 'Metadatos actualizados.', 'success');</script>
<?php endif; ?>
</body>
</html>