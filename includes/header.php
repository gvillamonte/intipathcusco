<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/cookie_consent.php';

// --- 1. CONTROL DE IDIOMA CON LIMPIEZA FORZADA ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] == 'en' ? 'en' : 'es');

    // Persistir idioma 12 meses solo si el visitante aceptó cookies de preferencias
    if (cookiesPermitidas('preferencias')) {
        setcookie('intipath_lang', $_SESSION['lang'], time() + 31536000, '/', '', false, true);
    }

    $params = $_GET;
    unset($params['lang']);
    $params['refresh'] = time(); // Rompe la caché de la URL al cambiar idioma

    $query = '?' . http_build_query($params);

    if (!headers_sent()) {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Location: " . basename($_SERVER['PHP_SELF']) . $query);
        exit;
    }
}

// Idioma recordado por cookie (preferencia persistida en visitas anteriores)
if (empty($_SESSION['lang']) && !empty($_COOKIE['intipath_lang'])) {
    $_SESSION['lang'] = ($_COOKIE['intipath_lang'] === 'en') ? 'en' : 'es';
}
$idioma = $_SESSION['lang'] ?? 'es';
$is_en = ($idioma === 'en'); // Variable booleana para facilitar el uso en el HTML

// --- 2. DICCIONARIO DE TRADUCCIONES ---
$m = $translations[$idioma];

// --- 3. INICIALIZACIÓN DE VARIABLES PREVENTIVAS ---
$menu_agrupado = [];
$nosotros_agrupado = [];
$config = [
    'titulo_web' => 'IntiPath Tours',
    'meta_descripcion' => '',
    'logo' => 'logo.png',
    'favicon' => 'favicon.ico'
];
$f = ['horario' => '', 'telefono' => '', 'whatsapp' => ''];
$wa_link_header = "";
$error_db = "";

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        // --- 4. OBTENER HEADER VIA PROCEDURE (config + header_links + tours + colors) ---
        $menu_agrupado = [];
        $colores_db = [];
        $header_links = [];

        $stmt_header = $db->query("CALL obtener_datos_header()");

        // Result 1: configuracion
        $res_conf = $stmt_header->fetch(PDO::FETCH_ASSOC);
        if ($res_conf) {
            $config = array_merge($config, $res_conf);
        }
        $stmt_header->nextRowset();

        // Result 2: header_links
        $header_links = $stmt_header->fetchAll(PDO::FETCH_ASSOC);
        $stmt_header->nextRowset();

        // Result 3: tours con sub_treks (JSON)
        $tours_raw = $stmt_header->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tours_raw as $tour) {
            $titulo = $tour['titulo_para_menu'];
            $menu_agrupado[$titulo][] = [
                'tour_id' => $tour['tour_id'],
                'titulo_para_menu' => $tour['titulo_para_menu'],
                'titulo_para_menu_en' => $tour['titulo_para_menu_en'],
                'titulo_completo' => $tour['titulo_completo'],
                'titulo_completo_en' => $tour['titulo_completo_en'],
                'descripcion_corta' => $tour['descripcion_corta'],
                'descripcion_corta_en' => $tour['descripcion_corta_en'],
                'imagen_principal' => $tour['imagen_principal'],
                'caminata_id' => null,
                'nombre_caminata' => null,
                'nombre_caminata_en' => null,
            ];
            // Decodificar sub_treks del JSON
            if (!empty($tour['sub_treks'])) {
                $sub_treks = json_decode($tour['sub_treks'], true);
                foreach ($sub_treks as $st) {
                    $menu_agrupado[$titulo][] = [
                        'tour_id' => $tour['tour_id'],
                        'titulo_para_menu' => $tour['titulo_para_menu'],
                        'titulo_para_menu_en' => $tour['titulo_para_menu_en'],
                        'titulo_completo' => $tour['titulo_completo'],
                        'titulo_completo_en' => $tour['titulo_completo_en'],
                        'descripcion_corta' => $tour['descripcion_corta'],
                        'descripcion_corta_en' => $tour['descripcion_corta_en'],
                        'imagen_principal' => $tour['imagen_principal'],
                        'caminata_id' => $st['id'],
                        'nombre_caminata' => $st['titulo'],
                        'nombre_caminata_en' => $st['titulo_en'],
                    ];
                }
            }
        }
        $stmt_header->nextRowset();

        // Result 4: colores
        $colores_raw = $stmt_header->fetchAll(PDO::FETCH_ASSOC);
        foreach ($colores_raw as $r) {
            $colores_db[$r['variable']] = $r['valor_actual'];
        }
        $stmt_header->closeCursor();

        // --- 5. DATOS DE CONTACTO (footer_config) ---
        $stmt_footer = $db->query("SELECT clave, valor FROM footer_config");
        while ($row = $stmt_footer->fetch(PDO::FETCH_ASSOC)) {
            $f[$row['clave']] = $row['valor'];
        }
        $wa_link_header = preg_replace('/[^0-9]/', '', $f['whatsapp'] ?? '');
        $tel_link_header = preg_replace('/[^0-9+]/', '', $f['telefono'] ?? '+51920307331');

        // --- 6. MEGA MENÚ 'NOSOTROS' ---
        $enlaces_nosotros = $db->query("SELECT * FROM menu_nosotros ORDER BY columna_nro ASC, orden ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($enlaces_nosotros as $enlace) {
            $nosotros_agrupado[$enlace['columna_nro']][] = $enlace;
        }
    }
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    $error_db = "Error de conexión.";
}

