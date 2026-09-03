<?php
// admin/edit_sliders.php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('sliders');

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensaje_swal = "";
$edit_mode = false; 
$datos_edit = [
    'id' => '', 
    'titulo' => '', 
    'titulo_en' => '', 
    'subtitulo' => '', 
    'subtitulo_en' => '', 
    'imagen' => ''
];

// --- 2. LÓGICA: CARGAR DATOS PARA EDITAR ---
if (isset($_GET['editar'])) {
    $edit_mode = true;
    $id_edit = $_GET['editar'];
    $stmt = $db->prepare("SELECT * FROM sliders WHERE id = ?");
    $stmt->execute([$id_edit]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) { $datos_edit = array_merge($datos_edit, $res); }
}

// --- 3. LÓGICA: ELIMINAR SLIDER ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $db->prepare("SELECT imagen FROM sliders WHERE id = ?");
    $stmt->execute([$id]);
    $video_file = $stmt->fetchColumn();

    if ($video_file) {
        $ruta_archivo = __DIR__ . "/../assets/video/" . $video_file;
        if (file_exists($ruta_archivo)) { unlink($ruta_archivo); }
    }

    $db->prepare("DELETE FROM sliders WHERE id = ?")->execute([$id]);
    $mensaje_swal = "Swal.fire('¡Eliminado!', 'El video ha sido borrado.', 'success');";
}

// --- 4. LÓGICA: GUARDAR O ACTUALIZAR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_slider'])) {
    $titulo = strtoupper(htmlspecialchars($_POST['titulo']));
    $titulo_en = strtoupper(htmlspecialchars($_POST['titulo_en']));
    $subtitulo = htmlspecialchars($_POST['subtitulo']);
    $subtitulo_en = htmlspecialchars($_POST['subtitulo_en']);
    $id_actualizar = $_POST['id_slider'];

    $video_sql = "";
    $params_video = [];

    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $allowed = ['mp4', 'webm', 'ogg'];
        
        if (in_array($ext, $allowed)) {
            $nombre_video = "video_slider_" . time() . "." . $ext;
            $ruta_destino = __DIR__ . "/../assets/video/" . $nombre_video;

            if (!is_dir(__DIR__ . "/../assets/video/")) {
                mkdir(__DIR__ . "/../assets/video/", 0777, true);
            }

            if (move_uploaded_file($_FILES['video']['tmp_name'], $ruta_destino)) {
                $video_sql = ", imagen = :vid";
                $params_video = [':vid' => $nombre_video];
            }
        }
    }

    if (!empty($id_actualizar)) {
        // ACTUALIZAR
        $sql = "UPDATE sliders SET titulo = :tit, titulo_en = :titen, subtitulo = :sub, subtitulo_en = :suben $video_sql WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge([
            ':tit' => $titulo, 
            ':titen' => $titulo_en, 
            ':sub' => $subtitulo, 
            ':suben' => $subtitulo_en, 
            ':id' => $id_actualizar
        ], $params_video));
        $mensaje_swal = "Swal.fire('¡Actualizado!', 'Slider bilingüe actualizado.', 'success');";
    } else {
        // INSERTAR NUEVO
        $video_db = $params_video[':vid'] ?? "";
        if ($video_db != "") {
            $sql = "INSERT INTO sliders (imagen, titulo, titulo_en, subtitulo, subtitulo_en) VALUES (:vid, :tit, :titen, :sub, :suben)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':vid' => $video_db, 
                ':tit' => $titulo, 
                ':titen' => $titulo_en, 
                ':sub' => $subtitulo, 
                ':suben' => $subtitulo_en
            ]);
            $mensaje_swal = "Swal.fire('¡Creado!', 'Nuevo video-slider bilingüe añadido.', 'success');";
        } else {
            $mensaje_swal = "Swal.fire('Error', 'Debes subir un archivo de video.', 'error');";
        }
    }
    $edit_mode = false;
}

