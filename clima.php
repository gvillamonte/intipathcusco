<?php
include 'includes/header.php';
$idioma = $_SESSION['lang'] ?? 'es';

function fmtPlano($t) {
    if (!$t) return '';
    $t = preg_replace('/^# (.*)$/m', '<h2 class="tit-referencia-verde">$1</h2>', $t);
    $t = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $t);
    $t = preg_replace('/^- (.*)$/m', '<div class="check-item"><i class="fas fa-check"></i> $1</div>', $t);
    return nl2br($t);
}

$page = $db->query("CALL obtener_contenido_clima('$idioma')")->fetch(PDO::FETCH_ASSOC);

$btit = $page['banner_titulo'] ?? 'El Clima en Cusco';
$bsub = $page['banner_subtitulo'] ?? '';
$itxt = $page['intro_texto'] ?? '';
$atxt = $page['aside_texto'] ?? '';
$abtn = $page['aside_btn'] ?? '';
$aimg = $page['aside_imagen'] ?? '';
$ctatit = $page['cta_titulo'] ?? '';
$ctatxt = $page['cta_texto'] ?? '';
$ctabtn = $page['cta_btn'] ?? '';
$ctabg = "assets/img/clima/" . ($page['cta_imagen'] ?? 'default.jpg');
$intro_img = $page['intro_imagen'] ?? 'banner.jpg';
$color_titulo = $page['color_titulo'] ?? '';
$tamano_titulo = $page['tamano_titulo'] ?? '';
$color_texto = $page['color_texto'] ?? '';
$tamano_texto = $page['tamano_texto'] ?? '';
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #fefefe; }
    .hero-clima { height: 60vh; min-height: 450px; display: flex; align-items: center; justify-content: center; text-align: center; color: #fff; background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('assets/img/clima/<?= $intro_img ?>'); background-size: cover; background-position: center; }
    .hero-clima h1 { font-size: 4rem; font-weight: 900; text-transform: uppercase; margin: 0; line-height: 1.1; text-shadow: 2px 4px 15px rgba(0,0,0,0.5); }
    .hero-clima .sub-banner { font-size: 1.3rem; font-weight: 300; text-transform: uppercase; letter-spacing: 6px; margin-top: 15px; color: #f1f1f1; }
    .clima-wrapper { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 350px; gap: 50px; }
    .tit-referencia-verde { 
        <?php if(!empty($color_titulo)): ?>color: <?= $color_titulo ?>;<?php else: ?>color: var(--esmeralda);<?php endif; ?> font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; }
    .check-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; color: #444; font-size: 1.1rem; }
    .check-item i { color: var(--dark); margin-top: 5px; font-size: 0.9rem; }
    .linea-limon { height: 5px; background: var(--limon); width: 80px; margin-bottom: 30px; }
    .sidebar-clima { background: #fff; padding: 40px 30px; border-radius: 25px; text-align: center; border: 1px solid #eee; position: sticky; top: 100px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .sidebar-clima img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 3px solid var(--esmeralda); }
    .btn-consultar-inti { display: block; padding: 16px; background: var(--esmeralda); color: #fff; text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; transition: 0.3s; }
    .btn-consultar-inti:hover { background: var(--limon); color: var(--dark); transform: translateY(-3px); }
    .section-cta-clima { padding: 100px 20px; text-align: center; color: #fff; background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $ctabg ?>'); background-size: cover; background-attachment: fixed; }
    .btn-cta-clima { background-color: var(--esmeralda); color: white; padding: 18px 45px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
    .btn-cta-clima:hover { background-color: var(--limon); color: #fff; }
    @media (max-width: 992px) { .clima-wrapper { grid-template-columns: 1fr; } .hero-clima h1 { font-size: 2.5rem; } }
    @media (max-width: 480px) { .hero-clima { height: 250px; } .hero-clima h1 { font-size: 1.8rem; } .hero-clima .sub-banner { font-size: 0.9rem; letter-spacing: 3px; } .clima-wrapper { margin: 30px auto; gap: 30px; } .tit-referencia-verde { font-size: 1.8rem; } .section-cta-clima { padding: 60px 15px; } .section-cta-clima h2 { font-size: 1.4rem; } .section-cta-clima p { font-size: 0.9rem; } }
</style>

<section class="hero-clima">
    <div class="container">
        <h1><?= htmlspecialchars($btit) ?></h1>
        <?php if(!empty($bsub)): ?><p class="sub-banner"><?= htmlspecialchars($bsub) ?></p><?php endif; ?>
    </div>
</section>

<div class="clima-wrapper">
    <main>
        <div class="linea-limon"></div>
        <div class="intro-content"><?= fmtPlano($itxt) ?></div>
    </main>
    <aside class="sidebar-clima">
        <?php if(!empty($aimg)): ?><img src="assets/img/clima/<?= $aimg ?>" alt="Especialista" loading="lazy"><?php endif; ?>
        <?php if(!empty($atxt)): ?><div class="aside-content"><?= fmtPlano($atxt) ?></div><?php endif; ?>
        <?php if(!empty($abtn)): ?><a href="contacto.php" class="btn-consultar-inti"><?= htmlspecialchars($abtn) ?></a><?php endif; ?>
    </aside>
</div>

<?php if(!empty($ctatit)): ?>
<section class="section-cta-clima">
    <h2><?= htmlspecialchars($ctatit) ?></h2>
    <p><?= htmlspecialchars($ctatxt) ?></p>
    <?php if(!empty($ctabtn)): ?><a href="contacto.php" class="btn-cta-clima"><?= htmlspecialchars($ctabtn) ?></a><?php endif; ?>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>