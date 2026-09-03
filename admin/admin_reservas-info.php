<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas_info');

require_once '../config/database.php';
$db = (new Database())->getConnection();

if (isset($_POST['update_page'])) {
    // Banner images
    $banner_img = $_POST['current_banner_img'];
    if (isset($_FILES['banner_imagen']) && $_FILES['banner_imagen']['error'] == 0) {
        $banner_img = "reservas_banner_" . time() . ".jpg";
        move_uploaded_file($_FILES['banner_imagen']['tmp_name'], "../assets/img/reservas-info/" . $banner_img);
    }
    
    // Tarjeta 1
    $t1_img = $_POST['current_t1_img'];
    if (isset($_FILES['t1_imagen']) && $_FILES['t1_imagen']['error'] == 0) {
        $t1_img = "reservas_t1_" . time() . ".jpg";
        move_uploaded_file($_FILES['t1_imagen']['tmp_name'], "../assets/img/reservas-info/" . $t1_img);
    }
    
    // Tarjeta 2
    $t2_img = $_POST['current_t2_img'];
    if (isset($_FILES['t2_imagen']) && $_FILES['t2_imagen']['error'] == 0) {
        $t2_img = "reservas_t2_" . time() . ".jpg";
        move_uploaded_file($_FILES['t2_imagen']['tmp_name'], "../assets/img/reservas-info/" . $t2_img);
    }
    
    // Tarjeta 3
    $t3_img = $_POST['current_t3_img'];
    if (isset($_FILES['t3_imagen']) && $_FILES['t3_imagen']['error'] == 0) {
        $t3_img = "reservas_t3_" . time() . ".jpg";
        move_uploaded_file($_FILES['t3_imagen']['tmp_name'], "../assets/img/reservas-info/" . $t3_img);
    }
    
    // Tarjeta 4
    $t4_img = $_POST['current_t4_img'];
    if (isset($_FILES['t4_imagen']) && $_FILES['t4_imagen']['error'] == 0) {
        $t4_img = "reservas_t4_" . time() . ".jpg";
        move_uploaded_file($_FILES['t4_imagen']['tmp_name'], "../assets/img/reservas-info/" . $t4_img);
    }
    
    // Fondo motivacional
    $motiva_img = $_POST['current_motiva_img'];
    if (isset($_FILES['motiva_fondo']) && $_FILES['motiva_fondo']['error'] == 0) {
        $motiva_img = "reservas_motiva_" . time() . ".jpg";
        move_uploaded_file($_FILES['motiva_fondo']['tmp_name'], "../assets/img/reservas-info/" . $motiva_img);
    }
    
    $stmt = $db->prepare("UPDATE reservas_info SET 
        banner_titulo = ?, banner_titulo_en = ?,
        banner_subtitulo = ?, banner_subtitulo_en = ?,
        banner_imagen = ?,
        tarjeta1_titulo = ?, tarjeta1_titulo_en = ?, tarjeta1_enlace = ?, tarjeta1_imagen = ?,
        tarjeta2_titulo = ?, tarjeta2_titulo_en = ?, tarjeta2_enlace = ?, tarjeta2_imagen = ?,
        tarjeta3_titulo = ?, tarjeta3_titulo_en = ?, tarjeta3_enlace = ?, tarjeta3_imagen = ?,
        tarjeta4_titulo = ?, tarjeta4_titulo_en = ?, tarjeta4_enlace = ?, tarjeta4_imagen = ?,
        motiva_titulo = ?, motiva_titulo_en = ?,
        motiva_texto = ?, motiva_texto_en = ?,
        motiva_cta = ?, motiva_cta_en = ?,
        motiva_fondo = ?,
        color_titulo = ?, tamano_titulo = ?, fuente_titulo = ?,
        color_texto = ?, tamano_texto = ?, fuente_texto = ?
        WHERE id = 1");
    
    $stmt->execute([
        $_POST['b_tit'], $_POST['b_tit_en'],
        $_POST['b_sub'], $_POST['b_sub_en'], $banner_img,
        $_POST['t1_tit'], $_POST['t1_tit_en'], $_POST['t1_enlace'], $t1_img,
        $_POST['t2_tit'], $_POST['t2_tit_en'], $_POST['t2_enlace'], $t2_img,
        $_POST['t3_tit'], $_POST['t3_tit_en'], $_POST['t3_enlace'], $t3_img,
        $_POST['t4_tit'], $_POST['t4_tit_en'], $_POST['t4_enlace'], $t4_img,
        $_POST['m_tit'], $_POST['m_tit_en'],
        $_POST['m_txt'], $_POST['m_txt_en'],
        $_POST['m_cta'], $_POST['m_cta_en'], $motiva_img,
        $_POST['color_titulo'], $_POST['tamano_titulo'], $_POST['fuente_titulo'],
        $_POST['color_texto'], $_POST['tamano_texto'], $_POST['fuente_texto']
    ]);
    
    header("Location: admin_reservas-info.php?res=updated");
    exit;
}

$config = $db->query("SELECT * FROM reservas_info WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Reservas Info | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .current-img-preview { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-top: 10px; border: 1px solid #e9ecef; }
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body{background-color:var(--bg-light);font-family:'Segoe UI',sans-serif}
        .admin-title-inti{color:var(--admin-blue);font-weight:800;border-bottom:4px solid var(--admin-accent);display:inline-block;padding-bottom:5px;margin-bottom:25px;text-transform:uppercase}
        .card-admin{background:#fff;padding:25px;border-radius:15px;box-shadow:0 8px 20px rgba(0,0,0,0.05);margin-bottom:30px;border:1px solid #ddd}
        .card-admin h3{border-left:5px solid var(--admin-blue);padding-left:15px;color:var(--admin-blue);margin-bottom:20px;text-transform:uppercase;font-size:1rem}
        .form-control{width:100%;padding:12px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box;margin-bottom:10px}
        .btn-admin{background:var(--admin-blue);color:#fff;padding:14px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;transition:0.3s;width:100%}
        .btn-admin:hover{background:#0e2245;transform:translateY(-2px)}
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding:30px;">
            <h1 class="admin-title-inti"><i class="fas fa-calendar-check"></i> Configuración - Reservas Info</h1>
            <form method="POST" enctype="multipart/form-data">
                
                <!-- Banner -->
                <div class="card-admin">
                    <h3>1. Banner Principal</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label>Título Banner (ES)</label>
                            <input type="text" name="b_tit" value="<?= $config['banner_titulo'] ?? '' ?>" class="form-control">
                            <label>Subtítulo (ES)</label>
                            <input type="text" name="b_sub" value="<?= $config['banner_subtitulo'] ?? '' ?>" class="form-control">
                        </div>
                        <div>
                            <label>Título Banner (EN)</label>
                            <input type="text" name="b_tit_en" value="<?= $config['banner_titulo_en'] ?? '' ?>" class="form-control">
                            <label>Subtítulo (EN)</label>
                            <input type="text" name="b_sub_en" value="<?= $config['banner_subtitulo_en'] ?? '' ?>" class="form-control">
                        </div>
                        <div style="grid-column:1/3;">
                            <label>Imagen Banner</label>
                            <input type="file" name="banner_imagen" class="form-control" accept="image/jpeg, image/png, image/webp">
                            <div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 1920x600 px | Formato: JPG, PNG, WEBP</div>
                            <input type="hidden" name="current_banner_img" value="<?= $config['banner_imagen'] ?? '' ?>">
                            <?php if($config["banner_imagen"]): ?>
                            <div class="current-img-preview">
                                <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                                <img src="../assets/img/reservas-info/<?= $config["banner_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                                <p style="font-size:0.75rem;color:#666;margin:5px 0;">🖼️ 1920x600 | <?= $config["banner_imagen"] ?></p>
                            </div>
                            <?php else: ?>
                            <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada (1920x600)</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjetas -->
                <div class="card-admin">
                    <h3>2. Tarjetas de Acceso Directo</h3>
                    
                    <!-- Tarjeta 1 -->
                    <div style="background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:20px;">
                        <h4 style="color:#15305D;margin-bottom:15px;">📌 Tarjeta 1: Términos y Condiciones</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div>
                                <label>Título (ES)</label>
                                <input type="text" name="t1_tit" value="<?= $config['tarjeta1_titulo'] ?? '' ?>" class="form-control">
                                <label>Enlace</label>
                                <input type="text" name="t1_enlace" value="<?= $config['tarjeta1_enlace'] ?? '' ?>" class="form-control">
                            </div>
                            <div>
                                <label>Título (EN)</label>
                                <input type="text" name="t1_tit_en" value="<?= $config['tarjeta1_titulo_en'] ?? '' ?>" class="form-control">
                            </div>
                            <div style="grid-column:1/3;">
                                <label>Imagen</label>
                                <input type="file" name="t1_imagen" class="form-control" accept="image/jpeg, image/png, image/webp">
                                <div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 400x300 px | Formato: JPG, PNG, WEBP</div>
                                <input type="hidden" name="current_t1_img" value="<?= $config['tarjeta1_imagen'] ?? '' ?>">
                                <?php if($config["tarjeta1_imagen"]): ?>
                                <div class="current-img-preview">
                                    <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                                    <img src="../assets/img/reservas-info/<?= $config["tarjeta1_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                                </div>
                                <?php else: ?>
                                <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta 2 -->
                    <div style="background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:20px;">
                        <h4 style="color:#15305D;margin-bottom:15px;">📌 Tarjeta 2: Cómo Hacer una Reserva</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div>
                                <label>Título (ES)</label>
                                <input type="text" name="t2_tit" value="<?= $config['tarjeta2_titulo'] ?? '' ?>" class="form-control">
                                <label>Enlace</label>
                                <input type="text" name="t2_enlace" value="<?= $config['tarjeta2_enlace'] ?? '' ?>" class="form-control">
                            </div>
                            <div>
                                <label>Título (EN)</label>
                                <input type="text" name="t2_tit_en" value="<?= $config['tarjeta2_titulo_en'] ?? '' ?>" class="form-control">
                            </div>
                            <div style="grid-column:1/3;">
                                <label>Imagen</label>
                                <input type="file" name="t2_imagen" class="form-control" accept="image/jpeg, image/png, image/webp">
                                <div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 400x300 px | Formato: JPG, PNG, WEBP</div>
                                <input type="hidden" name="current_t2_img" value="<?= $config['tarjeta2_imagen'] ?? '' ?>">
                                <?php if($config["tarjeta2_imagen"]): ?>
                                <div class="current-img-preview">
                                    <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                                    <img src="../assets/img/reservas-info/<?= $config["tarjeta2_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                                </div>
                                <?php else: ?>
                                <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta 3 -->
                    <div style="background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:20px;">
                        <h4 style="color:#15305D;margin-bottom:15px;">📌 Tarjeta 3: Alquiler Opcional</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div>
                                <label>Título (ES)</label>
                                <input type="text" name="t3_tit" value="<?= $config['tarjeta3_titulo'] ?? '' ?>" class="form-control">
                                <label>Enlace</label>
                                <input type="text" name="t3_enlace" value="<?= $config['tarjeta3_enlace'] ?? '' ?>" class="form-control">
                            </div>
                            <div>
                                <label>Título (EN)</label>
                                <input type="text" name="t3_tit_en" value="<?= $config['tarjeta3_titulo_en'] ?? '' ?>" class="form-control">
                            </div>
                            <div style="grid-column:1/3;">
                                <label>Imagen</label>
                                <input type="file" name="t3_imagen" class="form-control" accept="image/jpeg, image/png, image/webp">
                                <div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 400x300 px | Formato: JPG, PNG, WEBP</div>
                                <input type="hidden" name="current_t3_img" value="<?= $config['tarjeta3_imagen'] ?? '' ?>">
                                <?php if($config["tarjeta3_imagen"]): ?>
                                <div class="current-img-preview">
                                    <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                                    <img src="../assets/img/reservas-info/<?= $config["tarjeta3_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                                </div>
                                <?php else: ?>
                                <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tarjeta 4 -->
                    <div style="background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:20px;">
                        <h4 style="color:#15305D;margin-bottom:15px;">📌 Tarjeta 4: Calendarios de Excursiones</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div>
                                <label>Título (ES)</label>
                                <input type="text" name="t4_tit" value="<?= $config['tarjeta4_titulo'] ?? '' ?>" class="form-control">
                                <label>Enlace</label>
                                <input type="text" name="t4_enlace" value="<?= $config['tarjeta4_enlace'] ?? '' ?>" class="form-control">
                            </div>
                            <div>
                                <label>Título (EN)</label>
                                <input type="text" name="t4_tit_en" value="<?= $config['tarjeta4_titulo_en'] ?? '' ?>" class="form-control">
                            </div>
                            <div style="grid-column:1/3;">
                                <label>Imagen</label>
                                <input type="file" name="t4_imagen" class="form-control" accept="image/jpeg, image/png, image/webp">
                                <div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 400x300 px | Formato: JPG, PNG, WEBP</div>
                                <input type="hidden" name="current_t4_img" value="<?= $config['tarjeta4_imagen'] ?? '' ?>">
                                <?php if($config["tarjeta4_imagen"]): ?>
                                <div class="current-img-preview">
                                    <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                                    <img src="../assets/img/reservas-info/<?= $config["tarjeta4_imagen"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                                </div>
                                <?php else: ?>
                                <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección Motivacional -->
                <div class="card-admin">
                    <h3>3. Sección Motivacional</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label>Título (ES)</label>
                            <input type="text" name="m_tit" value="<?= $config['motiva_titulo'] ?? '' ?>" class="form-control">
                            <label>Descripción (ES)</label>
                            <textarea name="m_txt" rows="4" class="form-control"><?= $config['motiva_texto'] ?? '' ?></textarea>
                            <label>Botón CTA (ES)</label>
                            <input type="text" name="m_cta" value="<?= $config['motiva_cta'] ?? '' ?>" class="form-control">
                        </div>
                        <div>
                            <label>Título (EN)</label>
                            <input type="text" name="m_tit_en" value="<?= $config['motiva_titulo_en'] ?? '' ?>" class="form-control">
                            <label>Descripción (EN)</label>
                            <textarea name="m_txt_en" rows="4" class="form-control"><?= $config['motiva_texto_en'] ?? '' ?></textarea>
                            <label>Botón CTA (EN)</label>
                            <input type="text" name="m_cta_en" value="<?= $config['motiva_cta_en'] ?? '' ?>" class="form-control">
                        </div>
                        <div style="grid-column:1/3;">
                            <label>Imagen de Fondo</label>
                            <input type="file" name="motiva_fondo" class="form-control" accept="image/jpeg, image/png, image/webp">
                            <div class="img-hint" style="font-size:0.85rem; color:#666; margin-top:5px; margin-bottom:15px;">📏 Tamaño recomendado: 1920x800 px | Formato: JPG, PNG, WEBP</div>
                            <input type="hidden" name="current_motiva_img" value="<?= $config['motiva_fondo'] ?? '' ?>">
                            <?php if($config["motiva_fondo"]): ?>
                            <div class="current-img-preview">
                                <p style="margin:10px 0 5px;font-weight:600;color:#15305D;">📷 Imagen Actual:</p>
                                <img src="../assets/img/reservas-info/<?= $config["motiva_fondo"] ?>" style="max-width:200px;max-height:150px;border-radius:8px;border:2px solid #ddd;">
                            </div>
                            <?php else: ?>
                            <p style="font-size:0.85rem;color:#999;margin:10px 0;">⚠️ No hay imagen cargada</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Estilos -->
                <div class="card-admin">
                    <h3>4. Estilos de Página</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                        <div>
                            <label>Color del Título (Hex)</label>
                            <input type="text" name="color_titulo" value="<?= $config['color_titulo'] ?? '' ?>" class="form-control" placeholder="#0f9b9e">
                            <label>Tamaño del Título</label>
                            <input type="text" name="tamano_titulo" value="<?= $config['tamano_titulo'] ?? '' ?>" class="form-control" placeholder="3rem">
                            <label>Fuente del Título</label>
                            <input type="text" name="fuente_titulo" value="<?= $config['fuente_titulo'] ?? '' ?>" class="form-control" placeholder="Poppins">
                        </div>
                        <div>
                            <label>Color del Texto (Hex)</label>
                            <input type="text" name="color_texto" value="<?= $config['color_texto'] ?? '' ?>" class="form-control" placeholder="#444444">
                            <label>Tamaño del Texto</label>
                            <input type="text" name="tamano_texto" value="<?= $config['tamano_texto'] ?? '' ?>" class="form-control" placeholder="1.1rem">
                            <label>Fuente del Texto</label>
                            <input type="text" name="fuente_texto" value="<?= $config['fuente_texto'] ?? '' ?>" class="form-control" placeholder="Poppins">
                        </div>
                    </div>
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top:20px;"><i class="fas fa-save"></i> GUARDAR TODO</button>
                </div>
            </form>
        </main>
    </div>
    <script>
        const res = new URLSearchParams(window.location.search).get('res');
        if (res === 'updated') {
            Swal.fire({icon:'success',title:'¡Guardado Correctamente!',confirmButtonColor:'#15305D'}).then(()=>{
                window.history.replaceState({},document.title,window.location.pathname);
            });
        }
    </script>
</body>
</html>