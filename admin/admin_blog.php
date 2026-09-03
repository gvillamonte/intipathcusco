<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('blog');

require_once '../config/database.php';
$db = (new Database())->getConnection();

// --- 1. LÓGICA DE SUBIDA DE IMÁGENES A LA GALERÍA ---
if (isset($_POST['subir_a_galeria'])) {
    if (isset($_FILES['foto_galeria']) && $_FILES['foto_galeria']['error'] == 0) {
        $dir = "../assets/img/blog/";
        if (!is_dir($dir)) { mkdir($dir, 0777, true); }
        
        $custom_name = trim($_POST['nombre_foto_custom']);
        $ext = pathinfo($_FILES['foto_galeria']['name'], PATHINFO_EXTENSION);
        $final_name = (empty($custom_name) ? pathinfo($_FILES['foto_galeria']['name'], PATHINFO_FILENAME) : str_replace(' ', '_', $custom_name)) . "." . $ext;
        
        move_uploaded_file($_FILES['foto_galeria']['tmp_name'], $dir . $final_name);
        header("Location: admin_blog.php?res=upload_ok"); exit;
    }
}

// --- 2. LÓGICA PARA ELIMINAR IMAGEN FÍSICA DE LA GALERÍA ---
if (isset($_GET['borrar_archivo'])) {
    $archivo = $_GET['borrar_archivo'];
    $ruta = "../assets/img/blog/" . $archivo;
    if (file_exists($ruta) && !is_dir($ruta)) { unlink($ruta); }
    header("Location: admin_blog.php?res=file_deleted"); exit;
}

// --- 3. LÓGICA PARA ACTUALIZAR EL BANNER (ID 1) ---
if (isset($_POST['actualizar_banner'])) {
    $stmt = $db->prepare("UPDATE blog SET banner_titulo = ?, banner_titulo_en = ?, banner_subtitulo = ?, banner_subtitulo_en = ? WHERE id = 1");
    $stmt->execute([$_POST['b_titulo'], $_POST['b_titulo_en'], $_POST['b_subtitulo'], $_POST['b_subtitulo_en']]);
    
    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] == 0) {
        move_uploaded_file($_FILES['banner_img']['tmp_name'], "../assets/img/banner_blog_header.jpg");
    }
    header("Location: admin_blog.php?res=banner_ok"); exit;
}

// --- 4. GUARDAR O EDITAR POST ---
if (isset($_POST['guardar_post'])) {
    $id = !empty($_POST['post_id']) ? intval($_POST['post_id']) : null;
    $titulo = $_POST['titulo'];
    $titulo_en = $_POST['titulo_en'];
    $contenido = $_POST['contenido'];
    $contenido_en = $_POST['contenido_en'];
    $img_p = $_POST['imagen_principal']; 

    if ($id) {
        $stmt = $db->prepare("UPDATE blog SET titulo=?, titulo_en=?, contenido=?, contenido_en=?, imagen=? WHERE id=?");
        $stmt->execute([$titulo, $titulo_en, $contenido, $contenido_en, $img_p, $id]);
        $res = "updated";
    } else {
        $stmt = $db->prepare("INSERT INTO blog (titulo, titulo_en, contenido, contenido_en, imagen, fecha, estado) VALUES (?,?,?,?,?, NOW(), 'activo')");
        $stmt->execute([$titulo, $titulo_en, $contenido, $contenido_en, $img_p]);
        $res = "success";
    }
    header("Location: admin_blog.php?res=$res"); exit;
}

// --- 5. ELIMINAR POST ---
if (isset($_GET['eliminar'])) {
    $db->prepare("DELETE FROM blog WHERE id = ?")->execute([$_GET['eliminar']]);
    header("Location: admin_blog.php?res=deleted"); exit;
}

