<?php
require_once 'config/database.php';
require_once 'includes/moneda_helper.php';
$database = new Database();
$db = $database->getConnection();

$idioma = $_SESSION['lang'] ?? 'es';

// categoria.php - Línea 11 corregida
$id_get = isset($_GET['id']) ? $_GET['id'] : '';

// 1. Obtenemos el nombre de la categoría para el título
$stmt_cat = $db->prepare("SELECT nombre FROM categorias WHERE id = ?");
$stmt_cat->execute([$id_get]);
$cat_row = $stmt_cat->fetch(PDO::FETCH_ASSOC);
$titulo_categoria = ($cat_row) ? $cat_row['nombre'] : 'Tours';

// 2. Consulta corregida: Filtramos por id_categoria (la columna que creamos)
$query = "SELECT * FROM tours WHERE id_categoria = ? AND estado = 'activo' ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute([$id_get]);
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucwords($titulo_categoria) ?> | IntiPath Tours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/categoria.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="cat-banner">
        <div class="container">
            <h1 class="display-4 fw-bold text-uppercase"><?= $titulo_categoria ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white">Inicio</a></li>
                    <li class="breadcrumb-item active text-warning" aria-current="page"><?= ucwords($titulo_categoria) ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row g-4" id="tours-container">
            <?php if (count($tours) > 0): ?>
                <?php foreach ($tours as $t): ?>
                    <div class="col-md-4 tour-item">
                        <div class="tour-card">
                            <div class="tour-img-container">
                                <img src="assets/img/tours/<?= $t['imagen_principal'] ?>" alt="<?= $t['titulo'] ?>" loading="lazy">
                                <?php if($t['mostrar_precio']): ?>
                                    <div class="badge-price">
                                        <?= precioFormato($t, $idioma) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold"><?= mb_strtoupper($t['titulo_corto'] ?: $t['titulo']) ?></h5>
                                <div class="d-flex align-items-center mb-3 text-muted small">
                                    <i class="far fa-clock me-2 text-warning"></i> <?= $t['duracion'] ?>
                                </div>
                                <p class="card-text text-secondary">
                                    <?= substr($t['descripcion_corta'], 0, 90) ?>...
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <a href="detalle-tour.php?id=<?= $t['id'] ?>" class="btn-ver-tour">DETALLES</a>
                                    <?php if($t['es_recomendado']): ?>
                                        <span class="text-warning" title="Recomendado"><i class="fas fa-star"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <img src="assets/img/no-results.png" alt="Sin resultados" style="width: 150px; opacity: 0.5;">
                    <h3 class="mt-4 text-muted">No tenemos tours disponibles en esta categoría por ahora.</h3>
                    <a href="index.php" class="btn btn-outline-primary mt-3">Explorar otros destinos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/categoria.js"></script>
</body>
</html>