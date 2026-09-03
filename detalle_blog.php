<?php 
require_once 'config/database.php';

$db = (new Database())->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $db->prepare("SELECT * FROM blog WHERE id = ? AND estado = 'activo'");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) { header("Location: blog.php"); exit; }

// --- FUNCIÓN DE RENDERIZADO ACTUALIZADA ---
function formatearContenidoDetalle($texto) {
    // 1. Títulos y Estilos básicos
    $texto = preg_replace('/^# (.*)$/m', '<h2 class="detalle-h1">$1</h2>', $texto);
    $texto = preg_replace('/^## (.*)$/m', '<h3 class="detalle-h2">$1</h3>', $texto);
    $texto = preg_replace('/\*\*(.*?)\*\*/', '<strong style="color: var(--inti-esmeralda);">$1</strong>', $texto);
    $texto = preg_replace('/_(.*?)_/', '<u style="text-decoration: underline;">$1</u>', $texto);

    /* 2. LÓGICA DE IMÁGENES EN COLUMNAS:
       Buscamos bloques de una o más imágenes [img:...] seguidas por espacios o saltos de línea.
    */
    $texto = preg_replace_callback('/(\[img:.*?\](?:\s+\[img:.*?\])*)/s', function($matches) {
        // Extraemos todos los nombres de imagen del bloque encontrado
        preg_match_all('/\[img:(.*?)\]/', $matches[1], $imgs);
        $cantidad = count($imgs[1]);
        
        // Clase dinámica: si es 1 imagen es 'single-img', si son más es 'grid-gallery'
        $claseContainer = ($cantidad == 1) ? 'img-solo-container' : 'img-grid-container';
        
        $html = '<div class="' . $claseContainer . '">';
        foreach ($imgs[1] as $img_name) {
            $html .= '<div class="post-media-item"><img src="assets/img/blog/' . trim($img_name) . '" alt="Blog Media" loading="lazy"></div>';
        }
        $html .= '</div>';
        
        return $html;
    }, $texto);

    return nl2br($texto);
}

$titulo = ($idioma == 'en' && !empty($post['titulo_en'])) ? $post['titulo_en'] : $post['titulo'];
$contenido_crudo = ($idioma == 'en' && !empty($post['contenido_en'])) ? $post['contenido_en'] : $post['contenido'];
$fecha = date('d M, Y', strtotime($post['fecha']));
$btn_volver = ($idioma == 'en') ? 'Back to Blog' : 'Volver al Blog';

$seo_override = [
    'titulo'      => ($titulo ?? 'Blog') . ' | IntiPath Tours',
    'descripcion' => mb_substr(strip_tags($contenido_crudo ?? ''), 0, 160),
    'og_imagen'   => 'assets/img/blog/' . ($post['imagen_portada'] ?? ''),
    'url'         => 'https://www.intipathtours.com/detalle_blog.php?id=' . (int)$post['id'],
];

include 'includes/header.php';
?>

<style>
    :root {
        --inti-esmeralda: #0f9b9e;
        --inti-limon: #c6d544;
        --inti-text: #333;
    }

    /* ESTILOS DEL CUERPO */
    .post-body { max-width: 900px; margin: 50px auto; padding: 0 25px; line-height: 1.9; font-size: 1.15rem; color: var(--inti-text); }

    /* 1. ESTILO PARA UNA SOLA IMAGEN (Pequeña y centrada) */
    .img-solo-container {
        display: flex;
        justify-content: center;
        margin: 40px 0;
    }
    .img-solo-container .post-media-item {
        max-width: 550px; /* Tamaño controlado para que no ocupe todo el ancho */
        width: 100%;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border: 2px solid var(--inti-limon);
    }

    /* 2. ESTILO PARA VARIAS IMÁGENES (Columnas) */
    .img-grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Columnas automáticas */
        gap: 20px;
        margin: 40px 0;
    }
    .img-grid-container .post-media-item {
        border-radius: 12px;
        overflow: hidden;
        height: 250px; /* Altura fija para que la cuadrícula se vea ordenada */
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .post-media-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
    .post-media-item img:hover { transform: scale(1.05); }

    /* OTROS ESTILOS */
    .post-hero { width: 100%; height: 500px; position: relative; background: #000; overflow: hidden; }
    .post-hero img.main-bg { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; }
    .post-hero-info { position: absolute; bottom: 0; left: 0; width: 100%; padding: 60px 5%; background: linear-gradient(transparent, rgba(15, 155, 158, 0.95)); color: #fff; }
    .post-hero-info h1 { font-size: 3rem; font-weight: 900; text-transform: uppercase; }
    .detalle-h1 { color: var(--inti-esmeralda); border-left: 6px solid var(--inti-limon); padding-left: 20px; margin: 40px 0 20px; }
    
    .btn-back { display: inline-block; padding: 15px 40px; background: var(--inti-esmeralda); color: #fff; border-radius: 50px; text-decoration: none; font-weight: bold; transition: 0.3s; }
    .btn-back:hover { background: var(--inti-limon); color: #fff; }
</style>

<article>
    <section class="post-hero">
        <img src="assets/img/blog/<?= $post['imagen'] ?>" alt="<?= $titulo ?>" class="main-bg" loading="lazy">
        <div class="post-hero-info">
            <div style="font-weight: bold; color: var(--inti-limon); margin-bottom: 10px;">
                <i class="far fa-calendar-alt"></i> <?= $fecha ?>
            </div>
            <h1><?= $titulo ?></h1>
        </div>
    </section>

    <main class="post-body">
        <div class="post-render-content">
            <?= formatearContenidoDetalle($contenido_crudo) ?>
        </div>

        <div style="text-align: center; margin-top: 60px; border-top: 1px solid #eee; padding-top: 40px;">
            <a href="blog.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> <?= $btn_volver ?>
            </a>
        </div>
    </main>
</article>

<?php include 'includes/footer.php'; ?>