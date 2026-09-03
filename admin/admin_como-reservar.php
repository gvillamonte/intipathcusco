<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('como_reservar');

require_once '../config/database.php';
$db = (new Database())->getConnection();

if (isset($_POST['update_page'])) {
    $aside_img = $_POST['current_aside_img'];
    if (isset($_FILES['aside_foto']) && $_FILES['aside_foto']['error'] == 0) {
        $aside_img = "reservar_spec_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_foto']['tmp_name'], "../assets/img/como-reservar/" . $aside_img);
    }
    $intro_img = $_POST['current_intro_img'];
    if (isset($_FILES['intro_imagen']) && $_FILES['intro_imagen']['error'] == 0) {
        $intro_img = "reservar_banner_" . time() . ".jpg";
        move_uploaded_file($_FILES['intro_imagen']['tmp_name'], "../assets/img/como-reservar/" . $intro_img);
    }
    $cta_img = $_POST['current_cta_img'];
    if (isset($_FILES['cta_foto']) && $_FILES['cta_foto']['error'] == 0) {
        $cta_img = "reservar_cta_bg_" . time() . ".jpg";
        move_uploaded_file($_FILES['cta_foto']['tmp_name'], "../assets/img/como-reservar/" . $cta_img);
    }
    $stmt = $db->prepare("UPDATE como_reservar SET 
        banner_titulo = ?, banner_titulo_en = ?, 
        banner_subtitulo = ?, banner_subtitulo_en = ?, 
        intro_texto = ?, intro_texto_en = ?, intro_imagen = ?, 
        aside_texto = ?, aside_texto_en = ?, 
        aside_btn = ?, aside_btn_en = ?, aside_imagen = ?, 
        cta_titulo = ?, cta_titulo_en = ?, 
        cta_texto = ?, cta_texto_en = ?, 
        cta_btn = ?, cta_btn_en = ?, cta_imagen = ?,
        color_titulo = ?, tamano_titulo = ?,
        color_texto = ?, tamano_texto = ?
        WHERE id = 1");
    $stmt->execute([
        $_POST['b_tit'], $_POST['b_tit_en'],
        $_POST['b_sub'], $_POST['b_sub_en'],
        $_POST['i_txt'], $_POST['i_txt_en'], $intro_img,
        $_POST['a_txt'], $_POST['a_txt_en'],
        $_POST['a_btn'], $_POST['a_btn_en'], $aside_img,
        $_POST['cta_tit'], $_POST['cta_tit_en'],
        $_POST['cta_txt'], $_POST['cta_txt_en'],
        $_POST['cta_btn'], $_POST['cta_btn_en'], $cta_img,
        $_POST['color_titulo'], $_POST['tamano_titulo'],
        $_POST['color_texto'], $_POST['tamano_texto']
    ]);
    header("Location: admin_como-reservar.php?res=updated");
    exit;
}
$config = $db->query("SELECT * FROM como_reservar WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Cómo Reservar | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; --esmeralda: #0f9b9e; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title-inti { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; }
        .card-admin { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #ddd; }
        .card-admin h3 { border-left: 5px solid var(--admin-blue); padding-left: 15px; color: var(--admin-blue); margin-bottom: 25px; text-transform: uppercase; font-size: 1rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; margin-bottom: 15px; }
        .btn-admin { background: var(--admin-blue); color: #fff; padding: 14px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; width: 100%; }
        .btn-admin:hover { background: #0e2245; transform: translateY(-2px); }
        
        .section-title { font-size: 0.9rem; font-weight: 700; color: #555; margin: 20px 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #eee; }
        
        .img-upload-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 12px; 
            border: 2px dashed #ccc; 
            margin: 15px 0;
            transition: 0.3s;
        }
        .img-upload-box:hover { border-color: var(--esmeralda); background: #f0fdfd; }
        
        .img-preview-container {
            margin-top: 15px;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid;
        }
        .img-preview-container.has-image {
            background: #d4edda;
            border-color: #28a745;
        }
        .img-preview-container.no-image {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .img-preview-container img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .status-badge.success { background: #d4edda; color: #155724; }
        .status-badge.warning { background: #fff3cd; color: #856404; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-1 { grid-column: 1 / -1; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding: 30px;">
            <h1 class="admin-title-inti"><i class="fas fa-calendar-check"></i> Configuración - Cómo Reservar</h1>
            
            <form method="POST" enctype="multipart/form-data">
                
                <!-- ==================== SECCIÓN 1: BANNER ==================== -->
                <div class="card-admin">
                    <h3>1. Banner e Introducción</h3>
                    
                    <!-- Textos -->
                    <p class="section-title">📝 CONTENIDO DE TEXTO</p>
                    <div class="grid-2">
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
                        <div class="grid-1">
                            <label>Texto Introducción (ES)</label>
                            <textarea name="i_txt" rows="4" class="form-control"><?= $config['intro_texto'] ?? '' ?></textarea>
                            <label>Texto Introducción (EN)</label>
                            <textarea name="i_txt_en" rows="4" class="form-control"><?= $config['intro_texto_en'] ?? '' ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Imagen Banner -->
                    <p class="section-title">🖼️ IMAGEN DEL BANNER</p>
                    <div class="img-upload-box">
                        <label>Subir imagen (1920x600 px)</label>
                        <input type="file" name="intro_imagen" class="form-control" accept="image/jpeg, image/png, image/webp">
                        <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">📏 Tamaño recomendado: 1920x600 px | Formato: JPG, PNG, WEBP</p>
                        
                        <?php if(!empty($config['intro_imagen'])): ?>
                            <div class="img-preview-container has-image">
                                <span class="status-badge success">✅ Imagen cargada correctamente</span>
                                <img src="../assets/img/como-reservar/<?= $config['intro_imagen'] ?>" alt="Banner">
                                <p style="margin-top: 10px; font-size: 0.8rem; color: #666;">📄 Archivo: <?= $config['intro_imagen'] ?></p>
                            </div>
                        <?php else: ?>
                            <div class="img-preview-container no-image">
                                <span class="status-badge warning">⚠️ No hay imagen cargada</span>
                                <p style="margin-top: 10px; font-size: 0.85rem; color: #666;">Sube una imagen para ver el preview</p>
                            </div>
                        <?php endif; ?>
                        
                        <input type="hidden" name="current_intro_img" value="<?= $config['intro_imagen'] ?? '' ?>">
                    </div>
                </div>
                
                <!-- ==================== SECCIÓN 2: BARRA LATERAL ==================== -->
                <div class="card-admin">
                    <h3>2. Barra Lateral</h3>
                    
                    <!-- Textos -->
                    <p class="section-title">📝 CONTENIDO DE TEXTO</p>
                    <div class="grid-2">
                        <div>
                            <label>Texto (ES)</label>
                            <textarea name="a_txt" rows="3" class="form-control"><?= $config['aside_texto'] ?? '' ?></textarea>
                            <label>Botón (ES)</label>
                            <input type="text" name="a_btn" value="<?= $config['aside_btn'] ?? '' ?>" class="form-control">
                        </div>
                        <div>
                            <label>Texto (EN)</label>
                            <textarea name="a_txt_en" rows="3" class="form-control"><?= $config['aside_texto_en'] ?? '' ?></textarea>
                            <label>Botón (EN)</label>
                            <input type="text" name="a_btn_en" value="<?= $config['aside_btn_en'] ?? '' ?>" class="form-control">
                        </div>
                    </div>
                    
                    <!-- Imagen Lateral -->
                    <p class="section-title">🖼️ IMAGEN DE LA BARRA LATERAL</p>
                    <div class="img-upload-box">
                        <label>Subir imagen (400x400 px)</label>
                        <input type="file" name="aside_foto" class="form-control" accept="image/jpeg, image/png, image/webp">
                        <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">📏 Tamaño recomendado: 400x400 px | Formato: JPG, PNG, WEBP</p>
                        
                        <?php if(!empty($config['aside_imagen'])): ?>
                            <div class="img-preview-container has-image">
                                <span class="status-badge success">✅ Imagen cargada correctamente</span>
                                <img src="../assets/img/como-reservar/<?= $config['aside_imagen'] ?>" alt="Lateral">
                                <p style="margin-top: 10px; font-size: 0.8rem; color: #666;">📄 Archivo: <?= $config['aside_imagen'] ?></p>
                            </div>
                        <?php else: ?>
                            <div class="img-preview-container no-image">
                                <span class="status-badge warning">⚠️ No hay imagen cargada</span>
                                <p style="margin-top: 10px; font-size: 0.85rem; color: #666;">Sube una imagen para ver el preview</p>
                            </div>
                        <?php endif; ?>
                        
                        <input type="hidden" name="current_aside_img" value="<?= $config['aside_imagen'] ?? '' ?>">
                    </div>
                </div>
                
                <!-- ==================== SECCIÓN 3: CTA ==================== -->
                <div class="card-admin">
                    <h3>3. Sección CTA</h3>
                    
                    <!-- Textos -->
                    <p class="section-title">📝 CONTENIDO DE TEXTO</p>
                    <div class="grid-2">
                        <div>
                            <label>Título CTA (ES)</label>
                            <input type="text" name="cta_tit" value="<?= $config['cta_titulo'] ?? '' ?>" class="form-control">
                            <label>Botón CTA (ES)</label>
                            <input type="text" name="cta_btn" value="<?= $config['cta_btn'] ?? '' ?>" class="form-control">
                            <label>Texto CTA (ES)</label>
                            <textarea name="cta_txt" rows="2" class="form-control"><?= $config['cta_texto'] ?? '' ?></textarea>
                        </div>
                        <div>
                            <label>Título CTA (EN)</label>
                            <input type="text" name="cta_tit_en" value="<?= $config['cta_titulo_en'] ?? '' ?>" class="form-control">
                            <label>Botón CTA (EN)</label>
                            <input type="text" name="cta_btn_en" value="<?= $config['cta_btn_en'] ?? '' ?>" class="form-control">
                            <label>Texto CTA (EN)</label>
                            <textarea name="cta_txt_en" rows="2" class="form-control"><?= $config['cta_texto_en'] ?? '' ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Imagen CTA -->
                    <p class="section-title">🖼️ IMAGEN DE FONDO CTA</p>
                    <div class="img-upload-box">
                        <label>Subir imagen (1920x400 px)</label>
                        <input type="file" name="cta_foto" class="form-control" accept="image/jpeg, image/png, image/webp">
                        <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">📏 Tamaño recomendado: 1920x400 px | Formato: JPG, PNG, WEBP</p>
                        
                        <?php if(!empty($config['cta_imagen'])): ?>
                            <div class="img-preview-container has-image">
                                <span class="status-badge success">✅ Imagen cargada correctamente</span>
                                <img src="../assets/img/como-reservar/<?= $config['cta_imagen'] ?>" alt="CTA">
                                <p style="margin-top: 10px; font-size: 0.8rem; color: #666;">📄 Archivo: <?= $config['cta_imagen'] ?></p>
                            </div>
                        <?php else: ?>
                            <div class="img-preview-container no-image">
                                <span class="status-badge warning">⚠️ No hay imagen cargada</span>
                                <p style="margin-top: 10px; font-size: 0.85rem; color: #666;">Sube una imagen para ver el preview</p>
                            </div>
                        <?php endif; ?>
                        
                        <input type="hidden" name="current_cta_img" value="<?= $config['cta_imagen'] ?? '' ?>">
                    </div>
                    
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top: 20px;">
                        <i class="fas fa-save"></i> GUARDAR CONTENIDO
                    </button>
                </div>
                
                <!-- ==================== SECCIÓN 4: ESTILOS ==================== -->
                <div class="card-admin">
                    <h3>4. Estilos de Página</h3>
                    <p class="section-title">🎨 PERSONALIZACIÓN VISUAL</p>
                    
                    <div class="grid-2">
                        <div>
                            <label>Color del Título (Hex)</label>
                            <input type="text" name="color_titulo" value="<?= $config['color_titulo'] ?? '' ?>" class="form-control" placeholder="#0f9b9e">
                            <label>Tamaño del Título</label>
                            <input type="text" name="tamano_titulo" value="<?= $config['tamano_titulo'] ?? '' ?>" class="form-control" placeholder="4rem">
                        </div>
                        <div>
                            <label>Color del Texto (Hex)</label>
                            <input type="text" name="color_texto" value="<?= $config['color_texto'] ?? '' ?>" class="form-control" placeholder="#444444">
                            <label>Tamaño del Texto</label>
                            <input type="text" name="tamano_texto" value="<?= $config['tamano_texto'] ?? '' ?>" class="form-control" placeholder="1.1rem">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top: 20px;">
                        <i class="fas fa-save"></i> GUARDAR ESTILOS
                    </button>
                </div>
                
            </form>
        </main>
    </div>
    
    <script>
        const res = new URLSearchParams(window.location.search).get('res');
        if (res === 'updated') {
            Swal.fire({
                icon: 'success',
                title: '¡Guardado Correctamente!',
                confirmButtonColor: '#15305D'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>