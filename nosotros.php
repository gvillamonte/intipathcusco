<?php
// 1. Errores visibles (Solo para desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Conexión a la BD
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM pagina_nosotros WHERE id = 1");
    $stmt->execute();
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback si la BD está vacía
    if (!$info) {
        $info = [
            'titulo' => 'IntiPath Tours',
            'subtitulo' => 'Expertos en Cusco',
            'descripcion_larga' => 'Cargando historia...',
            'mision' => 'Nuestra misión no ha sido cargada.',
            'vision' => 'Nuestra visión no ha sido cargada.',
            'imagen_principal' => 'banner_default.jpg',
            'imagen_historia' => 'equipo_default.jpg',
            'titulo_gerencia' => 'Gerencia',
            'contenido_gerencia' => '',
            'frase_gerente' => '',
            'imagen_gerencia' => 'gerente-default.jpg'
        ];
    }
} catch (Exception $e) {
    die("Error crítico: " . $e->getMessage());
}

include 'includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/nosotros.css<?php echo assetVersion('assets/css/nosotros.css'); ?>">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">

<div id="nosotros-page">

    <section class="ip-nosotros-hero" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/<?= $info['imagen_principal'] ?>');">
        <div class="container text-center">
            <h1 class="text-white fw-bold"><?= htmlspecialchars($info['titulo']) ?></h1>
            <p class="text-white"><?= htmlspecialchars($info['subtitulo']) ?></p>
        </div>
    </section>

    <main class="ip-presentacion-main">
        <section class="ip-presentacion-container">
            <div class="ip-presentacion-row">
                <div class="ip-presentacion-col-text">
                    <div class="ip-presentacion-decorator"></div>
                    <div class="ip-presentacion-content">
                        <h2 class="ip-presentacion-title">
                            Nuestra <span class="ip-dorado-coorporativo">Historia</span>
                        </h2>
                        <div class="ip-presentacion-body">
                            <?= nl2br(htmlspecialchars($info['resumen'] ?? '')) ?>
                        </div>
                    </div>
                </div>

                <div class="ip-presentacion-col-img">
                    <div class="ip-presentacion-img-frame">
                        <?php
                        // Verificamos si existe la imagen del resumen, si no, una por defecto
                        $img_resumen = !empty($info['imagen_resumen']) ? $info['imagen_resumen'] : 'equipo-default.jpg';
                        ?>
                        <img src="assets/img/<?= $img_resumen ?>" class="ip-presentacion-img-fluid" alt="Historia IntiPath Tours" loading="lazy">
                    </div>
                </div>
            </div>
        </section>
    </main>

    <section class="ip-presentacion-main ip-bg-gris-suave">
        <div class="ip-presentacion-container">
            <div class="ip-presentacion-row ip-flex-reverse">
                <div class="ip-presentacion-col-img">
                    <div class="ip-presentacion-img-frame">
                        <img src="assets/img/<?= !empty($info['imagen_gerencia']) ? $info['imagen_gerencia'] : 'gerente-default.jpg' ?>"
                            class="ip-presentacion-img-fluid"
                            alt="Gerencia" loading="lazy">
                    </div>
                    <p class="ip-presentacion-footer-name"> (GERENTE FUNDADOR)</p>
                </div>
                <div class="ip-presentacion-col-text">
                    <div class="ip-presentacion-content">
                        <div class="ip-presentacion-decorator"></div>
                        <h2 class="ip-presentacion-title"><?= htmlspecialchars($info['titulo_gerencia']) ?></h2>
                        <div class="ip-presentacion-body">
                            <?= nl2br(htmlspecialchars((string)$info['contenido_gerencia'])) ?>
                            <?php if (!empty($info['frase_gerente'])): ?>
                                <p class="ip-quote">"<?= htmlspecialchars($info['frase_gerente']) ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ip-equipo-full-wrapper">
        <div class="ip-equipo-container">

            <div class="ip-equipo-main-header">
                <h2 class="ip-equipo-title">Nuestro <span>Equipo</span></h2>
                <div class="ip-equipo-line"></div>
            </div>

            <?php
            $texto_plano = (string)($info['equipo_json'] ?? '');
            $areas_bloques = explode('[AREA:', $texto_plano);
            array_shift($areas_bloques);

            foreach ($areas_bloques as $area_bloque):
                $partes_area = explode(']', $area_bloque, 2);
                $titulo_area = trim($partes_area[0]);

                preg_match('/\[DESC:(.*?)\]/s', $partes_area[1], $match_desc);
                $desc_area = isset($match_desc[1]) ? trim($match_desc[1]) : '';
            ?>

                <div class="ip-equipo-area-block">
                    <div class="ip-equipo-area-label-box">
                        <h3 class="ip-equipo-area-name"><?= htmlspecialchars($titulo_area) ?></h3>
                        <p class="ip-equipo-area-description"><?= htmlspecialchars($desc_area) ?></p>
                    </div>

                    <div class="ip-equipo-flex-cards">
                        <?php
                        preg_match_all('/\[NOMBRE:(.*?)\]/', $partes_area[1], $colaboradores);
                        foreach ($colaboradores[1] as $colab_raw):
                            $datos = explode('|', $colab_raw);
                            $p_nombre = trim(str_replace('NOMBRE:', '', $datos[0]));
                            $p_cargo  = isset($datos[1]) ? trim(str_replace('CARGO:', '', $datos[1])) : '';
                            $p_foto   = isset($datos[2]) ? trim(str_replace('FOTO:', '', $datos[2])) : 'default.jpg';
                        ?>
                            <div class="ip-equipo-card">
                                <div class="ip-equipo-img-holder">
                                    <img src="assets/img/equipo/<?= $p_foto ?>" alt="<?= $p_nombre ?>" loading="lazy">
                                </div>
                                <div class="ip-equipo-info">
                                    <h4 class="ip-equipo-name"><?= htmlspecialchars($p_nombre) ?></h4>
                                    <span class="ip-equipo-role"><?= htmlspecialchars($p_cargo) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    </section>

    <section class="ip-stone-cta">
        <div class="ip-stone-overlay">
            <div class="ip-stone-content-box">
                <div class="ip-stone-text-wrapper">
                    <div class="ip-stone-divider"></div>
                    <h2 class="ip-stone-title">¿Qué hace que estos viajes sean diferentes?</h2>
                    <p class="ip-stone-description">
                        Entendemos las vacaciones como algo mucho más profundo que una simple logística de transporte y alojamiento. Creemos en los viajes que remueven el alma y nos invitan a evolucionar a través de la aventura. Nuestra misión es crear experiencias que realmente valgan la pena, priorizando siempre el bienestar del viajero, el respeto al anfitrión y la conexión real entre las personas.
                    </p>
                </div>

                <div class="ip-stone-actions">
                    <a href="contacto.php" class="ip-stone-btn">
                        ¡Ayúdame a planificar mi viaje!
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>