// --- 8. LÓGICA DE URL PARA CAMBIO DE IDIOMA SIN PERDER PARÁMETROS ---
$current_page = basename($_SERVER['PHP_SELF']);
$query_params = $_GET;
unset($query_params['lang']);
unset($query_params['refresh']); // Limpiamos el refresh para que no se acumule

$url_base = $current_page . '?' . http_build_query($query_params);
if (!empty($query_params)) {
    $url_base .= '&';
}

// --- 9. CONFIGURACIÓN DE RUTA ABSOLUTA (Localhost vs Producción) ---
// Comentario: Esto soluciona que el Favicon y CSS se pierdan en detalle_tour.php
$base_path = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? '/intipathcusco/' : '/';
$favicon_url = $base_path . 'assets/img/' . ($config['favicon'] ?? 'favicon.ico');

// --- 10. SEO POR PÁGINA (metas_pagina + override de la página) ---
// Las páginas pueden definir ANTES del include:
//   $seo_clave = 'tours';                              // clave en tabla metas_pagina
//   $seo_override = ['titulo'=>..., 'descripcion'=>..., 'og_imagen'=>..., 'canonical'=>...];
// Si la página no define clave, se deriva automáticamente del nombre del archivo:
$seo_clave = $seo_clave ?? [
    'index.php'              => 'home',
    'tours.php'              => 'tours',
    'blog.php'               => 'blog',
    'contacto.php'           => 'contacto',
    'nosotros.php'           => 'nosotros',
    'preguntas.php'          => 'preguntas',
    'reservas-info.php'      => 'reservas_info',
    'garantia.php'           => 'garantia',
    'seguridad.php'          => 'seguridad',
    'terminos-y-condiciones.php' => 'terminos',
    'politica-privacidad.php'    => 'privacidad',
][$current_page] ?? '';

$seo_titulo      = $config['titulo_web'] ?? 'IntiPath Tours';
$seo_descripcion = !empty($config['meta_descripcion']) ? $config['meta_descripcion'] : 'Agencia de viajes en Cusco especializada en trekking y tours personalizados.';
$seo_og_imagen   = $config['logo'] ?? 'logo.png';
$seo_canonical   = 'https://www.intipathtours.com/';
$seo_url_actual  = 'https://www.intipathtours.com/' . $current_page;
if (!empty($query_params)) {
    $seo_url_actual .= '?' . http_build_query($query_params);
}

