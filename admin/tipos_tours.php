<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('tipos_tours');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// --- 1. PROCESAMIENTO DE ACCIONES ---

// EDITAR CAMINATA (MODAL)
if (isset($_POST['editar_caminata_modal'])) {
    $id = $_POST['caminata_id'];
    $titulo = $_POST['titulo'];
    $titulo_en = $_POST['titulo_en'];
    $precio = $_POST['precio'];
    $precio_soles = !empty($_POST['precio_soles']) ? $_POST['precio_soles'] : null;

    $stmt = $db->prepare("UPDATE tours SET titulo = ?, titulo_en = ?, precio = ?, precio_soles = ? WHERE id = ?");
    $stmt->execute([$titulo, $titulo_en, $precio, $precio_soles, $id]);
    header("Location: tipos_tours.php?res=success");
    exit;
}

// CREAR CAMINATA
if (isset($_POST['crear_caminata_nueva'])) {
    $precio_nuevo = $_POST['precio'];
    $precio_soles_nuevo = !empty($_POST['precio_soles']) ? $_POST['precio_soles'] : null;
    if ($precio_soles_nuevo === null && $precio_nuevo > 0) {
        require_once __DIR__ . '/../includes/tipo_cambio_helper.php';
        $precio_soles_nuevo = round((float)$precio_nuevo * obtenerTipoCambio($db), 2);
    }
    $stmt = $db->prepare("INSERT INTO tours (titulo, titulo_en, precio, precio_soles, parent_id, estado) VALUES (?, ?, ?, ?, ?, 'activo')");
    $stmt->execute([$_POST['titulo'], $_POST['titulo_en'], $precio_nuevo, $precio_soles_nuevo, $_POST['parent_id_nuevo'], $en_menu]);
    header("Location: tipos_tours.php?res=success");
    exit;
}

// VINCULAR
if (isset($_POST['vincular_desde_tipos'])) {
    $stmtNombre = $db->prepare("SELECT nombre, nombre_en FROM tipos_tours WHERE id = ?");
    $stmtNombre->execute([$_POST['tipo_id']]);
    $tipo = $stmtNombre->fetch(PDO::FETCH_ASSOC);

    if ($tipo) {
        $stmt = $db->prepare("INSERT INTO tours (titulo, titulo_en, parent_id, estado, precio, en_menu) VALUES (?, ?, ?, 'activo', 0, 1)");
        $stmt->execute([$tipo['nombre'], $tipo['nombre_en'], $_POST['parent_id']]);
        header("Location: tipos_tours.php?res=success");
    }
    exit;
}

// ELIMINAR (DESVINCULAR)
if (isset($_GET['eliminar'])) {
    $stmt = $db->prepare("UPDATE tours SET parent_id = NULL WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: tipos_tours.php?res=success");
    exit;
}

// AJAX TOGGLE EN_MENU (caminatas)

if (isset($_GET['ajax']) && $_GET['ajax'] === 'toggle_en_menu' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    $id = (int)($_POST['id'] ?? 0);

    $en_menu = (int)($_POST['en_menu'] ?? 0);

    if ($id > 0) {

        $stmt = $db->prepare("UPDATE tours SET en_menu = ? WHERE id = ?");

        $stmt->execute([$en_menu, $id]);

        echo json_encode(['ok' => true]);

    } else {

        echo json_encode(['ok' => false, 'error' => 'ID inválido']);

    }

    exit;

}


