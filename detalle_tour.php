<?php
// 1. LIMPIEZA DE CACHÉ Y ERRORES
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 2. CONTROL DE SESIÓN E IDIOMA ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] == 'en' ? 'en' : 'es');
    $params = $_GET;
    unset($params['lang']);
    $params['v'] = time();
    header("Location: " . basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params));
    exit;
}

$idioma = $_SESSION['lang'] ?? 'es';

// --- 3. REQUERIMIENTOS Y CONEXIÓN ---
require_once 'config/database.php';
require_once 'config/lang.php';
require_once __DIR__ . '/includes/moneda_helper.php';
// Protección CSRF: se carga si existe (el archivo va en el paquete de despliegue)
if (file_exists(__DIR__ . '/includes/csrf_helper.php')) {
    require_once __DIR__ . '/includes/csrf_helper.php';
}

$database = new Database();
$db = $database->getConnection();

$m = $translations[$idioma];

// --- 4. CARGAR CONFIGURACIÓN (Vital para el Favicon) ---
$config = ['favicon' => 'favicon.ico', 'titulo_web' => 'IntiPath Tours'];
$stmt_conf = $db->query("SELECT * FROM configuracion WHERE id = 1");
if ($res_c = $stmt_conf->fetch(PDO::FETCH_ASSOC)) {
    $config = $res_c;
}

// --- 5. VALIDACIÓN DE ID DEL TOUR ---
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: index.php");
    exit;
}

// --- 6. CONSULTA PRINCIPAL TOUR ---
$stmt = $db->prepare("SELECT * FROM tours WHERE id = ? AND estado = 'activo'");
$stmt->execute([$id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
    header("Location: index.php");
    exit;
}

// --- 7. LÓGICA DE TRADUCCIÓN DE COLUMNAS ---
$is_en = ($idioma == 'en');

$titulo_display            = ($is_en && !empty($t['titulo_en'])) ? $t['titulo_en'] : $t['titulo'];
$duracion_display          = ($is_en && !empty($t['duracion_en'])) ? $t['duracion_en'] : $t['duracion'];
$resumen_display           = ($is_en && !empty($t['descripcion_corta_en'])) ? $t['descripcion_corta_en'] : $t['descripcion_corta'];
$itinerario_display        = ($is_en && !empty($t['itinerario_en'])) ? $t['itinerario_en'] : $t['itinerario'];
$resumen_itinerario_display = ($is_en && !empty($t['itinerario_resumen_en'])) ? $t['itinerario_resumen_en'] : $t['itinerario_resumen'];
$incluye_display           = ($is_en && !empty($t['incluye_en'])) ? $t['incluye_en'] : $t['incluye'];
$no_incluye_display        = ($is_en && !empty($t['no_incluye_en'])) ? $t['no_incluye_en'] : $t['no_incluye'];
$destacados_display        = ($is_en && !empty($t['destacados_en'])) ? $t['destacados_en'] : $t['destacados'];
$info_importante_display   = ($is_en && !empty($t['info_importante_detallada_en'])) ? $t['info_importante_detallada_en'] : $t['info_importante_detallada'];
$lista_equipaje_display    = ($is_en && !empty($t['lista_equipaje_en'])) ? $t['lista_equipaje_en'] : $t['lista_equipaje'];
$antes_viajar_display      = ($is_en && !empty($t['antes_de_viajar_en'])) ? $t['antes_de_viajar_en'] : $t['antes_de_viajar'];
$aclimatacion_display      = ($is_en && !empty($t['aclimatacion_texto_en'])) ? $t['aclimatacion_texto_en'] : $t['aclimatacion_texto'];
$comidas_display           = ($is_en && !empty($t['comidas_info_en'])) ? $t['comidas_info_en'] : $t['comidas_info'];
$extras_display            = ($is_en && !empty($t['extras_texto_en'])) ? $t['extras_texto_en'] : ($t['extras_texto'] ?? '');

// --- 8. DETECCIÓN DE RUTA BASE ---
$base_path = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' : '/';

// --- 9. CONSULTAS COMPLEMENTARIAS ---
$stmt_info = $db->prepare("SELECT * FROM info_viaje WHERE tour_id = ? LIMIT 3");
$stmt_info->execute([$id]);
$infos_viaje = $stmt_info->fetchAll(PDO::FETCH_ASSOC);

$stmt_porque = $db->query("SELECT * FROM porque_nosotros ORDER BY orden ASC");
$tarjetas = $stmt_porque->fetchAll(PDO::FETCH_ASSOC);

$stmt_rel = $db->prepare("SELECT * FROM tours WHERE id_categoria = ? AND id != ? AND estado = 'activo' LIMIT 4");
$stmt_rel->execute([$t['id_categoria'], $id]);
$relacionados = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

if (empty($relacionados)) {
    $stmt_fallback = $db->prepare("SELECT * FROM tours WHERE id != ? AND estado = 'activo' ORDER BY RAND() LIMIT 4");
    $stmt_fallback->execute([$id]);
    $relacionados = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
}

// --- 10. ARRAY DE PAÍSES (RECUPERADO) ---
$paises = [
    "AF" => "Afganistán",
    "AL" => "Albania",
    "DE" => "Alemania",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AR" => "Argentina",
    "AM" => "Armenia",
    "AU" => "Australia",
    "AT" => "Austria",
    "AZ" => "Azerbaiyán",
    "BE" => "Bélgica",
    "BO" => "Bolivia",
    "BR" => "Brasil",
    "CA" => "Canadá",
    "CL" => "Chile",
    "CN" => "China",
    "CO" => "Colombia",
    "CR" => "Costa Rica",
    "CU" => "Cuba",
    "EC" => "Ecuador",
    "ES" => "España",
    "US" => "Estados Unidos",
    "FR" => "Francia",
    "GT" => "Guatemala",
    "HN" => "Honduras",
    "IT" => "Italia",
    "MX" => "México",
    "NI" => "Nicaragua",
    "PA" => "Panamá",
    "PY" => "Paraguay",
    "PE" => "Perú",
    "PR" => "Puerto Rico",
    "GB" => "Reino Unido",
    "DO" => "República Dominicana",
    "UY" => "Uruguay",
    "VE" => "Venezuela"
];

if ($is_en) {
    $paises["DE"] = "Germany";
    $paises["ES"] = "Spain";
    $paises["US"] = "United States";
    $paises["FR"] = "France";
    $paises["GB"] = "United Kingdom";
}
asort($paises);
$paises_opts = '';
foreach ($paises as $cod => $nom) {
    $sel = ($cod === 'PE') ? ' selected' : '';
    $paises_opts .= '<option value="' . htmlspecialchars($nom) . '"' . $sel . '>' . htmlspecialchars($nom) . '</option>';
}
?>
<!DOCTYPE html>
<html lang="<?= $idioma ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_display ?> | IntiPath Tours</title>

    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/detalle_tour.css?v=<?php echo time(); ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/shift-away.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        #loading-overlay-booking {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        #loading-overlay-booking.active {
            display: flex;
        }
        .spinner-booking {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #c6d544;
            animation: spin-booking 1s ease-in-out infinite;
            margin-bottom: 20px;
        }
        @keyframes spin-booking {
            to { transform: rotate(360deg); }
        }
        .loading-text-booking {
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: 1px;
        }
    </style>

    <?php if (!empty($config['favicon'])): ?>
        <link rel="icon" href="<?php echo $base_path; ?>assets/img/<?php echo $config['favicon']; ?>?v=<?php echo time(); ?>" type="image/x-icon">
        <link rel="shortcut icon" href="<?php echo $base_path; ?>assets/img/<?php echo $config['favicon']; ?>?v=<?php echo time(); ?>" type="image/x-icon">
    <?php endif; ?>
</head>

