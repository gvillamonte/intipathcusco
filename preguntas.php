<?php
include 'includes/header.php';
require_once 'config/database.php';
$db = (new Database())->getConnection();

$page = $db->query("SELECT * FROM preguntas_frecuentes_page WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$faqs = $db->query("SELECT * FROM preguntas_frecuentes WHERE estado = 1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
$idioma = $_SESSION['lang'] ?? 'es';

function fmtPlano($t) {
    if (!$t) return '';
    $t = preg_replace('/^# (.*)$/m', '<h2 class="tit-referencia-verde">$1</h2>', $t);
    $t = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $t);
    $t = preg_replace('/^- (.*)$/m', '<div class="check-item"><i class="fas fa-check"></i> $1</div>', $t);
    return nl2br($t);
}

$btit = ($idioma == 'en') ? ($page['banner_titulo_en'] ?? 'Frequently Asked Questions') : ($page['banner_titulo'] ?? 'Preguntas Frecuentes');
$bsub = ($idioma == 'en') ? ($page['banner_subtitulo_en'] ?? '') : ($page['banner_subtitulo'] ?? '');
$itxt = ($idioma == 'en') ? ($page['intro_texto_en'] ?? '') : ($page['intro_texto'] ?? '');

$atxt = ($idioma == 'en') ? ($page['aside_texto_en'] ?? '') : ($page['aside_texto'] ?? '');
$abtn = ($idioma == 'en') ? ($page['aside_btn_en'] ?? '') : ($page['aside_btn'] ?? '');
$aimg = $page['aside_imagen'] ?? '';

$ctatit = ($idioma == 'en') ? ($page['cta_titulo_en'] ?? '') : ($page['cta_titulo'] ?? '');
$ctatxt = ($idioma == 'en') ? ($page['cta_texto_en'] ?? '') : ($page['cta_texto'] ?? '');
$ctabtn = ($idioma == 'en') ? ($page['cta_btn_en'] ?? '') : ($page['cta_btn'] ?? '');
$ctabg = "assets/img/preguntas/" . ($page['cta_imagen'] ?? 'default.jpg');
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #fefefe; }
    
    .hero-preguntas {
        height: 60vh; min-height: 450px; 
        display: flex; align-items: center; justify-content: center; text-align: center; color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), 
                    url('assets/img/preguntas/<?= $page["intro_imagen"] ?? "banner.jpg" ?>');
        background-size: cover; background-position: center;
    }
    .hero-preguntas h1 { 
        font-size: 4rem; font-weight: 900; text-transform: uppercase; margin: 0; 
        line-height: 1.1; text-shadow: 2px 4px 15px rgba(0,0,0,0.5);
    }
    .hero-preguntas .sub-banner {
        font-size: 1.3rem; font-weight: 300; text-transform: uppercase; 
        letter-spacing: 6px; margin-top: 15px; color: #f1f1f1;
    }

    .preguntas-wrapper { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 350px; gap: 50px; }
    
    .tit-referencia-verde { color: var(--esmeralda); font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; }
    .check-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; color: #444; font-size: 1.1rem; }
    .check-item i { color: var(--dark); margin-top: 5px; font-size: 0.9rem; }

    .linea-limon { height: 5px; background: var(--limon); width: 80px; margin-bottom: 30px; }

    .faq-item { background: #fff; border-radius: 15px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .faq-question { padding: 20px 25px; background: var(--esmeralda); color: #fff; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .faq-question i { transition: 0.3s; }
    .faq-answer { padding: 0; max-height: 0; overflow: hidden; transition: 0.3s; }
    .faq-answer.active { padding: 20px 25px; max-height: 500px; }
    .faq-answer p { color: #555; line-height: 1.8; }

    .sidebar-preguntas { background: #fff; padding: 40px 30px; border-radius: 25px; text-align: center; border: 1px solid #eee; position: sticky; top: 100px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .sidebar-preguntas img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 3px solid var(--esmeralda); }
    .btn-consultar-inti { display: block; padding: 16px; background: var(--esmeralda); color: #fff; text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; transition: 0.3s; }
    .btn-consultar-inti:hover { background: var(--limon); color: var(--dark); transform: translateY(-3px); }

    .section-cta-preguntas {
        padding: 100px 20px; text-align: center; color: #fff;
        background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $ctabg ?>');
        background-size: cover; background-attachment: fixed;
    }
    .btn-cta-preguntas { background-color: var(--esmeralda); color: white; padding: 18px 45px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
    .btn-cta-preguntas:hover { background-color: var(--limon); color: #fff; }
    
    @media (max-width: 992px) { 
        .preguntas-wrapper { grid-template-columns: 1fr; } 
        .hero-preguntas h1 { font-size: 2.5rem; }
    }
</style>

<section class="hero-preguntas">
    <div class="container">
        <h1><?= htmlspecialchars($btit) ?></h1>
        <?php if(!empty($bsub)): ?>
            <p class="sub-banner"><?= htmlspecialchars($bsub) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="preguntas-wrapper">
    <main>
        <div class="linea-limon"></div>
        <?php if(!empty($itxt)): ?>
            <div class="intro-content" style="margin-bottom: 40px;"><?= fmtPlano($itxt) ?></div>
        <?php endif; ?>
        
        <div class="faq-list">
            <?php foreach($faqs as $faq): 
                $pregunta = ($idioma == 'en') ? ($faq['pregunta_en'] ?? $faq['pregunta']) : $faq['pregunta'];
                $respuesta = ($idioma == 'en') ? ($faq['respuesta_en'] ?? $faq['respuesta']) : $faq['respuesta'];
            ?>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span><?= htmlspecialchars($pregunta) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?= $respuesta ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <aside class="sidebar-preguntas">
        <?php if(!empty($aimg)): ?>
            <img src="assets/img/preguntas/<?= $aimg ?>" alt="Especialista" loading="lazy">
        <?php endif; ?>
        <?php if(!empty($atxt)): ?>
            <div class="aside-content"><?= fmtPlano($atxt) ?></div>
        <?php endif; ?>
        <?php if(!empty($abtn)): ?>
            <a href="contacto.php" class="btn-consultar-inti"><?= htmlspecialchars($abtn) ?></a>
        <?php endif; ?>
    </aside>
</div>

<?php if(!empty($ctatit)): ?>
<section class="section-cta-preguntas">
    <h2><?= htmlspecialchars($ctatit) ?></h2>
    <p><?= htmlspecialchars($ctatxt) ?></p>
    <?php if(!empty($ctabtn)): ?>
        <a href="contacto.php" class="btn-cta-preguntas"><?= htmlspecialchars($ctabtn) ?></a>
    <?php endif; ?>
</section>
<?php endif; ?>

<script>
function toggleFaq(el) {
    el.nextElementSibling.classList.toggle('active');
    el.querySelector('i').classList.toggle('rotated');
}
</script>
<style>
.faq-question i.rotated { transform: rotate(180deg); }
</style>

<?php include 'includes/footer.php'; ?>