<?php
include 'includes/header.php';
include 'includes/moneda_helper.php';

// 1. CAPTURA DE FILTROS
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$cats_seleccionadas = isset($_GET['cats']) ? (array)$_GET['cats'] : [];
$precio_max = isset($_GET['precio_max']) ? (int)$_GET['precio_max'] : 3000;

// 2. CONSTRUCCIÓN DE LA CONSULTA SQL PROFESIONAL
$params = [];
$sql = "SELECT t.*, c.nombre as nombre_categoria 
        FROM tours t 
        LEFT JOIN categorias c ON t.id_categoria = c.id 
        WHERE t.estado = 'activo' AND t.parent_id IS NULL";

// Filtro por Precio Máximo
$sql .= " AND t.precio <= ?";
$params[] = $precio_max;

// Filtro por Búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (t.titulo LIKE ? OR t.descripcion_corta LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

// Filtro por Categorías (Dinamismo con IDs)
if (!empty($cats_seleccionadas)) {
    $placeholders = implode(',', array_fill(0, count($cats_seleccionadas), '?'));
    $sql .= " AND t.id_categoria IN ($placeholders)";
    foreach ($cats_seleccionadas as $cat_id) {
        $params[] = $cat_id;
    }
}

$sql .= " ORDER BY t.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listado real de categorías para el Sidebar
$listado_cats = $db->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --azul-corp: #0f9b9e;
        --naranja-corp: #c6d544;
        --naranja-hover: #0f9b9e;
        --gris-fondo: #f4f7f6;
        --blanco: #ffffff;
    }

    body { background-color: var(--gris-fondo); font-family: 'Poppins', sans-serif; margin: 0; }

    /* BANNER HERO SUPERIOR (NUEVO) */
    .ip-tours-hero {
        background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/img/Machu-Picchu.jpg'); 
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: var(--blanco);
        padding: 0 20px;
        margin-top: 80px; /* Ajuste para header fijo */
    }

    .ip-tours-hero h1 { font-size: 3rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; }
    .ip-tours-hero p { font-size: 1.4rem; font-weight: 300; max-width: 800px; }

    /* CONTENEDOR PRINCIPAL */
    .ip-tienda-container { padding: 60px 0; }

    .ip-section-header { text-align: center; margin-bottom: 50px; }
    .ip-section-header h2 { color: var(--azul-corp); font-weight: 800; font-size: 2.5rem; text-transform: uppercase; }
    .ip-header-line { width: 80px; height: 4px; background: var(--naranja-corp); margin: 15px auto; border-radius: 2px; }

    .ip-grid-shop {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 40px;
    }

    /* SIDEBAR DE FILTROS */
    .ip-sidebar {
        background: var(--blanco);
        padding: 35px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        height: fit-content;
        position: sticky;
        top: 110px;
    }

    .ip-filter-group { margin-bottom: 35px; }
    .ip-filter-group h3 { font-size: 1rem; font-weight: 700; color: var(--azul-corp); margin-bottom: 20px; text-transform: uppercase; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    
    .ip-check-item { display: flex; align-items: center; margin-bottom: 12px; cursor: pointer; font-size: 0.95rem; color: #555; }
    .ip-check-item input { margin-right: 12px; width: 18px; height: 18px; accent-color: var(--naranja-corp); }

    /* GRILLA DE TOURS */
    .ip-tours-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
    }

    .ip-tour-card {
        background: var(--blanco);
        border-radius: 25px;
        overflow: hidden;
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .ip-tour-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }

    .ip-card-img { height: 240px; position: relative; overflow: hidden; }
    .ip-card-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .ip-tour-card:hover .ip-card-img img { transform: scale(1.1); }

    /* BADGES */
    .ip-badge { position: absolute; top: 15px; left: 15px; padding: 6px 14px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; color: #fff; z-index: 5; }
    .ip-badge-rec { background: var(--naranja-corp); }
    .ip-badge-off { background: #e74c3c; right: 15px; left: auto; }

    /* CONTENIDO CENTRADO */
    .ip-card-body { padding: 25px; flex-grow: 1; text-align: center; } 
    .ip-card-cat { font-size: 0.75rem; color: var(--naranja-corp); font-weight: 700; text-transform: uppercase; margin-bottom: 10px; display: block; }
    .ip-card-title { font-size: 1.3rem; font-weight: 700; color: var(--azul-corp); margin-bottom: 15px; line-height: 1.2; height: 3.2em; overflow: hidden; }
    .ip-card-desc { font-size: 0.9rem; color: #777; margin-bottom: 20px; line-height: 1.6; }

    .ip-card-footer { padding: 20px 25px; border-top: 1px solid #f1f1f1; display: flex; justify-content: space-between; align-items: center; background: #fafafa; }
    
    .ip-price-box { text-align: left; }
    .ip-price-val { font-size: 1.4rem; font-weight: 800; color: var(--azul-corp); display: block; }
    .ip-price-lab { font-size: 0.7rem; color: #999; text-transform: uppercase; }

    .ip-btn-book { background: var(--azul-corp); color: #fff; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: 0.3s; }
    .ip-btn-book:hover { background: var(--naranja-corp); transform: scale(1.05); color: #fff; }

    /* RESPONSIVE */
   /* ================================================================
       DISEÑO RESPONSIVE (768px y Ajustes Finales)
       ================================================================ */

    /* Tablets y Pantallas Medianas (768px a 991px) */
    @media (max-width: 991px) {
        .ip-grid-shop { 
            grid-template-columns: 1fr; /* El sidebar pasa arriba */
            gap: 30px;
        }

        .ip-sidebar { 
            position: relative; 
            top: 0; 
            margin-bottom: 30px; 
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr; /* En tablet los filtros se ponen de a dos */
            gap: 20px;
        }

        .ip-filter-group { margin-bottom: 10px; }
        
        .ip-tours-hero h1 { font-size: 2.2rem; }
        .ip-tours-hero p { font-size: 1.1rem; }
        
        .ip-section-header h2 { font-size: 2rem; }
    }

    /* Ajuste específico para Celulares (767px hacia abajo) */
    @media (max-width: 767px) {
        .ip-sidebar {
            grid-template-columns: 1fr; /* En celular vuelve a ser una sola columna arriba */
        }

        .ip-tours-hero {
            height: 300px;
            padding-top: 100px;
        }

        .ip-tours-hero h1 { font-size: 1.8rem; }
        
        .ip-tours-grid {
            grid-template-columns: 1fr; /* Tarjetas ocupan todo el ancho */
        }

        .ip-tour-card {
            max-width: 400px;
            margin: 0 auto; /* Centrar tarjeta en móviles */
        }

        .ip-card-title {
            font-size: 1.2rem;
            height: auto; /* Que el título crezca libremente */
        }
    }

    /* Ajuste para pantallas muy pequeñas */
    @media (max-width: 480px) {
        .ip-tours-hero h1 { font-size: 1.5rem; }
        .ip-card-footer {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        .ip-price-box { text-align: center; }
        .ip-btn-book { width: 100%; text-align: center; }
    }
</style>

<section class="ip-tours-hero">
    <h1>Tu próxima aventura empieza aquí</h1>
    <p>Descubre Cusco y el Perú con tours diseñados a tu medida por expertos locales.</p>
</section>

<div class="ip-tienda-container">
    <div class="contenedor">
        
        <header class="ip-section-header">
            <h2>Nuestras Experiencias</h2>
            <div class="ip-header-line"></div>
            <p class="text-muted">Explora los mejores destinos y caminatas guiadas.</p>
        </header>

        <div class="ip-grid-shop">
            <aside class="ip-sidebar">
                <form action="tours.php" method="GET" id="form-filtros">
                    <div class="ip-filter-group">
                        <h3>Buscar</h3>
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Ej: Inca Trail" value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="ip-filter-group">
                        <h3>Categorías</h3>
                        <?php foreach ($listado_cats as $cat): ?>
                            <label class="ip-check-item">
                                <input type="checkbox" name="cats[]" value="<?= $cat['id'] ?>" 
                                    <?= in_array($cat['id'], $cats_seleccionadas) ? 'checked' : '' ?>
                                    onchange="this.form.submit()">
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="ip-filter-group">
                        <h3>Precio Máximo: <span class="text-primary">$<?= $precio_max ?></span></h3>
                        <input type="range" name="precio_max" class="form-range" min="0" max="4000" step="50" 
                               value="<?= $precio_max ?>" onchange="this.form.submit()" 
                               oninput="this.previousElementSibling.querySelector('span').innerText = '$' + this.value">
                    </div>

                    <a href="tours.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-sync-alt me-1"></i> Ver todo</a>
                </form>
            </aside>

            <section>
                <div class="ip-tours-grid">
                    <?php if (!empty($tours)): ?>
                        <?php foreach ($tours as $t): ?>
                            <article class="ip-tour-card">
                                <?php if($t['es_recomendado']): ?><span class="ip-badge ip-badge-rec">⭐ RECOMENDADO</span><?php endif; ?>
                                <?php if($t['en_oferta']): ?><span class="ip-badge ip-badge-off">🔥 OFERTA</span><?php endif; ?>

                                <div class="ip-card-img">
                                    <a href="detalle_tour.php?id=<?= $t['id'] ?>">
<img src="assets/img/tours/<?= $t['imagen_principal'] ?>" alt="<?= htmlspecialchars($t['titulo']) ?>" loading="lazy" onerror="this.src='assets/img/Machu-Picchu.jpg'">
                                    </a>
                                </div>

                                <div class="ip-card-body">
                                    <span class="ip-card-cat"><?= htmlspecialchars($t['nombre_categoria'] ?: 'Aventura') ?></span>
                                    <h3 class="ip-card-title"><?= htmlspecialchars($t['titulo']) ?></h3>
                                    <p class="ip-card-desc"><?= mb_strimwidth($t['descripcion_corta'], 0, 100, "...") ?></p>
                                    
                                    <div class="d-flex justify-content-center gap-3 text-muted small mt-auto border-top pt-3">
                                        <span><i class="far fa-clock me-1 text-primary"></i> <?= $t['duracion'] ?></span>
                                        <span><i class="fas fa-mountain me-1 text-primary"></i> <?= $t['altitud_max'] ?></span>
                                    </div>
                                </div>

                                <div class="ip-card-footer">
                                    <div class="ip-price-box">
                                        <span class="ip-price-lab">Desde</span>
                                        <span class="ip-price-val">
                                            <?= simboloMoneda($idioma) . number_format(montoMoneda($t, $idioma), 0) ?>
                                        </span>
                                    </div>
                                    <a href="detalle_tour.php?id=<?= $t['id'] ?>" class="ip-btn-book">VER TOUR</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 w-100" style="grid-column: 1 / -1;">
                            <i class="fas fa-search-minus fa-5x text-muted opacity-25 mb-4"></i>
                            <h3 class="text-muted">No se encontraron tours con estos filtros</h3>
                            <p>Intenta buscando con otros términos o limpiando los filtros.</p>
                            <a href="tours.php" class="btn btn-primary mt-3">Ver todos los tours</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>