<?php
include 'includes/header.php';
$idioma = $_SESSION['lang'] ?? 'es';

function fmtPlano($t) {
    if (!$t) return '';
    $t = preg_replace('/^# (.*)$/m', '<h2 class="tit-referencia-verde">$1</h2>', $t);
    $t = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $t);
    $t = preg_replace('/_(.*?)_/', '<u style="text-decoration: underline;">$1</u>', $t);
    $t = preg_replace('/^- (.*)$/m', '<div class="check-item"><i class="fas fa-check"></i> $1</div>', $t);
    return nl2br($t);
}

$page = $db->query("CALL obtener_contenido_info_previa('$idioma')")->fetch(PDO::FETCH_ASSOC);

$btit = $page['banner_titulo'] ?? '';
$bsub = $page['banner_subtitulo'] ?? '';
$itxt = $page['intro_texto'] ?? '';
$atxt = $page['aside_texto'] ?? '';
$abtn = $page['aside_btn'] ?? '';
$aimg = $page['aside_imagen'] ?? '';
$ctatit = $page['cta_titulo'] ?? '';
$ctatxt = $page['cta_texto'] ?? '';
$ctabtn = $page['cta_btn'] ?? '';
$ctabg = "assets/img/info-previa/" . ($page['cta_imagen'] ?? 'default.jpg');
$intro_img = $page['intro_imagen'] ?? 'banner.jpg';
$color_titulo = $page['color_titulo'] ?? '';
$tamano_titulo = $page['tamano_titulo'] ?? '';
$color_texto = $page['color_texto'] ?? '';
$tamano_texto = $page['tamano_texto'] ?? '';

