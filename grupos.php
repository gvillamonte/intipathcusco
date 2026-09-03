<?php
include 'includes/header.php';
require_once 'config/database.php';
$db = (new Database())->getConnection();

$page = $db->query("SELECT * FROM grupos_viajes WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$galeria = $db->query("SELECT * FROM grupos_galeria ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$idioma = $_SESSION['lang'] ?? 'es';

// 1. MODIFICACIÓN DEL MOTOR DE TEXTO PLANO PARA SOPORTAR SÍMBOLOS
function fmtPlano($t) {
    if (!$t) return '';
    // Títulos con # (Convertir a h2 verde)
    $t = preg_replace('/^# (.*)$/m', '<h2 class="tit-referencia-verde">$1</h2>', $t);
    // Negritas con **
    $t = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $t);
    // Subrayados con _
    $t = preg_replace('/_(.*?)_/', '<u style="text-decoration: underline;">$1</u>', $t);
    // NUEVO: Párrafos con Check (Líneas que empiezan con -)
    $t = preg_replace('/^- (.*)$/m', '<div class="check-item"><i class="fas fa-check"></i> $1</div>', $t);
    
    return nl2br($t);
}

$btit = ($idioma == 'en') ? $page['banner_titulo_en'] : $page['banner_titulo'];
// Traemos el subtítulo para el banner
$bsub = ($idioma == 'en') ? ($page['banner_subtitulo_en'] ?? '') : ($page['banner_subtitulo'] ?? '');
$itxt = ($idioma == 'en') ? $page['intro_texto_en'] : $page['intro_texto'];
$cta_bg = "assets/img/grupos/" . ($page['cta_imagen'] ?? 'default.jpg');
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #fefefe; }
    
    /* 2. MEJORA DEL HERO (BANNER) ESTILO IMAGEN */
    .hero-grupos {
        height: 60vh; min-height: 450px; 
        display: flex; align-items: center; justify-content: center; text-align: center; color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), 
                    url('assets/img/grupos/<?= $page['intro_imagen'] ?>');
        background-size: cover; background-position: center;
    }
    .hero-grupos h1 { 
        font-size: 4rem; font-weight: 900; text-transform: uppercase; margin: 0; 
        line-height: 1.1; text-shadow: 2px 4px 15px rgba(0,0,0,0.5);
    }
    .hero-grupos .sub-banner {
        font-size: 1.3rem; font-weight: 300; text-transform: uppercase; 
        letter-spacing: 6px; margin-top: 15px; color: #f1f1f1;
    }

    .grupos-wrapper { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 350px; gap: 50px; }
    
    /* ESTILOS PARA EL TEXTO PLANO PROCESADO */
    .tit-referencia-verde { color: var(--esmeralda); font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; }
    .check-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; color: #444; font-size: 1.1rem; }
    .check-item i { color: var(--dark); margin-top: 5px; font-size: 0.9rem; }

    .linea-limon { height: 5px; background: var(--limon); width: 80px; margin-bottom: 30px; }
    .galeria-filas { display: flex; flex-direction: column; gap: 30px; margin-top: 40px; }
    .galeria-filas img { width: 100%; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

    .sidebar-grupos { background: #fff; padding: 40px 30px; border-radius: 25px; text-align: center; border: 1px solid #eee; position: sticky; top: 100px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .btn-consultar-inti { display: block; padding: 16px; background: var(--esmeralda); color: #fff; text-decoration: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; transition: 0.3s; }
    .btn-consultar-inti:hover { background: var(--limon); color: var(--dark); transform: translateY(-3px); }

    .section-cta-plan {
        padding: 100px 20px; text-align: center; color: #fff;
        background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $cta_bg ?>');
        background-size: cover; background-attachment: fixed;
    }
    .btn-cta-plan { background-color: var(--esmeralda); color: white; padding: 18px 45px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
    .btn-cta-plan:hover { background-color: var(--limon); color: #fff; }
    @media (max-width: 992px) { 
        .grupos-wrapper { grid-template-columns: 1fr; } 
        .hero-grupos h1 { font-size: 2.5rem; }
    }
</style>

<section class="hero-grupos">
    <div class="container">
        <h1><?= htmlspecialchars($btit) ?></h1>
        <?php if(!empty($bsub)): ?>
            <p class="sub-banner"><?= htmlspecialchars($bsub) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="grupos-wrapper">
    <main>
        <div class="linea-limon"></div>
        <div class="intro-content"><?= fmtPlano($itxt) ?></div>
        
        <div class="galeria-filas">
            <?php foreach ($galeria as $g): ?>
                <img src="assets/img/grupos/galeria/<?= $g['imagen'] ?>" loading="lazy">
            <?php endforeach; ?>
        </div>
    </main>

    <aside>
        <div class="sidebar-grupos">
            <img src="assets/img/grupos/<?= $page['aside_imagen'] ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:20px; border:5px solid #f8f9fa;" loading="lazy">
            <p><?= htmlspecialchars(($idioma == 'en') ? $page['aside_texto_en'] : $page['aside_texto']) ?></p>
            <a href="contacto.php" class="btn-consultar-inti" style="margin-bottom: 30px;">
                <?= htmlspecialchars(($idioma == 'en') ? $page['aside_btn_en'] : $page['aside_btn']) ?>
            </a>

            <div class="side-testimonial" style="margin-top: 40px; text-align: left; border-top: 1px solid #eee; padding-top: 30px;">
                <?php if (!empty($page['aside_test_img'])): ?>
                    <img src="assets/img/grupos/<?= $page['aside_test_img'] ?>" alt="Group Experience" style="width: 100%; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" loading="lazy">
                <?php endif; ?>
                <h4 style="font-size: 1.1rem; color: var(--esmeralda); margin-bottom: 12px; display: inline-block; border-bottom: 3px solid var(--limon); padding-bottom: 4px; font-weight: 800; text-transform: uppercase;">
                    <?= ($idioma == 'en') ? $page['aside_test_tit_en'] : $page['aside_test_tit'] ?>
                </h4>
                <p style="font-size: 0.9rem; color: #666; line-height: 1.6; font-style: italic; margin-bottom: 10px; text-align: justify;">
                    "<?= ($idioma == 'en') ? $page['aside_test_txt_en'] : $page['aside_test_txt'] ?>"
                </p>
                <small style="color: #bbb; display: block; margin-bottom: 20px; font-weight: bold; text-transform: uppercase;">
                    <i class="far fa-calendar-alt"></i> 
                    <?= ($idioma == 'en') ? $page['aside_test_fecha_en'] : $page['aside_test_fecha']; ?>
                </small>
                <a href="testimonios.php" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border: 1px solid #ddd; border-radius: 50px; text-decoration: none; color: var(--esmeralda); font-weight: 800; font-size: 0.85rem; transition: 0.3s; background: #fff;">
                    <i class="far fa-thumbs-up"></i>
                    <?= ($idioma == 'en') ? 'VIEW MORE REVIEWS' : 'VER MÁS TESTIMONIOS'; ?>
                </a>
            </div>
        </div>
    </aside>
</div>

<?php if($page['cta_titulo']): ?>
<section class="section-cta-plan">
    <h2><?= htmlspecialchars(($idioma == 'en') ? $page['cta_titulo_en'] : $page['cta_titulo']) ?></h2>
    <p><?= htmlspecialchars(($idioma == 'en') ? $page['cta_texto_en'] : $page['cta_texto']) ?></p>
    <a href="contacto.php" class="btn-cta-plan"><?= htmlspecialchars(($idioma == 'en') ? $page['cta_btn_en'] : $page['cta_btn']) ?></a>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>