// CONSULTAS PARA LA VISTA
$config_banner = $db->query("SELECT * FROM blog WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$posts = $db->query("SELECT * FROM blog WHERE id != 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$fotos_galeria = is_dir("../assets/img/blog/") ? array_diff(scandir("../assets/img/blog/"), array('.', '..')) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Blog Pro | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-teal: #0f9b9e; --admin-lime: #c6d544; }
        .main-content { padding: 30px; }
        
        /* GALERÍA */
        .media-manager { background: #fff; padding: 25px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .gallery-scroll { display: flex; gap: 15px; overflow-x: auto; padding: 15px 5px; min-height: 120px; border: 1px dashed #ccc; border-radius: 10px; }
        .gallery-card { min-width: 135px; position: relative; text-align: center; background: #fff; padding: 10px; border-radius: 12px; border: 1px solid #edf2f7; }
        .gallery-card img { width: 100%; height: 75px; object-fit: cover; border-radius: 8px; cursor: pointer; }
        .btn-delete-file { position: absolute; top: 5px; right: 5px; background: #e74c3c; color: white; border: none; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        
        /* BOTONES */
        .btn-pro { background: var(--admin-blue); color: #fff; border: none; padding: 12px 22px; border-radius: 8px; cursor: pointer; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; }
        .btn-pro:hover { background: #1b3d75; transform: translateY(-2px); }
        .btn-teal { background: var(--admin-teal); }

        /* MODALES */
        .modal-blog { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-blog.active { display: flex; }
        .modal-box { background: #fff; width: 95%; max-width: 1000px; border-radius: 20px; overflow: hidden; }
        .modal-header { background: var(--admin-blue); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        
        input[type="text"], input[type="file"], textarea { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; outline: none; width: 100%; box-sizing: border-box; }
        textarea { font-family: monospace; resize: vertical; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h1 style="color: var(--admin-blue); font-weight: 900; margin-bottom: 30px;"><i class="fas fa-feather-alt"></i> Gestión de Blog</h1>

        <div class="media-manager">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: var(--admin-blue); margin:0; font-weight: 800;"><i class="fas fa-images"></i> Biblioteca de Imágenes</h3>
                <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: center;">
                    <input type="text" name="nombre_foto_custom" placeholder="Nombre (ej: tour_cusco)" style="width: 200px;">
                    <input type="file" name="foto_galeria" accept="image/*" required style="width: auto;">
                    <button type="submit" name="subir_a_galeria" class="btn-pro btn-teal"><i class="fas fa-upload"></i> Subir</button>
                </form>
            </div>
            <div class="gallery-scroll">
                <?php foreach($fotos_galeria as $foto): ?>
                    <div class="gallery-card">
                        <button class="btn-delete-file" onclick="confirmDeleteFile('<?= $foto ?>')"><i class="fas fa-times"></i></button>
                        <img src="../assets/img/blog/<?= $foto ?>" onclick="copyToClipboard('<?= $foto ?>')" title="Click para copiar nombre">
                        <code style="font-size:10px; color:var(--admin-teal);"><?= $foto ?></code>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="background: #fff; padding: 25px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 20px;">
                <img src="../assets/img/banner_blog_header.jpg?v=<?=time()?>" style="width: 100px; height: 50px; object-fit: cover; border-radius: 10px; border: 2px solid var(--admin-blue);">
                <h4 style="margin:0; color: var(--admin-blue);"><?= htmlspecialchars($config_banner['banner_titulo']) ?></h4>
            </div>
            <button onclick="document.getElementById('modalBanner').classList.add('active')" class="btn-pro"><i class="fas fa-paint-brush"></i> Editar Banner</button>
        </div>

        <div class="admin-contenedor" style="background:#fff; padding:30px; border-radius:15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin:0; color: var(--admin-blue); font-weight: 800;">Entradas Publicadas</h3>
                <button onclick="abrirNuevo()" class="btn-pro btn-teal"><i class="fas fa-plus-circle"></i> Nuevo Artículo</button>
            </div>
            <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #eee;">
                        <th style="padding: 10px;">Imagen</th>
                        <th>Título</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($posts as $p): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><img src="../assets/img/blog/<?= $p['imagen'] ?>" style="width:80px; height:50px; object-fit:cover; border-radius:5px;"></td>
                        <td style="font-weight: 700; color: var(--admin-blue);"><?= htmlspecialchars($p['titulo']) ?></td>
                        <td style="text-align: center;">
                            <button onclick='abrirEditar(<?= json_encode($p) ?>)' style="color: #f39c12; background:none; border:none; cursor:pointer; font-size:1.2rem; margin-right:15px;"><i class="fas fa-edit"></i></button>
                            <button onclick="confirmDeletePost(<?= $p['id'] ?>)" style="color: #e74c3c; background:none; border:none; cursor:pointer; font-size:1.2rem;"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div id="modalPost" class="modal-blog">
    <div class="modal-box">
        <div class="modal-header"><h3 id="modal_label">Editor de Contenido</h3><span onclick="cerrarPost()" style="cursor:pointer; font-size:25px;">&times;</span></div>
        <form method="POST" style="padding: 30px;">
            <input type="hidden" name="post_id" id="post_id">
            <div style="margin-bottom: 15px; background: #f8fafc; padding: 15px; border-radius: 10px;">
                <label style="font-weight: bold; color: var(--admin-blue);">Imagen de Portada (Nombre exacto de la galería):</label>
                <input type="text" name="imagen_principal" id="post_img" placeholder="ej: foto_cusco.jpg" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label>Título (ES):</label><input type="text" name="titulo" id="tit_es" required style="margin-bottom:10px;">
                    <label>Contenido (ES):</label><textarea name="contenido" id="cont_es" rows="10" required></textarea>
                </div>
                <div>
                    <label>Title (EN):</label><input type="text" name="titulo_en" id="tit_en" required style="margin-bottom:10px;">
                    <label>Content (EN):</label><textarea name="contenido_en" id="cont_en" rows="10" required></textarea>
                </div>
            </div>
            <button type="submit" name="guardar_post" class="btn-pro btn-teal" style="width:100%; margin-top:20px;">GUARDAR ARTÍCULO</button>
        </form>
    </div>
</div>

<div id="modalBanner" class="modal-blog">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-header"><h3>Editar Banner Principal</h3><span onclick="document.getElementById('modalBanner').classList.remove('active')" style="cursor:pointer; font-size:25px;">&times;</span></div>
        <form method="POST" enctype="multipart/form-data" style="padding:25px;">
            <div style="margin-bottom:15px;"><label>Cambiar Imagen de Fondo:</label><input type="file" name="banner_img" accept="image/*"></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <input type="text" name="b_titulo" value="<?= htmlspecialchars($config_banner['banner_titulo']) ?>" placeholder="Título ES">
                <input type="text" name="b_titulo_en" value="<?= htmlspecialchars($config_banner['banner_titulo_en']) ?>" placeholder="Title EN">
                <input type="text" name="b_subtitulo" value="<?= htmlspecialchars($config_banner['banner_subtitulo']) ?>" placeholder="Sub ES">
                <input type="text" name="b_subtitulo_en" value="<?= htmlspecialchars($config_banner['banner_subtitulo_en']) ?>" placeholder="Sub EN">
            </div>
            <button type="submit" name="actualizar_banner" class="btn-pro" style="width: 100%; margin-top: 20px;">ACTUALIZAR BANNER</button>
        </form>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copiado: ' + text, showConfirmButton: false, timer: 1000 });
    }

    function confirmDeleteFile(nombre) {
        Swal.fire({
            title: '¿Eliminar imagen?',
            text: "Esta acción perjudicará el detalle del blog si la imagen está en uso.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => { if (result.isConfirmed) { window.location.href = '?borrar_archivo=' + nombre; } });
    }

    function confirmDeletePost(id) {
        Swal.fire({
            title: '¿Eliminar artículo?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => { if (result.isConfirmed) { window.location.href = '?eliminar=' + id; } });
    }

    function abrirNuevo() {
        document.getElementById('post_id').value = '';
        document.getElementById('modal_label').innerText = 'Nuevo Artículo';
        document.getElementById('tit_es').value = ''; document.getElementById('tit_en').value = '';
        document.getElementById('cont_es').value = ''; document.getElementById('cont_en').value = '';
        document.getElementById('post_img').value = '';
        document.getElementById('modalPost').classList.add('active');
    }

    function abrirEditar(d) {
        document.getElementById('post_id').value = d.id;
        document.getElementById('modal_label').innerText = 'Editar Artículo';
        document.getElementById('tit_es').value = d.titulo;
        document.getElementById('tit_en').value = d.titulo_en;
        document.getElementById('cont_es').value = d.contenido;
        document.getElementById('cont_en').value = d.contenido_en;
        document.getElementById('post_img').value = d.imagen;
        document.getElementById('modalPost').classList.add('active');
    }

    function cerrarPost() { document.getElementById('modalPost').classList.remove('active'); }

    // --- ESCUCHADOR DE SWEETALERT AL CARGAR LA PÁGINA ---
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const res = urlParams.get('res');
        
        if (res) {
            let configAlert = { confirmButtonColor: '#15305D' };
            
            switch(res) {
                case 'success':
                    configAlert = { ...configAlert, icon: 'success', title: '¡Publicado!', text: 'El artículo se creó correctamente.' };
                    break;
                case 'updated':
                    configAlert = { ...configAlert, icon: 'success', title: '¡Actualizado!', text: 'Los cambios fueron guardados.' };
                    break;
                case 'deleted':
                    configAlert = { ...configAlert, icon: 'success', title: '¡Eliminado!', text: 'El artículo fue removido de la base de datos.' };
                    break;
                case 'upload_ok':
                    configAlert = { ...configAlert, icon: 'success', title: '¡Imagen Lista!', text: 'Se subió a la biblioteca de medios.' };
                    break;
                case 'file_deleted':
                    configAlert = { ...configAlert, icon: 'success', title: '¡Archivo Borrado!', text: 'La imagen fue removida del servidor.' };
                    break;
                case 'banner_ok':
                    configAlert = { ...configAlert, icon: 'success', title: '¡Banner Listo!', text: 'Los textos y cabecera gráfica se actualizaron.' };
                    break;
            }
            
            Swal.fire(configAlert).then(() => {
                // Limpia la URL de parámetros para que no se repita al recargar
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    });
</script>

</body>
</html>