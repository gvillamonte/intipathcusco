<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('equipo');

require_once '../config/database.php';
$db = (new Database())->getConnection();

// --- 1. PROCESAR ACTUALIZACIÓN DE CONTENIDO ---
if (isset($_POST['update_page'])) {

    // Manejo de la Foto del Especialista
    $aside_img = $_POST['current_aside_img'];
    if (isset($_FILES['aside_foto']) && $_FILES['aside_foto']['error'] == 0) {
        $aside_img = "specialist_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_foto']['tmp_name'], "../assets/img/equipo/" . $aside_img);
    }

    // Manejo de la Foto de la Experiencia
    $aside_test_img = $_POST['current_aside_test_img'];
    if (isset($_FILES['aside_test_foto']) && $_FILES['aside_test_foto']['error'] == 0) {
        $aside_test_img = "testimonio_" . time() . ".jpg";
        move_uploaded_file($_FILES['aside_test_foto']['tmp_name'], "../assets/img/equipo/" . $aside_test_img);
    }

    // Manejo de la Foto del Banner Principal (Intro)
    $intro_img = $_POST['current_intro_img'];
    if (isset($_FILES['intro_imagen']) && $_FILES['intro_imagen']['error'] == 0) {
        $intro_img = "banner_intro_" . time() . ".jpg";
        move_uploaded_file($_FILES['intro_imagen']['tmp_name'], "../assets/img/equipo/" . $intro_img);
    }

    // Manejo de la Foto de Fondo del CTA
    $cta_img = $_POST['current_cta_img'];
    if (isset($_FILES['cta_foto']) && $_FILES['cta_foto']['error'] == 0) {
        $cta_img = "cta_bg_" . time() . ".jpg";
        move_uploaded_file($_FILES['cta_foto']['tmp_name'], "../assets/img/equipo/" . $cta_img);
    }

    // Ejecutar Update COMPLETO
    $stmt = $db->prepare("UPDATE equipo_guias SET 
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
        $_POST['b_tit'], $_POST['b_tit_en'], $_POST['b_sub'], $_POST['b_sub_en'],
        $_POST['i_txt'], $_POST['i_txt_en'], $intro_img,
        $_POST['a_txt'], $_POST['a_txt_en'], $_POST['a_btn'], $_POST['a_btn_en'], $aside_img,
        $_POST['test_tit'], $_POST['test_tit_en'], $_POST['test_txt'], $_POST['test_txt_en'],
        $aside_test_img, $_POST['test_fecha'], $_POST['test_fecha_en'] ?? '', // Evita error si no existe en POST
        $_POST['cta_tit'], $_POST['cta_tit_en'],
        $_POST['cta_txt'], $_POST['cta_txt_en'],
        $_POST['cta_btn'], $_POST['cta_btn_en'],
        $cta_img
    ]);

    header("Location: admin_equipo-guia.php?res=updated");
    exit;
}

// --- 2. SUBIR A GALERÍA ---
if (isset($_POST['add_galeria'])) {
    if (isset($_FILES['img_gal']) && $_FILES['img_gal']['error'] == 0) {
        $path = "../assets/img/equipo/galeria/";
        if (!is_dir($path)) { mkdir($path, 0777, true); }
        $nom_gal = "gal_" . time() . "_" . $_FILES['img_gal']['name'];
        if (move_uploaded_file($_FILES['img_gal']['tmp_name'], $path . $nom_gal)) {
            $db->prepare("INSERT INTO equipo_galeria (imagen) VALUES (?)")->execute([$nom_gal]);
            header("Location: admin_equipo-guia.php?res=gallery_added");
            exit;
        }
    }
}

// --- 3. ELIMINAR DE GALERÍA ---
if (isset($_GET['del_gal'])) {
    $db->prepare("DELETE FROM equipo_galeria WHERE id = ?")->execute([$_GET['del_gal']]);
    header("Location: admin_equipo-guia.php?res=deleted");
    exit;
}

