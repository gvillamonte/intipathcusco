<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('garantia');

require_once '../config/database.php';
$db = (new Database())->getConnection();

// --- 1. PROCESAR ACTUALIZACIÓN DE CONTENIDO ---
if (isset($_POST['update_page'])) {

    // Manejo de la Foto del Banner (banner_imagen)
    $banner_img = $_POST['current_banner_img'] ?? 'hero_tours.jpg';
    if (isset($_FILES['banner_imagen']) && $_FILES['banner_imagen']['error'] == 0) {
        $ext = pathinfo($_FILES['banner_imagen']['name'], PATHINFO_EXTENSION);
        $banner_img = "garantia_banner_" . time() . "." . $ext;
        move_uploaded_file($_FILES['banner_imagen']['tmp_name'], "../assets/img/seguridad/" . $banner_img);
    }

    // Manejo de la Foto del Aside (aside_imagen)
    $aside_img = $_POST['current_aside_img'] ?? 'hero_tours.jpg';
    if (isset($_FILES['aside_imagen']) && $_FILES['aside_imagen']['error'] == 0) {
        $ext = pathinfo($_FILES['aside_imagen']['name'], PATHINFO_EXTENSION);
        $aside_img = "garantia_aside_" . time() . "." . $ext;
        move_uploaded_file($_FILES['aside_imagen']['tmp_name'], "../assets/img/garantia/" . $aside_img);
    }

    // Manejo de la Foto del Testimonio (aside_test_img)
    $aside_test_img = $_POST['current_aside_test_img'] ?? '';
    if (isset($_FILES['aside_test_foto']) && $_FILES['aside_test_foto']['error'] == 0) {
        $ext = pathinfo($_FILES['aside_test_foto']['name'], PATHINFO_EXTENSION);
        $aside_test_img = "garantia_testimonio_" . time() . "." . $ext;
        move_uploaded_file($_FILES['aside_test_foto']['tmp_name'], "../assets/img/garantia/" . $aside_test_img);
    }

    // Manejo de la Foto de Fondo del CTA (cta_imagen)
    $cta_img = $_POST['current_cta_img'] ?? 'hero_tours.jpg';
    if (isset($_FILES['cta_foto']) && $_FILES['cta_foto']['error'] == 0) {
        $ext = pathinfo($_FILES['cta_foto']['name'], PATHINFO_EXTENSION);
        $cta_img = "garantia_cta_bg_" . time() . "." . $ext;
        move_uploaded_file($_FILES['cta_foto']['tmp_name'], "../assets/img/" . $cta_img);
    }

    // Ejecutar Update COMPLETO
    $stmt = $db->prepare("UPDATE garantia SET 
        banner_titulo = ?, banner_titulo_en = ?, 
        banner_subtitulo = ?, banner_subtitulo_en = ?,
        banner_titulo_size = ?, banner_subtitulo_size = ?,
        banner_color_titulo = ?, banner_color_subtitulo = ?,
        banner_imagen = ?,
        intro_titulo = ?, intro_titulo_en = ?, intro_titulo_size = ?,
        intro_color_titulo = ?, intro_texto = ?, intro_texto_en = ?, intro_color_texto = ?,
        cta_titulo = ?, cta_titulo_en = ?, cta_texto = ?, cta_texto_en = ?,
        cta_btn = ?, cta_btn_en = ?, cta_imagen = ?,
        cta_color_titulo = ?, cta_color_texto = ?, cta_color_btn = ?, cta_color_btn_hover = ?,
        aside_texto = ?, aside_texto_en = ?, aside_btn = ?, aside_btn_en = ?, aside_imagen = ?,
        aside_color_titulo = ?, aside_color_btn = ?, aside_color_btn_hover = ?,
        aside_test_tit = ?, aside_test_tit_en = ?,
        aside_test_txt = ?, aside_test_txt_en = ?,
        aside_test_img = ?, aside_test_fecha = ?, aside_test_fecha_en = ?
        WHERE id = 1");

    $stmt->execute([
        $_POST['b_tit'],
        $_POST['b_tit_en'],
        $_POST['b_sub'],
        $_POST['b_sub_en'],
        $_POST['b_tit_size'],
        $_POST['b_sub_size'],
        $_POST['banner_color_titulo'],
        $_POST['banner_color_subtitulo'],
        $banner_img,
        $_POST['i_tit'],
        $_POST['i_tit_en'],
        $_POST['i_tit_size'],
        $_POST['intro_color_titulo'],
        $_POST['i_txt'],
        $_POST['i_txt_en'],
        $_POST['intro_color_texto'],
        $_POST['cta_tit'],
        $_POST['cta_tit_en'],
        $_POST['cta_txt'],
        $_POST['cta_txt_en'],
        $_POST['cta_btn'],
        $_POST['cta_btn_en'],
        $cta_img,
        $_POST['cta_color_titulo'],
        $_POST['cta_color_texto'],
        $_POST['cta_color_btn'],
        $_POST['cta_color_btn_hover'],
        $_POST['a_txt'],
        $_POST['a_txt_en'],
        $_POST['a_btn'],
        $_POST['a_btn_en'],
        $aside_img,
        $_POST['aside_color_titulo'],
        $_POST['aside_color_btn'],
        $_POST['aside_color_btn_hover'],
        $_POST['test_tit'],
        $_POST['test_tit_en'],
        $_POST['test_txt'],
        $_POST['test_txt_en'],
        $aside_test_img,
        $_POST['test_fecha'],
        $_POST['test_fecha_en']
    ]);

    header("Location: admin_garantia.php?res=updated");
    exit;
}

// --- 2. SUBIR A GALERÍA ---
if (isset($_POST['add_galeria'])) {
    if (isset($_FILES['img_gal']) && $_FILES['img_gal']['error'] == 0) {
        $path = "../assets/img/garantia/";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $nom_gal = "garantia_gal_" . time() . "_" . $_FILES['img_gal']['name'];
        if (move_uploaded_file($_FILES['img_gal']['tmp_name'], $path . $nom_gal)) {
            $db->prepare("INSERT INTO garantia_imagenes (imagen, titulo, titulo_en, tipo_ancho, orden) VALUES (?, ?, ?, ?, ?)")->execute([
                $nom_gal,
                $_POST['img_titulo'] ?? '',
                $_POST['img_titulo_en'] ?? '',
                $_POST['tipo_ancho'] ?? 'columnas',
                $_POST['img_orden'] ?? 0
            ]);
            header("Location: admin_garantia.php?res=gallery_added");
            exit;
        }
    }
}

// --- 3. ELIMINAR DE GALERÍA ---
if (isset($_GET['del_gal'])) {
    $img = $db->query("SELECT imagen FROM garantia_imagenes WHERE id = " . intval($_GET['del_gal']))->fetch(PDO::FETCH_ASSOC);
    if ($img && file_exists('../assets/img/garantia/' . $img['imagen'])) {
        unlink('../assets/img/garantia/' . $img['imagen']);
    }
    $db->prepare("DELETE FROM garantia_imagenes WHERE id = ?")->execute([$_GET['del_gal']]);
    header("Location: admin_garantia.php?res=deleted");
    exit;
}

// --- 4. ACTUALIZAR IMAGEN DE GALERÍA ---
if (isset($_POST['update_galeria'])) {
    $db->prepare("UPDATE garantia_imagenes SET titulo = ?, titulo_en = ?, tipo_ancho = ?, orden = ? WHERE id = ?")->execute([
        $_POST['img_titulo'],
        $_POST['img_titulo_en'],
        $_POST['tipo_ancho'],
        $_POST['img_orden'],
        $_POST['img_id']
    ]);
    header("Location: admin_garantia.php?res=updated");
    exit;
}

$config = $db->query("SELECT * FROM garantia WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$galeria = $db->query("SELECT * FROM garantia_imagenes ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Garantía | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --admin-blue: #15305D;
            --admin-accent: #E8AC18;
            --bg-light: #f4f7f6;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-title-inti {
            color: var(--admin-blue);
            font-weight: 800;
            border-bottom: 4px solid var(--admin-accent);
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 25px;
            text-transform: uppercase;
        }

        .card-admin {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }

        .card-admin h3 {
            border-left: 5px solid var(--admin-blue);
            padding-left: 15px;
            color: var(--admin-blue);
            margin-bottom: 20px;
            text-transform: uppercase;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        .btn-admin {
            background: var(--admin-blue);
            color: #fff;
            padding: 14px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            width: 100%;
        }

        .btn-admin:hover {
            background: #0e2245;
            transform: translateY(-2px);
        }

        .thumb-admin {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
            margin-top: 10px;
        }

        .galeria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .gal-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #eee;
        }

        .gal-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .btn-del {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding: 30px;">
            <h1 class="admin-title-inti"><i class="fas fa-shield-alt"></i> Configuración de Garantía</h1>

            <form method="POST" enctype="multipart/form-data">
                <!-- 1. BANNER PRINCIPAL -->
                <div class="card-admin">
                    <h3>1. Banner Principal</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título Banner (ES)</label>
                            <input type="text" name="b_tit" value="<?= $config['banner_titulo'] ?>" class="form-control">
                            <label>Subtítulo (ES)</label>
                            <input type="text" name="b_sub" value="<?= $config['banner_subtitulo'] ?>" class="form-control">
                        </div>
                        <div>
                            <label>Título Banner (EN)</label>
                            <input type="text" name="b_tit_en" value="<?= $config['banner_titulo_en'] ?>" class="form-control">
                            <label>Subtítulo (EN)</label>
                            <input type="text" name="b_sub_en" value="<?= $config['banner_subtitulo_en'] ?>" class="form-control">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Tamaño Título</label>
                            <select name="b_tit_size" class="form-control">
                                <?php for($i = 32; $i <= 96; $i += 4): ?>
                                    <option value="<?= $i ?>" <?= ($config['banner_titulo_size'] ?? 64) == $i ? 'selected' : '' ?>><?= $i ?>px</option>
                                <?php endfor; ?>
                            </select>
                            <label>Tamaño Subtítulo</label>
                            <select name="b_sub_size" class="form-control">
                                <?php for($i = 16; $i <= 48; $i += 2): ?>
                                    <option value="<?= $i ?>" <?= ($config['banner_subtitulo_size'] ?? 24) == $i ? 'selected' : '' ?>><?= $i ?>px</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Color Título</label>
                            <input type="color" name="banner_color_titulo" value="<?= $config['banner_color_titulo'] ?? '#0f9b9e' ?>" class="form-control" style="height:40px;">
                            <label>Color Subtítulo</label>
                            <input type="color" name="banner_color_subtitulo" value="<?= $config['banner_color_subtitulo'] ?? '#f1f1f1' ?>" class="form-control" style="height:40px;">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Imagen de Banner</label>
                            <input type="file" name="banner_imagen" class="form-control" onchange="previewImage(this, 'p_banner')">
                            <input type="hidden" name="current_banner_img" value="<?= $config['banner_imagen'] ?? 'hero_tours.jpg' ?>">
                            <?php if ($config['banner_imagen']): ?>
                                <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px;">
                                    <p style="font-size: 10px; color: #666; margin-bottom: 5px;">VISTA PREVIA DEL BANNER ACTUAL:</p>
                                    <img id="p_banner" src="../assets/img/seguridad/<?= $config['banner_imagen'] ?>?v=<?= time() ?>"
                                        style="width: 100%; max-height: 150px; object-fit: cover; border-radius: 5px; border: 1px solid #ccc;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. TÍTULO DE INTRODUCCIÓN -->
                <div class="card-admin">
                    <h3>2. Título de Introducción</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título (ES)</label>
                            <input type="text" name="i_tit" value="<?= $config['intro_titulo'] ?>" class="form-control">
                            <label>Tamaño</label>
                            <select name="i_tit_size" class="form-control">
                                <?php for($i = 24; $i <= 72; $i += 4): ?>
                                    <option value="<?= $i ?>" <?= ($config['intro_titulo_size'] ?? 40) == $i ? 'selected' : '' ?>><?= $i ?>px</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label>Título (EN)</label>
                            <input type="text" name="i_tit_en" value="<?= $config['intro_titulo_en'] ?>" class="form-control">
                            <label>Color</label>
                            <input type="color" name="intro_color_titulo" value="<?= $config['intro_color_titulo'] ?? '#0f9b9e' ?>" class="form-control" style="height:40px;">
                        </div>
                    </div>
                </div>

                <!-- 3. DESCRIPCIÓN (TEXTO PLANO) -->
                <div class="card-admin">
                    <h3>3. Descripción (Texto Plano)</h3>
                    <div class="help-text">
                        <strong>Guía de formato:</strong><br>
                        - Usa <code># Título</code> para crear títulos (será color verde esmeralda)<br>
                        - Usa <code>- Item</code> para crear listas con check (color verde limón)
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Contenido (ES)</label>
                            <textarea name="i_txt" rows="8" class="form-control"><?= $config['intro_texto'] ?></textarea>
                        </div>
                        <div>
                            <label>Contenido (EN)</label>
                            <textarea name="i_txt_en" rows="8" class="form-control"><?= $config['intro_texto_en'] ?></textarea>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Color del Texto</label>
                            <input type="color" name="intro_color_texto" value="<?= $config['intro_color_texto'] ?? '#555555' ?>" class="form-control" style="height:40px; width:100px;">
                        </div>
                    </div>
                </div>

                <!-- 4. BARRA LATERAL (ASIDE) -->
                <div class="card-admin">
                    <h3>4. Barra Lateral (Especialista & Testimonio)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Especialista (ES)</label>
                            <textarea name="a_txt" rows="3" class="form-control"><?= $config['aside_texto'] ?></textarea>
                            <label>Botón (ES)</label>
                            <input type="text" name="a_btn" value="<?= $config['aside_btn'] ?>" class="form-control">
                            <label>Testimonio Título (ES)</label>
                            <input type="text" name="test_tit" value="<?= $config['aside_test_tit'] ?>" class="form-control">
                            <label>Testimonio Texto (ES)</label>
                            <textarea name="test_txt" rows="3" class="form-control"><?= $config['aside_test_txt'] ?></textarea>
                        </div>
                        <div>
                            <label>Especialista (EN)</label>
                            <textarea name="a_txt_en" rows="3" class="form-control"><?= $config['aside_texto_en'] ?></textarea>
                            <label>Botón (EN)</label>
                            <input type="text" name="a_btn_en" value="<?= $config['aside_btn_en'] ?>" class="form-control">
                            <label>Testimonio Título (EN)</label>
                            <input type="text" name="test_tit_en" value="<?= $config['aside_test_tit_en'] ?>" class="form-control">
                            <label>Testimonio Texto (EN)</label>
                            <textarea name="test_txt_en" rows="3" class="form-control"><?= $config['aside_test_txt_en'] ?></textarea>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Fecha del Testimonio (ES / EN)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="test_fecha" value="<?= $config['aside_test_fecha'] ?>" class="form-control" placeholder="ES">
                                <input type="text" name="test_fecha_en" value="<?= $config['aside_test_fecha_en'] ?>" class="form-control" placeholder="EN">
                            </div>
                        </div>
                        <div>
                            <label>Color Título</label>
                            <input type="color" name="aside_color_titulo" value="<?= $config['aside_color_titulo'] ?? '#0f9b9e' ?>" class="form-control" style="height:40px;">
                            <label>Color Botón</label>
                            <input type="color" name="aside_color_btn" value="<?= $config['aside_color_btn'] ?? '#0f9b9e' ?>" class="form-control" style="height:40px;">
                            <label>Color Botón Hover</label>
                            <input type="color" name="aside_color_btn_hover" value="<?= $config['aside_color_btn_hover'] ?? '#c6d544' ?>" class="form-control" style="height:40px;">
                        </div>
                        <div>
                            <label>Foto Especialista</label>
                            <input type="file" name="aside_imagen" class="form-control" onchange="previewImage(this, 'p_aside')">
                            <input type="hidden" name="current_aside_img" value="<?= $config['aside_imagen'] ?? 'hero_tours.jpg' ?>">
                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                <img id="p_aside" src="../assets/img/garantia/<?= $config['aside_imagen'] ?>?v=<?= time() ?>"
                                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--admin-blue);"
                                    onerror="this.src='../assets/img/no-image.jpg'">
                                <p style="font-size: 10px; color: #666; margin-top: 5px;">MINIATURA ACTUAL</p>
                            </div>
                        </div>
                        <div>
                            <label>Foto Testimonio</label>
                            <input type="file" name="aside_test_foto" class="form-control" onchange="previewImage(this, 'p_test')">
                            <input type="hidden" name="current_aside_test_img" value="<?= $config['aside_test_img'] ?>">
                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                <img id="p_test" src="../assets/img/garantia/<?= $config['aside_test_img'] ?>?v=<?= time() ?>"
                                    style="width: 120px; height: 70px; border-radius: 6px; object-fit: cover; border: 2px solid var(--admin-blue);"
                                    onerror="this.src='../assets/img/no-image.jpg'">
                                <p style="font-size: 10px; color: #666; margin-top: 5px;">MINIATURA ACTUAL</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. SECCIÓN CTA -->
                <div class="card-admin">
                    <h3>5. Sección de Planificación (CTA)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título CTA (ES)</label>
                            <input type="text" name="cta_tit" value="<?= $config['cta_titulo'] ?>" class="form-control">
                            <label>Botón CTA (ES)</label>
                            <input type="text" name="cta_btn" value="<?= $config['cta_btn'] ?>" class="form-control">
                        </div>
                        <div>
                            <label>Título CTA (EN)</label>
                            <input type="text" name="cta_tit_en" value="<?= $config['cta_titulo_en'] ?>" class="form-control">
                            <label>Botón CTA (EN)</label>
                            <input type="text" name="cta_btn_en" value="<?= $config['cta_btn_en'] ?>" class="form-control">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Texto CTA (ES)</label>
                            <textarea name="cta_txt" rows="2" class="form-control"><?= $config['cta_texto'] ?></textarea>
                            <label>Texto CTA (EN)</label>
                            <textarea name="cta_txt_en" rows="2" class="form-control"><?= $config['cta_texto_en'] ?></textarea>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Color Título</label>
                            <input type="color" name="cta_color_titulo" value="<?= $config['cta_color_titulo'] ?? '#ffffff' ?>" class="form-control" style="height:40px;">
                            <label>Color Texto</label>
                            <input type="color" name="cta_color_texto" value="<?= $config['cta_color_texto'] ?? '#ffffff' ?>" class="form-control" style="height:40px;">
                            <label>Color Botón</label>
                            <input type="color" name="cta_color_btn" value="<?= $config['cta_color_btn'] ?? '#0f9b9e' ?>" class="form-control" style="height:40px;">
                            <label>Color Botón Hover</label>
                            <input type="color" name="cta_color_btn_hover" value="<?= $config['cta_color_btn_hover'] ?? '#c6d544' ?>" class="form-control" style="height:40px;">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Fondo Banner CTA</label>
                            <input type="file" name="cta_foto" class="form-control">
                            <input type="hidden" name="current_cta_img" value="<?= $config['cta_imagen'] ?? 'hero_tours.jpg' ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top: 20px;"><i class="fas fa-save"></i> GUARDAR TODO EL CONTENIDO</button>
                </div>
            </form>

            <!-- GALERÍA - FORMULARIO SEPARADO -->
            <div class="card-admin">
                <h3>6. Galería de Fotos</h3>
                <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="file" name="img_gal" required class="form-control" style="margin:0; flex:1; min-width:200px;">
                    <input type="text" name="img_titulo" class="form-control" placeholder="Título ES" style="margin:0; flex:1; min-width:150px;">
                    <input type="text" name="img_titulo_en" class="form-control" placeholder="Título EN" style="margin:0; flex:1; min-width:150px;">
                    <select name="tipo_ancho" class="form-control" style="margin:0; width:auto;">
                        <option value="columnas">Columnas</option>
                        <option value="full">Ancho Completo</option>
                    </select>
                    <input type="number" name="img_orden" class="form-control" placeholder="Orden" value="0" style="margin:0; width:80px;">
                    <button type="submit" name="add_galeria" class="btn-admin" style="width: 200px;">SUBIR FOTO</button>
                </form>
                
                <div class="galeria-grid">
                    <?php foreach ($galeria as $img): ?>
                        <div class="gal-item" style="position:relative;">
                            <img src="../assets/img/garantia/<?= $img['imagen'] ?>" style="width:100%; height:150px; object-fit:cover;">
                            <div style="position:absolute; top:5px; right:5px; display:flex; gap:5px;">
                                <button onclick="editGaleria(<?= $img['id'] ?>, '<?= htmlspecialchars($img['titulo']) ?>', '<?= htmlspecialchars($img['titulo_en']) ?>', '<?= $img['tipo_ancho'] ?>', <?= $img['orden'] ?>)" style="background:#15305D; color:white; border:none; border-radius:50%; width:25px; height:25px; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fas fa-edit" style="font-size:10px;"></i></button>
                                <button onclick="confDel(<?= $img['id'] ?>)" style="background:#e74c3c; color:white; border:none; border-radius:50%; width:25px; height:25px; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="fas fa-trash" style="font-size:10px;"></i></button>
                            </div>
                            <span style="display:inline-block; padding:3px 8px; background:var(--admin-blue); color:white; border-radius:3px; font-size:11px;"><?= $img['tipo_ancho'] === 'full' ? 'Full' : 'Columnas' ?></span>
                            <h4 style="margin:10px 0 5px; color:var(--admin-blue); font-size:14px;"><?= htmlspecialchars($img['titulo'] ?: 'Sin título') ?></h4>
                            <p style="margin:5px 0; font-size:12px; color:#666;">Orden: <?= $img['orden'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Editar Galería -->
    <div id="editGaleriaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:15px; max-width:500px; width:90%;">
            <h3 style="margin-top:0; color:var(--admin-blue);">Editar Imagen</h3>
            <form method="POST">
                <input type="hidden" name="update_galeria" value="1">
                <input type="hidden" name="img_id" id="edit_img_id">
                <label>Título (ES)</label>
                <input type="text" name="img_titulo" id="edit_img_titulo" class="form-control" style="margin-bottom:10px;">
                <label>Título (EN)</label>
                <input type="text" name="img_titulo_en" id="edit_img_titulo_en" class="form-control" style="margin-bottom:10px;">
                <label>Tipo</label>
                <select name="tipo_ancho" id="edit_tipo_ancho" class="form-control" style="margin-bottom:10px;">
                    <option value="columnas">Columnas</option>
                    <option value="full">Ancho Completo</option>
                </select>
                <label>Orden</label>
                <input type="number" name="img_orden" id="edit_img_orden" class="form-control" style="margin-bottom:15px;">
                <div style="display:flex; gap:10px;">
                    <button type="submit" style="flex:1; padding:12px; background:var(--admin-blue); color:white; border:none; border-radius:8px; cursor:pointer;">Guardar</button>
                    <button type="button" onclick="closeEditModal()" style="flex:1; padding:12px; background:#ccc; color:#333; border:none; border-radius:8px; cursor:pointer;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confDel(id) {
            Swal.fire({
                title: '¿Eliminar foto?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#15305D',
                confirmButtonText: 'Sí, borrar'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'admin_garantia.php?del_gal=' + id;
            });
        }
        
        function editGaleria(id, tit, titEn, tipo, orden) {
            document.getElementById('edit_img_id').value = id;
            document.getElementById('edit_img_titulo').value = tit || '';
            document.getElementById('edit_img_titulo_en').value = titEn || '';
            document.getElementById('edit_tipo_ancho').value = tipo || 'columnas';
            document.getElementById('edit_img_orden').value = orden || 0;
            document.getElementById('editGaleriaModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editGaleriaModal').style.display = 'none';
        }
        
        const res = new URLSearchParams(window.location.search).get('res');
        if (res) {
            Swal.fire({
                icon: 'success',
                title: '¡Operación Exitosa!',
                confirmButtonColor: '#15305D'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>

    <script>
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                    document.getElementById(previewId).style.borderColor = "#E8AC18";
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>