if ($db) {
    try {
        if (!empty($seo_clave)) {
            $stmt_seo = $db->prepare("SELECT meta_title, meta_description, og_imagen FROM metas_pagina WHERE clave = ? LIMIT 1");
            $stmt_seo->execute([$seo_clave]);
            $fila_seo = $stmt_seo->fetch(PDO::FETCH_ASSOC);
            $stmt_seo->closeCursor();
            if ($fila_seo) {
                if (!empty($fila_seo['meta_title']))       $seo_titulo      = $fila_seo['meta_title'];
                if (!empty($fila_seo['meta_description'])) $seo_descripcion = $fila_seo['meta_description'];
                if (!empty($fila_seo['og_imagen']))        $seo_og_imagen   = $fila_seo['og_imagen'];
            }
        }
    } catch (Exception $e) { /* sin SEO por BD */ }
}

if (isset($seo_override) && is_array($seo_override)) {
    if (!empty($seo_override['titulo']))       $seo_titulo      = $seo_override['titulo'];
    if (!empty($seo_override['descripcion']))  $seo_descripcion = $seo_override['descripcion'];
    if (!empty($seo_override['og_imagen']))    $seo_og_imagen   = $seo_override['og_imagen'];
    if (!empty($seo_override['canonical']))    $seo_canonical   = $seo_override['canonical'];
    if (!empty($seo_override['url']))          $seo_url_actual  = $seo_override['url'];
}

// Completar rutas absolutas para OG/canonical
if (!preg_match('~^https?://~', $seo_og_imagen)) $seo_og_imagen = 'https://www.intipathtours.com/' . ltrim($seo_og_imagen, '/');
$seo_og_imagen = htmlspecialchars($seo_og_imagen);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($seo_titulo); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_descripcion); ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $seo_url_actual; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_titulo); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_descripcion); ?>">

    <meta property="og:image" content="<?php echo $seo_og_imagen; ?>">
    <meta property="og:image:secure_url" content="<?php echo $seo_og_imagen; ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?php echo $seo_og_imagen; ?>">

    <link rel="canonical" href="<?php echo $seo_canonical; ?>" />
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/styles_header.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

    <?php
    // Versionado estable de assets: cambia solo cuando el archivo cambia (permite caché del navegador)
    if (!function_exists('assetVersion')) {
        function assetVersion($archivo) {
            $ruta = __DIR__ . '/../' . ltrim($archivo, '/');
            return file_exists($ruta) ? '?v=' . filemtime($ruta) : '';
        }
    }
    ?>
    <link rel="stylesheet" href="assets/css/style.css<?php echo assetVersion('assets/css/style.css'); ?>">
    <style>
        :root {
            --ip-dynamic-primary: <?= $colores_db['--ip-primary'] ?? '#0f9b9e' ?>;
            --ip-dynamic-accent: <?= $colores_db['--ip-accent'] ?? '#c6d544' ?>;
            --ip-dynamic-text: <?= $colores_db['--ip-text'] ?? '#333333' ?>;
            --ip-dynamic-text-light: <?= $colores_db['--ip-text-light'] ?? '#ECF0F1' ?>;
            --ip-dynamic-text-muted: <?= $colores_db['--ip-text-muted'] ?? '#666666' ?>;
            --ip-dynamic-bg: <?= $colores_db['--ip-bg'] ?? '#FFFFFF' ?>;
            --ip-dynamic-bg-section: <?= $colores_db['--ip-bg-section'] ?? '#f8f9fa' ?>;
            --ip-dynamic-bg-dark: <?= $colores_db['--ip-bg-dark'] ?? '#0d1a33' ?>;
            --ip-dynamic-btn-primary: <?= $colores_db['--ip-btn-primary'] ?? '#0f9b9e' ?>;
            --ip-dynamic-btn-accent: <?= $colores_db['--ip-btn-accent'] ?? '#c6d544' ?>;
            --ip-dynamic-btn-whatsapp: <?= $colores_db['--ip-btn-whatsapp'] ?? '#25d366' ?>;
            --ip-dynamic-success: <?= $colores_db['--ip-success'] ?? '#27ae60' ?>;
            --ip-dynamic-danger: <?= $colores_db['--ip-danger'] ?? '#e74c3c' ?>;
            --ip-dynamic-star: <?= $colores_db['--ip-star'] ?? '#ffbc00' ?>;
            --ip-dynamic-border: <?= $colores_db['--ip-border'] ?? '#eeeeee' ?>;
            --ip-dynamic-admin-primary: <?= $colores_db['--ip-admin-primary'] ?? '#15305D' ?>;
            --ip-dynamic-admin-accent: <?= $colores_db['--ip-admin-accent'] ?? '#E8AC18' ?>;
            --ip-dynamic-overlay: <?= $colores_db['--ip-overlay'] ?? 'rgba(0,0,0,0.5)' ?>;
        }
    </style>

    <?php if (!empty($config['favicon'])):
        // 1. Detectamos la base_url (mantenemos tu lógica de rutas)
        $base_url = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
            ? 'http://localhost/intipathcusco/'
            : 'https://www.intipathtours.com/';

        // 2. Ruta limpia hacia el archivo .ico
        $favicon_path = $base_url . "assets/img/" . $config['favicon'];
    ?>
        <link rel="icon" href="<?php echo $favicon_path; ?>" type="image/x-icon">

        <link rel="shortcut icon" href="<?php echo $favicon_path; ?>" type="image/x-icon">

        <link rel="apple-touch-icon" href="<?php echo $favicon_path; ?>">

        <meta name="msapplication-TileImage" content="<?php echo $favicon_path; ?>">

    <?php endif; ?>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "TravelAgency",
            "name": "<?php echo addslashes($config['titulo_web']); ?>",
            "url": "https://www.intipathtours.com/",
            "logo": "https://www.intipathtours.com/assets/img/<?php echo $config['logo']; ?>",
            "description": "<?php echo addslashes($config['meta_descripcion']); ?>",
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "Cusco",
                "addressCountry": "PE"
            },
            "potentialAction": {
                "@type": "SearchAction",
                "target": "https://www.intipathtours.com/tours.php?s={search_term_string}",
                "query-input": "required name=search_term_string"
            },
            "hasPart": [{
                    "@type": "WebPage",
                    "name": "Tours en Cusco",
                    "url": "https://www.intipathtours.com/tours.php"
                },
                {
                    "@type": "WebPage",
                    "name": "Sobre Nosotros",
                    "url": "https://www.intipathtours.com/nosotros.php"
                },
                {
                    "@type": "WebPage",
                    "name": "Contacto",
                    "url": "https://www.intipathtours.com/contacto.php"
                }
            ]
        }
    </script>