// --- 2. CONSULTAS ---
$relaciones = $db->query("SELECT h.id, h.titulo, h.titulo_en, h.en_menu, p.titulo as nombre_padre, h.precio, h.precio_soles 
                          FROM tours h JOIN tours p ON h.parent_id = p.id ORDER BY p.titulo ASC")->fetchAll(PDO::FETCH_ASSOC);

$tours_principales = $db->query("SELECT id, titulo FROM tours WHERE parent_id IS NULL AND estado = 'activo' ORDER BY titulo ASC")->fetchAll(PDO::FETCH_ASSOC);

$caminatas_de_tipos = $db->query("SELECT id, nombre FROM tipos_tours ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Caminatas | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/admin.css"> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* CSS REFORZADO PARA TU ADMIN */
        :root { --primary: #15305D; --accent: #f39c12; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; transition: all 0.3s; }
        
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-header { background: var(--primary); color: white; border-radius: 15px 15px 0 0 !important; font-weight: bold; padding: 15px 20px; }
        
        .table thead { background: var(--primary); color: white; }
        .table-hover tbody tr:hover { background-color: #f1f4f9; }
        
        .btn-success { background-color: #27ae60; border: none; }
        .btn-primary { background-color: var(--primary); border: none; }
        .btn-warning { background-color: var(--accent); color: white; border: none; }

        .modal-content { border-radius: 20px; border: none; }
        .modal-header { border-radius: 20px 20px 0 0; }
        
        .badge-lang { font-size: 0.7rem; padding: 3px 8px; border-radius: 5px; background: #eee; color: #555; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold" style="color: var(--primary);"><i class="fas fa-hiking"></i> Control de Caminatas</h2>
                <button class="btn btn-success px-4 py-2 shadow" data-bs-toggle="modal" data-bs-target="#modalNuevoTour">
                    <i class="fas fa-plus-circle me-1"></i> Crear Caminata
                </button>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-link"></i> Vincular desde Tabla General</div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Destino Principal (Padre)</label>
                            <select name="parent_id" class="form-select" required>
                                <?php foreach($tours_principales as $tp): ?>
                                    <option value="<?= $tp['id'] ?>"><?= mb_strtoupper($tp['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Nombre de Caminata (Tipos)</label>
                            <select name="tipo_id" class="form-select" required>
                                <?php foreach($caminatas_de_tipos as $ct): ?>
                                    <option value="<?= $ct['id'] ?>"><?= $ct['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="vincular_desde_tipos" class="btn btn-primary w-100">Vincular Ahora</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Destino Padre</th>
                                <th>Caminata (ES/EN)</th>
                                <th>Precio</th>
                                <th class="text-center">En Menú</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($relaciones as $r): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= $r['nombre_padre'] ?></td>
                                <td>
                                    <div class="text-dark fw-bold"><?= $r['titulo'] ?></div>
                                    <span class="badge-lang">EN: <?= $r['titulo_en'] ?></span>
                                </td>
                                <td class="text-success fw-bold">$<?= number_format($r['precio'], 2) ?></td>
                                <td class="text-center">
                                        <?php if (!empty($r['en_menu'])): ?>
                                            <span class="badge bg-success" title="Visible en menú">🟢 Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" title="No visible">⚪ No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleEnMenu(<?= $r['id'] ?>, <?= $r['en_menu'] ? 0 : 1 ?>)" title="<?= $r['en_menu'] ? 'Ocultar del menú' : 'Mostrar en menú' ?>">
                                            <i class="fas fa-<?= $r['en_menu'] ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="abrirModalEditar(<?= $r['id'] ?>, '<?= addslashes($r['titulo']) ?>', '<?= addslashes($r['titulo_en']) ?>', '<?= $r['precio'] ?>', <?= $r['en_menu'] ? 1 : 0 ?>, '<?= $r['precio_soles'] ?? '' ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmarDesvincular(<?= $r['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalNuevoTour" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-success text-white"><h5>Nueva Caminata</h5></div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Destino Padre</label>
                        <select name="parent_id_nuevo" class="form-select">
                            <?php foreach($tours_principales as $tp): ?>
                                <option value="<?= $tp['id'] ?>"><?= $tp['titulo'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Título (ES)</label><input type="text" name="titulo" class="form-control" required></div>
                    <div class="mb-3"><label class="small fw-bold">Título (EN)</label><input type="text" name="titulo_en" class="form-control" required></div>
                    <div class="mb-3"><label class="small fw-bold">Precio ($ USD)</label><input type="number" name="precio" step="0.01" class="form-control"></div>
                    <div class="mb-3"><label class="small fw-bold">Precio Soles (S/ PEN)</label><input type="number" name="precio_soles" step="0.01" class="form-control" placeholder="Auto si vacío"></div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="en_menu" value="1" id="nuevo_en_menu" class="form-check-input" checked>
                        <label class="form-check-label" for="nuevo_en_menu">Visible en menú principal y mega menú</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="crear_caminata_nueva" class="btn btn-success w-100 py-2">Guardar Registro</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditarCaminata" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Editar Caminata</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="caminata_id" id="edit_id">
                    <div class="mb-3">
                        <label class="small fw-bold">Título (Español)</label>
                        <input type="text" name="titulo" id="edit_titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Título (Inglés)</label>
                        <input type="text" name="titulo_en" id="edit_titulo_en" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Precio ($ USD)</label>
                        <input type="number" name="precio" id="edit_precio" step="0.01" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Precio Soles (S/ PEN)</label>
                        <input type="number" name="precio_soles" id="edit_precio_soles" step="0.01" class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="en_menu" value="1" id="edit_en_menu" class="form-check-input">
                        <label class="form-check-label" for="edit_en_menu">Visible en menú principal y mega menú</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_caminata_modal" class="btn btn-primary">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Opción A: Función manual reforzada
        function abrirModalEditar(id, es, en, pr, enMenu, prSoles) {
            // Llenamos los campos
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_titulo').value = es;
            document.getElementById('edit_titulo_en').value = en;
            document.getElementById('edit_precio').value = pr;
            document.getElementById('edit_precio_soles').value = prSoles || '';
            document.getElementById('edit_en_menu').checked = enMenu == 1;
            
            // Forzamos la apertura del modal
            var myModal = new bootstrap.Modal(document.getElementById('modalEditarCaminata'));
            myModal.show();
        }

        function confirmarDesvincular(id) {
            Swal.fire({
                title: '¿Desvincular?',
                text: "Se quitará de este destino.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, quitar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'tipos_tours.php?eliminar=' + id;
                }
            });
        }

        function toggleEnMenu(id, nuevoValor) {
            fetch("tipos_tours.php?ajax=toggle_en_menu", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "id=" + id + "&en_menu=" + nuevoValor
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    location.reload();
                } else {
                    Swal.fire("Error", data.error || "No se pudo actualizar", "error");
                }
            })
            .catch(() => Swal.fire("Error", "Error de conexión", "error"));
        }

    </script>
</body>
</html>