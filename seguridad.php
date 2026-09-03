<?php
include 'includes/header.php';
require_once 'config/database.php';
$db = (new Database())->getConnection();

$page = $db->query("SELECT * FROM seguridad WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$cards = $db->query("SELECT * FROM seguridad_cards ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$idioma = $_SESSION['lang'] ?? 'es';

function fmtPlano($t) {
    if (!$t) return '';
    // Procesamos títulos con #
    $t = preg_replace('/^# (.+)$/m', '<h2 class="tit-referencia-verde">$1</h2>', $t);
    // Procesamos listas con check
    $t = preg_replace('/^- (.+)$/m', '<div class="check-item"><i class="fas fa-check"></i> $1</div>', $t);
    // Convertimos saltos de línea en párrafos
    $parrafos = explode("\n", trim($t));
    $html = '';
    foreach($parrafos as $p) {
        $p = trim($p);
        if(empty($p)) continue;
        if(substr($p, 0, 1) === '<' ) {
            $html .= $p;
        } else {
            $html .= '<p>'.$p.'</p>';
        }
    }
    return $html;
}

function getYouTubeEmbed($url) {
    if (empty($url)) return '';
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return isset($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : '';
}

$btit = ($idioma == 'en') ? $page['banner_titulo_en'] : $page['banner_titulo'];
$bsub = ($idioma == 'en') ? $page['banner_subtitulo_en'] : $page['banner_subtitulo'];
$itxt = ($idioma == 'en') ? $page['intro_texto_en'] : $page['intro_texto'];

$titSize = $page['banner_titulo_size'] ?? 64;
$subSize = $page['banner_subtitulo_size'] ?? 24;
$introTitSize = $page['intro_titulo_size'] ?? 40;
$vidUrl = $page['video_url'] ?? '';
$sec2Tit = ($idioma == 'en') ? $page['seccion2_titulo_en'] : $page['seccion2_titulo'];
$cardsTit = ($idioma == 'en') ? $page['titulo_general_card_en'] : $page['titulo_general_card'];
$ctaTit = ($idioma == 'en') ? $page['cta_titulo_en'] : $page['cta_titulo'];
$ctaTxt = ($idioma == 'en') ? $page['cta_texto_en'] : $page['cta_texto'];
$ctaBtn = ($idioma == 'en') ? $page['cta_btn_en'] : $page['cta_btn'];
$ctaBg = "assets/img/" . ($page['cta_imagen'] ?? 'hero_tours.jpg');
$videoEmbed = getYouTubeEmbed($vidUrl);

$aTxt = ($idioma == 'en') ? $page['aside_texto_en'] : $page['aside_texto'];
$aBtn = ($idioma == 'en') ? $page['aside_btn_en'] : $page['aside_btn'];
$aImg = $page['aside_imagen'] ?? '';
$testTit = ($idioma == 'en') ? $page['aside_test_tit_en'] : $page['aside_test_tit'];
$testTxt = ($idioma == 'en') ? $page['aside_test_txt_en'] : $page['aside_test_txt'];
$testImg = $page['aside_test_img'] ?? '';
$testFecha = ($idioma == 'en') ? $page['aside_test_fecha_en'] : $page['aside_test_fecha'];

$mostrarAside = !empty($aTxt) || !empty($testTxt);

$colorTitulo = $page['cards_color_titulo'] ?? '#0f9b9e';
$colorBoton = $page['cta_color_btn'] ?? '#0f9b9e';
$colorBotonHover = $page['cta_color_btn_hover'] ?? '#c6d544';
$colorCheck = $page['cards_color_check'] ?? '#c6d544';
$colorFondoCard = $page['cards_color_fondo'] ?? '#ffffff';

$bannerColorTitulo = $page['banner_color_titulo'] ?? '#0f9b9e';
$bannerColorSub = $page['banner_color_subtitulo'] ?? '#f1f1f1';
$introColorTitulo = $page['intro_color_titulo'] ?? '#0f9b9e';
$introColorTexto = $page['intro_color_texto'] ?? '#555555';
$videoColorTitulo = $page['video_color_titulo'] ?? '#0f9b9e';
$asideColorTitulo = $page['aside_color_titulo'] ?? '#0f9b9e';
$asideColorBtn = $page['aside_color_btn'] ?? '#0f9b9e';
$asideColorBtnHover = $page['aside_color_btn_hover'] ?? '#c6d544';
$ctaColorTitulo = $page['cta_color_titulo'] ?? '#ffffff';
$ctaColorTexto = $page['cta_color_texto'] ?? '#ffffff';
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #fefefe; }
    
    .hero-seguridad {
        height: 60vh; min-height: 450px; 
        display: flex; align-items: center; justify-content: center; text-align: center; color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                    url('assets/img/seguridad/<?= $page['banner_imagen'] ?? 'hero_tours.jpg' ?>');
        background-size: cover; background-position: center;
    }
    .hero-seguridad h1 { 
        font-size: <?= $titSize ?>px; font-weight: 900; text-transform: uppercase; margin: 0; 
        line-height: 1.1; text-shadow: 2px 4px 15px rgba(0,0,0,0.5); color: <?= $bannerColorTitulo ?>;
    }
    .hero-seguridad .sub-banner {
        font-size: <?= $subSize ?>px; font-weight: 300; text-transform: uppercase; 
        letter-spacing: 4px; margin-top: 15px; color: <?= $bannerColorSub ?>;
    }

    .seg-wrapper { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 350px; gap: 50px; }
    
    .sidebar-seguridad { background: #fff; padding: 40px 30px; border-radius: 25px; text-align: center; border: 1px solid #eee; position: sticky; top: 100px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: fit-content; }
    .btn-consultar-seg { display: block; padding: 16px; background: <?= $asideColorBtn ?>; color: #fff; text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; transition: 0.3s; }
    .btn-consultar-seg:hover { background: <?= $asideColorBtnHover ?>; color: #333; transform: translateY(-3px); }
    
    .side-testimonial { margin-top: 40px; text-align: left; border-top: 1px solid #eee; padding-top: 30px; }
    .side-testimonial img { width: 100%; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .side-testimonial h4 { font-size: 1.1rem; color: <?= $asideColorTitulo ?>; margin-bottom: 12px; display: inline-block; border-bottom: 3px solid <?= $colorCheck ?>; padding-bottom: 4px; font-weight: 800; text-transform: uppercase; }
    .side-testimonial p { font-size: 0.9rem; color: #666; line-height: 1.6; font-style: italic; margin-bottom: 10px; text-align: justify; }
    .side-testimonial small { color: #bbb; display: block; margin-bottom: 20px; font-weight: bold; text-transform: uppercase; }
    .side-testimonial a { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border: 1px solid #ddd; border-radius: 50px; text-decoration: none; color: var(--esmeralda); font-weight: 800; font-size: 0.85rem; transition: 0.3s; background: #fff; }
    .side-testimonial a:hover { background: var(--esmeralda); color: #fff; }
    
    .tit-referencia-verde { color: <?= $introColorTitulo ?>; font-size: <?= $introTitSize ?>px; font-weight: 800; margin-bottom: 15px; }
    .check-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; color: #444; font-size: 1.1rem; }
    .check-item i { color: <?= $colorCheck ?>; margin-top: 5px; font-size: 0.9rem; }

    .linea-limon { height: 5px; background: <?= $colorCheck ?>; width: 80px; margin-bottom: 30px; }

    .intro-content { font-size: 1.1rem; line-height: 1.8; color: <?= $introColorTexto ?>; margin-bottom: 50px; }

    .video-section { margin: 60px 0; text-align: center; }
    .video-section h3 { color: <?= $videoColorTitulo ?>; font-size: 1.8rem; margin-bottom: 25px; font-weight: 700; }
    .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
    .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

    .cards-section { margin: 80px 0; }
    .cards-section h2 { color: <?= $colorTitulo ?>; font-size: 2.5rem; font-weight: 800; margin-bottom: 40px; text-align: center; }
    .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
    .card-seg { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.3s; border: 1px solid #eee; }
    .card-seg:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
    .card-seg img { width: 100%; height: 200px; object-fit: cover; }
    .card-seg-body { padding: 25px; background: <?= $colorFondoCard ?>; }
    .card-seg-body h4 { color: <?= $colorTitulo ?>; font-size: 1.3rem; margin-bottom: 10px; font-weight: 700; }
    .card-seg-body > h4 { margin-bottom: 15px; }
    .card-seg-body > h4:empty { display: none; }
    .card-seg-body p { color: #555; font-size: 0.95rem; line-height: 1.6; margin: 0 0 8px 0; }
    .card-seg-body p:empty { display: none; }
    .card-seg-body .tit-referencia-verde { display: block; color: <?= $colorTitulo ?>; font-size: 1.4rem; font-weight: 800; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 2px solid <?= $colorCheck ?>; }
    .card-seg-body .check-item { display: flex; align-items: flex-start; gap: 10px; margin: 0 0 8px 0; color: #555; font-size: 0.9rem; line-height: 1.5; }
    .card-seg-body .check-item i { color: <?= $colorCheck ?>; margin-top: 4px; min-width: 14px; }
    .card-seg-body strong { font-weight: 700; }

    .section-cta-seg {
        padding: 100px 20px; text-align: center; color: #fff;
        background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $ctaBg ?>');
        background-size: cover; background-attachment: fixed;
    }
    .section-cta-seg h2 { font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; width: 100%; color: <?= $ctaColorTitulo ?>; }
    .section-cta-seg p { font-size: 1.2rem; margin-bottom: 30px; color: <?= $ctaColorTexto ?>; }
    .btn-cta-seg { background-color: <?= $colorBoton ?>; color: white; padding: 18px 45px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-block; transition: 0.3s; text-transform: uppercase; }
    .btn-cta-seg:hover { background-color: <?= $colorBotonHover ?>; color: #333; transform: translateY(-3px); }

    @media (max-width: 992px) { 
        .seg-wrapper { grid-template-columns: 1fr; }
        .sidebar-seguridad { position: static; margin-top: 40px; }
        .hero-seguridad h1 { font-size: calc(<?= $titSize ?>px * 0.6); }
        .hero-seguridad .sub-banner { font-size: calc(<?= $subSize ?>px * 0.7); }
        .tit-referencia-verde { font-size: calc(<?= $introTitSize ?>px * 0.7); }
        .cards-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 576px) {
        .hero-seguridad h1 { font-size: calc(<?= $titSize ?>px * 0.45); }
        .hero-seguridad .sub-banner { font-size: calc(<?= $subSize ?>px * 0.5); letter-spacing: 2px; }
        .tit-referencia-verde { font-size: calc(<?= $introTitSize ?>px * 0.6); }
    }
</style>

<section class="hero-seguridad">
    <div class="container">
        <h1><?= htmlspecialchars($btit) ?></h1>
        <?php if(!empty($bsub)): ?>
            <p class="sub-banner"><?= htmlspecialchars($bsub) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="seg-wrapper">
    <main>
        <div class="linea-limon"></div>
        <div class="intro-content"><?= fmtPlano($itxt) ?></div>

        <?php if($videoEmbed): ?>
        <div class="video-section">
            <h3><?= htmlspecialchars($sec2Tit) ?></h3>
            <div class="video-container">
                <iframe src="<?= htmlspecialchars($videoEmbed) ?>" allowfullscreen></iframe>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($cards)): ?>
        <div class="cards-section">
            <h2><?= htmlspecialchars($cardsTit) ?></h2>
            <div class="cards-grid">
                <?php foreach ($cards as $card): ?>
                    <div class="card-seg">
                        <img src="assets/img/seguridad/<?= $card['imagen'] ?>" alt="<?= htmlspecialchars(($idioma == 'en' ? $card['titulo_en'] : $card['titulo'])) ?>" loading="lazy">
                        <div class="card-seg-body">
                            <h4><?= fmtPlano($idioma == 'en' ? $card['titulo_en'] : $card['titulo']) ?></h4>
                            <p><?= fmtPlano($idioma == 'en' ? $card['descripcion_en'] : $card['descripcion']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php if($mostrarAside): ?>
    <aside>
        <div class="sidebar-seguridad">
            <?php if(!empty($aImg)): ?>
                <img src="assets/img/seguridad/<?= $aImg ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:20px; border:5px solid #f8f9fa;" loading="lazy">
            <?php endif; ?>
            <?php if(!empty($aTxt)): ?>
                <p><?= htmlspecialchars($aTxt) ?></p>
            <?php endif; ?>
            <?php if(!empty($aBtn)): ?>
                <a href="contacto.php" class="btn-consultar-seg" style="margin-bottom: 30px;"><?= htmlspecialchars($aBtn) ?></a>
            <?php endif; ?>

            <?php if(!empty($testTxt)): ?>
                <div class="side-testimonial">
                    <?php if(!empty($testImg)): ?>
                        <img src="assets/img/seguridad/<?= $testImg ?>" alt="Security Experience" loading="lazy">
                    <?php endif; ?>
                    <?php if(!empty($testTit)): ?>
                        <h4><?= htmlspecialchars($testTit) ?></h4>
                    <?php endif; ?>
                    <p>"<?= htmlspecialchars($testTxt) ?>"</p>
                    <?php if(!empty($testFecha)): ?>
                        <small><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($testFecha) ?></small>
                    <?php endif; ?>
                    <a href="testimonios.php"><i class="far fa-thumbs-up"></i> <?= ($idioma == 'en') ? 'VIEW MORE REVIEWS' : 'VER MÁS TESTIMONIOS'; ?></a>
                </div>
            <?php endif; ?>
        </div>
    </aside>
    <?php endif; ?>
</div>

<?php if($ctaTit): ?>
<section class="section-cta-seg">
    <h2><?= htmlspecialchars($ctaTit) ?></h2>
    <p><?= htmlspecialchars($ctaTxt) ?></p>
    <a href="contacto.php" class="btn-cta-seg"><?= htmlspecialchars($ctaBtn) ?></a>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>