<body>
    <div id="loading-overlay-booking">
        <div class="spinner-booking"></div>
        <div class="loading-text-booking"><?= ($idioma == 'en') ? 'Processing your booking...' : 'Procesando tu reserva...' ?></div>
        <div class="mt-2 text-white-50 small"><?= ($idioma == 'en') ? 'Please do not close this window' : 'Por favor no cierres esta ventana' ?></div>
    </div>
    <?php
    $seo_override = [
        'titulo'      => ($t['titulo'] ?? 'Tour') . ' | IntiPath Tours',
        'descripcion' => mb_substr(strip_tags($t['descripcion_corta'] ?? ''), 0, 160),
        'og_imagen'   => 'assets/img/tours/' . ($t['imagen_principal'] ?? ''),
        'url'         => 'https://www.intipathtours.com/detalle_tour.php?id=' . (int)$id,
    ];
    include 'includes/header.php';
    ?>
    <section class="ip-details-hero" data-bg-lazy="<?php echo $base_path; ?>assets/img/tours/<?= $t['imagen_principal'] ?>">
        <div class="contenedor">
            <h1>
                <?php
                // Usamos la variable $is_en que definimos en el header para que el código sea más limpio
                $titulo_final = ($is_en && !empty($t['titulo_en'])) ? $t['titulo_en'] : $t['titulo'];
                echo mb_strtoupper($titulo_final);
                ?>
            </h1>
        </div>
    </section>

    <section class="ip-details-features-bar">
        <div class="contenedor ip-details-grid-features">
            <div class="ip-details-feat-item">
                <a href="<?= $t['video_url'] ?>" target="_blank" style="text-decoration:none;">
                    <i class="fas fa-play-circle text-danger"></i>
                    <small class="d-block"><?= ($idioma == 'en') ? 'Video Tour' : 'Video Tour' ?></small>
                    <span class="d-block"><?= ($idioma == 'en') ? 'WATCH NOW' : 'VER AHORA' ?></span>
                </a>
            </div>
            <div class="ip-details-feat-item">
                <i class="fas fa-campground"></i>
                <small><?= ($idioma == 'en') ? 'Accommodation' : 'Alojamiento' ?></small>
                <span><?= ($idioma == 'en') ? 'Campsites' : 'Campamentos' ?></span>
            </div>
            <div class="ip-details-feat-item">
                <i class="fas fa-users"></i>
                <small><?= ($idioma == 'en') ? 'Group Size' : 'Tamaño del Grupo' ?></small>
                <span><?= ($idioma == 'en') ? 'From 2 people' : 'Desde 2 personas' ?></span>
            </div>
            <div class="ip-details-feat-item">
                <i class="fas fa-mountain"></i>
                <small><?= ($idioma == 'en') ? 'Max Altitude' : 'Altitud Máxima' ?></small>
                <span><?= $t['altitud_max'] ?></span>
            </div>
            <div class="ip-details-feat-item">
                <i class="fas fa-chart-line"></i>
                <small><?= ($idioma == 'en') ? 'Difficulty' : 'Dificultad' ?></small>
                <span><?= ($idioma == 'en') ? 'Moderate' : $t['dificultad'] ?></span>
            </div>
            <div class="ip-details-feat-item-pdf">
                <?php if ($t['folleto_pdf']): ?>
                    <a href="assets/pdf/<?= $t['folleto_pdf'] ?>" class="ip-btn-download-pdf" download>
                        <?= ($idioma == 'en') ? 'DOWNLOAD BROCHURE' : 'DESCARGAR FOLLETO' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <main class="contenedor ip-details-main-full mt-0 pt-0">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item">
                    <a href="index.php"><?= ($idioma == 'en') ? 'Home' : 'Inicio' ?></a>
                </li>
                <li class="breadcrumb-item active">
                    <?= $titulo_display ?>
                </li>
            </ol>
        </nav>

        <div class="row align-items-center mb-4">
            <div class="col-md-12">
                <h2 class="fw-bold display-6" style="color: var(--color-primario-azul);">
                    <?= $titulo_display ?>
                </h2>
                <p class="ubicacion text-muted">
                    <i class="fas fa-map-marker-alt text-danger"></i> <?= $t['ubicacion_texto'] ?>
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="mb-4">
                    <h5 class="fw-bold text-primary border-start border-4 border-warning ps-3">
                        <?= ($idioma == 'en') ? 'Itinerary Summary' : 'Resumen de Itinerario' ?>
                    </h5>
                </div>
                <div class="itinerario-text-corta text-muted">
                    <?= $resumen_display ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ip-details-price-card shadow-lg border-0 p-4 text-center bg-white rounded-4 sticky-top" style="top: 100px;">
                    <p class="small text-muted mb-0"><?= ($idioma == 'en') ? 'From' : 'A partir de' ?></p>

                    <span class="amount display-5 fw-bold text-primary"><?= precioFormato($t, $idioma) ?></span>

                    <p class="small text-muted mb-4"><?= ($idioma == 'en') ? 'per person' : 'por persona' ?></p>

                    <div class="ip-sidebar-stats text-start mb-4 border-top pt-3">
                        <div class="mb-2">
                            <i class="fas fa-route text-primary me-2"></i>
                            <strong><?= $t['distancia_caminata'] ?></strong>
                        </div>

                        <div class="mb-2">
                            <i class="fas fa-utensils text-primary me-2"></i>
                            <strong><?= $comidas_display ?></strong>
                        </div>

                        <div>
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            <strong><?= ($idioma == 'en') ? 'Local Management' : 'Gestión Local' ?></strong>
                        </div>
                    </div>

                    <button type="button"
                        class="btn btn-warning w-100 fw-bold py-3 mb-2 shadow-sm text-white"
                        style="background-color: #c6d544; border-radius: 10px;"
                        data-bs-toggle="modal"
                        data-bs-target="#modalReservaTour">
                        <i class="fas fa-calendar-check me-2"></i>
                        <?= ($idioma == 'en') ? 'BOOK ONLINE' : 'RESERVAR EN LÍNEA' ?>
                    </button>

                    <button type="button"
                        class="btn w-100 fw-bold py-2"
                        style="color: #0f9b9e; border: 1px solid #0f9b9e; background: transparent;"
                        onmouseover="this.style.backgroundColor='#c6d544'; this.style.color='#fff';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#c6d544';"
                        onclick="irAConsulta()">
                        <?= $m['consultar'] ?>
                    </button>

                </div>
            </div>
        </div>
    </main>

    <section class="ip-full-width-actions py-5 mt-5 bg-light border-top border-bottom">
        <div class="contenedor">
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="<?= $t['video_url'] ?>" target="_blank" class="ip-action-card d-block position-relative" style="height: 250px; background-size:cover;" data-bg-lazy="assets/img/tours/<?= $t['imagen_principal'] ?>">
                        <div class="ip-action-overlay d-flex align-items-center justify-content-center text-white h-100 w-100 bg-dark bg-opacity-25 text-center">
                            <div>
                                <i class="fas fa-play-circle fa-3x mb-2"></i><br>
                                <span class="fw-bold"><?= ($idioma == 'en') ? 'WATCH VIDEO' : 'VER VÍDEO' ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="assets/pdf/<?= $t['folleto_pdf'] ?>" download class="ip-action-card d-block position-relative" style="height: 250px; background-size:cover;" data-bg-lazy="assets/img/tours/<?= $t['imagen_principal'] ?>">
                        <div class="ip-action-overlay d-flex align-items-center justify-content-center text-white h-100 w-100 bg-dark bg-opacity-25 text-center">
                            <div>
                                <i class="fas fa-file-pdf fa-3x mb-2"></i><br>
                                <span class="fw-bold"><?= ($idioma == 'en') ? 'DOWNLOAD BROCHURE' : 'DESCARGAR FOLLETO' ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <div class="ip-action-card d-block position-relative" style="height: 250px; background-size:cover; cursor:pointer;" data-bg-lazy="assets/img/mapas/<?= $t['mapa_imagen'] ?>" data-bs-toggle="modal" data-bs-target="#modalMapa">
                        <div class="ip-action-overlay d-flex align-items-center justify-content-center text-white h-100 w-100 bg-dark bg-opacity-25 text-center">
                            <div>
                                <i class="fas fa-map-marked-alt fa-3x mb-2"></i><br>
                                <span class="fw-bold"><?= ($idioma == 'en') ? 'VIEW MAP' : 'VER MAPA' ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalMapa" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
                        <div class="modal-content bg-dark border-0">
                            <div class="modal-header border-0 py-2 px-3">
                                <h6 class="modal-title text-white m-0 small"><?= ($idioma == 'en') ? 'Route Map' : 'Mapa de la Ruta' ?></h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body p-0 d-flex align-items-center justify-content-center" style="background: #1a1a1a; overflow: hidden;">
                                <div id="contenedorZoom" style="width: 100%; height: 100%; overflow: auto; display: flex; align-items: center;">
                                    <img src="assets/img/mapas/<?= $t['mapa_imagen'] ?>"
                                        id="imagenMapa"
                                        loading="lazy"
                                        alt="Mapa del Tour"
                                        style="width: 100%; height: auto; transition: transform 0.3s ease; cursor: zoom-in;">
                                </div>
                            </div>

                            <div class="modal-footer border-0 py-2">
                                <button type="button" class="btn btn-primary btn-sm w-100" onclick="toggleZoom()">
                                    <i class="fas fa-search-plus"></i> <?= ($idioma == 'en') ? 'ZOOM / RESET' : 'AMPLIAR / RESETEAR' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>

    <section class="ip-tour-tabs-global-wrapper mt-5">
        <div class="ip-tabs-nav-bg">
            <div class="contenedor">
                <ul class="ip-tour-tabs-main" id="tourTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#resumen-det" type="button">
                            <i class="fas fa-binoculars"></i>
                            <span><?= ($idioma == 'en') ? 'OVERVIEW' : 'RESUMEN' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#itinerario-det" type="button">
                            <i class="fas fa-route"></i>
                            <span><?= ($idioma == 'en') ? 'ITINERARY' : 'ITINERARIO' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inclusiones-det" type="button">
                            <i class="fas fa-list-ul"></i>
                            <span><?= ($idioma == 'en') ? 'INCLUSIONS' : 'INCLUSIONES' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adicionales-det" type="button">
                            <i class="fas fa-people-carry-box"></i>
                            <span><?= ($idioma == 'en') ? 'EXTRAS' : 'ADICIONALES' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#antes-viajar-det" type="button">
                            <i class="fas fa-question-circle"></i>
                            <span><?= ($idioma == 'en') ? 'BEFORE YOU GO' : 'ANTES DE VIAJAR' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#equipaje-det" type="button">
                            <i class="fas fa-clipboard-list"></i>
                            <span><?= ($idioma == 'en') ? 'PACKING LIST' : 'EQUIPAJE' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#precios-det" type="button">
                            <i class="fas fa-hand-holding-dollar"></i>
                            <span><?= ($idioma == 'en') ? 'PRICES' : 'PRECIOS' ?></span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#consultar-det" type="button">
                            <i class="fas fa-id-card-alt"></i>
                            <span><?= ($idioma == 'en') ? 'INQUIRE NOW' : 'CONSULTAR AHORA' ?></span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="contenedor mt-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="tab-content ip-tab-content-box p-4 border bg-white shadow-sm">



                        <div class="tab-pane fade show active" id="resumen-det">
                            <div class="ip-destacados-container mb-4">
                                <h4 class="fw-bold mb-3"><?= ($idioma == 'en') ? 'Highlights' : 'Destacados' ?></h4>
                                <div class="ip-destacados-lista">
                                    <?php
                                    // 1. FORZAMOS LA COLUMNA CORRECTA SEGÚN EL IDIOMA
                                    // Si el idioma es 'en', usamos la columna '_en'. Si no, usamos la normal.
                                    if ($idioma == 'en') {
                                        $texto_final = (!empty($t['destacados_en'])) ? $t['destacados_en'] : $t['destacados'];
                                    } else {
                                        $texto_final = $t['destacados'];
                                    }

                                    if (!empty($texto_final)) {
                                        // 2. LIMPIEZA: Quitamos el molesto [eos] y retornos de carro


                                        // 3. SEPARACIÓN: Intentamos por salto de línea, y si no hay, por punto seguido
                                        $puntos = explode("\n", $texto_final);
                                        if (count($puntos) <= 1) {
                                            $puntos = explode(". ", $texto_final);
                                        }

                                        echo "<ul>";
                                        foreach ($puntos as $punto) {
                                            $punto_limpio = trim($punto);

                                            if (!empty($punto_limpio)) {
                                                // Aseguramos que cada frase termine en punto si es una oración
                                                $mostrar = (substr($punto_limpio, -1) == '.') ? $punto_limpio : $punto_limpio . '.';
                                                echo "<li>" . $mostrar . "</li>";
                                            }
                                        }
                                        echo "</ul>";
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="ip-gallery-grid mt-4 mb-5">
                                <div class="row g-2">
                                    <?php for ($i = 1; $i <= 4; $i++):
                                        $img_name = $t['img_galeria' . $i];
                                        if (!empty($img_name)): ?>
                                            <div class="col-6 col-md-3">
                                                <div class="ip-gallery-item">
                                                    <img src="assets/img/tours/<?= $img_name ?>" class="img-fluid rounded shadow-sm ip-img-hover" loading="lazy" alt="<?= htmlspecialchars($t['titulo']) ?>">
                                                </div>
                                            </div>
                                    <?php endif;
                                    endfor; ?>
                                </div>
                            </div>

                            <div class="ip-resumen-itinerario-container">
                                <h4 class="fw-bold mb-4"><?= ($idioma == 'en') ? 'Itinerary Summary' : 'Resumen de itinerario' ?></h4>

                                <?php
                                // 1. FORZAMOS LA COLUMNA CORRECTA SEGÚN EL IDIOMA
                                if ($idioma == 'en') {
                                    // Si es inglés, buscamos la columna _en. Si está vacía, mostramos español como respaldo.
                                    $raw_itinerario = (!empty($t['itinerario_resumen_en'])) ? $t['itinerario_resumen_en'] : $t['itinerario_resumen'];
                                } else {
                                    // Si es español, vamos directo a la columna original.
                                    $raw_itinerario = $t['itinerario_resumen'];
                                }

                                if (!empty($raw_itinerario)) {
                                    // Limpiamos saltos de línea y dividimos por líneas
                                    $lineas = explode("\n", str_replace("\r", "", $raw_itinerario));

                                    foreach ($lineas as $linea) {
                                        $linea = trim($linea);
                                        if ($linea == "") continue;

                                        // Separamos por asterisco para obtener la descripción
                                        $partes = explode("*", $linea);
                                        // Separamos por dos puntos para obtener "DÍA 01" y el "Título"
                                        $cabecera = explode(":", $partes[0]);

                                        $dia_texto = isset($cabecera[0]) ? trim($cabecera[0]) : ''; // Ej: "DÍA 01" o "DAY 01"
                                        $titulo_dia = isset($cabecera[1]) ? trim($cabecera[1]) : ''; // Ej: "Cusco - Ausangate"
                                        $descripcion = isset($partes[1]) ? trim($partes[1]) : ''; // El texto después del *
                                ?>
                                        <div class="ip-it-item mb-4 d-flex">
                                            <div class="ip-it-dia text-orange fw-bold me-4 text-center" style="min-width: 60px;">
                                                <span class="d-block small" style="font-size: 0.7rem;"><?= ($idioma == 'en') ? 'DAY' : 'DÍA' ?></span>
                                                <span class="d-block h3 mb-0"><?= mb_substr($dia_texto, 4) ?></span>
                                            </div>
                                            <div class="ip-it-info">
                                                <h6 class="fw-bold mb-2"><?= $titulo_dia ?></h6>
                                                <p class="text-muted small mb-0"><?= $descripcion ?></p>
                                            </div>
                                        </div>
                                <?php
                                    }
                                } else {
                                    echo "<p class='text-muted'>" . ($idioma == 'en' ? 'No summary available.' : 'No hay resumen disponible.') . "</p>";
                                }
                                ?>
                            </div>

                            <section class="mt-5">
                                <div class="ip-info-importante-box p-4 border rounded shadow-sm bg-white">
                                    <h3 class="fw-bold mb-4 border-bottom pb-2">
                                        <?= ($idioma == 'en') ? 'Important Information' : 'Información importante' ?>
                                    </h3>

                                    <div class="ip-info-text text-muted">
                                        <?php
                                        // 1. ASIGNACIÓN DIRECTA PARA EVITAR CRUCES DE IDIOMA
                                        if ($idioma == 'en') {
                                            // Si es inglés, usamos la columna _en. Si está vacía, usamos la de español como respaldo.
                                            $texto_info = (!empty($t['info_importante_detallada_en'])) ? $t['info_importante_detallada_en'] : $t['info_importante_detallada'];
                                        } else {
                                            // Si es español, vamos directo a la columna original.
                                            $texto_info = $t['info_importante_detallada'];
                                        }

                                        // 2. PROCESAMIENTO DE LOS PUNTOS
                                        if (!empty($texto_info)) {
                                            // Limpiamos retornos de carro (\r) y dividimos por saltos de línea (\n)
                                            $puntos_info = explode("\n", str_replace("\r", "", $texto_info));

                                            foreach ($puntos_info as $p) {
                                                $item = trim($p);
                                                if ($item != "") {
                                        ?>
                                                    <p class="d-flex align-items-start mb-2">
                                                        <i class="fas fa-check text-orange me-2 mt-1"></i>
                                                        <?= $item ?>
                                                    </p>
                                        <?php
                                                }
                                            }
                                        } else {
                                            // Mensaje en caso de que ambos campos estén vacíos
                                            echo "<p class='small'>" . ($idioma == 'en' ? 'No information available.' : 'No hay información disponible.') . "</p>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </section>

                            <section class="mt-5 mb-5">
                                <h3 class="fw-bold mb-4 border-bottom pb-2">
                                    <?= ($idioma == 'en') ? 'Why travel with Us?' : '¿Por qué viajar con Nosotros?' ?>
                                </h3>

                                <div class="row g-4 justify-content-center">
                                    <?php foreach ($tarjetas as $card): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="ip-why-card">
                                                <img src="assets/img/iconos/<?= $card['imagen'] ?>" alt="<?= ($idioma == 'en' && !empty($card['titulo_en'])) ? $card['titulo_en'] : $card['titulo'] ?>" loading="lazy">

                                                <div class="ip-why-card-content">
                                                    <h6 class="fw-bold" style="color: <?= !empty($card['color']) ? $card['color'] : 'var(--color-primario-azul)' ?>;">
                                                        <?php
                                                        if ($idioma == 'en' && !empty($card['titulo_en'])) {
                                                            echo mb_strtoupper($card['titulo_en']);
                                                        } else {
                                                            echo mb_strtoupper($card['titulo']);
                                                        }
                                                        ?>
                                                    </h6>

                                                    <p class="small text-muted mb-0">
                                                        <?php
                                                        if ($idioma == 'en' && !empty($card['descripcion_en'])) {
                                                            echo $card['descripcion_en'];
                                                        } else {
                                                            echo $card['descripcion'];
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>


                        <!-- AQUI EMPIEZA EL ITINERARIO: EL ITINERARIO DETALLADO -->
                        <div class="tab-pane fade" id="itinerario-det">
                            <div class="ip-premium-itinerary p-2">
                                <?php
                                // 1. CARGA DE DATOS: Obtenemos ambos idiomas
                                $raw_es = !empty($t['itinerario']) ? $t['itinerario'] : '';
                                $raw_en = !empty($t['itinerario_en']) ? $t['itinerario_en'] : '';

                                // Prioridad al inglés si estamos en ese idioma, si no español
                                $fuente = ($idioma == 'en' && !empty($raw_en)) ? $raw_en : $raw_es;

                                if (!empty($fuente)):
                                    $lineas_display = explode("\n", str_replace("\r", "", trim($fuente)));
                                    $lineas_respaldo = explode("\n", str_replace("\r", "", trim($raw_es)));

                                    $cont_det = 1;

                                    // Etiquetas según idioma
                                    $lbl_dia = ($idioma == 'en') ? 'DAY' : 'DÍA';
                                    $lbl_best = ($idioma == 'en') ? 'Highlight' : 'Lo mejor';
                                    $lbl_tec = ($idioma == 'en')
                                        ? ['MEALS', 'ACCOMMODATION', 'DIFFICULTY', 'DISTANCE', 'TIME', 'START ALT.', 'MIN. ALT.', 'MAX. ALT.', 'CAMPSITE']
                                        : ['COMIDAS', 'ALOJAMIENTO', 'DIFICULTAD', 'DISTANCIA', 'TIEMPO', 'ALT. INICIAL', 'ALT. MÍNIMA', 'ALT. MÁXIMA', 'CAMPAMENTO'];

                                    foreach ($lineas_display as $idx => $linea):
                                        // LIMPIEZA CRÍTICA: Quitamos los asteriscos dobles (**) que causan el error
                                        $linea_limpia = trim($linea, "* \t\n\r\0\x0B");
                                        if (empty($linea_limpia)) continue;

                                        // Separamos por asterisco simple
                                        $sec = explode("*", $linea_limpia);

                                        // Si la línea está mal formateada en inglés, rescatamos datos del español
                                        if (count($sec) < 4 && isset($lineas_respaldo[$idx])) {
                                            $sec_ref = explode("*", trim($lineas_respaldo[$idx], "* "));
                                            if (!isset($sec[3]) || empty(trim($sec[3]))) $sec[3] = $sec_ref[3] ?? '';
                                            if (!isset($sec[4]) || empty(trim($sec[4]))) $sec[4] = $sec_ref[4] ?? '';
                                        }

                                        // Título
                                        $p_tit = explode(":", $sec[0]);
                                        $t_tit = isset($p_tit[1]) ? trim($p_tit[1]) : trim($sec[0]);

                                        // "Lo mejor" y Descripción
                                        $t_mejor = isset($sec[1]) ? trim($sec[1]) : '';
                                        $t_desc  = isset($sec[2]) ? trim($sec[2]) : '';

                                        // Datos Técnicos (|)
                                        $i_data_raw = isset($sec[3]) ? trim($sec[3]) : '';
                                        $i_data = (!empty($i_data_raw) && strpos($i_data_raw, '|') !== false)
                                            ? explode("|", $i_data_raw)
                                            : array_fill(0, 9, '-');

                                        // Fotos (,)
                                        $fotos_raw = isset($sec[4]) ? trim($sec[4]) : '';
                                        $fotos_dia = (!empty($fotos_raw) && $fotos_raw !== '-') ? explode(",", $fotos_raw) : [];
                                ?>
                                        <div class="ip-day-wrapper mb-5 pb-4 border-bottom">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="day-number-circle d-flex flex-column align-items-center justify-content-center me-3"
                                                    style="width: 65px; height: 65px; background: #c6d544; color: white; border-radius: 50%; flex-shrink: 0;">
                                                    <small style="font-size: 0.6rem; font-weight: bold;"><?= $lbl_dia ?></small>
                                                    <span class="h4 fw-bold mb-0"><?= str_pad($cont_det, 2, '0', STR_PAD_LEFT) ?></span>
                                                </div>
                                                <h4 class="fw-bold mb-0 text-dark"><?= mb_strtoupper($t_tit) ?></h4>
                                            </div>

                                            <?php if (!empty($t_mejor)): ?>
                                                <div class="best-highlight mb-3 p-2 px-3 border-start border-4 border-warning bg-light ms-4">
                                                    <p class="mb-0 text-dark italic small"><strong><?= $lbl_best ?>:</strong> <?= $t_mejor ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($fotos_dia)): ?>
                                                <div class="row g-2 mb-3 ms-4">
                                                    <?php foreach ($fotos_dia as $foto):
                                                        $fn = trim($foto);
                                                        if (!empty($fn) && strpos($fn, '.') !== false): ?>
                                                            <div class="col-4 col-md-3">
                                                                <a href="assets/img/tours/<?= $fn ?>" data-fancybox="gallery-<?= $cont_det ?>">
                                                                    <img src="assets/img/tours/<?= $fn ?>" class="img-fluid rounded shadow-sm" loading="lazy"
                                                                        style="height: 100px; width: 100%; object-fit: cover;"
                                                                        onerror="this.closest('.col-4').remove();">
                                                                </a>
                                                            </div>
                                                    <?php endif;
                                                    endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="day-description text-muted mb-4 ps-1 small" style="text-align: justify; line-height: 1.8;">
                                                <?= nl2br($t_desc) ?>
                                            </div>

                                            <div class="technical-grid p-3 rounded-3 border bg-white shadow-sm">
                                                <div class="row g-3">
                                                    <?php
                                                    $iconos = ['fa-utensils', 'fa-bed', 'fa-chart-line', 'fa-walking', 'fa-clock', 'fa-mountain', 'fa-level-down-alt', 'fa-level-up-alt', 'fa-campground'];
                                                    for ($i = 0; $i < 9; $i++): ?>
                                                        <div class="col-6 col-md-4 d-flex align-items-start">
                                                            <div class="icon-box me-2 text-center" style="width: 25px;"><i class="fas <?= $iconos[$i] ?> text-secondary small"></i></div>
                                                            <div>
                                                                <span class="d-block fw-bold text-dark" style="font-size:0.75rem;"><?= trim($i_data[$i] ?? '-') ?></span>
                                                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.5rem;"><?= $lbl_tec[$i] ?></small>
                                                            </div>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                <?php
                                        $cont_det++;
                                    endforeach;
                                endif; ?>
                            </div>
                        </div>
                        <!-- FIN DE DEL ITINERARIO DETALLADO -->

                        <!-- INCLUIDO Y NO INCLUIDOS: AQUÍ HAY QUE HACER UN PROCESAMIENTO MÁS COMPLEJO PARA MOSTRARLO DE FORMA ATRACTIVA Y CLARA, YA QUE EN LA BASE DE DATOS ESTÁN EN UN FORMATO DE TEXTO PLANO. VAMOS A SUPONER UNA ESTRUCTURA FLEXIBLE DONDE CADA LÍNE -->

                        <div class="tab-pane fade" id="inclusiones-det">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="p-4 border-0 bg-white h-100">
                                        <p class="text-muted small mb-4">
                                            <?php if ($idioma == 'en'): ?>
                                                At <strong>Intipath Tours</strong>, included and not included services are specified in all itineraries to ensure transparency and safety on your trip.
                                            <?php else: ?>
                                                En <strong>Intipath Tours</strong>, los servicios incluidos y no incluidos están especificados en todos los itinerarios para garantizar transparencia y seguridad en su viaje.
                                            <?php endif; ?>
                                        </p>

                                        <h4 class="fw-bold mb-4 text-dark border-bottom pb-2">
                                            <?= ($idioma == 'en') ? 'What is included?' : '¿Qué está incluido?' ?>
                                        </h4>

                                        <div class="ip-inclusiones-list">
                                            <?php
                                            // 1. LÓGICA DE RESPALDO PARA INCLUSIONES
                                            $raw_inc_es = !empty($t['incluye']) ? $t['incluye'] : '';
                                            $raw_inc_en = !empty($t['incluye_en']) ? $t['incluye_en'] : '';

                                            // Si el idioma es inglés y hay contenido en inglés, lo usamos; si no, usamos español
                                            $fuente_inc = ($idioma == 'en' && !empty($raw_inc_en)) ? $raw_inc_en : $raw_inc_es;

                                            if (!empty($fuente_inc)):
                                                $incluye_items = explode("\n", str_replace("\r", "", trim($fuente_inc)));
                                                foreach ($incluye_items as $item):
                                                    if (trim($item) != ""):
                                                        $partes = explode("*", $item);
                                                        $titulo    = trim($partes[0] ?? '');
                                                        $contenido = trim($partes[1] ?? '');
                                                        $img_inc   = trim($partes[2] ?? '');
                                            ?>
                                                        <div class="inclusion-group mb-5">
                                                            <h6 class="fw-bold d-flex align-items-center mb-3" style="color: #333; font-size: 1.1rem;">
                                                                <i class="fas fa-check me-2" style="color: #c6d544;"></i> <?= $titulo ?>
                                                            </h6>

                                                            <div class="ps-4">
                                                                <?php
                                                                $bloques = explode("#", $contenido);
                                                                foreach ($bloques as $bloque):
                                                                    $detalles = explode("|", $bloque);
                                                                    $sub = trim($detalles[0] ?? '');
                                                                    $txt = trim($detalles[1] ?? '');
                                                                ?>
                                                                    <?php if ($sub): ?>
                                                                        <div class="d-flex align-items-start mb-2">
                                                                            <span class="me-2" style="color: #c6d544; font-size: 1.2rem; line-height: 1;">•</span>
                                                                            <strong class="text-dark small" style="font-size: 0.95rem;"><?= $sub ?></strong>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                    <?php if ($txt): ?>
                                                                        <p class="text-muted small mb-3 ps-3" style="text-align: justify; line-height: 1.6;">
                                                                            <?= $txt ?>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>

                                                                <?php if ($img_inc): ?>
                                                                    <div class="mt-3">
                                                                        <img src="assets/img/tours/<?= $img_inc ?>" loading="lazy"
                                                                            class="img-fluid rounded shadow-sm border"
                                                                            style="max-width: 550px; cursor: pointer; transition: transform 0.3s;"
                                                                            onclick="abrirZoom(this.src)"
                                                                            onerror="this.style.display='none';">
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                            <?php
                                                    endif;
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>

                                        <div class="mt-5 p-4 border rounded-3 bg-light shadow-sm">
                                            <h5 class="fw-bold text-danger mb-4 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-times-circle me-2"></i> <?= ($idioma == 'en') ? 'NOT INCLUDED' : 'NO INCLUYE' ?>
                                            </h5>

                                            <div class="ip-no-incluye-list">
                                                <?php
                                                // 2. LÓGICA DE RESPALDO PARA NO INCLUYE
                                                $raw_no_es = !empty($t['no_incluye']) ? $t['no_incluye'] : '';
                                                $raw_no_en = !empty($t['no_incluye_en']) ? $t['no_incluye_en'] : '';

                                                $fuente_no = ($idioma == 'en' && !empty($raw_no_en)) ? $raw_no_en : $raw_no_es;

                                                if (!empty($fuente_no)):
                                                    $no_incluye_items = explode("\n", str_replace("\r", "", trim($fuente_no)));
                                                    foreach ($no_incluye_items as $item):
                                                        if (trim($item) != ""):
                                                            $partes_ni = explode("|", $item);
                                                            $sub_ni    = trim($partes_ni[0] ?? '');
                                                            $txt_ni    = trim($partes_ni[1] ?? '');
                                                ?>
                                                            <div class="mb-3 pb-2 border-bottom border-danger border-opacity-10">
                                                                <div class="d-flex align-items-start mb-1">
                                                                    <i class="fas fa-times text-danger mt-1 me-2" style="font-size: 0.8rem;"></i>
                                                                    <strong class="text-dark small" style="font-size: 0.9rem;"><?= strtoupper($sub_ni) ?></strong>
                                                                </div>
                                                                <?php if ($txt_ni): ?>
                                                                    <p class="text-muted mb-0 ps-4" style="font-size: 0.75rem; line-height: 1.4;">
                                                                        <?= $txt_ni ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                <?php
                                                        endif;
                                                    endforeach;
                                                endif;
                                                ?>
                                            </div>

                                            <div class="mt-4 p-3 bg-white rounded border border-danger border-opacity-25 text-center">
                                                <small class="text-muted italic" style="font-size: 0.7rem;">
                                                    <i class="fas fa-info-circle text-danger me-1"></i>
                                                    <?= ($idioma == 'en') ? 'Personal expenses or tips are not included.' : 'Gastos personales o propinas no están contemplados.' ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FIN DE INCLUIDOS Y NO INCLUIDOS -->

                        <!-- AQUI EMPIEZA LOS ADICIONALES Y EN INGLES EXTRAS                                    -->
                        <div class="tab-pane fade" id="adicionales-det">
                            <div class="p-4 border-0 bg-white">
                                <p class="text-muted small mb-4 italic">
                                    <?= ($idioma == 'en') ? 'Here is a brief list of the extras you can enjoy during this package:' : 'Aquí te dejamos una breve lista de los extras que puedes disfrutar durante este paquete:' ?>
                                </p>

                                <div class="ip-adicionales-list">
                                    <?php
                                    // Limpieza profunda: quitamos posibles espacios en blanco locos al inicio/fin
                                    $campo_extras = isset($extras_display) ? trim($extras_display) : '';

                                    if (!empty($campo_extras)):
                                        // Normalizamos saltos de línea (Windows/Linux) y segmentamos
                                        $campo_extras = str_replace(["\r\n", "\r"], "\n", $campo_extras);
                                        $adicionales_items = explode("\n", $campo_extras);

                                        foreach ($adicionales_items as $item):
                                            $item = trim($item);
                                            if ($item != ""):
                                                $partes = explode("*", $item);
                                                $titulo_adj = trim($partes[0] ?? '');
                                                $desc_adj   = trim($partes[1] ?? '');
                                                $precio_adj = trim($partes[2] ?? '');
                                    ?>
                                                <div class="adicional-item mb-5 pb-3 border-bottom border-light">
                                                    <div class="mb-3">
                                                        <h5 class="fw-bold text-dark d-inline-block mb-0">
                                                            <?= htmlspecialchars($titulo_adj) ?>
                                                        </h5>
                                                        <div style="height: 3px; width: 40px; background: #c6d544; margin-top: 5px;"></div>
                                                    </div>

                                                    <?php if ($desc_adj): ?>
                                                        <div class="text-muted small mb-3" style="text-align: justify; line-height: 1.7;">
                                                            <?= nl2br(htmlspecialchars($desc_adj)) ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($precio_adj): ?>
                                                        <div class="p-2 px-3 bg-light d-inline-block rounded border border-warning border-opacity-25">
                                                            <span class="text-dark small fw-bold">
                                                                <i class="fas fa-tag text-warning me-2"></i>
                                                                <?= ($idioma == 'en') ? 'Additional cost:' : 'Costo adicional:' ?>
                                                                <span class="text-primary"><?= htmlspecialchars($precio_adj) ?></span>
                                                                <?= ($idioma == 'en') ? 'per person.' : 'por persona.' ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                        <?php
                                            endif;
                                        endforeach;
                                    else: ?>
                                        <div class="alert alert-light text-muted small border-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <?= ($idioma == 'en') ? 'No additional items configured for this tour.' : 'No hay artículos adicionales configurados para este tour.' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- AQUI ES EL FIN LOS EXTRAS                                     -->


                        <!-- AQUI EMPIEZA DE ANTES DE VIAJAR -->
                        <div class="tab-pane fade" id="antes-viajar-det">
                            <div class="p-4 bg-white">
                                <div class="row ip-antes-viajar-grid">
                                    <?php
                                    // Usamos la variable bilingüe definida en el controlador
                                    $campo_antes = isset($antes_viajar_display) ? trim($antes_viajar_display) : '';

                                    if ($campo_antes != ""):
                                        $campo_antes = str_replace("\r", "", $campo_antes);
                                        $secciones = explode("\n", $campo_antes);

                                        foreach ($secciones as $item):
                                            $item = trim($item);
                                            if ($item != ""):
                                                /**
                                                 * ESTRUCTURA: Título * Contenido (sub|txt#sub|txt) * Archivo/Imagen
                                                 */
                                                $partes = explode("*", $item);
                                                $titulo_p   = trim($partes[0] ?? ($idioma == 'en' ? 'Information' : 'Información'));
                                                $contenido  = isset($partes[1]) ? trim($partes[1]) : '';
                                                $archivo    = isset($partes[2]) ? trim($partes[2]) : '';

                                                $es_pdf = (!empty($archivo) && pathinfo($archivo, PATHINFO_EXTENSION) === 'pdf');
                                    ?>

                                                <div class="col-lg-6 col-12 mb-4">
                                                    <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">

                                                        <?php if (!empty($archivo) && !$es_pdf): ?>
                                                            <div class="position-relative d-flex align-items-center justify-content-center text-center p-4"
                                                                style="height: 200px; background-position: center; background-size: cover; background-repeat: no-repeat;" data-bg-lazy="assets/img/tours/<?= $archivo ?>">
                                                                <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.4);"></div>
                                                                <h4 class="text-white fw-bold m-0 text-uppercase position-relative"><?= $titulo_p ?></h4>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="bg-secondary d-flex align-items-center justify-content-center text-center p-4" style="height: 200px;">
                                                                <h4 class="text-white fw-bold m-0 text-uppercase"><?= $titulo_p ?></h4>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="card-body p-4">
                                                            <?php if (!empty($contenido)):
                                                                $bloques = explode("#", $contenido);
                                                                foreach ($bloques as $bloque):
                                                                    $detalles = explode("|", $bloque);
                                                                    $sub = trim($detalles[0] ?? '');
                                                                    $txt = trim($detalles[1] ?? '');
                                                            ?>
                                                                    <div class="mb-3">
                                                                        <?php if ($sub && $txt): ?>
                                                                            <p class="mb-1"><strong><?= $sub ?>:</strong> <?= $txt ?></p>
                                                                        <?php else: ?>
                                                                            <p class="text-muted small mb-0"><?= $sub ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                            <?php endforeach;
                                                            endif; ?>

                                                            <?php if ($es_pdf): ?>
                                                                <a href="assets/img/tours/<?= $archivo ?>" target="_blank" class="btn btn-danger w-100 fw-bold mt-2 py-2" style="border-radius: 8px;">
                                                                    <i class="fas fa-file-pdf me-2"></i>
                                                                    <?= ($idioma == 'en') ? 'DOWNLOAD GUIDE' : 'DESCARGAR GUÍA' ?>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                        <?php
                                            endif;
                                        endforeach;
                                    else: ?>
                                        <div class="col-12 text-center text-muted p-5">
                                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                                            <p><?= ($idioma == 'en') ? 'No additional information available.' : 'No hay información adicional disponible.' ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- AQUI FIN DE ANTES DE VIAJAR -->

                        <!-- AQUI EMPIEZA DE EQUIPAJE -->
                        <div class="tab-pane fade" id="equipaje-det">
                            <div class="p-4">
                                <p class="text-muted small mb-4 italic">
                                    <?php if ($idioma == 'en'): ?>
                                        Below is a list of recommended items to bring on your trip.
                                    <?php else: ?>
                                        A continuación, te presentamos una lista de los artículos recomendados para llevar en tu viaje.
                                    <?php endif; ?>
                                </p>

                                <div class="row">
                                    <?php
                                    // Usamos la variable bilingüe definida en el controlador
                                    $campo_equipaje = isset($lista_equipaje_display) ? trim($lista_equipaje_display) : '';

                                    if ($campo_equipaje != ""):
                                        $campo_equipaje = str_replace("\r", "", $campo_equipaje);
                                        $lineas = explode("\n", $campo_equipaje);

                                        foreach ($lineas as $linea):
                                            $linea = trim($linea);
                                            if ($linea != ""):
                                                if (strpos($linea, '*') === false): ?>
                                                    <div class="col-12 mt-4 mb-3">
                                                        <h5 class="ip-categoria-titulo"><?= $linea ?></h5>
                                                    </div>
                                                <?php else:
                                                    $partes = explode("*", $linea);
                                                    $nombre_articulo = trim($partes[0] ?? '');
                                                    $archivo         = trim($partes[1] ?? '');
                                                    $ruta_img        = "assets/img/tours/" . $archivo;
                                                ?>
                                                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                                                        <div class="equipaje-card-custom">
                                                            <div class="equipaje-img-box">
                                                                <?php if (!empty($archivo)): ?>
                                                                    <img src="<?= $ruta_img ?>" alt="<?= $nombre_articulo ?>" loading="lazy">
                                                                <?php else: ?>
                                                                    <div class="no-img-box"><i class="fas fa-box-open"></i></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="equipaje-info-box">
                                                                <span><?= $nombre_articulo ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                        <?php
                                            endif;
                                        endforeach;
                                    else: ?>
                                        <div class="col-12 text-center text-muted p-5">
                                            <i class="fas fa-suitcase fa-2x mb-3"></i>
                                            <p><?= ($idioma == 'en') ? 'No luggage list available.' : 'No hay lista de equipaje disponible.' ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- AQUI ES EL FIN DE EQUIPAJE -->


                        <!-- AQUI EMPIEZA DE PRECIOS Y CONDICIONES -->
                        <div class="tab-pane fade" id="precios-det">
                            <div class="p-4 bg-white">
                                <div class="ip-precios-wrapper">
                                    <?php
                                    // Usamos la variable bilingüe definida en el controlador
                                    $campo_precios = isset($aclimatacion_display) ? trim($aclimatacion_display) : '';

                                    if ($campo_precios != ""):
                                        // Limpiamos saltos de línea y separamos por líneas
                                        $campo_precios = str_replace("\r", "", $campo_precios);
                                        $lineas = explode("\n", $campo_precios);

                                        foreach ($lineas as $linea):
                                            $linea = trim($linea);
                                            if ($linea != ""):

                                                // 1. SI EMPIEZA CON # -> TÍTULO CON RAYA NARANJA
                                                if (strpos($linea, '#') === 0): ?>
                                                    <h5 class="fw-bold text-dark mt-4 mb-3 pb-2" style="border-bottom: 2px solid #c6d544; display: inline-block; color: #333 !important;">
                                                        <?= trim(str_replace('#', '', $linea)) ?>
                                                    </h5>
                                                <?php

                                                // 2. SI EMPIEZA CON - -> LISTA CON CHECK (V) NARANJA
                                                elseif (strpos($linea, '-') === 0): ?>
                                                    <div class="d-flex align-items-start mb-2 ps-1">
                                                        <i class="fas fa-check me-2 mt-1" style="color: #c6d544; font-size: 0.9rem;"></i>
                                                        <span class="text-muted" style="font-size: 0.95rem;"><?= trim(str_replace('-', '', $linea)) ?></span>
                                                    </div>
                                                <?php

                                                // 3. SI TIENE DOS PUNTOS (:) -> PRIMERA PARTE EN NEGRITA
                                                elseif (strpos($linea, ':') !== false):
                                                    $partes = explode(':', $linea, 2);
                                                ?>
                                                    <p class="mb-2" style="font-size: 0.95rem;">
                                                        <strong class="text-dark" style="color: #222 !important;"><?= trim($partes[0]) ?>:</strong>
                                                        <span class="text-muted"><?= trim($partes[1]) ?></span>
                                                    </p>
                                                <?php

                                                // 4. TEXTO NORMAL O PÁRRAFO
                                                else: ?>
                                                    <p class="text-muted mb-3" style="font-size: 0.95rem; line-height: 1.6; text-align: justify;">
                                                        <?= $linea ?>
                                                    </p>
                                        <?php endif;
                                            endif;
                                        endforeach;
                                    else: ?>
                                        <div class="alert alert-light border text-center text-muted p-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <?= ($idioma == 'en') ? 'Price details for this tour are being updated.' : 'Los detalles de precios para este tour se están actualizando.' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- AQUI ES EL FIN DE PRECIOS Y CONDICIONES -->




                        <!-- AQUI EMEPIEZA DE CONSULTAR CON UN EXPERTO -->
                        <div class="tab-pane fade" id="consultar-det" role="tabpanel">
                            <div class="container-fluid py-4">

                                <div class="card border-0 shadow-sm p-4 mb-4 bg-white" style="border-radius: 15px;">
                                    <div class="mb-4">
                                        <h4 class="fw-bold" style="color: #2c3e50;">
                                            <?= ($idioma == 'en') ? 'Questions? Consult with an expert' : '¿Tienes dudas? Consulta con un experto' ?>
                                        </h4>
                                        <p class="text-muted small">
                                            <?php if ($idioma == 'en'): ?>
                                                Thank you for your interest in traveling with <strong>Intipath Tours</strong>. Complete the form and we will respond shortly.
                                            <?php else: ?>
                                                Gracias por tu interés en viajar con <strong>Intipath Tours</strong>. Completa el formulario y te responderemos en breve.
                                            <?php endif; ?>
                                        </p>
                                    </div>


                                    <?php if (isset($_GET['res'])): ?>
                                        <?php if ($_GET['res'] == 'success'): ?>
                                            <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 10px;">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <?= ($idioma == 'en') ? 'Inquiry sent successfully! We will contact you soon.' : '¡Consulta enviada con éxito! Nos contactaremos pronto.' ?>
                                            </div>
                                        <?php elseif ($_GET['res'] == 'error'): ?>
                                            <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 10px;">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <?= ($idioma == 'en') ? 'There was an error sending. Please try again.' : 'Hubo un error al enviar. Por favor, intenta de nuevo.' ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <form action="admin/enviar_consulta.php" method="POST" onsubmit="return bloquearEnvioBooking(this)">
                                        <input type="hidden" name="id_tour" value="<?php echo $t['id']; ?>">
                                        <input type="hidden" name="tour_interes" value="<?php echo $titulo_display; ?>">
                                        <input type="hidden" name="whatsapp" id="whatsapp-input-consultar" value="">
                                        <input type="hidden" name="accion" id="accion-input-consultar" value="consultar">
                                        <?php campoCSRF(); ?>
                                        <!-- Honeypot anti-bots (invisible) -->
                                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px; opacity:0; height:0; width:0;" aria-hidden="true">

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'FIRST NAME*' : 'NOMBRE*' ?>
                                                </label>
                                                <input type="text" name="nombre" class="form-control border-0 bg-light py-2" placeholder="<?= ($idioma == 'en') ? 'Your first name' : 'Tu nombre' ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'LAST NAME*' : 'APELLIDO*' ?>
                                                </label>
                                                <input type="text" name="apellido" class="form-control border-0 bg-light py-2" placeholder="<?= ($idioma == 'en') ? 'Your last name' : 'Tu apellido' ?>" required>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'EMAIL ADDRESS*' : 'CORREO ELECTRÓNICO*' ?>
                                                </label>
                                                <input type="email" name="email" class="form-control border-0 bg-light py-2" placeholder="example@mail.com" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="small fw-bold text-muted">
                                                    <?= ($idioma == 'en') ? 'Country*' : 'País*' ?>
                                                </label>
                                                <select name="pais" class="form-select border-0 border-bottom rounded-0 px-0">
                                                    <?php foreach ($paises as $codigo => $nombre): ?>
                                                        <option value="<?= $nombre ?>" <?= ($codigo == 'PE') ? 'selected' : '' ?>>
                                                            <?= ($codigo == 'PE' ? '🇵🇪 ' : '') . $nombre ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'PHONE / WHATSAPP' : 'TELÉFONO / WHATSAPP' ?>
                                                </label>
                                                <input type="tel" name="telefono" class="form-control border-0 bg-light py-2" placeholder="+51 ..." oninput="document.getElementById('whatsapp-input-consultar').value=this.value">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="small fw-bold text-muted">
                                                    <?= ($idioma == 'en') ? 'I am interested in:' : 'Estoy Interesado en:' ?>
                                                </label>
                                                <input type="text" name="tour_interes_visible" class="form-control bg-light border-0 fw-bold" value="<?php echo $titulo_display; ?>" readonly>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'TENTATIVE DATE*' : 'FECHA TENTATIVA*' ?>
                                                </label>
                                                <input type="date" name="fecha_viaje" class="form-control border-0 bg-light py-2" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'ADULTS*' : 'ADULTOS*' ?>
                                                </label>
                                                <input type="number" name="adultos" class="form-control border-0 bg-light py-2" value="2" min="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'CHILDREN' : 'NIÑOS' ?>
                                                </label>
                                                <input type="number" name="ninos" class="form-control border-0 bg-light py-2" value="0" min="0">
                                            </div>

                                             <div class="col-12" id="pasajeros-container-consultar">
<?php
$pas_idx = 0;
$adultos_n = 2;
$ninos_n = 0;
$pas_tipo_label = fn($t) => $t === 'adulto' ? ($idioma == 'en' ? 'Adult' : 'Adulto') : ($idioma == 'en' ? 'Child' : 'Niño');
for ($i = 0; $i < $adultos_n + $ninos_n; $i++):
    $pas_tipo = ($i < $adultos_n) ? 'adulto' : 'nino';
?>
<div class="pasajero-block row g-2 mt-1 align-items-end" style="background:#f9f9f9;padding:8px;border-radius:6px;margin-bottom:6px;position:relative;">
    <div class="col-md-1">
        <select name="pasajeros[<?= $pas_idx ?>][tipo]" class="form-select form-select-sm" style="font-size:12px;">
            <option value="adulto"<?= $pas_tipo === 'adulto' ? ' selected' : '' ?>><?= $idioma == 'en' ? 'Adult' : 'Adulto' ?></option>
            <option value="nino"<?= $pas_tipo === 'nino' ? ' selected' : '' ?>><?= $idioma == 'en' ? 'Child' : 'Niño' ?></option>
        </select>
    </div>
    <div class="col-md-3"><input type="text" name="pasajeros[<?= $pas_idx ?>][nombres]" class="form-control form-control-sm" placeholder="Nombres" required></div>
    <div class="col-md-3"><input type="text" name="pasajeros[<?= $pas_idx ?>][apellidos]" class="form-control form-control-sm" placeholder="Apellidos" required></div>
    <div class="col-md-2"><input type="text" name="pasajeros[<?= $pas_idx ?>][documento]" class="form-control form-control-sm" placeholder="DNI/Pasaporte" required></div>
    <div class="col-md-3"><select name="pasajeros[<?= $pas_idx ?>][pais]" class="form-select form-select-sm"><?= $paises_opts ?></select></div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <small class="text-muted" style="font-size:10px;"><?= $idioma == 'en' ? 'Passenger' : 'Pasajero' ?> #<?= $i + 1 ?> (<?= $pas_tipo_label($pas_tipo) ?>)</small>
        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:0 6px;" onclick="eliminarBloque(this)">✕</button>
    </div>
</div>
<?php $pas_idx++; endfor; ?>
<div class="pasajero-template pasajero-block row g-2 mt-1 align-items-end" style="background:#f9f9f9;padding:8px;border-radius:6px;margin-bottom:6px;position:relative;display:none;">
    <div class="col-md-1">
        <select name="pasajeros[INDEX][tipo]" class="form-select form-select-sm" style="font-size:12px;">
            <option value="adulto"><?= $idioma == 'en' ? 'Adult' : 'Adulto' ?></option>
            <option value="nino"><?= $idioma == 'en' ? 'Child' : 'Niño' ?></option>
        </select>
    </div>
    <div class="col-md-3"><input type="text" name="pasajeros[INDEX][nombres]" class="form-control form-control-sm" placeholder="Nombres"></div>
    <div class="col-md-3"><input type="text" name="pasajeros[INDEX][apellidos]" class="form-control form-control-sm" placeholder="Apellidos"></div>
    <div class="col-md-2"><input type="text" name="pasajeros[INDEX][documento]" class="form-control form-control-sm" placeholder="DNI/Pasaporte"></div>
    <div class="col-md-3"><select name="pasajeros[INDEX][pais]" class="form-select form-select-sm"><?= $paises_opts ?></select></div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <small class="text-muted" style="font-size:10px;"><?= $idioma == 'en' ? 'Passenger' : 'Pasajero' ?></small>
        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:0 6px;" onclick="eliminarBloque(this)">✕</button>
    </div>
</div>
<div class="col-12" style="margin-top:4px;">
    <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:12px;" onclick="agregarBloque('pasajeros-container-consultar','adulto')">+ <?= $idioma == 'en' ? 'Add adult' : 'Agregar adulto' ?></button>
    <button type="button" class="btn btn-sm btn-outline-success" style="font-size:12px;" onclick="agregarBloque('pasajeros-container-consultar','nino')">+ <?= $idioma == 'en' ? 'Add child' : 'Agregar niño' ?></button>
</div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-bold text-secondary small">
                                                    <?= ($idioma == 'en') ? 'YOUR MESSAGE*' : 'TU MENSAJE*' ?>
                                                </label>
                                                <textarea name="mensaje" class="form-control border-0 bg-light py-2" rows="4" placeholder="<?= ($idioma == 'en') ? 'Do you have any special request?' : '¿Tienes alguna petición especial?' ?>" required></textarea>
                                            </div>

                                            <div class="col-md-6">
                                                <button type="submit" class="btn w-100 fw-bold py-3 text-white" style="background-color: #15305D; border-radius: 10px;" onclick="document.getElementById('accion-input-consultar').value='consultar'">
                                                    <i class="fas fa-paper-plane me-2"></i>
                                                    <?= ($idioma == 'en') ? 'SEND INQUIRY' : 'SOLO CONSULTAR' ?>
                                                </button>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" class="btn w-100 fw-bold py-3 text-white" style="background-color: #27ae60; border-radius: 10px;" onclick="document.getElementById('accion-input-consultar').value='pagar'">
                                                    <i class="fas fa-credit-card me-2"></i>
                                                    <?= ($idioma == 'en') ? 'PAY NOW' : 'PAGAR AHORA' ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="p-4 border rounded bg-white shadow-sm mb-4" style="border-left: 5px solid #c6d544 !important;">
                                    <div class="mb-3">
                                        <p class="mb-1" style="font-size: 0.9rem;">
                                            <strong><?= ($idioma == 'en') ? 'Remember |' : 'Recuerda |' ?></strong>
                                            <?= ($idioma == 'en')
                                                ? 'Shortly, one of our travel experts will contact you to confirm the availability of the trip you have chosen.'
                                                : 'En breve, uno de nuestros expertos en viajes se pondrá en contacto contigo para confirmar la disponibilidad del viaje que hayas elegido.' ?>
                                        </p>
                                    </div>
                                    <hr class="my-2">
                                    <div>
                                        <p class="text-danger mb-0" style="font-size: 0.85rem;">
                                            <strong><?= ($idioma == 'en') ? 'Note |' : 'Nota |' ?></strong>
                                            <?= ($idioma == 'en')
                                                ? 'Due to the rainy season and trail maintenance, we do not offer treks during the month of February.'
                                                : 'Debido a la temporada de lluvias y al mantenimiento del sendero, no ofrecemos caminatas durante el mes de febrero.' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="p-4 border h-100 text-center bg-white shadow-sm" style="border-color: #c6d544 !important; border-radius: 12px;">
                                            <i class="fas fa-headset fa-2x mb-3" style="color: #c6d544;"></i>
                                            <p class="small text-muted mb-2">
                                                <?= ($idioma == 'en') ? 'Feel free to contact us by email at' : 'No dudes en contactarnos por correo a' ?>
                                            </p>
                                            <p class="fw-bold mb-1" style="color: #c6d544;">info@intipathtours.com</p>
                                            <p class="small text-muted">
                                                <?= ($idioma == 'en') ? 'or call us at' : 'o llámanos al' ?> <strong>(+51)920 307 331</strong>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="p-4 border h-100 text-center bg-white shadow-sm" style="border-color: #c6d544 !important; border-radius: 12px;">
                                            <i class="far fa-calendar-check fa-2x mb-3" style="color: #c6d544;"></i>
                                            <p class="fw-bold mb-2">
                                                <?= ($idioma == 'en') ? 'Opening Hours' : 'Horario de Atención' ?>
                                            </p>
                                            <p class="text-muted" style="font-size: 0.9rem;">
                                                <strong><?= ($idioma == 'en') ? 'Monday to Sunday:' : 'Lunes a Domingo:' ?></strong><br>
                                                9:00 am <?= ($idioma == 'en') ? 'to' : 'a' ?> Lun - Dom: 09:00 - 18:00
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="p-4 border h-100 text-center bg-white shadow-sm" style="border-color: #c6d544 !important; border-radius: 12px;">
                                            <i class="fas fa-map-marker-alt fa-2x mb-3" style="color: #c6d544;"></i>
                                            <p class="fw-bold mb-2">
                                                <?= ($idioma == 'en') ? 'Our Office' : 'Nuestra Oficina' ?>
                                            </p>
                                            <p class="text-muted" style="font-size: 0.9rem;">
                                                A.P.V Coviduc A-6, Paradero Sol de Oro, Cusco
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>



                <!-- /**RESERVA DE WIDGET */ -->

                <div class="col-lg-4">
                    <div class="ip-reserva-card-lateral border shadow-lg rounded-4 sticky-top bg-white" style="top: 100px; z-index: 10;">
                        <div class="bg-dark text-white text-center py-2 fw-bold rounded-top-4" style="font-size: 13px; letter-spacing: 1px; background: linear-gradient(45deg, #c6d544, #c6d544);">
                            <?= ($idioma == 'en') ? 'BOOK THIS TOUR' : 'RESERVAR TOUR' ?>
                        </div>
                        <div class="p-3">
                            <p class="text-center small mb-1 text-muted text-uppercase" style="font-size: 10px;">
                                <?= ($idioma == 'en' && !empty($t['titulo_corto_en'])) ? $t['titulo_corto_en'] : $t['titulo_corto'] ?>
                            </p>
                            <h3 class="text-center fw-bold text-primary mb-3">
                                <?= precioFormato($t, $idioma, 2) ?>
                            </h3>

                            <div class="mb-3">
                                <label class="small fw-bold mb-2 text-dark d-block text-center">
                                    <i class="fas fa-calendar-alt me-1 text-warning"></i>
                                    <?= ($idioma == 'en') ? '1. Select your date' : '1. Selecciona tu fecha' ?>
                                </label>
                                <div id='calendar-widget-lateral'></div>
                                <input type="hidden" id="fechaReservaSeleccionada" name="fecha_viaje">

                                <div class="mt-2 text-center" style="font-size: 10px; line-height: 1.6;">
                                    <span style="display: inline-block; width: 10px; height: 10px; background: #e8f5e9; border-radius: 2px; margin-right: 3px; vertical-align: middle;"></span>
                                    <span style="vertical-align: middle;"><?= ($idioma == 'en') ? 'Available' : 'Disponible' ?></span>
                                    <span class="ms-2" style="display: inline-block; width: 10px; height: 10px; background: #fdf2f2; border-radius: 2px; margin-right: 3px; vertical-align: middle;"></span>
                                    <span style="vertical-align: middle;"><?= ($idioma == 'en') ? 'Past' : 'Pasado' ?></span>
                                    <span class="ms-2" style="display: inline-block; width: 10px; height: 10px; background: #fffde7; border: 1px solid #fbc02d; border-radius: 2px; margin-right: 3px; vertical-align: middle;"></span>
                                    <span style="vertical-align: middle;"><?= ($idioma == 'en') ? 'Today' : 'Hoy' ?></span>
                                </div>
                                <div id="fechaConfirmacion" class="mt-2 text-center" style="font-size: 11px; color: #2e7d32; font-weight: 600; display: none;"></div>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold mb-2 d-block text-center">
                                    <i class="fas fa-users me-1 text-warning"></i>
                                    <?= ($idioma == 'en') ? '2. Passengers' : '2. Pasajeros' ?>
                                </label>
                                <div class="input-group input-group-sm mx-auto" style="max-width: 130px;">
                                    <button class="btn btn-outline-primary" type="button" onclick="cambiarPax(-1)">-</button>
                                    <input type="number" id="paxReserva" class="form-control text-center fw-bold" value="1" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="cambiarPax(1)">+</button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 border-top pt-2">
                                <span class="small fw-bold"><?= ($idioma == 'en') ? 'TOTAL:' : 'TOTAL:' ?></span>
                                <span class="fw-bold text-primary h4 mb-0" id="totalReservaDisplay"><?= precioFormato($t, $idioma, 2) ?></span>
                            </div>

                            <button type="button" id="btnReservarAhora" disabled
                                class="btn btn-secondary w-100 fw-bold py-3 shadow-sm text-white"
                                style="border-radius: 12px; transition: 0.3s;"
                                data-bs-toggle="modal"
                                data-bs-target="#modalReservaTour">
                                <?= ($idioma == 'en') ? 'BOOK NOW' : 'RESERVAR AHORA' ?>
                            </button>

                            <p id="msgAvisoFecha" class="text-danger text-center mt-2 mb-0" style="font-size: 10px;">
                                <?= ($idioma == 'en') ? '* Choose a day in green to enable' : '* Elige un día en verde para habilitar' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($relacionados)): ?>
        <section class="contenedor mt-5 mb-5" style="clear: both;">
            <div class="text-center mb-5">
                <h3 class="fw-bold text-uppercase">
                    <?= ($idioma == 'en') ? 'Related Peru Trips' : 'Viajes a Perú relacionados' ?>
                </h3>
                <p class="text-muted">
                    <?= ($idioma == 'en') ? 'Here are other options that suit your needs' : 'Aquí tienes otras opciones que se adapten a tus necesidades' ?>
                </p>
                <div class="mx-auto" style="width: 60px; height: 3px; background: #c6d544;"></div>
            </div>
            <div class="row g-4">
                <?php foreach ($relacionados as $rel):
                    // Lógica de traducción para cada tour relacionado
                    $tit_rel   = ($idioma == 'en' && !empty($rel['titulo_en'])) ? $rel['titulo_en'] : $rel['titulo'];
                    $short_rel = ($idioma == 'en' && !empty($rel['titulo_corto_en'])) ? $rel['titulo_corto_en'] : $rel['titulo_corto'];
                    $dur_rel   = ($idioma == 'en' && !empty($rel['duracion_en'])) ? $rel['duracion_en'] : $rel['duracion'];
                ?>
                    <div class="col-md-6 col-lg-3">
                        <a href="detalle_tour.php?id=<?= $rel['id'] ?>" class="ip-rel-card">
                            <div class="ip-rel-img-wrapper">
