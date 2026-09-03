<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('info_viaje');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$edit_mode = false;
$datos_edit = ['id' => '', 'tour_id' => '', 'titulo' => '', 'enlace' => '#'];

if (isset($_GET['editar'])) {
    $edit_mode = true;
    $stmt = $db->prepare("SELECT * FROM info_viaje WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) { $datos_edit = $res; }
}

// Consultas para la tabla (Usando Título Corto)
$infos = $db->query("SELECT iv.*, t.titulo_corto, t.titulo 
                     FROM info_viaje iv 
                     JOIN tours t ON iv.tour_id = t.id 
                     ORDER BY iv.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$tours = $db->query("SELECT id, titulo, titulo_corto FROM tours WHERE estado = 'activo' ORDER BY titulo_corto ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Info de Viaje | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_info.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <main class="inf-main-content">
            <header class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="inf-title"><?= $edit_mode ? "🔧 Editar Tarjeta" : "Gestión de Tarjetas Informativas" ?></h2>
                    <p class="text-muted">Cuadro 2 del Mega Menú (Máximo 3 por tour).</p>
                </div>
                <?php if($edit_mode): ?>
                    <a href="info_viaje.php" class="btn btn-secondary btn-sm">Cancelar Edición</a>
                <?php endif; ?>
            </header>

            <section class="inf-card shadow-sm mb-5 border-0">
                <form action="procesar_info.php" method="POST" enctype="multipart/form-data" class="row g-3 p-4">
                    <input type="hidden" name="id_info" value="<?= $datos_edit['id'] ?>">

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tour (Título Corto):</label>
                        <select name="tour_id" class="form-select border-2" required>
                            <option value="">-- Seleccione un Tour --</option>
                            <?php foreach ($tours as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= ($t['id'] == $datos_edit['tour_id']) ? 'selected' : '' ?>>
                                    <?= mb_strtoupper($t['titulo_corto'] ?: $t['titulo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Título de la Card:</label>
                        <input type="text" name="titulo" class="form-control border-2" value="<?= $datos_edit['titulo'] ?>" placeholder="Ej: CLIMA" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Imagen <?= $edit_mode ? "(Opcional)" : "(Requerida)" ?>:</label>
                        <input type="file" name="imagen" class="form-control border-2" accept="image/*" <?= $edit_mode ? "" : "required" ?>>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Enlace (#):</label>
                        <input type="text" name="enlace" class="form-control border-2" value="<?= $datos_edit['enlace'] ?>" required>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" name="guardar_info" class="btn btn-primary w-100 py-3 fw-bold shadow">
                            <i class="fas <?= $edit_mode ? "fa-sync-alt" : "fa-save" ?> me-2"></i> 
                            <?= $edit_mode ? "ACTUALIZAR TARJETA" : "PUBLICAR TARJETA" ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="inf-card shadow-sm border-0 p-3">
                <table class="table table-hover align-middle border">
                    <thead class="table-dark">
                        <tr>
                            <th>Imagen</th>
                            <th>Tour (Identificador)</th>
                            <th>Título Card</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($infos as $i): ?>
                        <tr>
                            <td><img src="../assets/img/info/<?= $i['imagen'] ?>" class="rounded border shadow-sm" style="width: 80px; height: 50px; object-fit: cover;"></td>
                            <td><span class="badge bg-primary px-3"><?= mb_strtoupper($i['titulo_corto'] ?: $i['titulo']) ?></span></td>
                            <td class="fw-bold"><?= $i['titulo'] ?></td>
                            <td class="text-center">
                                <a href="info_viaje.php?editar=<?= $i['id'] ?>" class="btn btn-sm btn-info text-white"><i class="fas fa-edit"></i></a>
                                <button type="button" onclick="confirmarEliminarInfo(<?= $i['id'] ?>)" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin_info.js"></script>
</body>
</html>