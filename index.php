<?php
// 1. Limpieza de caché y Control de Sesión
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- ESTO ES LO QUE DEBES PEGAR: LA DEFINICIÓN DEL BASE_PATH ---
// Detecta si estás en tu PC (localhost) o en el dominio real
$base_path = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? '/intipathcusco/' : '/';
// --------------------------------------------------------------
// 2. Cambio de idioma con redirección para limpiar caché
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] == 'en' ? 'en' : 'es');
    $params = $_GET;
    unset($params['lang']);
    $params['v'] = time();
    header("Location: " . basename($_SERVER['PHP_SELF']) . (!empty($params) ? '?' . http_build_query($params) : ''));
    exit;
}

$idioma = $_SESSION['lang'] ?? 'es';

// 3. Requerimientos
include 'includes/header.php';
include 'includes/moneda_helper.php';

/**
 * 4. Consultas con soporte bilingüe
 */

// Sliders principales
$sliders = $db->query("SELECT * FROM sliders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Tours activos
$tours = $db->query("SELECT * FROM tours WHERE estado = 'activo' ORDER BY id DESC LIMIT 9")->fetchAll(PDO::FETCH_ASSOC);

// Preguntas Frecuentes
$faqs = $db->query("SELECT * FROM preguntas_frecuentes WHERE estado = 1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);

// Licencias
$licencias = [];
try {
    $stmt_lic = $db->prepare("SELECT * FROM licencias ORDER BY id DESC");
    $stmt_lic->execute();
    $licencias = $stmt_lic->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $licencias = [];
}

$is_en = ($idioma == 'en');

// Sección "Lo que dicen nuestros clientes" (auto-instalable, editable en admin)
include 'includes/resenas_helper.php';
asegurar_infraestructura_resenas($db);
verificar_sync_automatico($db);
$resenas_data = obtener_datos_resenas($db);
$rev_google_n = (int)($resenas_data['datos']['plataformas']['google']['opiniones'] ?? 552);
$rev_google_p = ($resenas_data['datos']['plataformas']['google']['puntaje'] ?? '4.9');
?>
<main>
    <section id="inicio" class="main-s-hero">
        <div class="main-s-slider-wrapper">
            <?php if (!empty($sliders)): ?>
                <?php foreach ($sliders as $key => $s):
                    // Lógica de selección de idioma para contenido de BD
                    $tit_slider = ($is_en && !empty($s['titulo_en'])) ? $s['titulo_en'] : $s['titulo'];
                    $sub_slider = ($is_en && !empty($s['subtitulo_en'])) ? $s['subtitulo_en'] : $s['subtitulo'];
                ?>
                    <div class="main-s-slide <?php echo ($key == 0) ? 'is-active' : ''; ?>">

                        <div class="main-s-bg" style="background: #000; overflow: hidden; position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)); z-index: 1;"></div>

                            <video autoplay muted loop playsinline
                                style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                                <source src="<?= $base_path ?>assets/video/<?php echo $s['imagen']; ?>" type="video/mp4">
                                <?= ($is_en) ? 'Your browser does not support video.' : 'Tu navegador no soporta video.' ?>
                            </video>
                        </div>

                        <div class="main-s-container" style="position: relative; z-index: 2;">
                            <div class="main-s-content">
                                <h1 class="main-s-title"><?php echo mb_strtoupper($tit_slider); ?></h1>

                                <?php if (!empty($sub_slider)): ?>
                                    <div class="main-s-description-box">
                                        <p class="main-s-text"><?php echo $sub_slider; ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="main-s-actions">
                                    <a href="contacto.php" class="main-s-btn m-btn-border">
                                        <?= ($is_en) ? 'INQUIRE NOW' : 'CONSULTE AHORA' ?>
                                    </a>
                                    <a href="tours.php" class="main-s-btn m-btn-solid">
                                        <?= ($is_en) ? 'EXPLORE TOURS 2026' : 'EXPLORAR TOURS 2026' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="main-s-slide is-active">
                    <div class="main-s-bg" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/Machu-Picchu.jpg'); background-size: cover; background-position: center;"></div>
                    <div class="main-s-container">
                        <div class="main-s-content">
                            <h1 class="main-s-title">
                                <?= ($is_en) ? 'WELCOME TO INTIPATH TOURS' : 'BIENVENIDOS A INTIPATH TOURS' ?>
                            </h1>
                            <div class="main-s-actions">
                                <a href="#tours" class="main-s-btn m-btn-solid">
                                    <?= ($is_en) ? 'VIEW TOURS' : 'VER TOURS' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($sliders)): ?>
        <button id="ipSndToggle" class="main-s-sound-toggle"
                aria-label="<?= ($idioma == 'en') ? 'Toggle sound' : 'Activar/Silenciar sonido' ?>"
                title="<?= ($idioma == 'en') ? 'Toggle sound' : 'Activar/Silenciar sonido' ?>">
            <i class="fas fa-volume-up"></i>
            <i class="fas fa-volume-mute" style="display:none"></i>
        </button>
        <?php endif; ?>

    </section>
    <section class="intro-ip-section">
        <div class="intro-ip-contenedor">
            <div class="intro-ip-separator"></div>

            <h2 class="intro-ip-title">
                <?php if ($idioma == 'en'): ?>
                    Explore the Heart of the Andes & <span>Machu Picchu</span> with Adventure Travel Experts
                <?php else: ?>
                    Explora el Corazón de los Andes & <span>Machu Picchu</span> con Expertos en Viajes de Aventura
                <?php endif; ?>
            </h2>

            <p class="intro-ip-text">
                <?php if ($idioma == 'en'): ?>
                    At <strong>IntiPath Tours</strong>, we specialize in guiding authentic and personalized experiences towards the wonders of Peru. We focus on offering optimal routes and high-quality services that perfectly fit your travel dreams. With a passionate team and local guides, we challenge you to <strong>explore new places and cultures</strong>, guaranteeing a deep cultural immersion and memories that will last a lifetime.
                <?php else: ?>
                    En <strong>IntiPath Tours</strong>, somos especialistas en guiar experiencias auténticas y personalizadas hacia las maravillas del Perú. Nos enfocamos en ofrecer rutas óptimas y servicios de alta calidad que se ajustan perfectamente a tus sueños de viaje. Con un equipo apasionado y guías locales, te desafiamos a <strong>explorar nuevos lugares y culturas</strong>, garantizando una inmersión cultural profunda y recuerdos que durarán toda la vida.
                <?php endif; ?>
            </p>
        </div>
    </section>




    <section class="features-ip-section">
        <div class="features-ip-contenedor">
            <div class="features-ip-grid">

                <div class="features-ip-card">
                    <div class="features-ip-icon">
                        <i class="fas fa-hiking"></i>
                    </div>
                    <div class="features-ip-content">
                        <p>
                            <?php if ($idioma == 'en'): ?>
                                <strong>We are a passionate team of travel specialists</strong> providing high-level experiences in iconic destinations such as <strong>Machu Picchu, the Inca Trail, Salkantay</strong>, and the majestic Rainbow Mountain.
                            <?php else: ?>
                                <strong>Somos un equipo apasionado de especialistas en viajes</strong> que brindamos experiencias de alto nivel en destinos emblemáticos como <strong>Machu Picchu, el Camino Inca, Salkantay</strong> y la majestuosa Montaña de Colores.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="features-ip-card">
                    <div class="features-ip-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="features-ip-content">
                        <p>
                            <?php if ($idioma == 'en'): ?>
                                <strong>We know that every traveler is unique</strong>, which is why we design tailor-made adventures, taking the necessary time to understand your dreams and guaranteeing <strong>the perfect trip you will remember for a lifetime.</strong>
                            <?php else: ?>
                                <strong>Sabemos que cada viajero es único</strong>, por eso diseñamos aventuras a tu medida, tomando el tiempo necesario para entender tus sueños y garantizando <strong>el viaje perfecto que recordarás toda la vida.</strong>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="features-ip-card">
                    <div class="features-ip-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="features-ip-content">
                        <p>
                            <?php if ($idioma == 'en'): ?>
                                <strong>We operate with small groups and total respect for nature</strong> to ensure a personalized and high-quality service. Our motto is: <strong>"Small groups, big adventures"</strong>.
                            <?php else: ?>
                                <strong>Operamos con grupos pequeños y respeto total a la naturaleza</strong> para asegurar un servicio personalizado y de alta calidad. Nuestro lema es: <strong>"Pequeños grupos, grandes aventuras"</strong>.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <section class="tours-ip-section">
        <div class="tours-ip-contenedor">

            <div class="tours-ip-header">
                <div class="tours-ip-separator"></div>
                <h2 class="tours-ip-main-title">
                    <?= ($idioma == 'en') ? 'Best Treks in Peru' : 'Las mejores caminatas en Perú' ?>
                </h2>
                <p class="tours-ip-subtitle">
                    <?= ($idioma == 'en')
                        ? 'Enjoy the best adventure treks to Machu Picchu, the Lost City of the Incas.'
                        : 'Disfruta de las mejores caminatas de aventura a Machu Picchu, la Ciudad Perdida de los Incas.' ?>
                </p>
                <p class="tours-ip-desc-top">
                    <?php if ($idioma == 'en'): ?>
                        Each of our tours is operated by our experienced and certified guides who will take you through the world-famous <strong>Machu Picchu Treks</strong>. Whether you travel with a small group or in private, our team is available 24 hours a day.
                    <?php else: ?>
                        Cada uno de nuestros tours es operado por nuestros guías experimentados y certificados quienes te llevarán a través de las mundialmente famosas <strong>Caminatas hacia Machu Picchu</strong>. Ya sea que viajes con un grupo pequeño o en privado, nuestro equipo está disponible las 24 horas del día.
                    <?php endif; ?>
                </p>
            </div>

            <div class="tours-ip-grid">
                <?php if (!empty($tours)): ?>
                    <?php foreach ($tours as $t):
                        // Lógica de traducción por cada tour
                        $tit_tour  = ($is_en && !empty($t['titulo_en'])) ? $t['titulo_en'] : $t['titulo'];
                        $desc_tour = ($is_en && !empty($t['descripcion_corta_en'])) ? $t['descripcion_corta_en'] : $t['descripcion_corta'];
                        $dur_tour  = ($is_en && !empty($t['duracion_en'])) ? $t['duracion_en'] : $t['duracion'];
                        $diff_tour = ($is_en && !empty($t['dificultad_en'])) ? $t['dificultad_en'] : $t['dificultad'];
                        $loc_tour  = ($is_en && !empty($t['ubicacion_texto_en'])) ? $t['ubicacion_texto_en'] : $t['ubicacion_texto'];
                    ?>
                        <article class="tours-ip-card">
                            <div class="tours-ip-img-container">
                                <a href="detalle_tour.php?id=<?php echo $t['id']; ?>" class="tours-ip-img-link">
<img src="assets/img/tours/<?php echo $t['imagen_principal']; ?>" loading="lazy" alt="<?php echo $tit_tour; ?>">
                                </a>
                                <div class="tours-ip-badge-year">
                                    <i class="fas fa-award"></i> <span>2026</span>
                                </div>
                            </div>

                            <div class="tours-ip-quick-stats">
                                <span class="stat-item"><i class="far fa-clock"></i> <?php echo $dur_tour; ?></span>
                                <span class="stat-item"><i class="fas fa-chart-line"></i> <?php echo $diff_tour; ?></span>
                            </div>

                            <div class="tours-ip-card-body">
                                <h3 class="tours-ip-card-title"><?php echo $tit_tour; ?></h3>
                                <p class="tours-ip-card-loc"><i class="fas fa-map-marker-alt"></i> <?php echo $loc_tour; ?></p>

                                <p class="tours-ip-card-text">
                                    <?php echo mb_strimwidth($desc_tour, 0, 115, "..."); ?>
                                </p>

                                <div class="tours-ip-technical">
                                    <div class="tech-row">
                                        <i class="fas fa-users"></i>
                                        <?= ($idioma == 'en') ? 'Max.' : 'Máx.' ?> <?php echo $t['grupo_max'] ?? '12'; ?> <?= ($idioma == 'en') ? 'people' : 'personas' ?>
                                    </div>
                                    <div class="tech-row">
                                        <i class="fas fa-mountain"></i> <?php echo $t['altitud_max'] ?? '4630 m / 15190 ft'; ?>
                                    </div>
                                    <div class="tech-row rating">
                                        <i class="fas fa-star"></i> <?= $rev_google_p ?> (<?= number_format($rev_google_n, 0); ?> <?= ($idioma == 'en') ? 'reviews' : 'reseñas' ?>)
                                    </div>
                                </div>

                                <div class="tours-ip-card-footer">
                                    <div class="tours-ip-price-wrapper">
                                        <span class="price-label"><?= ($idioma == 'en') ? 'From' : 'Desde' ?></span>
                                        <div class="price-main">
                                            <span class="price-symbol"><?= simboloMoneda($idioma) ?></span>
                                            <span class="price-val"><?php echo number_format(montoMoneda($t, $idioma), 0); ?></span>
                                            <span class="price-unit">/ <?= ($idioma == 'en') ? 'person' : 'persona' ?></span>
                                        </div>
                                    </div>
                                    <a href="detalle_tour.php?id=<?php echo $t['id']; ?>" class="tours-ip-btn-itinerary">
                                        <?= ($idioma == 'en') ? 'View Itinerary' : 'Ver Itinerario' ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- <section class="counter-ip-section">
    <div class="counter-ip-contenedor">
        <div class="counter-ip-grid">
            
            <div class="counter-ip-item">
                <div class="counter-ip-number">15</div>
                <div class="counter-ip-label">Años de experiencia</div>
            </div>

            <div class="counter-ip-item">
                <div class="counter-ip-number">200+</div>
                <div class="counter-ip-label">Colaboradores locales</div>
            </div>

            <div class="counter-ip-item">
                <div class="counter-ip-number">6,000+</div>
                <div class="counter-ip-label">Aventuras planificadas</div>
            </div>

            <div class="counter-ip-item">
                <div class="counter-ip-number">70,000+</div>
                <div class="counter-ip-label">Clientes satisfechos, y siguen siéndolo</div>
            </div>

        </div>
    </div>
</section> -->





    <section class="compact-ip-section">
        <div class="compact-ip-container">

            <div class="compact-ip-scroll-wrapper">
                <?php if (!empty($tours)): ?>
                    <?php foreach ($tours as $t):
                        // Traducción de campos de la base de datos
                        $tit_tour = ($is_en && !empty($t['titulo_en'])) ? $t['titulo_en'] : $t['titulo'];
                        $dur_tour = ($is_en && !empty($t['duracion_en'])) ? $t['duracion_en'] : $t['duracion'];
                        $loc_tour = ($is_en && !empty($t['ubicacion_texto_en'])) ? $t['ubicacion_texto_en'] : $t['ubicacion_texto'];
                        $itin_res = ($is_en && !empty($t['itinerario_resumen_en'])) ? $t['itinerario_resumen_en'] : $t['itinerario_resumen'];
                    ?>
                        <article class="compact-ip-card">
                            <div class="compact-ip-img-box">
                                <a href="detalle_tour.php?id=<?php echo $t['id']; ?>">
<img src="assets/img/tours/<?php echo $t['imagen_principal']; ?>" loading="lazy" alt="<?php echo $tit_tour; ?>">
                                </a>
                            </div>

                            <div class="compact-ip-content">
                                <p class="compact-ip-meta-info">
                                    <?php echo mb_strtoupper($dur_tour); ?> <?= ($is_en) ? 'FROM' : 'DESDE' ?> <span><?= precioFormato($t, $idioma) ?></span>
                                </p>

                                <h3 class="compact-ip-title">
                                    <a href="detalle_tour.php?id=<?php echo $t['id']; ?>" style="text-decoration: none; color: #0f9b9e;">
                                        <?php echo mb_strtoupper($tit_tour); ?>
                                    </a>
                                </h3>

                                <div class="compact-ip-details-list">
                                    <p><strong><?= ($is_en) ? 'Location' : 'Ubicación' ?>:</strong> <?php echo $loc_tour; ?></p>
                                    <p><strong><?= ($is_en) ? 'Trip Type' : 'Tipo de viaje' ?>:</strong> <?= ($is_en) ? 'Guided Trek' : 'Caminata guiada' ?></p>

                                    <div class="compact-ip-diff-container">
                                        <strong><?= ($is_en) ? 'Difficulty' : 'Dificultad' ?>:</strong>
                                        <div class="compact-ip-bars">
                                            <?php
                                            // Lógica visual para barras de dificultad (ejemplo estático mantenido)
                                            ?>
                                            <span class="c-bar active"></span>
                                            <span class="c-bar active"></span>
                                            <span class="c-bar active"></span>
                                            <span class="c-bar"></span>
                                            <span class="c-bar"></span>
                                        </div>
                                    </div>

                                    <p><strong><?= ($is_en) ? 'Group Size' : 'Tamaño del grupo' ?>:</strong> <?= ($is_en) ? 'Max.' : 'Máximo' ?> <?php echo $t['grupo_max'] ?? '12'; ?> <?= ($is_en) ? 'people' : 'personas' ?></p>
                                    <p class="compact-ip-visited-sites">
                                        <strong><?= ($is_en) ? 'Sites visited' : 'Sitios visitados' ?>:</strong> <?php echo mb_strimwidth($itin_res, 0, 85, "..."); ?>
                                    </p>
                                </div>

                                <div class="compact-ip-card-footer">
                                    <div class="compact-ip-rating-box">
                                        <i class="fas fa-star"></i> <?= $rev_google_p ?> <span>(<?= number_format($rev_google_n, 0); ?> <?= ($is_en) ? 'reviews' : 'opiniones' ?>)</span>
                                    </div>
                                    <a href="detalle_tour.php?id=<?php echo $t['id']; ?>" class="compact-ip-btn-action"><?= ($is_en) ? 'VIEW ITINERARY' : 'VER ITINERARIO' ?></a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <section class="banner-ip-section">
        <div class="banner-ip-overlay">
            <div class="banner-ip-container">

                <div class="banner-ip-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>

                <h2 class="banner-ip-title">
                    <?= ($idioma == 'en') ? 'Your safety and well-being is our priority' : 'Tu seguridad y bienestar es nuestra prioridad' ?>
                </h2>
                <p class="banner-ip-subtitle">
                    <?= ($idioma == 'en') ? 'Explore with confidence' : 'Explora con confianza' ?>
                </p>

                <div class="banner-ip-text-wrapper">
                    <p>
                        <?php if ($idioma == 'en'): ?>
                            We have a firm commitment to ensuring the safety and well-being of our travelers, as well as the people and communities involved in our trips. As believers and promoters of exploration and learning, even in this time of social distancing, we have established strategic health and safety measures that support our services and guarantee an unforgettable travel experience.
                        <?php else: ?>
                            Tenemos el firme compromiso de garantizar la seguridad y el bienestar de nuestros viajeros, así como de las personas y comunidades involucradas en nuestros viajes. Como creyentes y promotores de la exploración y el aprendizaje, incluso en este tiempo de distanciamiento social, hemos establecido medidas estratégicas de salud y seguridad que respaldan nuestros servicios y garantizan una inolvidable experiencia de viaje.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="banner-ip-action-box">
                    <a href="#" class="banner-ip-btn">
                        <?= ($idioma == 'en') ? 'Read more' : 'Leer más' ?>
                    </a>
                </div>

            </div>
        </div>
    </section>



    <!-- <section class="reviews-ip-section">
    <div class="reviews-ip-container">
        
        <div class="reviews-ip-header">
            <div class="reviews-ip-separator"></div>
            <h2 class="reviews-ip-title">Lo que dicen nuestros viajeros</h2>
            <p class="reviews-ip-subtitle">Experiencias reales de quienes confiaron en nosotros para su aventura en Cusco.</p>
        </div>

        <div class="reviews-ip-grid">
            
            <div class="reviews-ip-card">
                <div class="reviews-ip-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <blockquote class="reviews-ip-text">
                    "Increíble servicio desde el primer día. Hicimos el Salkantay Trek y la organización fue impecable. Nuestro guía conocía cada detalle de la ruta y la comida fue de lo mejor. ¡Totalmente recomendado!"
                </blockquote>
                <div class="reviews-ip-author">
                    <div class="reviews-ip-avatar">
                        <img src="assets/img/usuarios/user1.jpg" alt="Cliente IntiPath" loading="lazy">
                    </div>
                    <div class="reviews-ip-info">
                        <span class="reviews-ip-name">Marcos Reátegui</span>
                        <span class="reviews-ip-country">Lima, Perú</span>
                    </div>
                </div>
            </div>

            <div class="reviews-ip-card">
                <div class="reviews-ip-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <blockquote class="reviews-ip-text">
                    "Viajar a Machu Picchu con IntiPath fue la mejor decisión. Se nota la pasión que le ponen a su trabajo y el respeto que tienen por la cultura local. Grupos pequeños y atención personalizada."
                </blockquote>
                <div class="reviews-ip-author">
                    <div class="reviews-ip-avatar">
                        <img src="assets/img/usuarios/user2.jpg" alt="Cliente IntiPath" loading="lazy">
                    </div>
                    <div class="reviews-ip-info">
                        <span class="reviews-ip-name">Elena Rossi</span>
                        <span class="reviews-ip-country">Italia</span>
                    </div>
                </div>
            </div>

            <div class="reviews-ip-card">
                <div class="reviews-ip-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <blockquote class="reviews-ip-text">
                    "The best experience in Cusco! The guides were very professional and the equipment was top quality. I felt safe and very well taken care of throughout the entire hike. Thank you so much!"
                </blockquote>
                <div class="reviews-ip-author">
                    <div class="reviews-ip-avatar">
                        <img src="assets/img/usuarios/user3.jpg" alt="Cliente IntiPath" loading="lazy">
                    </div>
                    <div class="reviews-ip-info">
                        <span class="reviews-ip-name">James Wilson</span>
                        <span class="reviews-ip-country">Australia</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section> -->



    <section class="destinos-db-section">
        <div class="destinos-db-contenedor">

            <div class="destinos-db-header">
                <div class="destinos-db-icon-top">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h2 class="destinos-db-title">
                    <?= ($idioma == 'en') ? 'Suggested Destinations in Peru' : 'Destinos sugeridos en Perú' ?>
                </h2>
                <p class="destinos-db-subtitle">
                    <?= ($idioma == 'en')
                        ? 'Discover Machu Picchu and Cusco with the best travel guides in the country.'
                        : 'Descubre Machu Picchu y Cusco junto a los mejores guías de viaje del país.' ?>
                </p>
                <p class="destinos-db-text">
                    <?php if ($idioma == 'en'): ?>
                        Peru has world-class tourist attractions, imposing archaeological sites, and a great diversity of flora and fauna. No matter what type of traveler you are; Peru offers you a wide variety of activities to satisfy all tastes.
                    <?php else: ?>
                        Perú posee atracciones turísticas de clase mundial, imponentes sitios arqueológicos y una gran diversidad de flora y fauna. No importa qué tipo de viajero seas; el Perú te ofrece una amplia variedad de actividades para satisfacer todos los gustos.
                    <?php endif; ?>
                </p>
            </div>

            <div class="destinos-db-grid">
                <?php if (!empty($tours)): ?>
                    <?php
                    // Si quieres mostrar solo los primeros 6 destinos
                    $destinos_limitados = array_slice($tours, 0, 6);
                    foreach ($destinos_limitados as $t):
                        // Lógica de traducción para los datos de la BD
                        $tit_dest = ($is_en && !empty($t['titulo_en'])) ? $t['titulo_en'] : $t['titulo'];
                        $loc_dest = ($is_en && !empty($t['ubicacion_texto_en'])) ? $t['ubicacion_texto_en'] : ($t['ubicacion_texto'] ?? 'Cusco, Peru');
                    ?>
                        <div class="destinos-db-item">
                            <a href="detalle_tour.php?id=<?php echo $t['id']; ?>" class="destinos-db-link">
                                <div class="destinos-db-circle">
<img src="assets/img/tours/<?php echo $t['imagen_principal']; ?>" loading="lazy" alt="<?php echo $tit_dest; ?>">
                                </div>
                                <h4 class="destinos-db-name"><?php echo mb_strtoupper($tit_dest); ?></h4>
                                <p class="destinos-db-loc"><?php echo $loc_dest; ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?= ($idioma == 'en') ? 'No destinations available at the moment.' : 'No hay destinos disponibles por el momento.' ?></p>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <section class="faq-ip-section">
        <div class="faq-ip-container">

            <div class="faq-ip-header">
                <div class="faq-ip-icon"><i class="fas fa-question-circle"></i></div>
                <h2 class="faq-ip-title">
                    <?= ($idioma == 'en') ? 'Frequently Asked Questions' : 'Las preguntas más frecuentes' ?>
                </h2>
                <p class="faq-ip-subtitle">
                    <?= ($idioma == 'en') ? 'We solve your doubts about IntiPath Tours' : 'Resolvemos tus dudas sobre IntiPath Tours' ?>
                </p>
            </div>

            <div class="faq-ip-wrapper">
                <?php
                if (!empty($faqs)):
                    foreach ($faqs as $f):
                        // LÓGICA DE TRADUCCIÓN:
                        // Si el idioma es inglés y existe la traducción en la base de datos, la usamos.
                        $pregunta_faq  = ($idioma == 'en' && !empty($f['pregunta_en'])) ? $f['pregunta_en'] : $f['pregunta'];
                        $respuesta_faq = ($idioma == 'en' && !empty($f['respuesta_en'])) ? $f['respuesta_en'] : $f['respuesta'];
                ?>
                        <div class="faq-ip-item">
                            <button class="faq-ip-question">
                                <?php echo mb_strtoupper($pregunta_faq); ?>
                                <span class="faq-ip-icon-status">+</span>
                            </button>
                            <div class="faq-ip-answer">
                                <div class="faq-ip-answer-content">
                                    <?php echo $respuesta_faq; ?>
                                </div>
                            </div>
                        </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>

        </div>
    </section>

    <<?php if (!empty($licencias)): ?>
        <section class="licencias-custom-section">
        <div class="licencias-container">
            <div class="licencias-header">
                <h2 class="licencias-titulo">
                    <?= (isset($idioma) && $idioma == 'en') ? 'Our Licenses & Certifications' : 'Nuestras Licencias y Permisos' ?>
                </h2>
                <div class="licencias-linea"></div>
            </div>

            <div class="licencias-grid">
                <?php foreach ($licencias as $lic): ?>
                    <div class="licencia-card">
                        <a href="assets/img/licencias/<?= $lic['imagen'] ?>"
                            data-fancybox="gallery-licencias"
                            data-caption="<?= htmlspecialchars($lic['titulo']) ?>"
                            class="licencia-box-link">
                            <img src="assets/img/licencias/<?= $lic['imagen'] ?>"
                                alt="<?= htmlspecialchars($lic['titulo']) ?>" loading="lazy">
                        </a>
                        <span class="licencia-tag"><?= htmlspecialchars($lic['titulo']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        </section>
    <?php endif; ?>

<?php
// ================= SECCIÓN "LO QUE DICEN NUESTROS CLIENTES" =================
$rev_cfg  = $resenas_data['config'] ?? [];
$rev_json = $resenas_data['datos'] ?? resenas_valores_default();
if (!empty($rev_cfg) && (int)($rev_cfg['activo'] ?? 0) === 1):
    $rev_etiqueta = $rev_json['etiqueta'] ?? 'Inti Path Tours';
    $rev_plats    = $rev_json['plataformas'] ?? [];
    $rev_max      = max(1, min(6, (int)($rev_json['max_por_plataforma'] ?? 3)));
    $rev_lines    = max(1, min(8, (int)($rev_json['lineas_texto'] ?? 3)));
?>
<section class="resenas-ip-section" style="--rv-lines: <?= $rev_lines ?>;">
    <div class="resenas-ip-container">

        <div class="resenas-ip-header">
            <div class="resenas-ip-separator"></div>
            <h2 class="resenas-ip-title"><?= htmlspecialchars($is_en ? ($rev_cfg['titulo_en'] ?? '') : ($rev_cfg['titulo_es'] ?? '')) ?></h2>
            <h3 class="resenas-ip-subtitle"><?= htmlspecialchars($is_en ? ($rev_cfg['subtitulo_en'] ?? '') : ($rev_cfg['subtitulo_es'] ?? '')) ?></h3>
            <p class="resenas-ip-descripcion"><?= $is_en ? ($rev_cfg['texto_en'] ?? '') : ($rev_cfg['texto_es'] ?? '') ?></p>
        </div>

        <?php foreach (['tripadvisor', 'google', 'trustpilot'] as $plat):
            $pf = $rev_plats[$plat] ?? null;
            if (!$pf || empty($pf['activo'])) continue;
            $reviews_fila = $resenas_data['resenas'][$plat] ?? [];
            if (empty($reviews_fila)) continue;
            $reviews_fila = array_slice($reviews_fila, 0, $rev_max);
        ?>
        <div class="resenas-ip-row rv-plat-<?= $plat ?>">

            <aside class="resenas-ip-summary">
                <div class="rv-sum-logo">
                    <?php if ($plat === 'tripadvisor'): ?>
                        <img src="https://cdn.trustindex.io/assets/platform/Tripadvisor/icon.svg" alt="TripAdvisor" width="24" height="24"> <span>TripAdvisor</span>
                    <?php elseif ($plat === 'google'): ?>
                        <img src="https://cdn.trustindex.io/assets/platform/Google/icon.svg" alt="Google" width="24" height="24"> <span>Google Reviews</span>
                    <?php else: ?>
                        <img src="https://cdn.trustindex.io/assets/platform/Trustpilot/icon.svg" alt="Trustpilot" width="24" height="24"> <span>Trustpilot</span>
                    <?php endif; ?>
                </div>
                <p class="rv-sum-trek"><?= htmlspecialchars($rev_etiqueta) ?></p>
                <p class="rv-sum-score"><?= htmlspecialchars($pf['puntaje'] ?? '') ?></p>
                <p class="rv-sum-stars-label"><?= $is_en ? 'Out of 5 stars' : 'De 5 estrellas' ?></p>
                <div class="rv-sum-rating"><?= render_valoracion_resena($plat) ?></div>
                <a class="rv-sum-count" href="<?= htmlspecialchars($pf['url'] ?? '#') ?>" target="_blank" rel="noopener">
                    <?= $is_en ? 'Based on' : 'Basado en' ?> <?= number_format((int)($pf['opiniones'] ?? 0), 0) ?> <?= $is_en ? 'reviews' : 'opiniones' ?>
                </a>
            </aside>

            <?php foreach ($reviews_fila as $r): ?>
            <article class="resenas-ip-card">
                <div class="rv-plat-badge rv-badge-<?= $plat ?>">
                    <?php if ($plat === 'tripadvisor'): ?>
                        <img src="https://cdn.trustindex.io/assets/platform/Tripadvisor/icon.svg" alt="TripAdvisor" width="16" height="16">
                    <?php elseif ($plat === 'google'): ?>
                        <img src="https://cdn.trustindex.io/assets/platform/Google/icon.svg" alt="Google" width="16" height="16">
                    <?php else: ?>
                        <img src="https://cdn.trustindex.io/assets/platform/Trustpilot/icon.svg" alt="Trustpilot" width="16" height="16">
                    <?php endif; ?>
                </div>
                <div class="rv-card-head">
                    <span class="rv-avatar" style="background: <?= htmlspecialchars($r['color_avatar'] ?: '#0f9b9e') ?>;"><?= htmlspecialchars(mb_strtoupper(mb_substr($r['autor'], 0, 1))) ?></span>
                    <div class="rv-card-who">
                        <span class="rv-autor"><?= htmlspecialchars($r['autor']) ?></span>
                        <span class="rv-fecha"><?= htmlspecialchars($r['fecha']) ?></span>
                    </div>
                </div>
                <?php if (!empty($r['titulo'])): ?>
                    <h4 class="rv-titulo"><?= htmlspecialchars($r['titulo']) ?></h4>
                <?php endif; ?>
                <div class="rv-rating-sm"><?= render_valoracion_resena($plat) ?></div>
                <p class="rv-texto"><?= htmlspecialchars($r['texto']) ?></p>
                <a class="rv-link-full" href="<?= htmlspecialchars($r['link'] ?: '#') ?>" target="_blank" rel="noopener"><?= $is_en ? 'Read the full review' : 'Ver la opinión completa' ?></a>
            </article>
            <?php endforeach; ?>

        </div>
        <?php endforeach; ?>

        <?php if (!empty($rev_json['widget_activo']) && !empty($rev_json['widget_code'])): ?>
        <div class="resenas-ip-widget">
            <?= $rev_json['widget_code'] ?>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>
</main>
<script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<?php
// 3. Llamamos al Footer (Cierra etiquetas y trae el JS del Slider)
include 'includes/footer.php';
?>