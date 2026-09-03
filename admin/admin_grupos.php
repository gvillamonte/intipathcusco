<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('grupos');

require_once '../config/database.php';
$db = (new Database())->getConnection();

// --- 1. PROCESAR ACTUALIZACIÓN DE CONTENIDO ---
if (isset($_POST['update_page'])) {

    // Manejo de la Foto del Especialista (aside_imagen)
    $aside_img = $_POST['current_aside_img'];
    if (isset($_FILES['aside_foto']) && $_FILES['aside_foto']['error'] == 0) {
        $aside_img = "grp_spec_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_foto']['tmp_name'], "../assets/img/grupos/" . $aside_img);
    }

    // Manejo de la Foto de la Experiencia (aside_test_img)
    $aside_test_img = $_POST['current_aside_test_img'];
    if (isset($_FILES['aside_test_foto']) && $_FILES['aside_test_foto']['error'] == 0) {
        $aside_test_img = "grp_testimonio_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_test_foto']['tmp_name'], "../assets/img/grupos/" . $aside_test_img);
    }

    // Manejo de la Foto del Banner Principal (intro_imagen)
    $intro_img = $_POST['current_intro_img'];
    if (isset($_FILES['intro_imagen']) && $_FILES['intro_imagen']['error'] == 0) {
        $intro_img = "grp_banner_" . time() . ".jpg";
        move_uploaded_file($_FILES['intro_imagen']['tmp_name'], "../assets/img/grupos/" . $intro_img);
    }

    // Manejo de la Foto de Fondo del CTA (cta_imagen)
    $cta_img = $_POST['current_cta_img'];
    if (isset($_FILES['cta_foto']) && $_FILES['cta_foto']['error'] == 0) {
        $cta_img = "grp_cta_bg_" . time() . ".jpg";
        move_uploaded_file($_FILES['cta_foto']['tmp_name'], "../assets/img/grupos/" . $cta_img);
    }

    // Ejecutar Update COMPLETO usando exactamente tus columnas
    $stmt = $db->prepare("UPDATE grupos_viajes SET 
        banner_titulo = ?, banner_titulo_en = ?, 
        banner_subtitulo = ?, banner_subtitulo_en = ?,
        intro_texto = ?, intro_texto_en = ?, intro_imagen = ?,
        aside_texto = ?, aside_texto_en = ?, 
        aside_btn = ?, aside_btn_en = ?, 
        aside_imagen = ?,
        aside_test_tit = ?, aside_test_tit_en = ?,
        aside_test_txt = ?, aside_test_txt_en = ?,
        aside_test_img = ?, aside_test_fecha = ?, aside_test_fecha_en = ?,
        cta_titulo = ?, cta_titulo_en = ?,
        cta_texto = ?, cta_texto_en = ?,
        cta_btn = ?, cta_btn_en = ?,
        cta_imagen = ?
        WHERE id = 1");

    $stmt->execute([
        $_POST['b_tit'],
        $_POST['b_tit_en'],
        $_POST['b_sub'],
        $_POST['b_sub_en'],
        $_POST['i_txt'],
        $_POST['i_txt_en'],
        $intro_img,
        $_POST['a_txt'],
        $_POST['a_txt_en'],
        $_POST['a_btn'],
        $_POST['a_btn_en'],
        $aside_img,
        $_POST['test_tit'],
        $_POST['test_tit_en'],
        $_POST['test_txt'],
        $_POST['test_txt_en'],
        $aside_test_img,
        $_POST['test_fecha'],
        $_POST['test_fecha_en'] ?? '',
        $_POST['cta_tit'],
        $_POST['cta_tit_en'],
        $_POST['cta_txt'],
        $_POST['cta_txt_en'],
        $_POST['cta_btn'],
        $_POST['cta_btn_en'],
        $cta_img
    ]);

    header("Location: admin_grupos.php?res=updated");
    exit;
}

// --- 2. SUBIR A GALERÍA (grupos_galeria) ---
if (isset($_POST['add_galeria'])) {
    if (isset($_FILES['img_gal']) && $_FILES['img_gal']['error'] == 0) {
        $path = "../assets/img/grupos/galeria/";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $nom_gal = "grp_gal_" . time() . "_" . $_FILES['img_gal']['name'];
        if (move_uploaded_file($_FILES['img_gal']['tmp_name'], $path . $nom_gal)) {
            $db->prepare("INSERT INTO grupos_galeria (imagen) VALUES (?)")->execute([$nom_gal]);
            header("Location: admin_grupos.php?res=gallery_added");
            exit;
        }
    }
}

// --- 3. ELIMINAR DE GALERÍA ---
if (isset($_GET['del_gal'])) {
    $db->prepare("DELETE FROM grupos_galeria WHERE id = ?")->execute([$_GET['del_gal']]);
    header("Location: admin_grupos.php?res=deleted");
    exit;
}

$config = $db->query("SELECT * FROM grupos_viajes WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$galeria = $db->query("SELECT * FROM grupos_galeria ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Grupos | IntiPath</title>
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
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .gal-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            height: 110px;
            border: 1px solid #ddd;
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
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding: 30px;">
            <h1 class="admin-title-inti"><i class="fas fa-users"></i> Configuración de Salidas Grupales</h1>

            <form method="POST" enctype="multipart/form-data">
                <div class="card-admin">
                    <h3>1. Cabecera de Página (Banner) e Introducción</h3>
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
                            <label style="font-weight: bold; color: var(--admin-blue);">Texto Introducción - ESPAÑOL</label>
                            <textarea name="i_txt" rows="6" class="form-control"
                                placeholder="# Título en Verde&#10;- Ítem con check&#10;- Otro ítem con check&#10;Texto normal con **negrita**..."><?= $config['intro_texto'] ?></textarea>

                            <label style="font-weight: bold; color: var(--admin-blue); margin-top: 10px;">Texto Introducción - INGLÉS</label>
                            <textarea name="i_txt_en" rows="6" class="form-control"
                                placeholder="# Green Title&#10;- Item with checkmark&#10;- Another item with checkmark&#10;Normal text with **bold**..."><?= $config['intro_texto_en'] ?></textarea>

                            <label style="margin-top: 15px; display: block; font-weight: bold;">Imagen Banner Principal</label>
                            <input type="file" name="intro_imagen" class="form-control" onchange="previewImage(this, 'p_banner')">
                            <input type="hidden" name="current_intro_img" value="<?= $config['intro_imagen'] ?>">

                            <?php if ($config['intro_imagen']): ?>
                                <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px;">
                                    <p style="font-size: 10px; color: #666; margin-bottom: 5px;">VISTA PREVIA DEL BANNER ACTUAL:</p>
                                    <img id="p_banner" src="../assets/img/grupos/<?= $config['intro_imagen'] ?>?v=<?= time() ?>"
                                        style="width: 100%; max-height: 150px; object-fit: cover; border-radius: 5px; border: 1px solid #ccc;"
                                        onerror="this.src='../assets/img/no-image.jpg'">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card-admin">
                    <h3>2. Barra Lateral (Especialista & Testimonio)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Especialista (ES)</label>
                            <textarea name="a_txt" rows="2" class="form-control"><?= $config['aside_texto'] ?></textarea>
                            <label>Botón (ES)</label>
                            <input type="text" name="a_btn" value="<?= $config['aside_btn'] ?>" class="form-control">
                            <label>Testimonio Tit (ES)</label>
                            <input type="text" name="test_tit" value="<?= $config['aside_test_tit'] ?>" class="form-control">
                            <label>Testimonio Texto (ES)</label>
                            <textarea name="test_txt" rows="2" class="form-control"><?= $config['aside_test_txt'] ?></textarea>
                        </div>
                        <div>
                            <label>Especialista (EN)</label>
                            <textarea name="a_txt_en" rows="2" class="form-control"><?= $config['aside_texto_en'] ?></textarea>
                            <label>Botón (EN)</label>
                            <input type="text" name="a_btn_en" value="<?= $config['aside_btn_en'] ?>" class="form-control">
                            <label>Testimonio Tit (EN)</label>
                            <input type="text" name="test_tit_en" value="<?= $config['aside_test_tit_en'] ?>" class="form-control">
                            <label>Testimonio Texto (EN)</label>
                            <textarea name="test_txt_en" rows="2" class="form-control"><?= $config['aside_test_txt_en'] ?></textarea>
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Fecha del Testimonio (ES / EN)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="test_fecha" value="<?= $config['aside_test_fecha'] ?>" class="form-control" placeholder="ES">
                                <input type="text" name="test_fecha_en" value="<?= $config['aside_test_fecha_en'] ?>" class="form-control" placeholder="EN">
                            </div>
                        </div>

                        <div>
                            <label>Foto Especialista</label>
                            <input type="file" name="aside_foto" class="form-control" onchange="previewImage(this, 'p_aside')">
                            <input type="hidden" name="current_aside_img" value="<?= $config['aside_imagen'] ?>">
                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                <img id="p_aside" src="../assets/img/grupos/<?= $config['aside_imagen'] ?>?v=<?= time() ?>"
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
                                <img id="p_test" src="../assets/img/grupos/<?= $config['aside_test_img'] ?>?v=<?= time() ?>"
                                    style="width: 120px; height: 70px; border-radius: 6px; object-fit: cover; border: 2px solid var(--admin-blue);"
                                    onerror="this.src='../assets/img/no-image.jpg'">
                                <p style="font-size: 10px; color: #666; margin-top: 5px;">MINIATURA ACTUAL</p>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="card-admin">
                    <h3>3. Sección de Planificación (CTA)</h3>
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
                            <label>Fondo Banner CTA</label>
                            <input type="file" name="cta_foto" class="form-control">
                            <input type="hidden" name="current_cta_img" value="<?= $config['cta_imagen'] ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_page" class="btn-admin" style="margin-top: 20px;"><i class="fas fa-save"></i> GUARDAR TODO EL CONTENIDO</button>
                </div>
            </form>

            <div class="card-admin">
                <h3>4. Galería de Fotos de Grupos</h3>
                <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="file" name="img_gal" required class="form-control" style="margin:0">
                    <button type="submit" name="add_galeria" class="btn-admin" style="width: 200px">SUBIR FOTO</button>
                </form>
                <div class="galeria-grid">
                    <?php foreach ($galeria as $img): ?>
                        <div class="gal-item">
                            <img src="../assets/img/grupos/galeria/<?= $img['imagen'] ?>" style="width:100%; height:100%; object-fit:cover;">
                            <button onclick="confDel(<?= $img['id'] ?>)" class="btn-del"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confDel(id) {
            Swal.fire({
                    title: '¿Eliminar foto?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#15305D',
                    confirmButtonText: 'Sí, borrar'
                })
                .then((res) => {
                    if (result.isConfirmed) window.location.href = 'admin_grupos.php?del_gal=' + id;
                });
        }
        const res = new URLSearchParams(window.location.search).get('res');
        if (res) Swal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            confirmButtonColor: '#15305D'
        });
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