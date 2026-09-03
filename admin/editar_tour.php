<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('tours');

require_once __DIR__ . '/../config/database.php';

// Conexión a la base de datos siguiendo tu estándar de objetos
$database = new Database();
$db = $database->getConnection();

// --- 2. PROCESAR ACCIONES (POST) ---

// A. Crear nuevo enlace y archivo físico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_enlace'])) {
    $nom_es = trim($_POST['nombre_es']);
    $nom_en = trim($_POST['nombre_en']);
    $archivo = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom_es))) . ".php";
    
    $sql = "INSERT INTO menu_nosotros (titulo_columna, nombre_enlace, nombre_enlace_en, url_enlace, es_footer_link, columna_nro) VALUES (?, ?, ?, ?, 1, 1)";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute(['NUESTRA EMPRESA', $nom_es, $nom_en, $archivo])) {
        if (!file_exists("../$archivo")) {
            $plantilla = "<?php include('header.php'); ?>\n<section style='padding:80px 20px;'>\n\t<h1>$nom_es</h1>\n\t<p>Contenido en construcción.</p>\n</section>\n<?php include('footer.php'); ?>";
            file_put_contents("../$archivo", $plantilla);
        }
        $exito = "¡Enlace creado y archivo generado con éxito!";
    }
}

// B. Actualizar estado del Header (Petición AJAX)
if (isset($_POST['update_status'])) {
    $id_link = intval($_POST['id']);
    $estado = intval($_POST['estado']);
    $stmt = $db->prepare("UPDATE menu_nosotros SET es_footer_link = ? WHERE id = ?");
    $stmt->execute([$estado, $id_link]);
    echo "OK"; 
    exit;
}

// --- 3. OBTENER DATOS PARA LA TABLA ---
$links = $db->query("SELECT * FROM menu_nosotros ORDER BY columna_nro ASC, orden ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Nosotros | Admin IntiPath</title>
    <!-- Tus estilos y librerías -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card-custom { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.1); border: 1px solid #e1e8ef; margin-bottom: 25px; }
        .table-admin { width: 100%; border-collapse: collapse; }
        .table-admin th { text-align: left; padding: 15px; background: #f8f9fa; color: #15305D; border-bottom: 2px solid #e1e8ef; font-size: 0.8rem; }
        .table-admin td { padding: 15px; border-bottom: 1px solid #edf2f7; }
        
        /* Switch de activación */
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #15305D; }
        input:checked + .slider:before { transform: translateX(20px); }
        
        .btn-inti { background: #15305D; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar con validación de accesos incluida -->
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1><i class="fas fa-users-cog"></i> Gestión de Menú "Nosotros"</h1>

            <!-- Formulario de Creación -->
            <div class="card-custom">
                <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-weight: bold; font-size: 0.8rem;">Nombre (Español)</label>
                        <input type="text" name="nombre_es" placeholder="Ej. Nuestro Equipo" style="padding: 10px; border-radius: 6px; border: 1px solid #ddd;" required>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-weight: bold; font-size: 0.8rem;">Name (English)</label>
                        <input type="text" name="nombre_en" placeholder="Ej. Our Team" style="padding: 10px; border-radius: 6px; border: 1px solid #ddd;" required>
                    </div>
                    <button type="submit" name="crear_enlace" class="btn-inti">
                        <i class="fas fa-plus"></i> CREAR PÁGINA
                    </button>
                </form>
            </div>

            <!-- Listado de Enlaces -->
            <div class="card-custom">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Nombre (ES)</th>
                            <th>Nombre (EN)</th>
                            <th>Archivo</th>
                            <th>Estado Header</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($links as $l): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($l['nombre_enlace']); ?></strong></td>
                            <td style="color: #666;"><?php echo htmlspecialchars($l['nombre_enlace_en']); ?></td>
                            <td><code style="color: #d35400;"><?php echo $l['url_enlace']; ?></code></td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" class="status-toggle" 
                                           data-id="<?php echo $l['id']; ?>" 
                                           <?php echo ($l['es_footer_link'] == 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td>
                                <a href="editar_nosotros.php?id=<?php echo $l['id']; ?>" style="color: #15305D; text-decoration: none; font-weight: bold;">
                                    <i class="fas fa-edit"></i> Editar Contenido
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Lógica AJAX para actualizar el estado sin recargar la página
    $('.status-toggle').change(function() {
        const id = $(this).data('id');
        const estado = $(this).is(':checked') ? 1 : 0;
        
        $.post('', { update_status: 1, id: id, estado: estado }, function(response) {
            console.log("Estado actualizado en BD");
        });
    });

    // Notificación SweetAlert
    <?php if(isset($exito)): ?>
        Swal.fire({
            title: '¡Logrado!',
            text: '<?php echo $exito; ?>',
            icon: 'success',
            confirmButtonColor: '#15305D'
        });
    <?php endif; ?>
    </script>
</body>
</html>