<img src="assets/img/tours/<?= $rel['imagen_principal'] ?>" loading="lazy" alt="<?= htmlspecialchars($tit_rel) ?>">
                                <span class="ip-rel-badge">CUSCO</span>
                            </div>
                            <div class="ip-rel-content">
                                <p class="ip-rel-tags small text-uppercase mb-1"><?= htmlspecialchars($short_rel) ?></p>
                                <h5 class="fw-bold text-white mb-2"><?= htmlspecialchars($tit_rel) ?></h5>
                                <div class="ip-rel-divider"></div>
                                <p class="ip-rel-price mb-0">
                                    <?= $dur_rel ?> <?= ($idioma == 'en') ? 'from' : 'desde' ?>
                                    <span class="fw-bold text-warning"><?= precioFormato($rel, $idioma) ?></span>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="modal fade" id="modalReservaTour" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-body p-0">
                    <div class="row g-0">

                        <div class="col-lg-5 d-none d-lg-block" style="background: #15305D; min-height: 550px;">
                            <div class="h-100 w-100 d-flex flex-column justify-content-center align-items-center text-center p-5">
                                <img src="assets/img/Machu-Picchu.png" alt="IntiPath Tours" style="max-width: 140px;" class="mb-3" loading="lazy">

                                <h2 class="text-white fw-bold">
                                    <?= ($idioma == 'en') ? 'Easy Contact' : 'Contáctanos Fácil' ?>
                                </h2>

                                <p class="text-white-50">
                                    <?= ($idioma == 'en')
                                        ? 'Our travel specialists will provide everything you need to make this an unforgettable experience.'
                                        : 'Nuestros especialistas en viajes te brindarán todo lo necesario para que esta sea una experiencia inolvidable.' ?>
                                </p>

                                <div id="modal-fecha-display" class="mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.15);">
                                    <p class="text-white-50 mb-1" style="font-size:0.85rem;">
                                        <i class="fas fa-calendar-alt me-2"></i><?= ($idioma == 'en') ? 'Travel date:' : 'Fecha de viaje:' ?>
                                    </p>
                                    <p class="text-white fw-bold" id="modal-fecha-value" style="font-size:1.05rem;">
                                        <?= ($idioma == 'en') ? 'Not selected' : 'No seleccionada' ?>
                                    </p>
                                </div>

                                <div id="modal-resumen-pago" class="mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.15); width:100%; text-align:left;">
                                    <p class="text-white-50 mb-3" style="font-size:0.85rem; text-align:center;">
                                        <i class="fas fa-calculator me-2"></i><?= ($idioma == 'en') ? 'Payment options:' : 'Opciones de pago:' ?>
                                    </p>
                                    
                                    <!-- Selección de Pago -->
                                    <div class="mb-3 p-2 rounded" style="background: rgba(255,255,255,0.1);">
                                        <div class="d-flex gap-2">
                                            <div class="flex-fill">
                                                <input type="radio" class="btn-check" name="tipo_pago" id="modal_pago_adelanto" value="adelanto" checked onchange="actualizarResumenPago()">
                                                <label class="btn btn-outline-light btn-sm w-100 py-2 border-0" for="modal_pago_adelanto" style="background: rgba(255,255,255,0.05); font-size: 0.75rem;">
                                                    <?= ($idioma == 'en') ? 'Advance' : 'Adelanto' ?> (<span id="modal-porc-adelanto-btn"><?= (int)($t['porcentaje_adelanto'] ?? 30) ?></span>%)
                                                </label>
                                            </div>
                                            <div class="flex-fill">
                                                <input type="radio" class="btn-check" name="tipo_pago" id="modal_pago_total" value="total" onchange="actualizarResumenPago()">
                                                <label class="btn btn-outline-light btn-sm w-100 py-2 border-0" for="modal_pago_total" style="background: rgba(255,255,255,0.05); font-size: 0.75rem;">
                                                    <?= ($idioma == 'en') ? '100% Full' : '100% Total' ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="font-size:0.8rem; color:rgba(255,255,255,0.75); line-height:1.7;">
                                        <div class="d-flex justify-content-between">
                                            <span><?= ($idioma == 'en') ? 'Adults' : 'Adultos' ?> <span id="modal-adultos-label">2</span> × <?= simboloMoneda($idioma) ?><?= number_format(montoMoneda($t, $idioma), 2) ?></span>
                                            <span><?= simboloMoneda($idioma) ?><span id="modal-total-adultos"><?= number_format(2 * montoMoneda($t, $idioma), 2) ?></span></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span><?= ($idioma == 'en') ? 'Children' : 'Niños' ?> <span id="modal-ninos-label">0</span> × <?= simboloMoneda($idioma) ?><span id="modal-precio-nino"><?= number_format(montoNinoMoneda($t, $idioma), 2) ?></span></span>
                                            <span><?= simboloMoneda($idioma) ?><span id="modal-total-ninos">0.00</span></span>
                                        </div>
                                        <hr style="border-color:rgba(255,255,255,0.15); margin:4px 0;">
                                        <div class="d-flex justify-content-between text-white fw-bold" style="font-size:0.9rem;">
                                            <span><?= ($idioma == 'en') ? 'Total' : 'Total' ?></span>
                                            <span><?= simboloMoneda($idioma) ?><span id="modal-total-general"><?= number_format(2 * montoMoneda($t, $idioma), 2) ?></span></span>
                                        </div>
                                        <div class="d-flex justify-content-between" style="color:#c6d544; font-weight:bold; font-size:0.9rem;">
                                            <span><?= ($idioma == 'en') ? 'Advance' : 'Adelanto' ?> (<span id="modal-porc-adelanto"><?= (int)($t['porcentaje_adelanto'] ?? 30) ?></span>%)</span>
                                            <span><?= simboloMoneda($idioma) ?><span id="modal-adelanto-valor"><?= number_format(2 * montoMoneda($t, $idioma) * (($t['porcentaje_adelanto'] ?? 30) / 100), 2) ?></span></span>
                                        </div>
                                        <div class="d-flex justify-content-between" style="color:#FFD700;">
                                            <span><?= ($idioma == 'en') ? 'Balance in Cusco' : 'Saldo en Cusco' ?></span>
                                            <span><?= simboloMoneda($idioma) ?><span id="modal-saldo-valor"><?= number_format(2 * montoMoneda($t, $idioma) * (1 - (($t['porcentaje_adelanto'] ?? 30) / 100)), 2) ?></span></span>
                                        </div>
                                    </div>
                                    <?php if ((int)($t['max_personas'] ?? 0) > 0): ?>
                                    <div class="mt-2 text-center" style="font-size:0.75rem; color:#ffc107;">
                                        <i class="fas fa-users me-1"></i><?= ($idioma == 'en') ? 'Max.' : 'Máx.' ?> <?= (int)$t['max_personas'] ?> <?= ($idioma == 'en') ? 'people per booking' : 'personas por reserva' ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 p-4 p-md-5 bg-white position-relative">
                            <button type="button" class="btn-close position-absolute" style="top: 25px; right: 25px;" data-bs-dismiss="modal" aria-label="Close"></button>

                            <h3 class="fw-bold mb-4" style="color: #c6d544; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                                <?= ($idioma == 'en') ? 'Inquire now!' : '¡Consulta ahora!' ?>
                            </h3>

                            <form action="admin/enviar_consulta.php" method="POST" onsubmit="return bloquearEnvioBooking(this)">
                                <input type="hidden" name="id_tour" value="<?php echo $t['id']; ?>">
                                <input type="hidden" name="whatsapp" id="whatsapp-input-modal" value="">
                                <input type="hidden" name="accion" id="accion-input-modal" value="consultar">
                                <input type="hidden" name="tipo_pago" id="tipo-pago-input-modal" value="adelanto">
                                <input type="hidden" name="tour_interes" value="<?php echo $titulo_display; ?>">
                                <?php campoCSRF(); ?>
                                <!-- Honeypot anti-bots (invisible) -->
                                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px; opacity:0; height:0; width:0;" aria-hidden="true">

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'First Name*' : 'Nombre*' ?></label>
                                        <input type="text" name="nombre" class="form-control border-0 border-bottom rounded-0 px-0" placeholder="<?= ($idioma == 'en') ? 'Ex. John' : 'Ej. Juan' ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Last Name*' : 'Apellido*' ?></label>
                                        <input type="text" name="apellido" class="form-control border-0 border-bottom rounded-0 px-0" placeholder="<?= ($idioma == 'en') ? 'Ex. Smith' : 'Ej. Sanchez' ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Email Address*' : 'Correo Electrónico*' ?></label>
                                        <input type="email" name="email" class="form-control border-0 border-bottom rounded-0 px-0" placeholder="<?= ($idioma == 'en') ? 'example@mail.com' : 'ejemplo@correo.com' ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Country*' : 'País*' ?></label>
                                        <select name="pais" class="form-select border-0 border-bottom rounded-0 px-0">
                                            <?php foreach ($paises as $codigo => $nombre): ?>
                                                <option value="<?= $nombre ?>" <?= ($codigo == 'PE') ? 'selected' : '' ?>>
                                                    <?= ($codigo == 'PE' ? '🇵🇪 ' : '') . $nombre ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Phone Number / WhatsApp:' : 'Número de Teléfono / WhatsApp:' ?></label>
                                        <input type="tel" name="telefono" class="form-control border-0 border-bottom rounded-0 px-0" placeholder="+51 987..." oninput="document.getElementById('whatsapp-input-modal').value=this.value">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Travel Date*' : 'Fecha de Viaje*' ?></label>
                                        <input type="date" name="fecha_viaje" class="form-control border-0 border-bottom rounded-0 px-0" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Adults*' : 'Adultos*' ?></label>
                                        <input type="number" name="adultos" class="form-control border-0 border-bottom rounded-0 px-0" value="2" min="1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Children' : 'Niños' ?></label>
                                        <input type="number" name="ninos" class="form-control border-0 border-bottom rounded-0 px-0" value="0" min="0">
                                    </div>

                                     <div class="col-12" id="pasajeros-container-modal">
