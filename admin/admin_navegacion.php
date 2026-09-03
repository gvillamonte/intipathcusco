<?php
// admin/admin_navegacion.php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('nosotros');

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

$usuario_nombre = $_SESSION['admin_nombre'] ?? 'Administrador';
$edit_mode = false;
$datos_edit = ['id' => '', 'nombre' => '', 'enlace' => '', 'orden' => '1'];

// 1. LÓGICA: CARGAR DATOS PARA EDITAR
if (isset($_GET['editar'])) {
    $edit_mode = true;
    $stmt = $db->prepare("SELECT * FROM navegacion WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $datos_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. LÓGICA: GUARDAR O ACTUALIZAR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_nav'])) {
    $nombre = strtoupper(htmlspecialchars($_POST['nombre']));
    $enlace = htmlspecialchars($_POST['enlace']);
    $orden = intval($_POST['orden']);
    $id_nav = $_POST['id_nav'];

    if (!empty($id_nav)) {
        // ACTUALIZAR
        $sql = "UPDATE navegacion SET nombre = ?, enlace = ?, orden = ? WHERE id = ?";
        $db->prepare($sql)->execute([$nombre, $enlace, $orden, $id_nav]);
    } else {
        // INSERTAR NUEVO
        $sql = "INSERT INTO navegacion (nombre, enlace, orden) VALUES (?, ?, ?)";
        $db->prepare($sql)->execute([$nombre, $enlace, $orden]);

        // GENERACIÓN AUTOMÁTICA DEL ARCHIVO PHP FÍSICO (HEADER + CONTENIDO + FOOTER)
        $nombre_archivo = basename($enlace);
        $ruta_archivo = "../" . $nombre_archivo;

        if (strpos($nombre_archivo, '.php') !== false && !file_exists($ruta_archivo)) {
            $plantilla = '<?php include "includes/header.php"; ?>

<main style="padding: 100px 0; background: var(--color-fondo-blanco); min-height: 60vh;">
    <div class="container" style="max-width: 1100px; margin: 0 auto; text-align: center;">
        <h1 style="color: var(--color-primario-azul); font-size: 3rem; text-transform: uppercase;">' . $nombre . '</h1>
        <div style="width: 60px; height: 4px; background: var(--color-secundario-amarillo); margin: 20px auto;"></div>
        
        <div class="contenido-editable">
            <p style="color: var(--color-texto-oscuro); font-size: 1.2rem; line-height: 1.8;">
                Bienvenido a la sección de <strong>' . $nombre . '</strong>. 
                Este es un espacio listo para que describas tus mejores servicios turísticos.
            </p>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>';
            file_put_contents($ruta_archivo, $plantilla);
        }
    }
    header("Location: admin_navegacion.php?success=1");
    exit();
}

// 3. LÓGICA: ELIMINAR
if (isset($_GET['eliminar'])) {
    $db->prepare("DELETE FROM navegacion WHERE id = ?")->execute([$_GET['eliminar']]);
    header("Location: admin_navegacion.php?deleted=1");
    exit();
}

// Traer enlaces para la tabla
$enlaces = $db->query("SELECT * FROM navegacion ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Navegación | IntiPath Tours</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content" id="admin-nav-container">
            
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1>Gestión de <span>Navegación</span></h1>
                <div style="background: white; padding: 10px 20px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); font-size: 0.85rem;">
                    <i class="fas fa-user-circle" style="color: var(--color-secundario-amarillo);"></i> 
                    <strong><?php echo $usuario_nombre; ?></strong>
                </div>
            </header>

            <section class="admin-contenedor">
                <h2 style="color: var(--color-primario-azul); margin-bottom: 20px; font-size: 1.2rem;">
                    <i class="fas fa-plus-circle"></i> <?php echo $edit_mode ? "Modificar Enlace" : "Nueva Sección para la Web"; ?>
                </h2>
                
                <form method="POST" class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <input type="hidden" name="id_nav" value="<?php echo $datos_edit['id']; ?>">
                    
                    <div class="form-grupo">
                        <label>Etiqueta en Menú (Ej: TOURS)</label>
                        <input type="text" name="nombre" value="<?php echo $datos_edit['nombre']; ?>" required placeholder="NOMBRE">
                    </div>

                    <div class="form-grupo">
                        <label>Archivo Destino (Ej: tours.php)</label>
                        <input type="text" name="enlace" value="<?php echo $datos_edit['enlace']; ?>" required placeholder="archivo.php">
                    </div>

                    <div class="form-grupo">
                        <label>Orden de Aparición</label>
                        <input type="number" name="orden" value="<?php echo $datos_edit['orden']; ?>" required>
                    </div>

                    <div style="grid-column: 1 / -1; margin-top: 10px;">
                        <button type="submit" name="guardar_nav" class="btn-publicar">
                            <?php echo $edit_mode ? "💾 GUARDAR CAMBIOS" : "🚀 PUBLICAR EN LA WEB"; ?>
                        </button>
                        <?php if($edit_mode): ?>
                            <a href="admin_navegacion.php" style="margin-left: 15px; color: #666; text-decoration: none;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="admin-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px; text-align: center;">ORDEN</th>
                            <th style="text-align: left;">NOMBRE EN LA WEB</th>
                            <th style="text-align: left;">ARCHIVO</th>
                            <th style="width: 150px; text-align: center;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enlaces as $en): ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold; color: var(--color-secundario-amarillo);">
                                <?php echo $en['orden']; ?>
                            </td>
                            <td><strong><?php echo $en['nombre']; ?></strong></td>
                            <td><span class="badge-file"><?php echo $en['enlace']; ?></span></td>
                            <td style="text-align: center;">
                                
                                <?php if(strpos($en['enlace'], '.php') !== false): ?>
                                    <a href="editar_contenido.php?file=<?php echo $en['enlace']; ?>" style="color:#27ae60; margin-right:15px; font-size: 1.1rem;" title="Editar Contenido">
                                        <i class="fas fa-file-signature"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="admin_navegacion.php?editar=<?php echo $en['id']; ?>" style="color:var(--color-primario-azul); margin-right:15px; font-size: 1.1rem;">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="admin_navegacion.php?eliminar=<?php echo $en['id']; ?>" style="color:#e74c3c; font-size: 1.1rem;" onclick="return confirm('¿Eliminar esta sección?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({ title: '¡Éxito!', text: 'La navegación se ha actualizado correctamente.', icon: 'success', confirmButtonColor: '#15305D' });
            window.history.replaceState({}, document.title, window.location.pathname);
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            Swal.fire({ title: 'Eliminado', text: 'La sección ha sido quitada del menú.', icon: 'warning', confirmButtonColor: '#15305D' });
            window.history.replaceState({}, document.title, window.location.pathname);
        <?php endif; ?>
    </script>
</body>
</html>