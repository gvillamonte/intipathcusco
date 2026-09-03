<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Obtener configuración de la fundación
$stmt = $db->prepare("SELECT * FROM fundacion WHERE id = 1");
$stmt->execute();
$fund = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$fund) {
    header("Location: index.php");
    exit;
}

// Obtener proyectos activos
$proyectos = $db->query("SELECT * FROM fundacion_proyectos WHERE activo = 1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/moneda_helper.php';

// SEO (después de header.php que define $idioma)
$titulo_seo = 'Fundación INTI PATH TOURS';
$desc_seo = 'Conoce la fundación de INTI PATH TOURS: turismo responsable y desarrollo sostenible en Cusco.';
if ($idioma == 'en') {
    $titulo_seo = 'INTI PATH TOURS Foundation';
    $desc_seo = 'Learn about the INTI PATH TOURS Foundation: responsible tourism and sustainable development in Cusco.';
}

// Textos bilingües
$hero_titulo = ($is_en && !empty($fund['titulo_en'])) ? $fund['titulo_en'] : $fund['titulo'];
$hero_sub = ($is_en && !empty($fund['subtitulo_en'])) ? $fund['subtitulo_en'] : $fund['subtitulo'];
$desc_texto = ($is_en && !empty($fund['descripcion_en'])) ? $fund['descripcion_en'] : $fund['descripcion'];
$mision_texto = ($is_en && !empty($fund['mision_en'])) ? $fund['mision_en'] : $fund['mision'];
$vision_texto = ($is_en && !empty($fund['vision_en'])) ? $fund['vision_en'] : $fund['vision'];
$valores_texto = ($is_en && !empty($fund['valores_en'])) ? $fund['valores_en'] : $fund['valores'];
$cita_texto = ($is_en && !empty($fund['cita_en'])) ? $fund['cita_en'] : $fund['cita'];
$diferente_texto = ($is_en && !empty($fund['diferente_en'])) ? $fund['diferente_en'] : $fund['diferente'];

// Ruta base de imágenes
$img_base = 'assets/img/fundacion/';
$hero_img = $img_base . ($fund['hero_imagen'] ?: 'default-hero.webp');
$logo_img = $img_base . ($fund['logo'] ?: 'default-logo.webp');
?>

<link rel="stylesheet" href="assets/css/fundacion.css<?= assetVersion('assets/css/fundacion.css') ?>">

<!-- HERO -->
<section class="fundacion-hero" style="background-image: url('<?= $hero_img ?>');">
    <h1><?= htmlspecialchars($hero_titulo) ?></h1>
    <p><?= htmlspecialchars($hero_sub) ?></p>
</section>

<!-- DESCRIPCIÓN + LOGO -->
<section class="fundacion-about">
    <div class="fundacion-about-texto">
        <h2><?= htmlspecialchars($hero_titulo) ?></h2>
        <h3><?= $is_en ? 'We Are Tourism and Sustainable Development' : 'Somos Turismo y Desarrollo Sostenible' ?></h3>
        <p><?= nl2br(htmlspecialchars($desc_texto)) ?></p>
    </div>
    <div class="fundacion-about-logo">
        <img src="<?= $logo_img ?>" alt="<?= htmlspecialchars($hero_titulo) ?>" loading="lazy">
    </div>
</section>

<!-- MISIÓN / VISIÓN / VALORES -->
<section class="fundacion-valores">
    <div class="valor-card">
        <div class="valor-icono">
            <i class="fas fa-bullseye"></i>
        </div>
        <h4><?= $is_en ? 'MISSION' : 'MISIÓN' ?></h4>
        <p><?= nl2br(htmlspecialchars($mision_texto)) ?></p>
    </div>
    <div class="valor-card">
        <div class="valor-icono">
            <i class="fas fa-eye"></i>
        </div>
        <h4><?= $is_en ? 'VISION' : 'VISIÓN' ?></h4>
        <p><?= nl2br(htmlspecialchars($vision_texto)) ?></p>
    </div>
    <div class="valor-card">
        <div class="valor-icono">
            <i class="fas fa-heart"></i>
        </div>
        <h4><?= $is_en ? 'VALUES' : 'VALORES' ?></h4>
        <p><?= nl2br(htmlspecialchars($valores_texto)) ?></p>
    </div>
</section>

<!-- PROYECTOS -->
<?php if (!empty($proyectos)): ?>
<section class="fundacion-proyectos-seccion">
    <div class="fundacion-proyectos-titulo">
        <h2><?= $is_en ? 'Our Projects' : 'Nuestros Proyectos' ?></h2>
        <div class="linea"></div>
    </div>
    <div class="fundacion-proyectos-grid">
        <?php foreach ($proyectos as $p):
            $p_titulo = ($is_en && !empty($p['titulo_en'])) ? $p['titulo_en'] : $p['titulo'];
            $p_desc = ($is_en && !empty($p['descripcion_en'])) ? $p['descripcion_en'] : $p['descripcion'];
            $p_img = $img_base . ($p['imagen'] ?: 'default-proyecto.webp');
            $tiene_enlace = !empty($p['slug_pagina']);
        ?>
        <div class="proyecto-card">
            <div class="proyecto-card-img">
                <img src="<?= $p_img ?>" alt="<?= htmlspecialchars($p_titulo) ?>" loading="lazy">
            </div>
            <div class="proyecto-card-body<?= $tiene_enlace ? '' : ' sin-enlace' ?>">
                <h4><?= htmlspecialchars($p_titulo) ?></h4>
                <?php if (!empty($p_desc)): ?>
                    <p><?= htmlspecialchars(mb_strimwidth($p_desc, 0, 120, '...')) ?></p>
                <?php endif; ?>
                <?php if ($tiene_enlace): ?>
                    <a href="fundacion/<?= urlencode($p['slug_pagina']) ?>">
                        <?= $is_en ? 'READ MORE' : 'LEER MÁS' ?> <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- CITA -->
<?php if (!empty($cita_texto)): ?>
<section class="fundacion-cita">
    <blockquote><?= nl2br(htmlspecialchars($cita_texto)) ?></blockquote>
</section>
<?php endif; ?>

<!-- ¿QUÉ HACE DIFERENTE? -->
<?php if (!empty($diferente_texto)): ?>
<section class="fundacion-diferente">
    <h2><?= $is_en ? 'What Makes These Trips Different?' : '¿Qué hace que estos viajes sean diferentes?' ?></h2>
    <p><?= nl2br(htmlspecialchars($diferente_texto)) ?></p>
    <div class="certificaciones">
        <img src="assets/img/certificaciones/ Lonely-Planet.png" alt="Lonely Planet" loading="lazy">
        <img src="assets/img/certificaciones/tripadvisor.png" alt="TripAdvisor" loading="lazy">
        <img src="assets/img/certificaciones/ travel-awards.png" alt="Travel Awards" loading="lazy">
        <img src="assets/img/certificaciones/mincetur.png" alt="MINCETUR" loading="lazy">
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