<?php
$pas_idx_m = 0;
for ($i = 0; $i < 2; $i++):
    $pas_tipo_m = ($i < 2) ? 'adulto' : 'nino';
?>
<div class="pasajero-block row g-2 mt-1 align-items-end" style="background:#f9f9f9;padding:8px;border-radius:6px;margin-bottom:6px;position:relative;">
    <div class="col-md-1">
        <select name="pasajeros[<?= $pas_idx_m ?>][tipo]" class="form-select form-select-sm" style="font-size:12px;">
            <option value="adulto"<?= $pas_tipo_m === 'adulto' ? ' selected' : '' ?>><?= $idioma == 'en' ? 'Adult' : 'Adulto' ?></option>
            <option value="nino"<?= $pas_tipo_m === 'nino' ? ' selected' : '' ?>><?= $idioma == 'en' ? 'Child' : 'Niño' ?></option>
        </select>
    </div>
    <div class="col-md-3"><input type="text" name="pasajeros[<?= $pas_idx_m ?>][nombres]" class="form-control form-control-sm" placeholder="Nombres" required></div>
    <div class="col-md-3"><input type="text" name="pasajeros[<?= $pas_idx_m ?>][apellidos]" class="form-control form-control-sm" placeholder="Apellidos" required></div>
    <div class="col-md-2"><input type="text" name="pasajeros[<?= $pas_idx_m ?>][documento]" class="form-control form-control-sm" placeholder="DNI/Pasaporte" required></div>
    <div class="col-md-3"><select name="pasajeros[<?= $pas_idx_m ?>][pais]" class="form-select form-select-sm"><?= $paises_opts ?></select></div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <small class="text-muted" style="font-size:10px;"><?= $idioma == 'en' ? 'Passenger' : 'Pasajero' ?> #<?= $i + 1 ?> (<?= $pas_tipo_label($pas_tipo_m) ?>)</small>
        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:0 6px;" onclick="eliminarBloque(this)">✕</button>
    </div>
