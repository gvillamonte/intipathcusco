<?php
/**
 * preguntas_frecuentes.php
 * Gestión de FAQs Bilingües - IntiPath Tours
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('faqs');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$edit_mode = false;
$datos = [
    'id'           => '',
    'pregunta'     => '',
    'pregunta_en'  => '',
    'respuesta'    => '',
    'respuesta_en' => '',
    'orden'        => 0,
    'estado'       => 1
];

// --- 1. CARGA DE DATOS PARA EDICIÓN ---
if (isset($_GET['editar'])) {
    $edit_mode = true;
    $stmt = $db->prepare("SELECT * FROM preguntas_frecuentes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) { $datos = array_merge($datos, $res); }
}

$faqs = $db->query("SELECT * FROM preguntas_frecuentes ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin FAQs | IntiPath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --p-blue: #15305D; --s-orange: #E8AC18; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .lang-en-field { border-left: 5px solid #0d6efd !important; background-color: #f0f7ff !important; }
        .tou-card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); background: #fff; padding: 25px; margin-bottom: 30px; }
        .nav-tabs .nav-link { color: #666; border: none; padding: 15px 20px; font-weight: 700; transition: 0.3s; }
        .nav-tabs .nav-link.active { color: var(--p-blue); border-bottom: 3px solid var(--p-blue); background: none; }
    </style>
</head>
<body>
<div class="admin-wrapper d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <main class="tou-main-content p-4 w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><?= $edit_mode ? "🔧 Editar FAQ" : "❓ Nueva Pregunta Frecuente" ?></h2>
                <p class="text-muted small">Administre las dudas comunes de sus viajeros en español e inglés.</p>
            </div>
            <?php if ($edit_mode): ?>
                <a href="preguntas_frecuentes.php" class="btn btn-primary rounded-pill px-4"><i class="fas fa-plus me-2"></i> Crear Nuevo</a>
            <?php endif; ?>
        </div>

        <form action="procesar_faq.php" method="POST">
            <input type="hidden" name="id" value="<?= $datos['id'] ?>">

            <div class="tou-card">
                <ul class="nav nav-tabs mb-4" id="faqTab" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-es" type="button">🇪🇸 Versión Español</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-en" type="button">🇺🇸 English Version</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-conf" type="button">⚙️ Configuración</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-es">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pregunta (ES)</label>
                            <input type="text" name="pregunta" class="form-control" value="<?= htmlspecialchars($datos['pregunta']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Respuesta (ES)</label>
                            <textarea name="respuesta" class="form-control" rows="6" required><?= htmlspecialchars($datos['respuesta']) ?></textarea>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-en">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">Question (EN)</label>
                            <input type="text" name="pregunta_en" class="form-control lang-en-field" value="<?= htmlspecialchars($datos['pregunta_en']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">Answer (EN)</label>
                            <textarea name="respuesta_en" class="form-control lang-en-field" rows="6"><?= htmlspecialchars($datos['respuesta_en']) ?></textarea>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-conf">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Orden de visualización</label>
                                <input type="number" name="orden" class="form-control" value="<?= $datos['orden'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="1" <?= $datos['estado']==1?'selected':'' ?>>Visible en la web</option>
                                    <option value="0" <?= $datos['estado']==0?'selected':'' ?>>Oculto temporalmente</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5 py-2 rounded-pill fw-bold shadow">
                        <i class="fas fa-save me-2"></i> <?= $edit_mode ? "ACTUALIZAR PREGUNTA" : "PUBLICAR PREGUNTA" ?>
                    </button>
                </div>
            </div>
        </form>

        <h4 class="fw-bold mb-3 mt-5">Preguntas Registradas</h4>
        <div class="tou-card overflow-hidden p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4" width="80">Orden</th>
                            <th>Pregunta</th>
                            <th>Traducción (EN)</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-4" width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($faqs as $f): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= $f['orden'] ?></td>
                            <td><span class="fw-bold text-dark"><?= $f['pregunta'] ?></span></td>
                            <td><span class="text-muted small"><?= $f['pregunta_en'] ?: '<em>Sin traducción</em>' ?></span></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $f['estado'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $f['estado'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm">
                                    <a href="preguntas_frecuentes.php?editar=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <button onclick="confirmarBorrado(<?= $f['id'] ?>)" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmarBorrado(id) {
        Swal.fire({ 
            title: '¿Borrar Pregunta?', 
            text: "Esta acción eliminará la FAQ de ambos idiomas.", 
            icon: 'error', 
            showCancelButton: true, 
            confirmButtonText: 'Sí, borrar' 
        }).then((res) => { 
            if(res.isConfirmed) window.location.href = `procesar_faq.php?eliminar=${id}`; 
        });
    }

    const params = new URLSearchParams(window.location.search);
    if(params.get('res') === 'success') {
        Swal.fire('¡Listo!', 'La información se actualizó correctamente.', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>
</body>
</html>