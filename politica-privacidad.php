<?php 
include 'includes/header.php'; 
require_once 'config/database.php';

$db = (new Database())->getConnection();

// 1. Obtenemos el contenido y el nombre de la imagen
$res = $db->query("SELECT contenido, imagen FROM politica_privacidad WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$texto = $res['contenido'] ?? 'Estamos actualizando nuestras políticas.';

// 2. Definimos la ruta de la imagen (Solo hasta assets/img/)
$banner = !empty($res['imagen']) 
          ? "assets/img/" . $res['imagen'] 
          : "assets/img/hero_tours.jpg"; 

function procesarPrivacidad($txt) {
    $lineas = explode("\n", $txt);
    $html = "";
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        if (strpos($linea, '##') === 0) $html .= "<h3 class='priv-sub'>" . trim(substr($linea, 2)) . "</h3>";
        elseif (strpos($linea, '#') === 0) $html .= "<h2 class='priv-tit'>" . trim(substr($linea, 1)) . "</h2>";
        elseif (strpos($linea, '-') === 0) $html .= "<div class='priv-item'><i class='fas fa-lock'></i> <span>" . trim(substr($linea, 1)) . "</span></div>";
        else $html .= "<p class='priv-p'>$linea</p>";
    }
    return $html;
}
?>

<style>
    .priv-hero { 
        /* CAMBIO CLAVE: Bajamos la opacidad de 1 a 0.6 para que la imagen se vea */
        background: linear-gradient(rgba(21, 22, 22, 0.6), rgba(28, 31, 31, 0.6)), url('<?= $banner ?>'); 
        background-size: cover;
        background-position: center;
        height: 300px; 
        display:flex; 
        justify-content:center; 
        align-items:center; 
        color:#fff; 
        margin-top:80px; 
    }
    .priv-box { max-width: 850px; margin: 50px auto; background:#fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .priv-tit { color:#0f9b9e; border-bottom:3px solid #c6d544; padding-bottom:10px; margin-bottom:30px; text-align:center; }
    .priv-sub { color:#0f9b9e; margin-top:30px; font-weight:800; }
    .priv-p { color:#555; line-height:1.8; margin-bottom:15px; }
    .priv-item { display:flex; gap:10px; margin-bottom:10px; color:#444; align-items:center; }
    .priv-item i { color:#c6d544; font-size: 0.9rem; }
</style>

<section class="priv-hero"><h1><?= $is_en ? 'Privacy Policy' : 'Privacidad de Datos' ?></h1></section>
<div class="priv-box"><?= procesarPrivacidad($texto) ?>
    <div id="cookies" style="margin-top:40px;padding-top:25px;border-top:2px solid #eee;">
        <h2 class="priv-tit"><?= $is_en ? 'Cookie Policy' : 'Política de Cookies' ?></h2>
        <p class="priv-p"><?= $is_en
            ? 'This website uses its own cookies (PHPSESSID) necessary for navigation and session. If you accept, we also use preference cookies to remember your language and currency for 12 months, and Google Analytics cookies (anonymous statistics) to improve our service.'
            : 'Este sitio web utiliza cookies propias (PHPSESSID) necesarias para la navegación y la sesión. Si lo aceptas, también usamos cookies de preferencias para recordar tu idioma y moneda durante 12 meses, y cookies de Google Analytics (estadísticas anónimas) para mejorar nuestro servicio.' ?></p>
        <div class="priv-item"><i class="fas fa-lock"></i> <span><strong><?= $is_en ? 'Essential' : 'Esenciales' ?>:</strong> PHPSESSID — <?= $is_en ? 'session and navigation' : 'sesión y navegación' ?></span></div>
        <div class="priv-item"><i class="fas fa-globe"></i> <span><strong><?= $is_en ? 'Preferences' : 'Preferencias' ?>:</strong> intipath_lang, intipath_moneda — <?= $is_en ? 'language and currency for 12 months' : 'idioma y moneda durante 12 meses' ?></span></div>
        <div class="priv-item"><i class="fas fa-chart-line"></i> <span><strong><?= $is_en ? 'Analytics' : 'Analíticas' ?>:</strong> _ga, _gid (Google Analytics) — <?= $is_en ? 'only with your consent' : 'solo con tu consentimiento' ?></span></div>
        <p class="priv-p"><?= $is_en
            ? 'You can accept, reject or configure cookies at any time using the banner at the bottom of the page.'
            : 'Puedes aceptar, rechazar o configurar las cookies en cualquier momento usando el aviso al pie de la página.' ?></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>