</div>
<?php $pas_idx_m++; endfor; ?>
<div class="pasajero-template pasajero-block row g-2 mt-1 align-items-end" style="background:#f9f9f9;padding:8px;border-radius:6px;margin-bottom:6px;position:relative;display:none;">
    <div class="col-md-1">
        <select name="pasajeros[INDEX][tipo]" class="form-select form-select-sm" style="font-size:12px;">
            <option value="adulto"><?= $idioma == 'en' ? 'Adult' : 'Adulto' ?></option>
            <option value="nino"><?= $idioma == 'en' ? 'Child' : 'Niño' ?></option>
        </select>
    </div>
    <div class="col-md-3"><input type="text" name="pasajeros[INDEX][nombres]" class="form-control form-control-sm" placeholder="Nombres"></div>
    <div class="col-md-3"><input type="text" name="pasajeros[INDEX][apellidos]" class="form-control form-control-sm" placeholder="Apellidos"></div>
    <div class="col-md-2"><input type="text" name="pasajeros[INDEX][documento]" class="form-control form-control-sm" placeholder="DNI/Pasaporte"></div>
    <div class="col-md-3"><select name="pasajeros[INDEX][pais]" class="form-select form-select-sm"><?= $paises_opts ?></select></div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <small class="text-muted" style="font-size:10px;"><?= $idioma == 'en' ? 'Passenger' : 'Pasajero' ?></small>
        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:0 6px;" onclick="eliminarBloque(this)">✕</button>
    </div>
