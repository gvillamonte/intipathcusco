<?php
include 'includes/header.php';
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Traer configuración (Fila 1) y datos de guías/galería
$page = $db->query("SELECT * FROM equipo_guias WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$galeria = $db->query("SELECT * FROM equipo_galeria ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$guias = $db->query("SELECT * FROM equipo_guias WHERE id > 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$idioma = $_SESSION['lang'] ?? 'es';

// --- MOTOR DE TEXTO PLANO ---
function fmtPlano($t) {
    if (!$t) return '';
    $t = preg_replace('/^# (.*)$/m', '<h2 class="tit-plano">$1</h2>', $t);
    $t = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $t);
    $t = preg_replace('/_(.*?)_/', '<u style="text-decoration: underline;">$1</u>', $t);
    return nl2br($t);
}

// Variables dinámicas según idioma (Corregido: usamos $page en lugar de $config)
$aside_txt = ($idioma == 'en') ? ($page['aside_texto_en'] ?? '') : ($page['aside_texto'] ?? '');
$aside_btn = ($idioma == 'en') ? ($page['aside_btn_en'] ?? '') : ($page['aside_btn'] ?? '');

// Variables del CTA (Corregido: usamos $page para evitar el error de variable indefinida)
$cta_tit = ($idioma == 'en') ? ($page['cta_titulo_en'] ?? '') : ($page['cta_titulo'] ?? '');
$cta_txt = ($idioma == 'en') ? ($page['cta_texto_en'] ?? '') : ($page['cta_texto'] ?? '');
$cta_btn = ($idioma == 'en') ? ($page['cta_btn_en'] ?? '') : ($page['cta_btn'] ?? '');
$cta_img_name = $page['cta_imagen'] ?? 'default-cta.jpg'; 
$cta_bg  = "assets/img/equipo/" . $cta_img_name;
?>

<style>
    :root {
        --esmeralda: #0f9b9e;
        --limon: #c6d544;
        --dark: #15305D;
    }

    body, html { margin: 0; padding: 0; }

    .hero-equipo {
        width: 100%;
        height: 450px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #15305D center center no-repeat;
        background-size: cover;
        margin-top: 0;
        padding-top: 80px;
    }

    .hero-equipo::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(21, 48, 93, 0.4);
    }

    .hero-content { position: relative; z-index: 2; text-align: center; color: #fff; }
    .hero-content h1 { font-size: 3.5rem; font-weight: 900; text-transform: uppercase; margin: 0; text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.5); }
    .hero-content h2 { font-size: 1.5rem; font-weight: 300; text-transform: uppercase; letter-spacing: 2px; }

    .equipo-wrapper {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 50px;
    }

    .tit-plano { color: var(--esmeralda); font-size: 2.2rem; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; }
    .linea-limon { height: 5px; background: var(--limon); width: 80px; border: none; margin-bottom: 30px; }

    .galeria-body { display: flex; flex-direction: column; gap: 25px; margin: 30px 0; width: 100%; }
    .galeria-body img { width: 100%; height: auto; max-height: 500px; object-fit: cover; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }

    .sidebar-card {
        background: #fff;
        padding: 40px 30px;
        border-radius: 25px;
        text-align: center;
        border: 1px solid #eee;
        position: sticky;
        top: 100px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .sidebar-card > img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 5px solid #f8f9fa; }
    .sidebar-card p { color: #555; font-size: 1rem; line-height: 1.5; margin-bottom: 30px; }

    .btn-consultar-inti {
        display: block; width: 100%; padding: 16px; background: var(--esmeralda); color: #fff;
        text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase;
        transition: 0.3s; border: 2px solid var(--esmeralda); box-sizing: border-box;
    }

    .btn-consultar-inti:hover { background: var(--limon); color: var(--dark); border-color: var(--limon); transform: translateY(-3px); }

    .section-cta-plan {
        position: relative;
        padding: 100px 20px;
        background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $cta_bg ?>');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        text-align: center;
        color: #fff;
        margin-top: 50px;
    }

    .cta-plan-content { max-width: 850px; margin: 0 auto; }
    .cta-plan-content h2 { font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; }
    .cta-plan-content p { font-size: 1.2rem; line-height: 1.6; margin-bottom: 35px; font-weight: 300; }

    .btn-cta-plan {
        background-color: var(--esmeralda); color: white; padding: 18px 45px;
        border-radius: 5px; text-decoration: none; font-weight: bold;
        font-size: 1.1rem; transition: 0.3s; display: inline-block;
    }

    .btn-cta-plan:hover { background-color: var(--limon); transform: scale(1.05); }

    @media (max-width: 992px) {
        .equipo-wrapper { grid-template-columns: 1fr; }
        .hero-equipo { height: 350px; }
        .hero-content h1 { font-size: 2.5rem; }
    }
</style>

<section class="hero-equipo" data-bg-lazy="assets/img/equipo/<?= $page['intro_imagen'] ?>">
    <div class="hero-content">
        <h1><?= ($idioma == 'en') ? ($page['banner_titulo_en'] ?? '') : ($page['banner_titulo'] ?? '') ?></h1>
        <h2><?= ($idioma == 'en') ? ($page['banner_subtitulo_en'] ?? '') : ($page['banner_subtitulo'] ?? '') ?></h2>
    </div>
</section>

<div class="equipo-wrapper">
    <div class="main-content">
        <div style="font-size: 1.1rem; color: #555; line-height: 1.8; margin-bottom: 30px;">
            <?= fmtPlano(($idioma == 'en') ? $page['intro_texto_en'] : $page['intro_texto']) ?>
        </div>

        <div class="galeria-body">
            <?php foreach ($galeria as $g_img): ?>
                <img src="assets/img/equipo/galeria/<?= $g_img['imagen'] ?>" alt="Gallery Image" loading="lazy">
            <?php endforeach; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-card">
            <?php if(!empty($page['aside_imagen'])): ?>
                <img src="assets/img/equipo/<?= $page['aside_imagen'] ?>" alt="Travel Specialist" loading="lazy">
            <?php endif; ?>

            <p><?= htmlspecialchars($aside_txt) ?></p>

            <a href="contacto.php" class="btn-consultar-inti">
                <?= htmlspecialchars($aside_btn) ?>
            </a>

            <div class="side-testimonial" style="margin-top: 40px; text-align: left; border-top: 1px solid #eee; padding-top: 30px;">
                <?php if (!empty($page['aside_test_img'])): ?>
                    <img src="assets/img/equipo/<?= $page['aside_test_img'] ?>" alt="Customer Experience" style="width: 100%; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" loading="lazy">
                <?php endif; ?>

                <h4 style="font-size: 1.1rem; color: var(--dark); margin-bottom: 12px; display: inline-block; border-bottom: 3px solid var(--limon); padding-bottom: 4px; font-weight: 800; text-transform: uppercase;">
                    <?= ($idioma == 'en') ? ($page['aside_test_tit_en'] ?? '') : ($page['aside_test_tit'] ?? '') ?>
                </h4>

                <p style="font-size: 0.9rem; color: #666; line-height: 1.6; font-style: italic; margin-bottom: 10px; text-align: justify;">
                    "<?= ($idioma == 'en') ? ($page['aside_test_txt_en'] ?? '') : ($page['aside_test_txt'] ?? '') ?>"
                </p>

                <small style="color: #bbb; display: block; margin-bottom: 20px; font-weight: bold; text-transform: uppercase;">
                    <?= ($idioma == 'en') ? ($page['aside_test_fecha_en'] ?? '') : ($page['aside_test_fecha'] ?? ''); ?>
                </small>

                <a href="testimonios.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border: 1px solid #ddd; border-radius: 50px; text-decoration: none; color: var(--esmeralda); font-weight: 800; font-size: 0.85rem; transition: 0.3s; background: #fff;">
                    <i class="far fa-thumbs-up"></i>
                    <?= ($idioma == 'en') ? 'VIEW MORE OPTIONS' : 'VER MÁS OPCIONES'; ?>
                </a>
            </div>
        </div>
    </aside>
</div>

<?php if(!empty($cta_tit)): ?>
<section class="section-cta-plan">
    <div class="cta-plan-content">
        <div style="width: 40px; height: 3px; background: #fff; margin: 0 auto 20px;"></div>
        <h2><?= htmlspecialchars($cta_tit) ?></h2>
        <p><?= htmlspecialchars($cta_txt) ?></p>
        <a href="contacto.php" class="btn-cta-plan">
            <?= htmlspecialchars($cta_btn) ?>
        </a>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>