<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('privacidad');

require_once '../config/database.php';
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contenido'])) {
    
    $nombre_imagen = $_POST['imagen_actual'] ?? '';
    $contenido = $_POST['contenido'];

    // 2. Mejora en la subida de imagen
    if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] == 0) {
        $directorio = "../assets/img/";
        
        // Crear carpeta si no existe (evita Error 500 si la ruta no existe)
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $ext = pathinfo($_FILES['nueva_imagen']['name'], PATHINFO_EXTENSION);
        // Usar un nombre limpio o único para evitar conflictos de caché
        $nombre_imagen = "banner_privacidad_" . time() . "." . $ext; 
        
        if (move_uploaded_file($_FILES['nueva_imagen']['tmp_name'], $directorio . $nombre_imagen)) {
            // Opcional: Borrar la imagen anterior para no llenar el servidor de basura
            if (!empty($_POST['imagen_actual']) && file_exists($directorio . $_POST['imagen_actual'])) {
                unlink($directorio . $_POST['imagen_actual']);
            }
        }
    }

    // 3. Uso de Try-Catch para capturar errores de base de datos
    try {
        $stmt = $db->prepare("UPDATE politica_privacidad SET contenido = ?, imagen = ? WHERE id = 1");
        $stmt->execute([$contenido, $nombre_imagen]);
        $exito = true;
    } catch (PDOException $e) {
        $error_db = "Error al actualizar: " . $e->getMessage();
    }
}

// Obtener datos actualizados
$res = $db->query("SELECT contenido, imagen FROM politica_privacidad WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Privacidad | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <!-- ... código anterior ... -->
        <main class="main-content">
            <h1>Administrar Política de Privacidad</h1>
            <div class="admin-contenedor">
                <!-- CAMBIO 1: Añadir enctype -->
                <form method="POST" enctype="multipart/form-data">

                    <!-- CAMBIO 2: Sección de imagen -->
                    <div style="margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #ddd;">
                        <label style="font-weight: bold; display: block; margin-bottom: 10px; color: #15305D;">IMAGEN DE CABECERA / BANNER</label>

                        <?php if (!empty($res['imagen'])): ?>
                            <img src="../assets/img/<?= $res['imagen'] ?>" style="width: 200px; border-radius: 8px; margin-bottom: 10px; display: block;">
                        <?php endif; ?>

                        <!-- Mantiene el nombre de la imagen actual -->
                        <input type="hidden" name="imagen_actual" value="<?= $res['imagen'] ?>">
                        <!-- Campo para subir el nuevo archivo -->
                        <input type="file" name="nueva_imagen" accept="image/*" style="font-size: 14px;">
                    </div>

                    <div style="margin-bottom: 20px; background: #fef2f2; padding: 15px; border-radius: 10px; border-left: 5px solid #ef4444;">
                        <strong>Instrucciones:</strong> Usa <code>#</code> para títulos, <code>##</code> para subtítulos y <code>-</code> para listas.
                    </div>

                    <textarea name="contenido" style="width: 100%; height: 500px; padding: 20px; border-radius: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 15px;"><?= htmlspecialchars($res['contenido']) ?></textarea>

                    <button type="submit" class="btn-save" style="background:#15305D; color:#fff; width:100%; padding:15px; margin-top:20px; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
                        <i class="fas fa-shield-alt"></i> ACTUALIZAR PRIVACIDAD E IMAGEN
                    </button>
                </form>
            </div>
        </main>
        <!-- ... resto del código ... -->
    </div>
    <?php if (isset($exito)): ?>
        <script>
            Swal.fire('¡Éxito!', 'Política de privacidad actualizada.', 'success');
        </script>
    <?php endif; ?>
</body>

</html>