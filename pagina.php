<?php
// pagina.php — plantilla pública para páginas libres del CMS
require_once 'config/database.php';

$db = (new Database())->getConnection();

$slug = trim($_GET['slug'] ?? '');
$stmt = $db->prepare("SELECT * FROM paginas WHERE slug = ? AND activo = 1 LIMIT 1");
$stmt->execute([$slug]);
$pag = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$pag) {
    header("Location: index.php");
    exit;
}

// SEO: título/descripción propios o desde metas_pagina (clave 'paginas')
$seo_clave = 'paginas';
$seo_override = [];
if (!empty($pag['meta_title']))       $seo_override['titulo'] = $pag['meta_title'];
if (!empty($pag['meta_description'])) $seo_override['descripcion'] = $pag['meta_description'];
if (!empty($pag['og_imagen']))        $seo_override['og_imagen'] = 'assets/img/paginas/' . $pag['og_imagen'];
$seo_override['url'] = 'https://www.intipathtours.com/pagina.php?slug=' . urlencode($pag['slug']);
$seo_override['canonical'] = 'https://www.intipathtours.com/pagina.php?slug=' . urlencode($pag['slug']);

include 'includes/header.php';

$titulo = ($is_en && !empty($pag['titulo_en'])) ? $pag['titulo_en'] : $pag['titulo'];
$contenido = ($is_en && !empty($pag['contenido_en'])) ? $pag['contenido_en'] : $pag['contenido'];

function renderPaginaLibre($txt) {
    $txt = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $txt);
    $txt = preg_replace('/_(.*?)_/', '<u>$1</u>', $txt);
    $txt = preg_replace_callback('/\[img:(.*?)\]/', function ($m) {
        return '<img src="assets/img/paginas/' . trim($m[1]) . '" alt="" loading="lazy" class="pag-img">';
    }, $txt);
    $lineas = explode("\n", $txt);
    $html = '';
    $en_lista = false;
    foreach ($lineas as $linea) {
        $l = trim($linea);
        if (empty($l)) { if ($en_lista) { $html .= '</ul>'; $en_lista = false; } continue; }
        if (strpos($l, '##') === 0) {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            $html .= '<h3 class="pag-sub">' . trim(substr($l, 2)) . '</h3>';
        } elseif (strpos($l, '#') === 0) {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            $html .= '<h2 class="pag-tit">' . trim(substr($l, 1)) . '</h2>';
        } elseif (strpos($l, '-') === 0) {
            if (!$en_lista) { $html .= '<ul class="pag-list">'; $en_lista = true; }
            $html .= '<li>' . trim(substr($l, 1)) . '</li>';
        } else {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            $html .= '<p class="pag-p">' . $l . '</p>';
        }
    }
    if ($en_lista) $html .= '</ul>';
    return $html;
}
?>

<style>
    .pag-hero {
        background: linear-gradient(rgba(21, 22, 22, 0.6), rgba(28, 31, 31, 0.6)), url('assets/img/hero_tours.jpg');
        background-size: cover;
        background-position: center;
        height: 300px;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #fff;
        margin-top: 80px;
    }
    .pag-box { max-width: 850px; margin: 50px auto; background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .pag-tit { color: #0f9b9e; border-bottom: 3px solid #c6d544; padding-bottom: 10px; margin-bottom: 30px; text-align: center; }
    .pag-sub { color: #0f9b9e; margin-top: 30px; font-weight: 800; }
    .pag-p { color: #555; line-height: 1.8; margin-bottom: 15px; }
    .pag-list { list-style: disc; padding-left: 25px; color: #444; line-height: 1.8; margin-bottom: 15px; }
    .pag-img { max-width: 100%; border-radius: 12px; margin: 15px 0; }
    .pag-box strong { color: #0f9b9e; }
</style>

<section class="pag-hero"><h1><?= htmlspecialchars($titulo) ?></h1></section>
<div class="pag-box"><?= renderPaginaLibre($contenido) ?></div>

<?php include 'includes/footer.php'; ?>