</div>
<div class="col-12" style="margin-top:4px;">
    <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:12px;" onclick="agregarBloque('pasajeros-container-modal','adulto')">+ <?= $idioma == 'en' ? 'Add adult' : 'Agregar adulto' ?></button>
    <button type="button" class="btn btn-sm btn-outline-success" style="font-size:12px;" onclick="agregarBloque('pasajeros-container-modal','nino')">+ <?= $idioma == 'en' ? 'Add child' : 'Agregar niño' ?></button>
</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="small fw-bold text-muted"><?= ($idioma == 'en') ? 'Your Message*' : 'Tu Mensaje*' ?></label>
                                        <textarea name="mensaje" class="form-control bg-light border-0" rows="3" placeholder="<?= ($idioma == 'en') ? 'Do you have any questions or special requirements?' : '¿Tienes alguna duda o requerimiento especial?' ?>"></textarea>
                                    </div>

                                    <div class="col-md-6 pt-2">
                                        <button type="submit" class="btn w-100 text-white fw-bold py-3 shadow" style="background-color: #15305D; border-radius: 50px;" onclick="document.getElementById('accion-input-modal').value='consultar'">
                                            <i class="fas fa-paper-plane me-2"></i> <?= ($idioma == 'en') ? 'SEND INQUIRY' : 'SOLO CONSULTAR' ?>
                                        </button>
                                    </div>
                                    <div class="col-md-6 pt-2">
                                        <button type="submit" class="btn w-100 text-white fw-bold py-3 shadow" style="background-color: #27ae60; border-radius: 50px;" onclick="document.getElementById('accion-input-modal').value='pagar'">
                                            <i class="fas fa-credit-card me-2"></i> <?= ($idioma == 'en') ? 'PAY NOW' : 'PAGAR AHORA' ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Tour pricing data
