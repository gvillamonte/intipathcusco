<?php
/**
 * licencias.php
 * Gestión de Licencias y Permisos - IntiPath Tours
 */
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('licencias');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// 1. LÓGICA DE CARGA DE ARCHIVO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_licencia'])) {
    $titulo = $_POST['titulo'];
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "licencia_" . time() . "." . $ext;
        $ruta_destino = "../assets/img/licencias/" . $nombre_archivo;

        // Crear carpeta si no existe
        if (!is_dir('../assets/img/licencias/')) {
            mkdir('../assets/img/licencias/', 0777, true);
        }

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
            $stmt = $db->prepare("INSERT INTO licencias (titulo, imagen) VALUES (?, ?)");
            $stmt->execute([$titulo, $nombre_archivo]);
            header("Location: licencias.php?res=success");
            exit;
        }
    }
}

// 2. LÓGICA DE ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    // Obtener nombre para borrar archivo físico
    $stmt = $db->prepare("SELECT imagen FROM licencias WHERE id = ?");
    $stmt->execute([$id]);
    $img_name = $stmt->fetchColumn();

    if ($img_name) {
        @unlink("../assets/img/licencias/" . $img_name);
    }

    $stmt = $db->prepare("DELETE FROM licencias WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: licencias.php?res=deleted");
    exit;
}

$licencias = $db->query("SELECT * FROM licencias ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Licencias y Permisos | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css"> <style>
        .lic-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .lic-card:hover { transform: translateY(-5px); }
        .lic-img-container { height: 160px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 10px 10px 0 0; padding: 15px; }
        .lic-img-container img { max-height: 100%; max-width: 100%; object-fit: contain; }
    </style>
</head>
<body>
    <div class="admin-wrapper d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="tou-main-content p-4 w-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 fw-bold text-dark"><i class="fas fa-certificate me-2 text-primary"></i>Licencias y Permisos</h2>
            </div>

            <section class="tou-card shadow-sm bg-white p-4 rounded mb-5">
                <h5 class="fw-bold mb-3">Registrar Nuevo Documento</h5>
                <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Nombre de la Institución / Documento</label>
                        <input type="text" name="titulo" class="form-control" placeholder="Ej: GERCETUR Cusco" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Imagen del Certificado (JPG/PNG)</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="subir_licencia" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-upload me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </section>

            <div class="row g-4">
                <?php if($licencias): foreach($licencias as $l): ?>
                    <div class="col-md-3">
                        <div class="card lic-card h-100">
                            <div class="lic-img-container">
                                <img src="../assets/img/licencias/<?= $l['imagen'] ?>" alt="<?= $l['titulo'] ?>">
                            </div>
                            <div class="card-body text-center p-3">
                                <h6 class="fw-bold mb-3 text-uppercase" style="font-size: 0.8rem;"><?= htmlspecialchars($l['titulo']) ?></h6>
                                <button onclick="confirmarEliminarLic(<?= $l['id'] ?>)" class="btn btn-sm btn-outline-danger w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No hay licencias registradas todavía.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmarEliminarLic(id) {
            Swal.fire({
                title: '¿Eliminar documento?',
                text: "Esta acción borrará la imagen permanentemente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `licencias.php?eliminar=${id}`;
                }
            });
        }

        // Notificaciones
        const params = new URLSearchParams(window.location.search);
        if(params.get('res') === 'success') {
            Swal.fire('¡Subido!', 'La licencia se guardó correctamente.', 'success');
        }
        if(params.get('res') === 'deleted') {
            Swal.fire('Eliminado', 'El registro fue borrado.', 'info');
        }
    </script>
</body>
</html>