</head>

<body>
    <!-- NUEVO DIV: ENLACES DEL HEADER + BANDERAS (PRIMER ELEMENTO) -->
    <div class="ip-header-links-bar" style="background: #fff; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 8px;">
        <div class="ip-container-flex ip-justify-end">
            
            <!-- Enlaces -->
            <div class="ip-pc-only" style="display: flex; align-items: center; gap: 15px;">
                <?php if (!empty($header_links)): ?>
                    <?php 
                    $total = count($header_links);
                    $contador = 0;
                    foreach ($header_links as $hl):
                        $link_texto = ($idioma == 'en' && !empty($hl['nombre_enlace_en'])) ? $hl['nombre_enlace_en'] : $hl['nombre_enlace'];
                        $contador++;
                    ?>
                        <a href="<?= $hl['url_enlace'] ?>" style="color: #333; text-decoration: none; font-weight: 600; font-size: 8px; transition: 0.3s;" onmouseover="this.style.color='#0f9b9e'" onmouseout="this.style.color='#333'">
                            <?= htmlspecialchars($link_texto) ?>
                        </a>
                        <?php if ($contador < $total): ?>
                            <span style="color: #ccc; font-size: 11px;">|</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: 
                    $placeholder_color = $config['header_placeholder_color'] ?? '#0f9b9e';
                    $placeholder_hover = $config['header_placeholder_hover'] ?? '#c6d544';
                    $placeholder_texto = $config['header_placeholder_texto'] ?? 'Configura los enlaces desde Gestionar Nosotros';
                ?>
                    <span style="color: <?= $placeholder_color ?>; font-weight: 700; font-size: 8px; transition: 0.3s;" 
                          onmouseover="this.style.color='<?= $placeholder_hover ?>'" 
                          onmouseout="this.style.color='<?= $placeholder_color ?>'">
                        <?= htmlspecialchars($placeholder_texto) ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Banderas -->
            <div style="display: flex; align-items: center; gap: 15px; margin-left: 25px;">
                <a href="<?= $url_base ?>lang=es" style="display: flex; align-items: center; gap: 5px; opacity: <?= $idioma == 'es' ? '1' : '0.4' ?>; color: #333; text-decoration: none; font-size: 8px; font-weight: 600; transition: 0.3s;">
                    <img src="https://flagcdn.com/w20/pe.png" width="20">
                    <span>Español</span>
                </a>
                <a href="<?= $url_base ?>lang=en" style="display: flex; align-items: center; gap: 5px; opacity: <?= $idioma == 'en' ? '1' : '0.4' ?>; color: #333; text-decoration: none; font-size: 8px; font-weight: 600; transition: 0.3s;">
                    <img src="https://flagcdn.com/w20/us.png" width="20">
                    <span>English</span>
                </a>
            </div>
        </div>
    </div>

    <header class="ip-header-master">

        <div class="ip-mid-bar ip-pc-only">
            <div class="ip-container-flex ip-justify-end">
                <div class="ip-contact-info">
                    <div class="ip-contact-item">
                        <i class="far fa-clock"></i>
                        <?php echo !empty($f['horario']) ? htmlspecialchars($f['horario']) : '9AM - 7PM'; ?>
                    </div>

                    <div class="ip-contact-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= $tel_link_header ?>" style="color: inherit; text-decoration: none;">
                            <?php echo !empty($f['telefono']) ? htmlspecialchars($f['telefono']) : '+51 920 307 331'; ?>
                        </a>
                    </div>

                    <div class="ip-contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= htmlspecialchars($f['email'] ?? '') ?>" style="color: inherit; text-decoration: none;">
                            <?php echo !empty($f['email']) ? htmlspecialchars($f['email']) : ''; ?>
                        </a>
                    </div>

                    <?php if (!empty($wa_link_header)): ?>
                        <a href="https://wa.me/<?php echo $wa_link_header; ?>" target="_blank" class="ip-btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>

                    <?php 
                    // Botón editable del header
                    $btn_texto = ($idioma == 'en' && !empty($config['header_btn_texto_en'])) ? $config['header_btn_texto_en'] : ($config['header_btn_texto'] ?? $m['consultar']);
                    $btn_url = !empty($config['header_btn_url']) ? $config['header_btn_url'] : 'contacto.php';
                    ?>
                    <a href="<?= $btn_url ?>" class="ip-btn-action">
                        <?= $btn_texto ?>
                    </a>
                </div>
            </div>