var TOUR_PRECIO = <?= (float)montoMoneda($t, $idioma) ?>;
var TOUR_PRECIO_NINO = <?= (float)montoNinoMoneda($t, $idioma) ?>;
var TOUR_PORC_ADELANTO = <?= (int)($t['porcentaje_adelanto'] ?? 30) ?>;
var TOUR_MAX_PERSONAS = <?= (int)($t['max_personas'] ?? 0) ?>;
var TOUR_SIMBOLO = '<?= simboloMoneda($idioma) ?>';

function cambiarPax(cambio) {
    var input = document.getElementById('paxReserva');
    if (!input) return;
    var actual = parseInt(input.value) || 1;
    var nuevo = actual + cambio;
    
    // Validar mínimo 1
    if (nuevo < 1) nuevo = 1;
    
    // Validar máximo si está configurado
    if (TOUR_MAX_PERSONAS > 0 && nuevo > TOUR_MAX_PERSONAS) {
        Swal.fire({
            icon: 'warning',
            title: '<?= ($idioma == 'en') ? 'Capacity Limit' : 'Límite de Capacidad' ?>',
            text: '<?= ($idioma == 'en') ? 'Maximum' : 'Máximo' ?> ' + TOUR_MAX_PERSONAS + ' <?= ($idioma == 'en') ? 'people per booking' : 'personas por reserva' ?>',
            confirmButtonColor: '#c6d544'
        });
        return;
    }
    
    input.value = nuevo;
    
    // Actualizar el total visual en el sidebar
    var totalEl = document.getElementById('totalReservaDisplay');
    if (totalEl) {
        var total = nuevo * TOUR_PRECIO;
        totalEl.textContent = TOUR_SIMBOLO + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function mostrarCargaBooking() {
    var overlay = document.getElementById('loading-overlay-booking');
    if (overlay) {
        overlay.classList.add('active');
    }
}

function bloquearEnvioBooking(form) {
    mostrarCargaBooking();
    var botones = form.querySelectorAll('button[type="submit"]');
    botones.forEach(function(btn) {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    });
    return true;
}

function actualizarResumenPago() {
    var adultos = parseInt(document.querySelector('#modalReservaTour input[name="adultos"]')?.value) || 0;
    var ninos = parseInt(document.querySelector('#modalReservaTour input[name="ninos"]')?.value) || 0;
    
    // Detectar tipo de pago (adelanto o total)
    var tipoPago = 'adelanto';
    var radioTotal = document.getElementById('modal_pago_total');
    if (radioTotal && radioTotal.checked) {
        tipoPago = 'total';
    }

    var totalA = adultos * TOUR_PRECIO;
    var totalN = ninos * TOUR_PRECIO_NINO;
    var total = totalA + totalN;
    
    var adelanto = (tipoPago === 'total') ? total : (total * (TOUR_PORC_ADELANTO / 100));
    var saldo = total - adelanto;

    var el = function(id) { return document.getElementById(id); };
    if (el('modal-adultos-label')) el('modal-adultos-label').textContent = adultos;
    if (el('modal-ninos-label')) el('modal-ninos-label').textContent = ninos;
    if (el('modal-precio-nino')) el('modal-precio-nino').textContent = TOUR_PRECIO_NINO.toFixed(2);
    if (el('modal-total-adultos')) el('modal-total-adultos').textContent = totalA.toFixed(2);
    if (el('modal-total-ninos')) el('modal-total-ninos').textContent = totalN.toFixed(2);
    if (el('modal-total-general')) el('modal-total-general').textContent = total.toFixed(2);
    
    if (el('modal-porc-adelanto')) el('modal-porc-adelanto').textContent = (tipoPago === 'total') ? '100' : TOUR_PORC_ADELANTO;
    if (el('modal-porc-adelanto-btn')) el('modal-porc-adelanto-btn').textContent = TOUR_PORC_ADELANTO;
    
    if (el('modal-adelanto-valor')) el('modal-adelanto-valor').textContent = adelanto.toFixed(2);
    if (el('modal-saldo-valor')) el('modal-saldo-valor').textContent = saldo.toFixed(2);

    // Actualizar campo oculto en el formulario
    var hiddenTipoPago = document.getElementById('tipo-pago-input-modal');
    if (hiddenTipoPago) {
        hiddenTipoPago.value = tipoPago;
    }
}

function validarMaxPersonas() {
    if (TOUR_MAX_PERSONAS <= 0) return true;
    var container = document.getElementById('pasajeros-container-modal');
    if (!container) return true;
    var actuales = container.querySelectorAll('.pasajero-block:not(.pasajero-template)').length;
    if (actuales >= TOUR_MAX_PERSONAS) {
        alert('<?= ($idioma == 'en') ? 'Maximum' : 'Máximo' ?> ' + TOUR_MAX_PERSONAS + ' <?= ($idioma == 'en') ? 'people per booking' : 'personas por reserva' ?>');
        return false;
    }
    return true;
}

// Override agregarBloque to check max_personas
var agregarBloqueOriginal = window.agregarBloque;
window.agregarBloque = function(containerId, tipo) {
    if (!validarMaxPersonas()) return;
    if (typeof agregarBloqueOriginal === 'function') {
        agregarBloqueOriginal(containerId, tipo);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalReservaTour');
    if (!modal) return;

    // Listen for changes in adults/ninos inputs
    modal.addEventListener('change', function(e) {
        if (e.target && (e.target.name === 'adultos' || e.target.name === 'ninos')) {
            actualizarResumenPago();
        }
    });
    modal.addEventListener('input', function(e) {
        if (e.target && (e.target.name === 'adultos' || e.target.name === 'ninos')) {
            actualizarResumenPago();
        }
    });

    modal.addEventListener('show.bs.modal', function() {
        // 1. Sincronizar Fecha
        var inputFecha = document.getElementById('fechaReservaSeleccionada');
        var displayEl = document.getElementById('modal-fecha-value');
        var fechaForm = document.querySelector('#modalReservaTour input[name="fecha_viaje"]');
        if (inputFecha && displayEl) {
            var fechaVal = inputFecha.value;
            if (fechaVal) {
                var partes = fechaVal.split('-');
                var fechaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
                displayEl.textContent = fechaObj.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
                if (fechaForm) fechaForm.value = fechaVal;
            } else {
                var hoy = new Date();
                displayEl.textContent = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
                if (fechaForm) {
                    var dd = String(hoy.getDate()).padStart(2, '0');
                    var mm = String(hoy.getMonth() + 1).padStart(2, '0');
                    var yyyy = hoy.getFullYear();
                    fechaForm.value = yyyy + '-' + mm + '-' + dd;
                }
            }
        }

        // 2. Sincronizar Pasajeros desde el Sidebar
        var paxSidebar = document.getElementById('paxReserva');
        var inputAdultosModal = document.querySelector('#modalReservaTour input[name="adultos"]');
        if (paxSidebar && inputAdultosModal) {
            var cantidad = parseInt(paxSidebar.value) || 1;
            inputAdultosModal.value = cantidad;
            
            // Ajustar bloques de pasajeros en el modal
            var container = document.getElementById('pasajeros-container-modal');
            if (container) {
                // Eliminar bloques actuales (excepto template)
                var bloques = container.querySelectorAll('.pasajero-block:not(.pasajero-template)');
                bloques.forEach(b => b.remove());
                
                // Agregar la cantidad seleccionada como adultos por defecto
                for (var i = 0; i < cantidad; i++) {
                    if (typeof window.agregarBloqueOriginal === 'function') {
                        // Usamos la función original para saltar la validación de max en el loop inicial
                        agregarBloqueOriginal('pasajeros-container-modal', 'adulto');
                    } else if (typeof window.agregarBloque === 'function') {
                        window.agregarBloque('pasajeros-container-modal', 'adulto');
                    }
                }
            }
        }

        // 3. Actualizar Precios en el Modal
        setTimeout(actualizarResumenPago, 50);
    });
});
</script>

    <?php
    // --- CONSULTAR CONFIGURACIÓN DE BARRA RESPONSIVE ---
    $stmt_barra = $db->query("SELECT * FROM barra_responsive WHERE activo = 1 ORDER BY orden ASC");
    $barra_elementos = $stmt_barra->fetchAll(PDO::FETCH_ASSOC);
    $tiene_barra = !empty($barra_elementos);
    ?>
    <?php if ($tiene_barra && basename($_SERVER['PHP_SELF']) === 'detalle_tour.php'): ?>
    <style>
    #barra-flotante-exclusiva-movil { display: none; }
    @media (max-width: 768px) {
      #barra-flotante-exclusiva-movil {
        display: flex;
        position: fixed; bottom: 0; left: 0; width: 100%;
        background: #fff;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.12);
        z-index: 9999;
        padding: 10px 12px;
        align-items: center;
        gap: 10px;
        box-sizing: border-box;
        opacity: 0;
        transform: translateY(100%);
        transition: all 0.35s ease-in-out;
        pointer-events: none;
      }
      #barra-flotante-exclusiva-movil.is-active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
      }
      #barra-flotante-exclusiva-movil .ip-mb-item-precio {
        display: inline-flex; align-items: center;
        padding: 10px 14px; border-radius: 8px; text-decoration: none;
        white-space: nowrap; margin-right: auto;
        font-size: 0.8rem; font-weight: 700; transition: opacity 0.25s;
      }
      #barra-flotante-exclusiva-movil .ip-mb-item-precio:hover { opacity: 0.7; }
      #barra-flotante-exclusiva-movil .ip-mb-item-btn {
        flex: 1; display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 16px; border-radius: 8px; text-decoration: none;
        white-space: nowrap; font-size: 0.85rem; font-weight: 700; transition: opacity 0.25s;
      }
      #barra-flotante-exclusiva-movil .ip-mb-item-btn:hover { opacity: 0.85; }
      #barra-flotante-exclusiva-movil .ip-mb-item-icono {
        display: inline-flex; align-items: center; justify-content: center;
        width: 45px; height: 45px; border-radius: 8px; text-decoration: none;
        flex-shrink: 0; font-size: 1.3rem; transition: opacity 0.25s;
      }
      #barra-flotante-exclusiva-movil .ip-mb-item-icono:hover { opacity: 0.85; }
      .footer-dark-premium { margin-bottom: 80px; }
    }
    </style>
    <div id="barra-flotante-exclusiva-movil">
      <?php foreach ($barra_elementos as $be):
        $icono_html = !empty($be['icono']) ? '<i class="fas ' . htmlspecialchars($be['icono']) . '"></i>' : '';
        $bg = htmlspecialchars($be['color_fondo']);
        $tc = htmlspecialchars($be['color_texto']);
        $bg_style = ($bg === 'transparent') ? 'background:transparent;' : "background:$bg;";
        $style_item = $bg_style . ' color:' . $tc . ';';
        $nombre_item = ($idioma == 'en' && !empty($be['nombre_en'])) ? $be['nombre_en'] : $be['nombre_es'];

        if ($be['elemento'] === 'precio'): ?>
          <a href="<?= htmlspecialchars($be['enlace'] ?: '#servicios') ?>" class="ip-mb-item-precio" style="<?= $style_item ?>">
            <?= $icono_html ?><?= htmlspecialchars($nombre_item) ?> <?= precioFormato($t, $idioma) ?>
          </a>
        <?php elseif ($be['elemento'] === 'boton_reserva'):
          $href = ''; $target = ''; $data_attrs = '';
          if ($be['enlace'] === 'whatsapp') {
            $href = 'https://wa.me/' . $wa_link_header . '?text=' . urlencode(($idioma == 'en') ? 'Hello IntiPath Tours, I want to book' : 'Hola IntiPath Tours, quiero reservar');
            $target = ' target="_blank"';
          } elseif ($be['enlace'] === 'modal') {
            $href = '#';
            $data_attrs = ' data-bs-toggle="modal" data-bs-target="#modalReservaTour"';
          } elseif ($be['enlace'] === 'mailto') {
            $href = 'mailto:' . htmlspecialchars($f['email'] ?? 'info@intipathtours.com');
          } else {
            $href = $be['enlace'];
            if (strpos($href, 'http') === 0) $target = ' target="_blank"';
          } ?>
          <a href="<?= $href ?>" class="ip-mb-item-btn" style="<?= $style_item ?>"<?= $data_attrs ?><?= $target ?>>
            <?= $icono_html ?><?= htmlspecialchars($nombre_item) ?>
          </a>
        <?php elseif ($be['elemento'] === 'boton_correo'):
          $href = $be['enlace'] === 'mailto' ? 'mailto:' . htmlspecialchars($f['email'] ?? 'info@intipathtours.com') : htmlspecialchars($be['enlace']); ?>
          <a href="<?= $href ?>" class="ip-mb-item-icono" style="<?= $style_item ?>">
            <i class="fas <?= htmlspecialchars($be['icono'] ?: 'fa-envelope') ?>"></i>
          </a>
        <?php else:
          $href = htmlspecialchars($be['enlace'] ?: '#');
          $target = (strpos($href, 'http') === 0) ? ' target="_blank"' : ''; ?>
          <a href="<?= $href ?>" class="ip-mb-item-btn" style="<?= $style_item ?>"<?= $target ?>>
            <?= $icono_html ?><?= htmlspecialchars($nombre_item) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <script>
    (function(){
      var bar = document.getElementById('barra-flotante-exclusiva-movil');
      if (!bar) return;
      var umbral = 450, activo = false;
      function evaluar(){
        var debe = window.scrollY > umbral;
        if (debe !== activo) { activo = debe; bar.classList.toggle('is-active', activo); }
      }
      window.addEventListener('scroll', evaluar, {passive: true});
      evaluar();
    })();
    </script>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle SweetAlert2 notifications
        const urlParams = new URLSearchParams(window.location.search);
        const res = urlParams.get('res');
        const isEn = '<?= $idioma ?>' === 'en';

        if (res === 'success') {
            Swal.fire({
                icon: 'success',
                title: isEn ? 'Success!' : '¡Éxito!',
                text: isEn ? 'Your inquiry has been sent successfully. We will contact you soon.' : 'Tu consulta ha sido enviada con éxito. Nos contactaremos pronto.',
                confirmButtonColor: '#15305D'
            });
        } else if (res === 'error') {
            Swal.fire({
                icon: 'error',
                title: isEn ? 'Error' : 'Error',
                text: isEn ? 'There was a problem sending your inquiry. Please try again.' : 'Hubo un problema al enviar tu consulta. Por favor, intenta de nuevo.',
                confirmButtonColor: '#15305D'
            });
        }

        // Capacity Limit Logic
        if (typeof window.agregarBloque === 'function') {
            const originalAgregarBloque = window.agregarBloque;
            window.agregarBloque = function(containerId, tipo) {
                if (TOUR_MAX_PERSONAS > 0) {
                    const container = document.getElementById(containerId);
                    if (container) {
                        const actuales = container.querySelectorAll('.pasajero-block:not(.pasajero-template)').length;
                        if (actuales >= TOUR_MAX_PERSONAS) {
                            Swal.fire({
                                icon: 'warning',
                                title: isEn ? 'Capacity Limit' : 'Límite de Capacidad',
                                text: (isEn ? 'Maximum ' : 'Máximo ') + TOUR_MAX_PERSONAS + (isEn ? ' people per booking.' : ' personas por reserva.'),
                                confirmButtonColor: '#c6d544'
                            });
                            return;
                        }
                    }
                }
                originalAgregarBloque(containerId, tipo);
            };
        }
    });
    </script>
</body>
</html>