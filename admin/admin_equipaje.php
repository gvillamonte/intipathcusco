<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('equipaje');

require_once '../config/database.php';
$db = (new Database())->getConnection();

if (isset($_POST['update_page'])) {
    $aside_img = $_POST['current_aside_img'];
    if (isset($_FILES['aside_foto']) && $_FILES['aside_foto']['error'] == 0) {
        $aside_img = "equipaje_spec_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_foto']['tmp_name'], "../assets/img/equipamiento/" . $aside_img);
    }
    $intro_img = $_POST['current_intro_img'];
    if (isset($_FILES['intro_imagen']) && $_FILES['intro_imagen']['error'] == 0) {
        $intro_img = "equipaje_banner_" . time() . ".jpg";
        move_uploaded_file($_FILES['intro_imagen']['tmp_name'], "../assets/img/equipamiento/" . $intro_img);
    }
    $cta_img = $_POST['current_cta_img'];
    if (isset($_FILES['cta_foto']) && $_FILES['cta_foto']['error'] == 0) {
        $cta_img = "equipaje_cta_bg_" . time() . ".jpg";
        move_uploaded_file($_FILES['cta_foto']['tmp_name'], "../assets/img/equipamiento/" . $cta_img);
    }
    $stmt = $db->prepare("UPDATE equipaje SET banner_titulo = ?, banner_titulo_en = ?, banner_subtitulo = ?, banner_subtitulo_en = ?, intro_texto = ?, intro_texto_en = ?, intro_imagen = ?, aside_texto = ?, aside_texto_en = ?, aside_btn = ?, aside_btn_en = ?, aside_imagen = ?, cta_titulo = ?, cta_titulo_en = ?, cta_texto = ?, cta_texto_en = ?, cta_btn = ?, cta_btn_en = ?, cta_imagen = ?,
        color_titulo = ?, tamano_titulo = ?,
        color_texto = ?, tamano_texto = ?
        WHERE id = 1");
    $stmt->execute([$_POST['b_tit'], $_POST['b_tit_en'], $_POST['b_sub'], $_POST['b_sub_en'], $_POST['i_txt'], $_POST['i_txt_en'], $intro_img, $_POST['a_txt'], $_POST['a_txt_en'], $_POST['a_btn'], $_POST['a_btn_en'], $aside_img, $_POST['cta_tit'], $_POST['cta_tit_en'], $_POST['cta_txt'], $_POST['cta_txt_en'], $_POST['cta_btn'], $_POST['cta_btn_en'], $cta_img, $_POST['color_titulo'], $_POST['tamano_titulo'], $_POST['color_texto'], $_POST['tamano_texto']]);
    header("Location: admin_equipaje.php?res=updated");
    exit;
}
$config = $db->query("SELECT * FROM equipaje WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Admin Equipaje | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.current-img-preview { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-top: 10px; border: 1px solid #e9ecef; }.img-hint { font-size: 0.85rem; color: #666; margin-top: 5px; margin-bottom: 15px; }:root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }body{background-color:var(--bg-light);font-family:'Segoe UI',sans-serif}.admin-title-inti{color:var(--admin-blue);font-weight:800;border-bottom:4px solid var(--admin-accent);display:inline-block;padding-bottom:5px;margin-bottom:25px;text-transform:uppercase}.card-admin{background:#fff;padding:25px;border-radius:15px;box-shadow:0 8px 20px rgba(0,0,0,0.05);margin-bottom:30px;border:1px solid #ddd}.card-admin h3{border-left:5px solid var(--admin-blue);padding-left:15px;color:var(--admin-blue);margin-bottom:20px;text-transform:uppercase;font-size:1rem}.form-control{width:100%;padding:12px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box;margin-bottom:10px}.btn-admin{background:var(--admin-blue);color:#fff;padding:14px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;transition:0.3s;width:100%}.btn-admin:hover{background:#0e2245;transform:translateY(-2px)}</style>
</head>
<body>
    <div class="admin-wrapper"><?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding:30px;">
            <h1 class="admin-title-inti"><i class="fas fa-suitcase"></i> Configuración - Equipaje</h1>
            <form method="POST" enctype="multipart/form-data">
                <div class="card-admin"><h3>1. Banner e Introducción</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div><label>Título Banner (ES)</label><input type="text" name="b_tit" value="<?= $config['banner_titulo'] ?? '' ?>" class="form-control"><label>Subtítulo (ES)</label><input type="text" name="b_sub" value="<?= $config['banner_subtitulo'] ?? '' ?>" class="form-control"></div>
                        <div><label>Título Banner (EN)</label><input type="text" name="b_tit_en" value="<?= $config['banner_titulo_en'] ?? '' ?>" class="form-control"><label>Subtítulo (EN)</label><input type="text" name="b_sub_en" value="<?= $config['banner_subtitulo_en'] ?? '' ?>" class="form-control"></div>
                        <div style="grid-column:1/3;"><label>Texto Introducción - ESPAÑOL</label><textarea name="i_txt" rows="6" class="form-control"><?= $config['intro_texto'] ?? '' ?></textarea><label>Texto Introducción - INGLÉS</label><textarea name="i_txt_en" rows="6" class="form-control"><?= $config['intro_texto_en'] ?? '' ?></textarea><label>Imagen Banner</label><input type="file" name="intro_imagen" class="form-control" accept="image/jpeg, image/png, image/webp"><div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 1920x600 px | Formato: JPG, PNG, WEBP</div><?php if($config["intro_imagen"]): ?>
            <div class="current-img-preview">
                <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                <img src="../assets/img/equipaje/<?= $config["intro_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                <p style="font-size:0.75rem;color:#666;margin:5px 0;">🖼️ 1920x600 | <?= $config["intro_imagen"] ?></p>
            </div>
        <?php else: ?>
            <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada (1920x600)</p>
        <?php endif; ?><input type="hidden" name="current_intro_img" value="<?= $config['intro_imagen'] ?? '' ?>"></div>
                    </div>
                </div>
                <div class="card-admin"><h3>2. Barra Lateral</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div><label>Texto (ES)</label><textarea name="a_txt" rows="3" class="form-control"><?= $config['aside_texto'] ?? '' ?></textarea><label>Botón (ES)</label><input type="text" name="a_btn" value="<?= $config['aside_btn'] ?? '' ?>" class="form-control"></div>
                        <div><label>Texto (EN)</label><textarea name="a_txt_en" rows="3" class="form-control"><?= $config['aside_texto_en'] ?? '' ?></textarea><label>Botón (EN)</label><input type="text" name="a_btn_en" value="<?= $config['aside_btn_en'] ?? '' ?>" class="form-control"></div>
                        <div style="grid-column:1/3;"><label>Foto</label><input type="file" name="aside_foto" class="form-control" accept="image/jpeg, image/png, image/webp"><div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 400x400 px | Formato: JPG, PNG, WEBP</div><?php if($config["aside_imagen"]): ?>
            <div class="current-img-preview">
                <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                <img src="../assets/img/equipaje/<?= $config["aside_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                <p style="font-size:0.75rem;color:#666;margin:5px 0;">🖼️ 400x400 | <?= $config["aside_imagen"] ?></p>
            </div>
        <?php else: ?>
            <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada (400x400)</p>
        <?php endif; ?><input type="hidden" name="current_aside_img" value="<?= $config['aside_imagen'] ?? '' ?>"></div>
                    </div>
                </div>
                <div class="card-admin"><h3>3. Sección CTA</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div><label>Título CTA (ES)</label><input type="text" name="cta_tit" value="<?= $config['cta_titulo'] ?? '' ?>" class="form-control"><label>Botón CTA (ES)</label><input type="text" name="cta_btn" value="<?= $config['cta_btn'] ?? '' ?>" class="form-control"></div>
                        <div><label>Título CTA (EN)</label><input type="text" name="cta_tit_en" value="<?= $config['cta_titulo_en'] ?? '' ?>" class="form-control"><label>Botón CTA (EN)</label><input type="text" name="cta_btn_en" value="<?= $config['cta_btn_en'] ?? '' ?>" class="form-control"></div>
                        <div style="grid-column:1/3;"><label>Texto CTA (ES)</label><textarea name="cta_txt" rows="2" class="form-control"><?= $config['cta_texto'] ?? '' ?></textarea><label>Texto CTA (EN)</label><textarea name="cta_txt_en" rows="2" class="form-control"><?= $config['cta_texto_en'] ?? '' ?></textarea><label>Fondo CTA</label><input type="file" name="cta_foto" class="form-control" accept="image/jpeg, image/png, image/webp"><div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 1920x400 px | Formato: JPG, PNG, WEBP</div><?php if($config["cta_imagen"]): ?>
            <div class="current-img-preview">
                <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                <img src="../assets/img/equipaje/<?= $config["cta_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                <p style="font-size:0.75rem;color:#666;margin:5px 0;">🖼️ 1920x400 | <?= $config["cta_imagen"] ?></p>
            </div>
        <?php else: ?>
            <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada (1920x400)</p>
        <?php endif; ?><input type="hidden" name="current_cta_img" value="<?= $config['cta_imagen'] ?? '' ?>"></div>
                    </div>
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top:20px;"><i class="fas fa-save"></i> GUARDAR</button>
                </div>
                <div class="card-admin">
                    <h3>4. Estilos de Página</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div><label>Color del Título (Hex)</label><input type="text" name="color_titulo" value="<?= $config['color_titulo'] ?? '' ?>" class="form-control" placeholder="#0f9b9e"><label>Tamaño del Título (px)</label><input type="text" name="tamano_titulo" value="<?= $config['tamano_titulo'] ?? '' ?>" class="form-control" placeholder="4rem"></div>
                        <div><label>Color del Texto (Hex)</label><input type="text" name="color_texto" value="<?= $config['color_texto'] ?? '' ?>" class="form-control" placeholder="#444444"><label>Tamaño del Texto (px)</label><input type="text" name="tamano_texto" value="<?= $config['tamano_texto'] ?? '' ?>" class="form-control" placeholder="1.1rem"></div>
                    </div>
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top:20px;"><i class="fas fa-save"></i> GUARDAR</button>
                </div>
            </form>
        </main>
    </div>
    <script>const res=new URLSearchParams(window.location.search).get('res');if(res==='updated'){Swal.fire({icon:'success',title:'¡Guardado Correctamente!',confirmButtonColor:'#15305D'}).then(()=>{window.history.replaceState({},document.title,window.location.pathname);});}</script>
</body>
</html>