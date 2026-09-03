<?php 
include 'includes/header.php'; 
require_once 'config/database.php';

$db = (new Database())->getConnection();

// --- FUNCIÓN PARA PROCESAR EL TEXTO PLANO ---
function formatearTextoPlano($texto) {
    $texto = preg_replace('/^# (.*)$/m', '<h3 class="blog-interno-h1">$1</h3>', $texto);
    $texto = preg_replace('/^## (.*)$/m', '<h4 class="blog-interno-h2">$1</h4>', $texto);
    $texto = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $texto);
    $texto = preg_replace('/_(.*?)_/', '<u style="text-decoration: underline;">$1</u>', $texto);
$texto = preg_replace('/\[img:(.*?)\]/', '<div class="blog-inline-media"><img src="assets/img/blog/$1" loading="lazy" alt="Media"></div>', $texto);
    return nl2br($texto);
}

// --- FUNCIÓN PARA CREAR UNA DESCRIPCIÓN CORTA (RESUMEN) ---
function crearResumen($texto, $limite = 120) {
    $texto = preg_replace('/\[img:(.*?)\]/', '', $texto);
    $texto = str_replace(['#', '##', '**', '_'], '', $texto);
    if (mb_strlen($texto) > $limite) {
        return mb_substr($texto, 0, $limite) . '...';
    }
    return $texto;
}

// Consulta del Banner (ID 1)
$config_banner = $db->query("SELECT banner_titulo, banner_titulo_en, banner_subtitulo, banner_subtitulo_en FROM blog WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$titulo_hero = ($idioma == 'en') ? $config_banner['banner_titulo_en'] : $config_banner['banner_titulo'];
$subtitulo_hero = ($idioma == 'en') ? $config_banner['banner_subtitulo_en'] : $config_banner['banner_subtitulo'];

// Consulta de los artículos
$posts = $db->query("SELECT * FROM blog WHERE id != 1 ORDER BY fecha DESC")->fetchAll(PDO::FETCH_ASSOC);

$t_blog = [
    'es' => ['leer' => 'Leer Artículo', 'vacio' => 'Próximamente más historias...'],
    'en' => ['leer' => 'Read Article', 'vacio' => 'More stories coming soon...']
];
$txt = $t_blog[$idioma];

$banner_blog = "assets/img/banner_blog_header.jpg?v=" . (file_exists("assets/img/banner_blog_header.jpg") ? filemtime("assets/img/banner_blog_header.jpg") : time());
?>

<style>
    :root {
        --inti-esmeralda: #0f9b9e; /* Verde Esmeralda */
        --inti-limon: #c6d544;    /* Verde Limón */
        --inti-text: #333;
    }

    .blog-hero {
        background-size: cover; background-position: center;
        height: 400px; display: flex; flex-direction: column;
        justify-content: center; align-items: center; color: #fff;
        text-align: center; margin-top: 0 !important;
    }

    .blog-hero h1 { 
        font-size: 3.5rem; font-weight: 900; text-transform: uppercase; 
        text-shadow: 2px 2px 12px rgba(0,0,0,0.85); margin: 0;
    }
    
    .blog-hero p { 
        font-size: 1.2rem; max-width: 700px; 
        text-shadow: 1px 1px 8px rgba(0,0,0,0.85); margin-top: 10px;
    }

    .blog-container {
        max-width: 1200px; margin: 50px auto 80px;
        padding: 0 20px; display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;
    }

    .post-card {
        background: #fff; border-radius: 20px; overflow: hidden;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1); transition: 0.3s;
        display: flex; flex-direction: column; border-bottom: 5px solid var(--inti-limon);
    }

    .post-card:hover { transform: translateY(-10px); }

    .post-img-box { width: 100%; height: 230px; overflow: hidden; }
    .post-img-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .post-card:hover .post-img-box img { scale: 1.1; }

    .post-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
    
    .post-date { 
        font-size: 0.8rem; font-weight: bold; color: var(--inti-esmeralda); 
        margin-bottom: 8px; display: block; text-transform: uppercase;
    }

    .post-title { 
        color: var(--inti-esmeralda); font-size: 1.4rem; font-weight: 800; 
        margin-bottom: 12px; line-height: 1.2; min-height: 50px;
    }

    .blog-resumen-zona {
        font-size: 0.95rem; color: #666; line-height: 1.5;
        margin-bottom: 20px; flex-grow: 1;
    }

    .btn-leer {
        display: inline-block; padding: 10px 25px;
        background: var(--inti-esmeralda); color: #fff;
        text-decoration: none; border-radius: 50px;
        font-weight: bold; font-size: 0.85rem;
        transition: 0.3s; text-align: center;
        border: 2px solid var(--inti-esmeralda);
    }

    /* HOVER MODIFICADO A VERDE LIMÓN */
    .btn-leer:hover {
        background: var(--inti-limon);
        border-color: var(--inti-limon);
        color: #fff;
    }

    @media (max-width: 768px) {
        .blog-hero h1 { font-size: 2.5rem; }
    }
</style>

<section class="blog-hero" data-bg-lazy="<?= $banner_blog ?>">
    <h1><?= htmlspecialchars($titulo_hero) ?></h1>
    <p><?= htmlspecialchars($subtitulo_hero) ?></p>
</section>

<main class="blog-container">
    <?php if ($posts): ?>
        <?php foreach ($posts as $p): 
            $titulo_final = ($idioma == 'en' && !empty($p['titulo_en'])) ? $p['titulo_en'] : $p['titulo'];
            $contenido_final = ($idioma == 'en' && !empty($p['contenido_en'])) ? $p['contenido_en'] : $p['contenido'];
            $fecha = date('d M, Y', strtotime($p['fecha']));
        ?>
            <article class="post-card">
                <div class="post-img-box">
                    <img src="assets/img/blog/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($titulo_final) ?>" loading="lazy" onerror="this.src='assets/img/no-image.jpg'">
                </div>
                
                <div class="post-content">
                    <span class="post-date"><i class="far fa-calendar-alt"></i> <?= $fecha ?></span>
                    <h2 class="post-title"><?= htmlspecialchars($titulo_final) ?></h2>
                    
                    <div class="blog-resumen-zona">
                        <?= htmlspecialchars(crearResumen($contenido_final)) ?>
                    </div>
                    
                    <a href="detalle_blog.php?id=<?= $p['id'] ?>" class="btn-leer">
                        <?= $txt['leer'] ?> <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: var(--inti-esmeralda);">
            <p><?= $txt['vacio'] ?></p>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>