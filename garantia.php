<?php
include 'includes/header.php';
require_once 'config/database.php';
$db = (new Database())->getConnection();

$page = $db->query("SELECT * FROM garantia WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$imagenes = $db->query("SELECT * FROM garantia_imagenes ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$idioma = $_SESSION['lang'] ?? 'es';

function fmtPlano($t) {
    if (!$t) return '';
    $t = preg_replace('/^# (.+)$/m', '<h2 class="tit-referencia-verde">$1</h2>', $t);
    $t = preg_replace('/^- (.+)$/m', '<div class="check-item"><i class="fas fa-check"></i> $1</div>', $t);
    $parrafos = explode("\n", trim($t));
    $html = '';
    foreach($parrafos as $p) {
        $p = trim($p);
        if(empty($p)) continue;
        if(substr($p, 0, 1) === '<') {
            $html .= $p;
        } else {
            $html .= '<p>'.$p.'</p>';
        }
    }
    return $html;
}

$btit = ($idioma == 'en') ? $page['banner_titulo_en'] : $page['banner_titulo'];
$bsub = ($idioma == 'en') ? $page['banner_subtitulo_en'] : $page['banner_subtitulo'];
$introTit = ($idioma == 'en') ? $page['intro_titulo_en'] : $page['intro_titulo'];
$introTxt = ($idioma == 'en') ? $page['intro_texto_en'] : $page['intro_texto'];
$ctaTit = ($idioma == 'en') ? $page['cta_titulo_en'] : $page['cta_titulo'];
$ctaTxt = ($idioma == 'en') ? $page['cta_texto_en'] : $page['cta_texto'];
$ctaBtn = ($idioma == 'en') ? $page['cta_btn_en'] : $page['cta_btn'];
$ctaBg = "assets/img/" . ($page['cta_imagen'] ?? 'hero_tours.jpg');

$aTxt = ($idioma == 'en') ? $page['aside_texto_en'] : $page['aside_texto'];
$aBtn = ($idioma == 'en') ? $page['aside_btn_en'] : $page['aside_btn'];
$aImg = $page['aside_imagen'] ?? '';
$testTit = ($idioma == 'en') ? $page['aside_test_tit_en'] : $page['aside_test_tit'];
$testTxt = ($idioma == 'en') ? $page['aside_test_txt_en'] : $page['aside_test_txt'];
$testImg = $page['aside_test_img'] ?? '';
$testFecha = ($idioma == 'en') ? $page['aside_test_fecha_en'] : $page['aside_test_fecha'];

$titSize = $page['banner_titulo_size'] ?? 64;
$subSize = $page['banner_subtitulo_size'] ?? 24;
$introTitSize = $page['intro_titulo_size'] ?? 40;

$bannerColorTitulo = $page['banner_color_titulo'] ?? '#0f9b9e';
$bannerColorSub = $page['banner_color_subtitulo'] ?? '#f1f1f1';
$introColorTitulo = $page['intro_color_titulo'] ?? '#0f9b9e';
$introColorTexto = $page['intro_color_texto'] ?? '#555555';
$asideColorTitulo = $page['aside_color_titulo'] ?? '#0f9b9e';
$asideColorBtn = $page['aside_color_btn'] ?? '#0f9b9e';
$asideColorBtnHover = $page['aside_color_btn_hover'] ?? '#c6d544';
$ctaColorTitulo = $page['cta_color_titulo'] ?? '#ffffff';
$ctaColorTexto = $page['cta_color_texto'] ?? '#ffffff';
$ctaColorBtn = $page['cta_color_btn'] ?? '#0f9b9e';
$ctaColorBtnHover = $page['cta_color_btn_hover'] ?? '#c6d544';

$mostrarAside = !empty($aTxt) || !empty($testTxt);
$mostrarTestimonial = !empty($testTxt);
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; }
    
    .hero-garantia {
        height: 60vh; min-height: 450px; 
        display: flex; align-items: center; justify-content: center; text-align: center; color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                    url('assets/img/seguridad/<?= $page['banner_imagen'] ?? 'hero_tours.jpg' ?>');
        background-size: cover; background-position: center;
    }
    .hero-garantia h1 { 
        font-size: <?= $titSize ?>px; font-weight: 900; text-transform: uppercase; margin: 0; 
        line-height: 1.1; text-shadow: 2px 4px 15px rgba(0,0,0,0.5); color: <?= $bannerColorTitulo ?>;
    }
    .hero-garantia .sub-banner {
        font-size: <?= $subSize ?>px; font-weight: 300; text-transform: uppercase; 
        letter-spacing: 4px; margin-top: 15px; color: <?= $bannerColorSub ?>;
    }

    .gar-wrapper { 
        max-width: 1200px; 
        margin: 60px auto; 
        padding: 0 20px; 
        display: grid; 
        grid-template-columns: 1fr 350px; 
        gap: 50px; 
        align-items: start;
    }
    
    .tit-referencia-verde { color: <?= $introColorTitulo ?>; font-size: <?= $introTitSize ?>px; font-weight: 800; margin-bottom: 15px; }
    .check-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; color: #444; font-size: 1.1rem; }
    .check-item i { color: var(--limon); margin-top: 5px; font-size: 0.9rem; }
    .linea-limon { height: 5px; background: var(--limon); width: 80px; margin-bottom: 30px; }
    .intro-content { font-size: 1.1rem; line-height: 1.8; color: <?= $introColorTexto ?>; margin-bottom: 50px; }

    .galeria-full { margin: 60px 0; }
    .galeria-full img { width: 100%; height: 400px; object-fit: cover; border-radius: 20px; margin-bottom: 30px; }

    .galeria-columnas { margin: 60px 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; }
    .galeria-columnas img { width: 100%; height: 300px; object-fit: cover; border-radius: 15px; transition: 0.3s; }
    .galeria-columnas img:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
    .galeria-columnas h4 { color: var(--esmeralda); margin: 15px 0 10px; font-weight: 700; }

    .aside-garantia {
        position: sticky; top: 100px; background: #fff; padding: 40px 30px; border-radius: 25px; text-align: center; 
        border: 1px solid #eee; box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    .btn-consultar-gar { display: inline-block; padding: 16px 40px; background: <?= $asideColorBtn ?>; color: #fff; text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; transition: 0.3s; }
    .btn-consultar-gar:hover { background: <?= $asideColorBtnHover ?>; color: #333; transform: translateY(-3px); }

    .testimonial-gar { margin-top: 40px; text-align: left; border-top: 1px solid #eee; padding-top: 30px; }
    .testimonial-gar h4 { font-size: 1.1rem; color: <?= $asideColorTitulo ?>; margin-bottom: 12px; display: inline-block; border-bottom: 3px solid var(--limon); padding-bottom: 4px; font-weight: 800; text-transform: uppercase; }
    .testimonial-gar p { font-size: 0.9rem; color: #666; line-height: 1.6; font-style: italic; margin-bottom: 10px; text-align: justify; }
    .testimonial-gar small { color: #bbb; display: block; margin-bottom: 20px; font-weight: bold; text-transform: uppercase; }
    .testimonial-gar img { width: 100%; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

    .cta-garantia {
        padding: 100px 20px; text-align: center; color: #fff;
        background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $ctaBg ?>');
        background-size: cover; background-attachment: fixed;
    }
    .cta-garantia h2 { font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; color: <?= $ctaColorTitulo ?>; }
    .cta-garantia p { font-size: 1.2rem; margin-bottom: 30px; color: <?= $ctaColorTexto ?>; }
    .btn-cta-gar { background-color: <?= $ctaColorBtn ?>; color: white; padding: 18px 45px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-block; transition: 0.3s; text-transform: uppercase; }
    .btn-cta-gar:hover { background-color: <?= $ctaColorBtnHover ?>; color: #333; transform: translateY(-3px); }

    @media (max-width: 992px) { 
        .gar-wrapper { grid-template-columns: 1fr; } 
    }
    @media (max-width: 768px) {
        .hero-garantia h1 { font-size: calc(<?= $titSize ?>px * 0.6); }
        .hero-garantia .sub-banner { font-size: calc(<?= $subSize ?>px * 0.7); }
        .tit-referencia-verde { font-size: calc(<?= $introTitSize ?>px * 0.7); }
        .galeria-columnas { grid-template-columns: 1fr; }
    }
    @media (max-width: 576px) {
        .hero-garantia h1 { font-size: calc(<?= $titSize ?>px * 0.45); }
        .hero-garantia .sub-banner { font-size: calc(<?= $subSize ?>px * 0.5); letter-spacing: 2px; }
    }
</style>

<section class="hero-garantia">
    <div class="container">
        <h1><?= htmlspecialchars($btit) ?></h1>
        <?php if(!empty($bsub)): ?>
            <p class="sub-banner"><?= htmlspecialchars($bsub) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="gar-wrapper">
    <main>
        <div class="linea-limon"></div>
        <?php if(!empty($introTit)): ?>
            <h2 class="tit-referencia-verde"><?= htmlspecialchars($introTit) ?></h2>
        <?php endif; ?>
        <div class="intro-content"><?= fmtPlano($introTxt) ?></div>

        <?php if(!empty($imagenes)): ?>
            <?php 
            $fullImages = array_filter($imagenes, function($i) { return $i['tipo_ancho'] === 'full'; });
            $colImages = array_filter($imagenes, function($i) { return $i['tipo_ancho'] === 'columnas'; });
            ?>
            
            <?php if(!empty($fullImages)): ?>
                <div class="galeria-full">
                    <?php foreach($fullImages as $img): ?>
                        <img src="assets/img/garantia/<?= htmlspecialchars($img['imagen']) ?>" alt="<?= htmlspecialchars(($idioma == 'en' ? $img['titulo_en'] : $img['titulo'])) ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($colImages)): ?>
                <div class="galeria-columnas">
                    <?php foreach($colImages as $img): ?>
                        <div>
                            <img src="assets/img/garantia/<?= htmlspecialchars($img['imagen']) ?>" alt="<?= htmlspecialchars(($idioma == 'en' ? $img['titulo_en'] : $img['titulo'])) ?>" loading="lazy">
                            <?php $titImg = ($idioma == 'en') ? $img['titulo_en'] : $img['titulo']; ?>
                            <?php if(!empty($titImg)): ?>
                                <h4><?= htmlspecialchars($titImg) ?></h4>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <aside>
        <div class="aside-garantia">
            <?php if(!empty($aImg)): ?>
                <img src="assets/img/garantia/<?= htmlspecialchars($aImg) ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:20px; border:5px solid #f8f9fa;" loading="lazy">
            <?php endif; ?>
            <?php if(!empty($aTxt)): ?>
                <div><?= fmtPlano($aTxt) ?></div>
            <?php endif; ?>
            <?php if(!empty($aBtn)): ?>
                <a href="contacto.php" class="btn-consultar-gar"><?= htmlspecialchars($aBtn) ?></a>
            <?php endif; ?>

            <?php if(!empty($testImg) || !empty($testTxt)): ?>
                <div class="testimonial-gar">
                    <?php if(!empty($testImg)): ?>
                        <img src="assets/img/garantia/<?= htmlspecialchars($testImg) ?>" alt="Testimonial" loading="lazy">
                    <?php endif; ?>
                    <?php if(!empty($testTit)): ?>
                        <h4><?= htmlspecialchars($testTit) ?></h4>
                    <?php endif; ?>
                    <?php if(!empty($testTxt)): ?>
                        <p><?= htmlspecialchars($testTxt) ?></p>
                    <?php endif; ?>
                    <?php if(!empty($testFecha)): ?>
                        <small><?= htmlspecialchars($testFecha) ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php if(!empty($ctaTit)): ?>
<section class="cta-garantia">
    <div class="container">
        <h2><?= htmlspecialchars($ctaTit) ?></h2>
        <?php if(!empty($ctaTxt)): ?>
            <p><?= htmlspecialchars($ctaTxt) ?></p>
        <?php endif; ?>
        <a href="contacto.php" class="btn-cta-gar"><?= htmlspecialchars($ctaBtn) ?></a>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>