$sliders = $db->query("SELECT * FROM sliders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Sliders Bilingües - IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .lang-en-field { border-left: 5px solid #0d6efd !important; background-color: #f0f7ff !important; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="admin-wrapper d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content p-4 w-100">
            <h1><i class="fas fa-images"></i> <?php echo $edit_mode ? "Editando Slider" : "Gestión de Sliders (Video de Inicio)"; ?></h1>

            <section class="admin-contenedor" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
                <form action="edit_sliders.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_slider" value="<?php echo $datos_edit['id']; ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-grupo" style="grid-column: span 2;">
                            <label class="fw-bold">Archivo de Video (MP4)</label>
                            <input type="file" name="video" accept="video/mp4,video/webm" <?php echo $edit_mode ? "" : "required"; ?> class="form-control">
                            <?php if($edit_mode): ?><small class="text-primary">Actual: <?php echo $datos_edit['imagen']; ?></small><?php endif; ?>
                        </div>

                        <div class="form-grupo">
                            <label class="fw-bold">Título (ES)</label>
                            <input type="text" name="titulo" value="<?php echo $datos_edit['titulo']; ?>" class="form-control" placeholder="Ej: AVENTURA EN CUSCO">
                        </div>
                        <div class="form-grupo">
                            <label class="fw-bold text-primary">Title (EN)</label>
                            <input type="text" name="titulo_en" value="<?php echo $datos_edit['titulo_en']; ?>" class="form-control lang-en-field" placeholder="Ej: ADVENTURE IN CUSCO">
                        </div>

                        <div class="form-grupo">
                            <label class="fw-bold">Subtítulo (ES)</label>
                            <textarea name="subtitulo" class="form-control" rows="2"><?php echo $datos_edit['subtitulo']; ?></textarea>
                        </div>
                        <div class="form-grupo">
                            <label class="fw-bold text-primary">Subtitle (EN)</label>
                            <textarea name="subtitulo_en" class="form-control lang-en-field" rows="2"><?php echo $datos_edit['subtitulo_en']; ?></textarea>
                        </div>
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" name="guardar_slider" class="btn btn-primary" style="padding: 12px 30px; background: #15305D; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                            <i class="fas fa-save"></i> <?php echo $edit_mode ? "Guardar Cambios" : "Cargar Video Slider"; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="edit_sliders.php" style="margin-left:15px; color:#666; text-decoration:none;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="admin-contenedor">
                <h3><i class="fas fa-play-circle"></i> Videos Actuales</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
                    <?php foreach ($sliders as $s): ?>
                        <div style="background: white; border: 1px solid #eee; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                            <video muted loop autoplay style="width:100%; height:180px; object-fit:cover; border-radius:8px; background: #000;">
                                <source src="../assets/video/<?php echo $s['imagen']; ?>" type="video/mp4">
                            </video>
                            
                            <div style="padding: 15px 0;">
                                <div style="margin-bottom: 10px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;">
                                    <small class="text-muted d-block">Español:</small>
                                    <strong style="color: #15305D;"><?php echo $s['titulo']; ?></strong>
                                    <p style="font-size:0.8rem; margin:0;"><?php echo $s['subtitulo']; ?></p>
                                </div>
                                <div>
                                    <small class="text-primary d-block">English:</small>
                                    <strong style="color: #0d6efd;"><?php echo $s['titulo_en'] ?: '---'; ?></strong>
                                    <p style="font-size:0.8rem; margin:0;"><?php echo $s['subtitulo_en'] ?: '---'; ?></p>
                                </div>
                            </div>

                            <div style="margin-top:10px; border-top:1px solid #eee; padding-top:15px; display: flex; justify-content: space-between;">
                                <a href="edit_sliders.php?editar=<?php echo $s['id']; ?>" class="btn-edit" style="color:#3498db; text-decoration:none; font-weight:bold;">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="edit_sliders.php?eliminar=<?php echo $s['id']; ?>"
                                   style="color:#e74c3c; text-decoration:none; font-weight:bold;"
                                   onclick="return confirm('¿Seguro que quieres borrar este video?');">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        <?php echo $mensaje_swal; ?>
    </script>
</body>
</html>