</div>

        <div class="ip-m-bar-compact ip-mobile-only">
            <div class="ip-m-logo">
                <a href="<?= $base_path ?>index.php">
                    <?php
                    // 1. Priorizamos SIEMPRE lo que viene de la base de datos ($config['logo'])
                    // 2. Solo si está vacío usamos el azul como última opción.
                    $nombre_logo = !empty($config['logo']) ? $config['logo'] : 'logo_intipath_azul.png';
                    ?>
                    <img src="<?= $base_path ?>assets/img/<?php echo $nombre_logo; ?>?v=1.0" alt="IntiPath Tours">
                </a>
            </div>
            <div class="ip-m-btns">
                <a href="tel:<?= $tel_link_header ?>" style="color: #0f9b9e; font-size: 22px; text-decoration: none;"><i class="fas fa-phone"></i></a>
                <a href="mailto:<?= htmlspecialchars($f['email'] ?? '') ?>" style="color: #0f9b9e; font-size: 22px; text-decoration: none;"><i class="fas fa-envelope"></i></a>
                <a href="https://wa.me/51920307331" class="ip-m-btn-wa"><i class="fab fa-whatsapp"></i></a>
                <button id="ipBtnOpen" class="ip-m-ham">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

        </div>

        <nav class="ip-nav-main">
            <div class="ip-m-overlay" id="ipOverlay">
                <div class="ip-m-panel">

                    <div class="ip-m-panel-header ip-mobile-only">
                        <span>MENÚ DE NAVEGACIÓN</span>
                        <button id="ipBtnClose"><i class="fas fa-times"></i></button>
                    </div>

                    <div class="ip-container-flex ip-justify-between">
                        <div class="ip-logo ip-pc-only">
                            <a href="<?= $base_path ?>index.php">
                                <?php
                                // Usamos la misma lógica: primero la BD, si no, el azul por defecto
                                $logo_pc = !empty($config['logo']) ? $config['logo'] : 'logo_intipath_azul.png';
                                ?>
                                <img src="<?= $base_path ?>assets/img/<?php echo $logo_pc; ?>?v=1.0" alt="Logo IntiPath Tours">
                            </a>
                        </div>

                        <ul class="ip-menu-list">
                            <?php if (!empty($menu_agrupado)): ?>
                                <?php foreach ($menu_agrupado as $titulo_pestana => $items):
                                    $info_tour = $items[0];
                                ?>
                                    <li class="ip-has-mega">
                                        <div class="ip-m-accordion-header">
                                            <a href="detalle_tour.php?id=<?= $info_tour['tour_id']; ?>" class="ip-menu-link">
                                                <?= mb_strtoupper(($idioma == 'en' && !empty($info_tour['titulo_para_menu_en'])) ? $info_tour['titulo_para_menu_en'] : $titulo_pestana); ?>
                                            </a>
                                            <i class="fas fa-chevron-down ip-m-arrow ip-mobile-only"></i>
                                        </div>


                                        <div class="ip-mega-panel">
                                            <div class="ip-mega-container">
                                                <!-- COLUMNA IZQUIERDA: CAMINATAS -->
                                                <div class="ip-mega-col ip-border-right">
                                                    <h3 class="ip-mega-title">
                                                        <?= ($idioma == 'en') ? 'AVAILABLE TREKS' : 'CAMINATAS DISPONIBLES'; ?>
                                                    </h3>
                                                    <ul class="ip-category-list">
                                                        <?php
                                                        $hay_caminatas = false;
                                                        foreach ($items as $caminata):
                                                            if (!empty($caminata['caminata_id'])):
                                                                $hay_caminatas = true;
                                                                // Traducción dinámica del nombre de la caminata
                                                                $nom_caminata = ($idioma == 'en' && !empty($caminata['nombre_caminata_en']))
                                                                    ? $caminata['nombre_caminata_en']
                                                                    : $caminata['nombre_caminata'];
                                                        ?>
                                                                <li>
                                                                    <a href="detalle_tour.php?id=<?= $caminata['caminata_id']; ?>">
                                                                        <i class="fas fa-walking"></i>
                                                                        <?= mb_strtoupper($nom_caminata); ?>
                                                                    </a>
                                                                </li>
                                                        <?php endif;
                                                        endforeach; ?>

                                                        <?php if (!$hay_caminatas): ?>
                                                            <li>
                                                                <a href="detalle_tour.php?id=<?= $info_tour['tour_id']; ?>">
                                                                    <i class="fas fa-info-circle"></i>
                                                                    <?= ($idioma == 'en') ? 'VIEW FULL ITINERARY' : 'VER ITINERARIO COMPLETO'; ?>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>

                                                <div class="ip-mega-col ip-col-center ip-border-right ip-pc-only">
                                                    <div class="ip-resumen-box">
                                                        <?php
                                                        // Traducción del Título y Descripción del Tour
                                                        $tit_tour = ($idioma == 'en' && !empty($info_tour['titulo_completo_en'])) ? $info_tour['titulo_completo_en'] : $info_tour['titulo_completo'];
                                                        $desc_tour = ($idioma == 'en' && !empty($info_tour['descripcion_corta_en'])) ? $info_tour['descripcion_corta_en'] : $info_tour['descripcion_corta'];
                                                        ?>
                                                        <h2 class="ip-tour-main-title"><?= mb_strtoupper($tit_tour); ?></h2>
                                                        <div class="ip-divider-gold"></div>
                                                        <p class="ip-resumen-text"><?= $desc_tour; ?></p>
                                                    </div>

                                                    <div class="ip-info-viaje-section">
                                                        <h4 class="ip-info-label">
                                                            <?= ($idioma == 'en') ? 'TOUR TRAVEL INFORMATION' : 'INFORMACIÓN DE VIAJE DEL TOUR'; ?>
                                                        </h4>
                                                        <div class="ip-info-grid">
                                                            <?php
                                                            // Asumimos que la tabla info_viaje tiene campos titulo y titulo_en
                                                            $stmt_img = $db->prepare("SELECT * FROM info_viaje WHERE tour_id = ? LIMIT 3");
                                                            $stmt_img->execute([$info_tour['tour_id']]);
                                                            $res_info = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

                                                            foreach ($res_info as $img_viaje):
                                                                $tit_info = ($idioma == 'en' && !empty($img_viaje['titulo_en'])) ? $img_viaje['titulo_en'] : $img_viaje['titulo'];
                                                            ?>
                                                                <div class="ip-info-card">
                                                                    <a href="<?= $img_viaje['enlace']; ?>">
                                                                        <img src="assets/img/info/<?= $img_viaje['imagen']; ?>" alt="<?= $tit_info; ?>">
                                                                        <div class="ip-card-overlay">
                                                                            <span><?= mb_strtoupper($tit_info); ?></span>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="ip-mega-col ip-col-banner ip-pc-only">
                                                    <div class="ip-banner-box">
                                                        <img src="assets/img/tours/<?= $info_tour['imagen_principal']; ?>"
                                                            width="350" height="250"
                                                            loading="lazy" decoding="async"
                                                            onerror="this.src='assets/img/Machu-Picchu.jpg'">
                                                    </div>
                                                </div>

                                            </div>
                                        </div>



                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <li class="ip-menu-item ip-has-mega">
                                <div class="ip-m-accordion-header">
                                    <a href="#" class="ip-menu-link"><?= $m['nosotros'] ?></a>
                                    <i class="fas fa-chevron-down ip-m-arrow ip-mobile-only"></i>
                                </div>

                                <div class="ip-mega-menu">
                                    <div class="ip-container-mega">
                                        <div class="ip-row-mega">
                                            <?php
                                            for ($i = 1; $i <= 4; $i++):
                                                if (isset($nosotros_agrupado[$i])):
                                                    $items_nos = $nosotros_agrupado[$i];

                                                    // Validar si hay ítems activos en esta columna
                                                    $tiene_items_activos = false;
                                                    foreach ($items_nos as $check) {
                                                        if ($check['es_footer_link'] == 1) {
                                                            $tiene_items_activos = true;
                                                            break;
                                                        }
                                                    }

                                                    if ($tiene_items_activos):
                                                        // --- 1. TRADUCCIÓN TÍTULO DE LA COLUMNA ---
                                                        $tit_columna = ($idioma == 'en' && !empty($items_nos[0]['titulo_columna_en']))
                                                            ? $items_nos[0]['titulo_columna_en']
                                                            : $items_nos[0]['titulo_columna'];
                                            ?>
                                                        <div class="ip-col-mega">
                                                            <h6 class="ip-title-col">
                                                                <?= htmlspecialchars($tit_columna) ?>
                                                            </h6>
                                                            <ul class="ip-list-mega">
                                                                <?php foreach ($items_nos as $item_n):
                                                                    if ($item_n['es_footer_link'] == 1):

                                                                        // --- 2. TRADUCCIÓN DEL NOMBRE DEL ENLACE ---
                                                                        $nombre_link = ($idioma == 'en' && !empty($item_n['nombre_enlace_en']))
                                                                            ? $item_n['nombre_enlace_en']
                                                                            : $item_n['nombre_enlace'];
                                                                ?>
                                                                        <li>
                                                                            <a href="<?= $item_n['url_enlace'] ?>">
                                                                                <?= htmlspecialchars($nombre_link) ?>
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                            <?php
                                                    endif;
                                                endif;
                                            endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>




    <script src="assets/js/main.js"></script>
</body>

</html>