<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('seguridad');

require_once '../config/database.php';
$db = (new Database())->getConnection();

if (isset($_POST['update_page'])) {
    $cta_img = $_POST['current_cta_img'];
    if (isset($_FILES['cta_foto']) && $_FILES['cta_foto']['error'] == 0) {
        $cta_img = "seg_cta_" . time() . ".jpg";
        move_uploaded_file($_FILES['cta_foto']['tmp_name'], "../assets/img/" . $cta_img);
    }

    $banner_img = $_POST['current_banner_img'];
    if (isset($_FILES['banner_imagen']) && $_FILES['banner_imagen']['error'] == 0) {
        $path = "../assets/img/seguridad/";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $banner_img = "seg_banner_" . time() . ".jpg";
        move_uploaded_file($_FILES['banner_imagen']['tmp_name'], $path . $banner_img);
    }

    $aside_img = $_POST['current_aside_img'];
    if (isset($_FILES['aside_foto']) && $_FILES['aside_foto']['error'] == 0) {
        $aside_img = "seg_spec_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_foto']['tmp_name'], "../assets/img/seguridad/" . $aside_img);
    }

    $aside_test_img = $_POST['current_aside_test_img'];
    if (isset($_FILES['aside_test_foto']) && $_FILES['aside_test_foto']['error'] == 0) {
        $aside_test_img = "seg_testimonio_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_test_foto']['tmp_name'], "../assets/img/seguridad/" . $aside_test_img);
    }

    $stmt = $db->prepare("UPDATE seguridad SET 
        banner_titulo = ?, banner_titulo_en = ?, 
        banner_subtitulo = ?, banner_subtitulo_en = ?,
        banner_titulo_size = ?, banner_subtitulo_size = ?,
        banner_imagen = ?,
        banner_color_titulo = ?, banner_color_subtitulo = ?,
        intro_texto = ?, intro_texto_en = ?, intro_titulo_size = ?,
        intro_color_titulo = ?, intro_color_texto = ?,
        video_url = ?,
        seccion2_titulo = ?, seccion2_titulo_en = ?,
        video_color_titulo = ?,
        titulo_general_card = ?, titulo_general_card_en = ?,
        cards_color_titulo = ?, cards_color_fondo = ?, cards_color_check = ?,
        aside_texto = ?, aside_texto_en = ?,
        aside_btn = ?, aside_btn_en = ?, 
        aside_imagen = ?,
        aside_color_titulo = ?, aside_color_btn = ?, aside_color_btn_hover = ?,
        aside_test_tit = ?, aside_test_tit_en = ?,
        aside_test_txt = ?, aside_test_txt_en = ?,
        aside_test_img = ?, aside_test_fecha = ?, aside_test_fecha_en = ?,
        cta_titulo = ?, cta_titulo_en = ?,
        cta_texto = ?, cta_texto_en = ?,
        cta_btn = ?, cta_btn_en = ?,
        cta_imagen = ?,
        cta_color_titulo = ?, cta_color_texto = ?, cta_color_btn = ?, cta_color_btn_hover = ?
        WHERE id = 1");

    $stmt->execute([
        $_POST['b_tit'],
        $_POST['b_tit_en'],
        $_POST['b_sub'],
        $_POST['b_sub_en'],
        $_POST['b_tit_size'],
        $_POST['b_sub_size'],
        $banner_img,
        $_POST['banner_color_titulo'],
        $_POST['banner_color_subtitulo'],
        $_POST['i_txt'],
        $_POST['i_txt_en'],
        $_POST['i_tit_size'],
        $_POST['intro_color_titulo'],
        $_POST['intro_color_texto'],
        $_POST['video_url'],
        $_POST['sec2_tit'],
        $_POST['sec2_tit_en'],
        $_POST['video_color_titulo'],
        $_POST['titulo_general_card'],
        $_POST['titulo_general_card_en'],
        $_POST['cards_color_titulo'],
        $_POST['cards_color_fondo'],
        $_POST['cards_color_check'],
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
        $_POST['test_fecha_en'],
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
        $_POST['cta_color_btn_hover']
    ]);

    $_SESSION['seguridad_mensaje'] = 'updated';
    header("Location: admin_seguridad.php");
    exit;
}

if (isset($_POST['add_card'])) {
    $path = "../assets/img/seguridad/";
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    $img_name = "";
    if (isset($_FILES['card_img']) && $_FILES['card_img']['error'] == 0) {
        $img_name = "seg_card_" . time() . "_" . $_FILES['card_img']['name'];
        move_uploaded_file($_FILES['card_img']['tmp_name'], $path . $img_name);
    }

    $db->prepare("INSERT INTO seguridad_cards (imagen, titulo, titulo_en, descripcion, descripcion_en) VALUES (?, ?, ?, ?, ?)")
        ->execute([$img_name, $_POST['card_tit'], $_POST['card_tit_en'], $_POST['card_desc'], $_POST['card_desc_en']]);
    
    $_SESSION['seguridad_mensaje'] = 'card_added';
    header("Location: admin_seguridad.php");
    exit;
}

if (isset($_GET['del_card'])) {
    $card = $db->prepare("SELECT imagen FROM seguridad_cards WHERE id = ?")->execute([$_GET['del_card']]);
    $card = $db->query("SELECT imagen FROM seguridad_cards WHERE id = " . intval($_GET['del_card']))->fetch(PDO::FETCH_ASSOC);
    if ($card && !empty($card['imagen']) && file_exists("../assets/img/seguridad/" . $card['imagen'])) {
        unlink("../assets/img/seguridad/" . $card['imagen']);
    }
    $db->prepare("DELETE FROM seguridad_cards WHERE id = ?")->execute([$_GET['del_card']]);
    $_SESSION['seguridad_mensaje'] = 'deleted';
    header("Location: admin_seguridad.php");
    exit;
}

if (isset($_POST['update_card'])) {
    $path = "../assets/img/seguridad/";
    $img_name = $_POST['current_card_img'] ?? '';
    if (isset($_FILES['edit_card_img']) && $_FILES['edit_card_img']['error'] == 0) {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $old = $db->prepare("SELECT imagen FROM seguridad_cards WHERE id = ?");
        $old->execute([$_POST['edit_card_id']]);
        $old_img = $old->fetchColumn();
        if ($old_img && file_exists($path . $old_img)) {
            unlink($path . $old_img);
        }
        $img_name = "seg_card_" . time() . "_" . $_FILES['edit_card_img']['name'];
        move_uploaded_file($_FILES['edit_card_img']['tmp_name'], $path . $img_name);
    }
    $stmt = $db->prepare("UPDATE seguridad_cards SET imagen = ?, titulo = ?, titulo_en = ?, descripcion = ?, descripcion_en = ? WHERE id = ?");
    $stmt->execute([
        $img_name,
        $_POST['edit_card_tit'],
        $_POST['edit_card_tit_en'],
        $_POST['edit_card_desc'],
        $_POST['edit_card_desc_en'],
        $_POST['edit_card_id']
    ]);
    $_SESSION['seguridad_mensaje'] = 'card_updated';
    header("Location: admin_seguridad.php");
    exit;
}

$mensaje = $_SESSION['seguridad_mensaje'] ?? null;
unset($_SESSION['seguridad_mensaje']);

$config = $db->query("SELECT * FROM seguridad WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$cards = $db->query("SELECT * FROM seguridad_cards ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Seguridad | IntiPath</title>
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

        .cards-grid-admin {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card-item-admin {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #ddd;
        }

        .card-item-admin img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .card-item-admin-body {
            padding: 15px;
        }

        .card-item-admin-body h4 {
            color: var(--admin-blue);
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .card-item-admin-body p {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .btn-del {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .help-text {
            background: #fff3cd;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding: 30px;">
            <h1 class="admin-title-inti"><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h1>

            <form method="POST" enctype="multipart/form-data">
                <div class="card-admin">
                    <h3>1. Banner y Introducción</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título Banner (ES)</label>
                            <input type="text" name="b_tit" value="<?= htmlspecialchars($config['banner_titulo'] ?? '') ?>" class="form-control">
                            <label>Tamaño Título (px)</label>
                            <select name="b_tit_size" class="form-control">
                                <?php for($i=32; $i<=96; $i+=4): ?>
                                    <option value="<?= $i ?>" <?= ($config['banner_titulo_size'] ?? 64) == $i ? 'selected' : '' ?>><?= $i ?>px</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label>Título Banner (EN)</label>
                            <input type="text" name="b_tit_en" value="<?= htmlspecialchars($config['banner_titulo_en'] ?? '') ?>" class="form-control">
                        </div>
                        <div>
                            <label>Subtítulo Banner (ES)</label>
                            <input type="text" name="b_sub" value="<?= htmlspecialchars($config['banner_subtitulo'] ?? '') ?>" class="form-control">
                            <label>Tamaño Subtítulo (px)</label>
                            <select name="b_sub_size" class="form-control">
                                <?php for($i=16; $i<=48; $i+=4): ?>
                                    <option value="<?= $i ?>" <?= ($config['banner_subtitulo_size'] ?? 24) == $i ? 'selected' : '' ?>><?= $i ?>px</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label>Subtítulo Banner (EN)</label>
                            <input type="text" name="b_sub_en" value="<?= htmlspecialchars($config['banner_subtitulo_en'] ?? '') ?>" class="form-control">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label style="font-weight: bold; color: var(--admin-blue);">Imagen de Banner</label>
                            <input type="file" name="banner_imagen" class="form-control" accept="image/*" onchange="previewImage(this, 'p_banner')">
                            <input type="hidden" name="current_banner_img" value="<?= htmlspecialchars($config['banner_imagen'] ?? 'hero_tours.jpg') ?>">
                            <?php if (!empty($config['banner_imagen'])): ?>
                                <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px;">
                                    <p style="font-size: 10px; color: #666; margin-bottom: 5px;">VISTA PREVIA DEL BANNER ACTUAL:</p>
                                    <img id="p_banner" src="../assets/img/seguridad/<?= htmlspecialchars($config['banner_imagen'] ?? 'hero_tours.jpg') ?>?v=<?= time() ?>"
                                        style="width: 100%; max-height: 150px; object-fit: cover; border-radius: 5px; border: 1px solid #ccc;"
                                        onerror="this.src='../assets/img/hero_tours.jpg'">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="grid-column: 1/3; display: flex; gap: 15px; margin-top: 10px;">
                            <div style="flex: 1;">
                                <label>Color Título Banner</label>
                                <input type="color" name="banner_color_titulo" value="<?= htmlspecialchars($config['banner_color_titulo'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px;">
                            </div>
                            <div style="flex: 1;">
                                <label>Color Subtítulo Banner</label>
                                <input type="color" name="banner_color_subtitulo" value="<?= htmlspecialchars($config['banner_color_subtitulo'] ?? '#f1f1f1') ?>" class="form-control" style="height: 40px;">
                            </div>
                            <div style="flex: 1;">
                                <label>Color Título Intro</label>
                                <input type="color" name="intro_color_titulo" value="<?= htmlspecialchars($config['intro_color_titulo'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px;">
                            </div>
                            <div style="flex: 1;">
                                <label>Color Texto Intro</label>
                                <input type="color" name="intro_color_texto" value="<?= htmlspecialchars($config['intro_color_texto'] ?? '#555555') ?>" class="form-control" style="height: 40px;">
                            </div>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label style="font-weight: bold; color: var(--admin-blue);">Texto Introducción - ESPAÑOL</label>
                            <textarea name="i_txt" rows="6" class="form-control"
                                placeholder="# Título en Verde&#10;- Ítem con check&#10;- Otro ítem con check&#10;Texto normal con **negrita**..."><?= htmlspecialchars($config['intro_texto'] ?? '') ?></textarea>

                            <label style="font-weight: bold; color: var(--admin-blue); margin-top: 10px;">Texto Introducción - INGLÉS</label>
                            <textarea name="i_txt_en" rows="6" class="form-control"
                                placeholder="# Green Title&#10;- Item with checkmark&#10;- Another item with checkmark&#10;Normal text with **bold**..."><?= htmlspecialchars($config['intro_texto_en'] ?? '') ?></textarea>
                            
                            <label style="font-weight: bold; color: var(--admin-blue); margin-top: 10px;">Tamaño Título Introducción (px)</label>
                            <select name="i_tit_size" class="form-control">
                                <?php for($i=24; $i<=72; $i+=4): ?>
                                    <option value="<?= $i ?>" <?= ($config['intro_titulo_size'] ?? 40) == $i ? 'selected' : '' ?>><?= $i ?>px</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-admin">
                    <h3>2. Video de YouTube</h3>
                    <label>URL del Video (YouTube)</label>
                    <input type="text" name="video_url" value="<?= htmlspecialchars($config['video_url'] ?? '') ?>" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                    <small style="color: #666;">Ejemplo: https://www.youtube.com/watch?v=XXXXX o https://youtu.be/XXXXX</small>

                    <label style="margin-top: 15px;">Título de Sección (ES)</label>
                    <input type="text" name="sec2_tit" value="<?= htmlspecialchars($config['seccion2_titulo']) ?>" class="form-control">
                    
                    <label>Título de Sección (EN)</label>
                    <input type="text" name="sec2_tit_en" value="<?= htmlspecialchars($config['seccion2_titulo_en'] ?? '') ?>" class="form-control">
                    <div style="margin-top: 10px;">
                        <label>Color Título Video</label>
                        <input type="color" name="video_color_titulo" value="<?= htmlspecialchars($config['video_color_titulo'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px; width: 100px;">
                    </div>
                </div>

                <div class="card-admin">
                    <h3>Título General de Tarjetas de Seguridad</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título General de Tarjetas (ES)</label>
                            <input type="text" name="titulo_general_card" value="<?= htmlspecialchars(isset($config['titulo_general_card']) ? $config['titulo_general_card'] : '') ?>" class="form-control" placeholder="Ej: Medidas de Protección">
                        </div>
                        <div>
                            <label>Título General de Tarjetas (EN)</label>
                            <input type="text" name="titulo_general_card_en" value="<?= htmlspecialchars(isset($config['titulo_general_card_en']) ? $config['titulo_general_card_en'] : '') ?>" class="form-control" placeholder="Ej: Protection Measures">
                        </div>
                    </div>
                </div>

                <div class="card-admin">
                    <h3>2.1. Barra Lateral (Especialista & Testimonio)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Especialista (ES)</label>
                            <textarea name="a_txt" rows="2" class="form-control"><?= htmlspecialchars($config['aside_texto'] ?? '') ?></textarea>
                            <label>Botón (ES)</label>
                            <input type="text" name="a_btn" value="<?= htmlspecialchars($config['aside_btn'] ?? '') ?>" class="form-control">
                            <label>Testimonio Tit (ES)</label>
                            <input type="text" name="test_tit" value="<?= htmlspecialchars($config['aside_test_tit'] ?? '') ?>" class="form-control">
                            <label>Testimonio Texto (ES)</label>
                            <textarea name="test_txt" rows="2" class="form-control"><?= htmlspecialchars($config['aside_test_txt'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label>Especialista (EN)</label>
                            <textarea name="a_txt_en" rows="2" class="form-control"><?= htmlspecialchars($config['aside_texto_en'] ?? '') ?></textarea>
                            <label>Botón (EN)</label>
                            <input type="text" name="a_btn_en" value="<?= htmlspecialchars($config['aside_btn_en'] ?? '') ?>" class="form-control">
                            <label>Testimonio Tit (EN)</label>
                            <input type="text" name="test_tit_en" value="<?= htmlspecialchars($config['aside_test_tit_en'] ?? '') ?>" class="form-control">
                            <label>Testimonio Texto (EN)</label>
                            <textarea name="test_txt_en" rows="2" class="form-control"><?= htmlspecialchars($config['aside_test_txt_en'] ?? '') ?></textarea>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Fecha del Testimonio (ES / EN)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="test_fecha" value="<?= htmlspecialchars($config['aside_test_fecha'] ?? '') ?>" class="form-control" placeholder="ES">
                                <input type="text" name="test_fecha_en" value="<?= htmlspecialchars($config['aside_test_fecha_en'] ?? '') ?>" class="form-control" placeholder="EN">
                            </div>
                        </div>

                        <div>
                            <label>Foto Especialista</label>
                            <input type="file" name="aside_foto" class="form-control" onchange="previewImage(this, 'p_aside')">
                            <input type="hidden" name="current_aside_img" value="<?= htmlspecialchars($config['aside_imagen'] ?? '') ?>">
                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                <img id="p_aside" src="../assets/img/seguridad/<?= htmlspecialchars($config['aside_imagen'] ?? 'no-image.jpg') ?>?v=<?= time() ?>"
                                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--admin-blue);"
                                    onerror="this.src='../assets/img/no-image.jpg'">
                                <p style="font-size: 10px; color: #666; margin-top: 5px;">MINIATURA ACTUAL</p>
                            </div>
                        </div>

                        <div>
                            <label>Foto Testimonio</label>
                            <input type="file" name="aside_test_foto" class="form-control" onchange="previewImage(this, 'p_test')">
                            <input type="hidden" name="current_aside_test_img" value="<?= htmlspecialchars($config['aside_test_img'] ?? '') ?>">
                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                <img id="p_test" src="../assets/img/seguridad/<?= htmlspecialchars($config['aside_test_img'] ?? 'no-image.jpg') ?>?v=<?= time() ?>"
                                    style="width: 120px; height: 70px; border-radius: 6px; object-fit: cover; border: 2px solid var(--admin-blue);"
                                    onerror="this.src='../assets/img/no-image.jpg'">
                                <p style="font-size: 10px; color: #666; margin-top: 5px;">MINIATURA ACTUAL</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-admin">
                    <h3>3. Sección Final (CTA)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título CTA (ES)</label>
                            <input type="text" name="cta_tit" value="<?= htmlspecialchars($config['cta_titulo']) ?>" class="form-control">
                            <label>Botón CTA (ES)</label>
                            <input type="text" name="cta_btn" value="<?= htmlspecialchars($config['cta_btn']) ?>" class="form-control">
                        </div>
                        <div>
                            <label>Título CTA (EN)</label>
                            <input type="text" name="cta_tit_en" value="<?= htmlspecialchars($config['cta_titulo_en']) ?>" class="form-control">
                            <label>Botón CTA (EN)</label>
                            <input type="text" name="cta_btn_en" value="<?= htmlspecialchars($config['cta_btn_en']) ?>" class="form-control">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Texto CTA (ES)</label>
                            <textarea name="cta_txt" rows="2" class="form-control"><?= htmlspecialchars($config['cta_texto']) ?></textarea>
                            <label>Texto CTA (EN)</label>
                            <textarea name="cta_txt_en" rows="2" class="form-control"><?= htmlspecialchars($config['cta_texto_en']) ?></textarea>
                            
                            <label>Fondo CTA (Imagen de Machu Picchu)</label>
                            <input type="file" name="cta_foto" class="form-control" accept="image/*">
                            <input type="hidden" name="current_cta_img" value="<?= htmlspecialchars($config['cta_imagen']) ?>">
                            <?php if (!empty($config['cta_imagen'])): ?>
                                <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px;">
                                    <p style="font-size: 10px; color: #666; margin-bottom: 5px;">IMAGEN ACTUAL:</p>
                                    <img src="../assets/img/<?= htmlspecialchars($config['cta_imagen']) ?>" style="width: 200px; height: 100px; object-fit: cover; border-radius: 5px; border: 1px solid #ccc;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top: 20px;"><i class="fas fa-save"></i> GUARDAR CONTENIDO PRINCIPAL</button>
                </div>

                <div class="card-admin">
                    <h3>Configuración de Colores - TARJETAS</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div>
                            <label>Color Título Tarjetas</label>
                            <input type="color" name="cards_color_titulo" value="<?= htmlspecialchars($config['cards_color_titulo'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Fondo Tarjetas</label>
                            <input type="color" name="cards_color_fondo" value="<?= htmlspecialchars($config['cards_color_fondo'] ?? '#ffffff') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Check Tarjetas</label>
                            <input type="color" name="cards_color_check" value="<?= htmlspecialchars($config['cards_color_check'] ?? '#c6d544') ?>" class="form-control" style="height: 40px;">
                        </div>
                    </div>
                </div>

                <div class="card-admin">
                    <h3>Configuración de Colores - ASIDE</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div>
                            <label>Color Título Aside</label>
                            <input type="color" name="aside_color_titulo" value="<?= htmlspecialchars($config['aside_color_titulo'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Botón Aside</label>
                            <input type="color" name="aside_color_btn" value="<?= htmlspecialchars($config['aside_color_btn'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Botón Hover</label>
                            <input type="color" name="aside_color_btn_hover" value="<?= htmlspecialchars($config['aside_color_btn_hover'] ?? '#c6d544') ?>" class="form-control" style="height: 40px;">
                        </div>
                    </div>
                </div>

                <div class="card-admin">
                    <h3>Configuración de Colores - CTA</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div>
                            <label>Color Título CTA</label>
                            <input type="color" name="cta_color_titulo" value="<?= htmlspecialchars($config['cta_color_titulo'] ?? '#ffffff') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Texto CTA</label>
                            <input type="color" name="cta_color_texto" value="<?= htmlspecialchars($config['cta_color_texto'] ?? '#ffffff') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Botón CTA</label>
                            <input type="color" name="cta_color_btn" value="<?= htmlspecialchars($config['cta_color_btn'] ?? '#0f9b9e') ?>" class="form-control" style="height: 40px;">
                        </div>
                        <div>
                            <label>Color Botón Hover</label>
                            <input type="color" name="cta_color_btn_hover" value="<?= htmlspecialchars($config['cta_color_btn_hover'] ?? '#c6d544') ?>" class="form-control" style="height: 40px;">
                        </div>
                    </div>
                </div>
            </form>

            <div class="card-admin">
                <h3>Tarjetas de Seguridad (Cards)</h3>
                <div class="help-text">
                    <strong>Guía de formato:</strong><br>
                    • <code># Título Principal</code> = Título verde grande<br>
                    • <code>- Punto con check</code> = Lista con check verde<br>
                    <em style="color:#666;">Para separar párrafos usa Enter (salto de línea)</em>
                </div>
                
                <form method="POST" enctype="multipart/form-data" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Imagen</label>
                            <input type="file" name="card_img" required class="form-control" accept="image/*">
                        </div>
                        <div>
                            <label>Orden</label>
                            <input type="number" name="card_orden" class="form-control" value="0" min="0">
                        </div>
                        <div>
                            <label>Título (ES) - Usa # para Título</label>
                            <input type="text" name="card_tit" required class="form-control" placeholder="# Escribe el título aquí">
                        </div>
                        <div>
                            <label>Título (EN) - Use # for Title</label>
                            <input type="text" name="card_tit_en" required class="form-control" placeholder="# Write title here">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Descripción (ES) - Usa - para listas con check</label>
                            <textarea name="card_desc" rows="4" class="form-control" required placeholder="- Primer punto&#10;- Segundo punto&#10;- Tercer punto"></textarea>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Description (EN) - Use - for check lists</label>
                            <textarea name="card_desc_en" rows="4" class="form-control" required placeholder="- First item&#10;- Second item&#10;- Third item"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_card" class="btn-admin" style="margin-top: 15px; width: auto; padding: 12px 30px;">
                        <i class="fas fa-plus"></i> AGREGAR CARD
                    </button>
                </form>

                <div class="cards-grid-admin">
                    <?php foreach ($cards as $card): ?>
                        <div class="card-item-admin">
                            <img src="../assets/img/seguridad/<?= htmlspecialchars($card['imagen']) ?>" onerror="this.src='../assets/img/no-image.jpg'">
                            <div class="card-item-admin-body">
                                <h4><?= htmlspecialchars($card['titulo']) ?></h4>
                                <p><?= htmlspecialchars($card['descripcion']) ?></p>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" class="btn-edit-card" data-id="<?= $card['id'] ?>" data-titulo="<?= htmlspecialchars($card['titulo']) ?>" data-titulo_en="<?= htmlspecialchars($card['titulo_en']) ?>" data-desc="<?= htmlspecialchars($card['descripcion']) ?>" data-desc_en="<?= htmlspecialchars($card['descripcion_en']) ?>" data-imagen="<?= htmlspecialchars($card['imagen']) ?>" style="width: auto; padding: 8px 15px; font-size: 0.85rem; background: #15305D; border: none; border-radius: 5px; color: white; cursor: pointer;"><i class="fas fa-edit"></i> Editar</button>
                                    <button onclick="confDelCard(<?= $card['id'] ?>)" class="btn-del"><i class="fas fa-trash"></i> Eliminar</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="editCardModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
                    <div style="background: #fff; max-width: 600px; margin: auto; padding: 30px; border-radius: 15px; position: relative;">
                        <button type="button" onclick="closeEditModal()" style="position: absolute; top: 10px; right: 10px; border: none; background: transparent; font-size: 24px; cursor: pointer;">&times;</button>
                        <h3 style="color: var(--admin-blue); margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar Tarjeta</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="edit_card_id" id="edit_card_id">
                            <input type="hidden" name="current_card_img" id="current_card_img">
                            <div style="background: #fff3cd; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                                <strong style="color: #856404;">Guía de formato:</strong><br>
                                <small style="color: #856404;">• <code># Título</code> = Título verde grande</small><br>
                                <small style="color: #856404;">• <code>- Punto</code> = Lista con check verde</small>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label>Título (ES) - Usa # para Título</label>
                                    <input type="text" name="edit_card_tit" id="edit_card_tit" class="form-control" placeholder="# Escribe el título aquí">
                                </div>
                                <div>
                                    <label>Título (EN) - Use # for Title</label>
                                    <input type="text" name="edit_card_tit_en" id="edit_card_tit_en" class="form-control" placeholder="# Write title here">
                                </div>
                                <div style="grid-column: 1/3;">
                                    <label>Descripción (ES) - Usa - para listas con check</label>
                                    <textarea name="edit_card_desc" id="edit_card_desc" rows="4" class="form-control" placeholder="- Primer punto&#10;- Segundo punto&#10;- Tercer punto"></textarea>
                                </div>
                                <div style="grid-column: 1/3;">
                                    <label>Description (EN) - Use - for check lists</label>
                                    <textarea name="edit_card_desc_en" id="edit_card_desc_en" rows="4" class="form-control" placeholder="- First item&#10;- Second item&#10;- Third item"></textarea>
                                </div>
                                <div style="grid-column: 1/3;">
                                    <label>Imagen (opcional - deja vacío para mantener la actual)</label>
                                    <input type="file" name="edit_card_img" class="form-control" accept="image/*" onchange="previewImage(this, 'edit_card_img_preview')">
                                    <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                        <img id="edit_card_img_preview" src="../assets/img/no-image.jpg"
                                            style="width: 200px; height: 120px; object-fit: cover; border-radius: 5px; border: 1px solid #ccc;">
                                        <p style="font-size: 10px; color: #666; margin-top: 5px;">IMAGEN ACTUAL</p>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" name="update_card" class="btn-admin" style="width: auto; flex: 1;"><i class="fas fa-save"></i> GUARDAR CAMBIOS</button>
                                <button type="button" onclick="closeEditModal()" class="btn-admin" style="width: auto; background: #666; flex: 1;">CANCELAR</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confDelCard(id) {
            Swal.fire({
                title: '¿Eliminar tarjeta?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#15305D',
                confirmButtonText: 'Sí, borrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'admin_seguridad.php?del_card=' + id;
                }
            });
        }
    </script>
    <script>
        // Event listeners setup
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-edit-card').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var id = this.getAttribute('data-id');
                    var tit = this.getAttribute('data-titulo');
                    var titEn = this.getAttribute('data-titulo_en');
                    var desc = this.getAttribute('data-desc');
                    var descEn = this.getAttribute('data-desc_en');
                    var img = this.getAttribute('data-imagen');
                    editCard(id, tit, titEn, desc, descEn, img);
                });
            });
        });
        
        function editCard(id, tit, titEn, desc, descEn, img) {
            document.getElementById('edit_card_id').value = id;
            document.getElementById('edit_card_tit').value = tit;
            document.getElementById('edit_card_tit_en').value = titEn;
            document.getElementById('edit_card_desc').value = desc;
            document.getElementById('edit_card_desc_en').value = descEn;
            document.getElementById('current_card_img').value = img ? img : '';
            var preview = document.getElementById('edit_card_img_preview');
            if (img) {
                preview.src = '../assets/img/seguridad/' + img;
            } else {
                preview.src = '../assets/img/no-image.jpg';
            }
            document.getElementById('editCardModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editCardModal').style.display = 'none';
        }
    </script>
    <?php if($mensaje === 'updated'): ?>
    <script>
        Swal.fire({ icon: 'success', title: '¡Guardado!', confirmButtonColor: '#15305D' });
    </script>
    <?php elseif($mensaje === 'card_added'): ?>
    <script>
        Swal.fire({ icon: 'success', title: '¡Card agregado!', confirmButtonColor: '#15305D' });
    </script>
    <?php elseif($mensaje === 'deleted'): ?>
    <script>
        Swal.fire({ icon: 'success', title: '¡Eliminado!', confirmButtonColor: '#15305D' });
    </script>
    <?php elseif($mensaje === 'card_updated'): ?>
    <script>
        Swal.fire({ icon: 'success', title: '¡Tarjeta actualizada!', confirmButtonColor: '#15305D' });
    </script>
    <?php endif; ?>
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