// Testimonial fields (not in procedure)
$page_full = $db->query("SELECT * FROM info_previa WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$test_tit = ($idioma == 'en') ? ($page_full['aside_test_tit_en'] ?? '') : ($page_full['aside_test_tit'] ?? '');
$test_txt = ($idioma == 'en') ? ($page_full['aside_test_txt_en'] ?? '') : ($page_full['aside_test_txt'] ?? '');
$test_img = $page_full['aside_test_img'] ?? '';
$test_fecha = ($idioma == 'en') ? ($page_full['aside_test_fecha_en'] ?? '') : ($page_full['aside_test_fecha'] ?? '');
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #fefefe; }
    
    .hero-info-previa {
        height: 60vh; min-height: 450px; 
        display: flex; align-items: center; justify-content: center; text-align: center; color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), 
                    url('assets/img/info-previa/<?= $intro_img ?>');
        background-size: cover; background-position: center;
    }
    .hero-info-previa h1 { 
        font-size: <?= !empty($tamano_titulo) ? $tamano_titulo : '4rem' ?>; font-weight: 900; text-transform: uppercase; margin: 0; 
        line-height: 1.1; text-shadow: 2px 4px 15px rgba(0,0,0,0.5);
        <?php if(!empty($color_titulo)): ?>
        color: <?= $color_titulo ?>;
        <?php endif; ?>
    }
    .hero-info-previa .sub-banner {
        font-size: 1.3rem; font-weight: 300; text-transform: uppercase; 
        letter-spacing: 6px; margin-top: 15px; color: #f1f1f1;
    }

    .info-previa-wrapper { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 350px; gap: 50px; }
    
    .tit-referencia-verde { 
        <?php if(!empty($color_titulo)): ?>color: <?= $color_titulo ?>;<?php else: ?>color: var(--esmeralda);<?php endif; ?>
        font-size: <?= !empty($tamano_titulo) ? $tamano_titulo : '2.5rem' ?>; font-weight: 800; margin-bottom: 15px; 
    }
    .check-item { 
        display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; 
        <?php if(!empty($color_texto)): ?>color: <?= $color_texto ?>;<?php else: ?>color: #444;<?php endif; ?>
        font-size: <?= !empty($tamano_texto) ? $tamano_texto : '1.1rem' ?>;
    }
    .check-item i { color: var(--dark); margin-top: 5px; font-size: 0.9rem; }

    .linea-limon { height: 5px; background: var(--limon); width: 80px; margin-bottom: 30px; }

    .sidebar-info-previa { background: #fff; padding: 40px 30px; border-radius: 25px; text-align: center; border: 1px solid #eee; position: sticky; top: 100px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .sidebar-info-previa > img:first-of-type { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 5px solid #f8f9fa; }
    .btn-consultar-inti { display: block; padding: 16px; background: var(--esmeralda); color: #fff; text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; transition: 0.3s; margin-bottom: 30px; }
    .btn-consultar-inti:hover { background: var(--limon); color: var(--dark); transform: translateY(-3px); }

    .side-testimonial { margin-top: 40px; text-align: left; border-top: 1px solid #eee; padding-top: 30px; }
    .side-testimonial img { width: 100%; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .side-testimonial h4 { font-size: 1.1rem; color: var(--esmeralda); margin-bottom: 12px; display: inline-block; border-bottom: 3px solid var(--limon); padding-bottom: 4px; font-weight: 800; text-transform: uppercase; }
    .side-testimonial p { font-size: 0.9rem; color: #666; line-height: 1.6; font-style: italic; margin-bottom: 10px; text-align: justify; }
    .side-testimonial small { color: #bbb; display: block; margin-bottom: 20px; font-weight: bold; text-transform: uppercase; }
    .side-testimonial a { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border: 1px solid #ddd; border-radius: 50px; text-decoration: none; color: var(--esmeralda); font-weight: 800; font-size: 0.85rem; transition: 0.3s; background: #fff; }

    .section-cta-info {
        padding: 100px 20px; text-align: center; color: #fff;
        background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $ctabg ?>');
        background-size: cover; background-attachment: fixed;
    }
    .btn-cta-info { background-color: var(--esmeralda); color: white; padding: 18px 45px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
    .btn-cta-info:hover { background-color: var(--limon); color: #fff; }
    
    @media (max-width: 992px) { 
        .info-previa-wrapper { grid-template-columns: 1fr; } 
        .hero-info-previa h1 { font-size: 2.5rem; }
    }
    @media (max-width: 480px) {
        .hero-info-previa { height: 250px; }
        .hero-info-previa h1 { font-size: 1.8rem; }
        .hero-info-previa .sub-banner { font-size: 0.9rem; letter-spacing: 3px; }
        .info-previa-wrapper { margin: 30px auto; gap: 30px; }
        .tit-referencia-verde { font-size: 1.8rem; }
        .section-cta-info { padding: 60px 15px; }
        .section-cta-info h2 { font-size: 1.4rem; }
        .section-cta-info p { font-size: 0.9rem; }
    }
</style>

<section class="hero-info-previa">
    <div class="container">
        <h1><?= htmlspecialchars($btit) ?></h1>
        <?php if(!empty($bsub)): ?>
            <p class="sub-banner"><?= htmlspecialchars($bsub) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="info-previa-wrapper">
    <main>
        <div class="linea-limon"></div>
        <div class="intro-content"><?= fmtPlano($itxt) ?></div>
    </main>

    <aside class="sidebar-info-previa">
        <?php if(!empty($aimg)): ?>
            <img src="assets/img/info-previa/<?= $aimg ?>" alt="Especialista" loading="lazy">
        <?php endif; ?>
        <p><?= fmtPlano($atxt) ?></p>
        <?php if(!empty($abtn)): ?>
            <a href="contacto.php" class="btn-consultar-inti"><?= htmlspecialchars($abtn) ?></a>
        <?php endif; ?>

        <?php if(!empty($test_tit)): ?>
        <div class="side-testimonial">
            <?php if(!empty($test_img)): ?>
                <img src="assets/img/info-previa/<?= $test_img ?>" alt="Experience" loading="lazy">
            <?php endif; ?>
            <h4><?= htmlspecialchars($test_tit) ?></h4>
            <p>"<?= htmlspecialchars($test_txt) ?>"</p>
            <small><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($test_fecha) ?></small>
            <a href="testimonios.php"><i class="far fa-thumbs-up"></i> <?= $idioma == 'en' ? 'VIEW MORE REVIEWS' : 'VER MÁS TESTIMONIOS'; ?></a>
        </div>
        <?php endif; ?>
    </aside>
</div>

<?php if(!empty($ctatit)): ?>
<section class="section-cta-info">
    <h2><?= htmlspecialchars($ctatit) ?></h2>
    <p><?= htmlspecialchars($ctatxt) ?></p>
    <?php if(!empty($ctabtn)): ?>
        <a href="contacto.php" class="btn-cta-info"><?= htmlspecialchars($ctabtn) ?></a>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>