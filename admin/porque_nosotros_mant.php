<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('nosotros');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensaje = "";

// --- LÓGICA: AGREGAR NUEVO BLOQUE ---
if (isset($_POST['agregar_nuevo'])) {
    $sql = "INSERT INTO porque_nosotros (titulo, titulo_en, descripcion, descripcion_en, imagen, orden) VALUES ('Nuevo Título', 'New Title', 'Descripción aquí...', 'Description here...', '', 0)";
    $db->query($sql);
    // Redirigimos con res=created para el JS
    header("Location: porque_nosotros_mant.php?res=created");
    exit;
}

// --- LÓGICA: ELIMINAR BLOQUE ---
if (isset($_GET['eliminar'])) {
    $id_del = $_GET['eliminar'];
    $stmt = $db->prepare("SELECT imagen FROM porque_nosotros WHERE id = ?");
    $stmt->execute([$id_del]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../assets/img/iconos/" . $img)) { @unlink("../assets/img/iconos/" . $img); }

    $stmt = $db->prepare("DELETE FROM porque_nosotros WHERE id = ?");
    $stmt->execute([$id_del]);
    header("Location: porque_nosotros_mant.php?res=deleted");
    exit;
}

// --- LÓGICA: GUARDAR CAMBIOS MASIVOS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_porque'])) {
    foreach ($_POST['ids'] as $id) {
        $titulo      = $_POST['titulo_' . $id];
        $titulo_en   = $_POST['titulo_en_' . $id];
        $descripcion = $_POST['desc_' . $id];
        $desc_en     = $_POST['desc_en_' . $id];
        $orden       = $_POST['orden_' . $id];
        $color       = $_POST['color_' . $id];
        $img_actual  = $_POST['img_actual_' . $id];

        if (isset($_FILES['img_' . $id]) && $_FILES['img_' . $id]['error'] == 0) {
            $ext = pathinfo($_FILES['img_' . $id]['name'], PATHINFO_EXTENSION);
            $nombre_img = "pq_" . $id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['img_' . $id]['tmp_name'], "../assets/img/iconos/" . $nombre_img)) {
                if (!empty($img_actual) && file_exists("../assets/img/iconos/" . $img_actual)) { @unlink("../assets/img/iconos/" . $img_actual); }
                $img_actual = $nombre_img;
            }
        }
        $sql = "UPDATE porque_nosotros SET titulo = ?, titulo_en = ?, descripcion = ?, descripcion_en = ?, imagen = ?, color = ?, orden = ? WHERE id = ?";
        $db->prepare($sql)->execute([$titulo, $titulo_en, $descripcion, $desc_en, $img_actual, $color, $orden, $id]);
    }
    // Redirigimos con res=updated para el JS
    header("Location: porque_nosotros_mant.php?res=updated");
    exit;
}

$items = $db->query("SELECT * FROM porque_nosotros ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Confianza | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/admin_porque_nosotros.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content-confianza p-4">
            <div class="confianza-header d-flex justify-content-between align-items-center mb-4">
                <h2 class="confianza-titulo"><i class="fas fa-shield-alt"></i> Porque trabajar con nosotros</h2>
                <div class="confianza-acciones d-flex gap-2">
                    <form method="POST">
                        <button type="submit" name="agregar_nuevo" class="btn btn-confianza-success">
                            <i class="fas fa-plus"></i> AGREGAR BLOQUE
                        </button>
                    </form>
                    <a href="tours.php" class="btn btn-confianza-azul">VOLVER A TOURS</a>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                    <?= $mensaje ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="confianza-lista-bloques">
                    <?php 
                    $i = 1; // Inicializamos el contador correlativo
                    foreach ($items as $item): 
                    ?>
                        <div class="confianza-card mb-4 shadow-sm border-0">
                            <div class="confianza-card-header d-flex justify-content-between align-items-center">
                                <span class="confianza-badge">BLOQUE #<?= $i ?></span>
                                <button type="button" onclick="confirmarEliminar(<?= $item['id'] ?>)" class="btn-confianza-delete">
                                    <i class="fas fa-trash-alt"></i> Eliminar Bloque
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <input type="hidden" name="ids[]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="img_actual_<?= $item['id'] ?>" value="<?= $item['imagen'] ?>">

                                <div class="row g-4 align-items-center">
                                    <div class="col-md-2 text-center border-end">
                                        <div class="confianza-preview-box mb-3">
                                            <?php if($item['imagen']): ?>
                                                <img src="../assets/img/iconos/<?= $item['imagen'] ?>" class="confianza-img-preview">
                                            <?php else: ?>
                                                <i class="fas fa-image fa-3x text-light"></i>
                                            <?php endif; ?>
                                        </div>
                                        <label class="confianza-label-file">Cambiar Icono</label>
                                        <input type="file" name="img_<?= $item['id'] ?>" class="form-control form-control-sm" accept="image/*">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="confianza-label">Título (ES)</label>
                                        <input type="text" name="titulo_<?= $item['id'] ?>" class="form-control confianza-input mb-2" value="<?= htmlspecialchars($item['titulo']) ?>" required>
                                        
                                        <label class="confianza-label">Título (EN)</label>
                                        <input type="text" name="titulo_en_<?= $item['id'] ?>" class="form-control confianza-input mb-2" value="<?= htmlspecialchars($item['titulo_en'] ?? '') ?>" required>

                                        <div class="row g-2 mt-2">
                                            <div class="col-6">
                                                <label class="confianza-label">Orden</label>
                                                <input type="number" name="orden_<?= $item['id'] ?>" class="form-control confianza-input" value="<?= $item['orden'] ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="confianza-label">Color Icono</label>
                                                <input type="color" name="color_<?= $item['id'] ?>" class="form-control form-control-color w-100" value="<?= $item['color'] ?? '#15305D' ?>" title="Elegir color">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-7">
                                        <label class="confianza-label">Descripción (ES)</label>
                                        <textarea name="desc_<?= $item['id'] ?>" class="form-control confianza-input mb-2" rows="2" required><?= htmlspecialchars($item['descripcion']) ?></textarea>
                                        
                                        <label class="confianza-label">Descripción (EN)</label>
                                        <textarea name="desc_en_<?= $item['id'] ?>" class="form-control confianza-input" rows="2" required><?= htmlspecialchars($item['descripcion_en'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                    $i++; // Incrementamos el contador para el siguiente bloque
                    endforeach; 
                    ?>
                </div>

                <div class="confianza-footer-sticky text-end mt-4">
                    <button type="submit" name="actualizar_porque" class="btn btn-confianza-save shadow-lg">
                        <i class="fas fa-save"></i> GUARDAR TODOS LOS CAMBIOS
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/admin_porque_nosotros.js"></script>
</body>
</html>