$config = $db->query("SELECT * FROM equipo_guias WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$galeria = $db->query("SELECT * FROM equipo_galeria ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Equipo Guía | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #15305D; --gris: #f4f7f6; }
        body { background-color: var(--gris); font-family: 'Segoe UI', sans-serif; }
        .admin-title-inti { color: var(--esmeralda); font-weight: 800; border-bottom: 4px solid var(--limon); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; }
        .card-inti { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05); margin-bottom: 30px; border: 1px solid #eee; }
        .card-inti h3 { border-left: 5px solid var(--esmeralda); padding-left: 15px; color: var(--dark); margin-bottom: 20px; }
        .btn-inti { background: var(--esmeralda); color: #fff; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; display: inline-block; }
        .btn-inti:hover { background: var(--dark); transform: translateY(-2px); }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .img-thumb { width: 120px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--limon); }
        .img-thumb-circle { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--limon); }
        .galeria-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        .gal-item { position: relative; border-radius: 12px; overflow: hidden; height: 130px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .btn-del-gal { position: absolute; top: 8px; right: 8px; background: rgba(255, 0, 0, 0.8); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <h1 class="admin-title-inti"><i class="fas fa-users-cog"></i> Configuración de Página Equipo</h1>

            <form id="formUpdate" method="POST" enctype="multipart/form-data">
                
                <div class="card-inti">
                    <h3>1. Banner Principal e Introducción</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>Título Banner (ES)</label><input type="text" name="b_tit" value="<?= $config['banner_titulo'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Título Banner (EN)</label><input type="text" name="b_tit_en" value="<?= $config['banner_titulo_en'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Subtítulo Banner (ES)</label><input type="text" name="b_sub" value="<?= $config['banner_subtitulo'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Subtítulo Banner (EN)</label><input type="text" name="b_sub_en" value="<?= $config['banner_subtitulo_en'] ?>" class="form-control"></div>
                        <div class="form-group" style="grid-column: 1/3;"><label>Texto Introducción (ES)</label><textarea name="i_txt" rows="3" class="form-control"><?= $config['intro_texto'] ?></textarea></div>
                        <div class="form-group" style="grid-column: 1/3;"><label>Texto Introducción (EN)</label><textarea name="i_txt_en" rows="3" class="form-control"><?= $config['intro_texto_en'] ?></textarea></div>
                        <div class="form-group" style="grid-column: 1/3;">
                            <label>Imagen Banner Principal</label>
                            <input type="file" name="intro_imagen" class="form-control">
                            <input type="hidden" name="current_intro_img" value="<?= $config['intro_imagen'] ?>">
                            <div class="thumb-container">
                                <img src="../assets/img/equipo/<?= $config['intro_imagen'] ?>" class="img-thumb" alt="Banner">
                                <span><i class="fas fa-arrow-left"></i> Actual</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-inti">
                    <h3>2. Configuración del Aside (Especialista y Testimonio)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>Texto Especialista (ES)</label><textarea name="a_txt" rows="3" class="form-control"><?= $config['aside_texto'] ?></textarea></div>
                        <div class="form-group"><label>Texto Especialista (EN)</label><textarea name="a_txt_en" rows="3" class="form-control"><?= $config['aside_texto_en'] ?></textarea></div>
                        <div class="form-group"><label>Texto del Botón (ES)</label><input type="text" name="a_btn" value="<?= $config['aside_btn'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Texto del Botón (EN)</label><input type="text" name="a_btn_en" value="<?= $config['aside_btn_en'] ?>" class="form-control"></div>
                        <div class="form-group" style="grid-column: 1/3;">
                            <label>Foto Especialista</label>
                            <input type="file" name="aside_foto" class="form-control">
                            <input type="hidden" name="current_aside_img" value="<?= $config['aside_imagen'] ?>">
                            <div class="thumb-container"><img src="../assets/img/equipo/<?= $config['aside_imagen'] ?>" class="img-thumb-circle"></div>
                        </div>
                    </div>
                    <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>Título Testimonio (ES)</label><input type="text" name="test_tit" value="<?= $config['aside_test_tit'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Título Testimonio (EN)</label><input type="text" name="test_tit_en" value="<?= $config['aside_test_tit_en'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Texto Testimonio (ES)</label><textarea name="test_txt" rows="3" class="form-control"><?= $config['aside_test_txt'] ?></textarea></div>
                        <div class="form-group"><label>Texto Testimonio (EN)</label><textarea name="test_txt_en" rows="3" class="form-control"><?= $config['aside_test_txt_en'] ?></textarea></div>
                        <div class="form-group"><label>Fecha (ES)</label><input type="text" name="test_fecha" value="<?= $config['aside_test_fecha'] ?>" class="form-control"></div>
                        <div class="form-group"><label>Date (EN)</label><input type="text" name="test_fecha_en" value="<?= $config['aside_test_fecha_en'] ?>" class="form-control"></div>
                        <div class="form-group" style="grid-column: 1/3;">
                            <label>Foto Experiencia</label>
                            <input type="file" name="aside_test_foto" class="form-control">
                            <input type="hidden" name="current_aside_test_img" value="<?= $config['aside_test_img'] ?>">
                            <div class="thumb-container"><img src="../assets/img/equipo/<?= $config['aside_test_img'] ?>" class="img-thumb"></div>
                        </div>
                    </div>
                </div>

                <div class="card-inti">
                    <h3>3. Sección Final de Planificación (CTA)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Título CTA (ES)</label><input type="text" name="cta_tit" value="<?= $config['cta_titulo'] ?>" class="form-control">
                            <label>Texto CTA (ES)</label><textarea name="cta_txt" rows="3" class="form-control"><?= $config['cta_texto'] ?></textarea>
                            <label>Botón CTA (ES)</label><input type="text" name="cta_btn" value="<?= $config['cta_btn'] ?>" class="form-control">
                        </div>
                        <div>
                            <label>Título CTA (EN)</label><input type="text" name="cta_tit_en" value="<?= $config['cta_titulo_en'] ?>" class="form-control">
                            <label>Texto CTA (EN)</label><textarea name="cta_txt_en" rows="3" class="form-control"><?= $config['cta_texto_en'] ?></textarea>
                            <label>Botón CTA (EN)</label><input type="text" name="cta_btn_en" value="<?= $config['cta_btn_en'] ?>" class="form-control">
                        </div>
                        <div style="grid-column: 1/3;">
                            <label>Fondo Banner CTA</label>
                            <input type="file" name="cta_foto" class="form-control">
                            <input type="hidden" name="current_cta_img" value="<?= $config['cta_imagen'] ?>">
                            <div class="thumb-container"><img src="../assets/img/equipo/<?= $config['cta_imagen'] ?>" class="img-thumb" style="width:200px"></div>
                        </div>
                    </div>
                    <button type="submit" name="update_page" class="btn-inti" style="margin-top: 30px; width: 100%; padding: 15px; font-size: 1.1rem;">
                        <i class="fas fa-save"></i> ACTUALIZAR TODO EL CONTENIDO
                    </button>
                </div>
            </form>

            <div class="card-inti">
                <h3>4. Galería de Imágenes</h3>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Seleccionar imagen para la galería:</label>
                        <input type="file" name="img_gal" required class="form-control">
                    </div>
                    <button type="submit" name="add_galeria" class="btn-inti"><i class="fas fa-plus"></i> AÑADIR A GALERÍA</button>
                </form>
                <div class="galeria-grid">
                    <?php foreach ($galeria as $img): ?>
                        <div class="gal-item">
                            <img src="../assets/img/equipo/galeria/<?= $img['imagen'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" class="btn-del-gal" onclick="confirmarEliminar(<?= $img['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const res = urlParams.get('res');
        if (res === 'updated') { Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Cambios actualizados.', confirmButtonColor: '#0f9b9e' });
        } else if (res === 'gallery_added') { Swal.fire({ icon: 'success', title: '¡Añadida!', text: 'Galería actualizada.', confirmButtonColor: '#0f9b9e' });
        } else if (res === 'deleted') { Swal.fire({ icon: 'success', title: '¡Eliminado!', text: 'Imagen borrada.', confirmButtonColor: '#0f9b9e' }); }

        function confirmarEliminar(id) {
            Swal.fire({
                title: '¿Eliminar imagen?',
                text: "No se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => { if (result.isConfirmed) { window.location.href = `admin_equipo-guia.php?del_gal=${id}`; } })
        }
    </script>
</body>
</html>