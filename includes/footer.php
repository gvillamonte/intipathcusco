<?php
// ============================================================
// 0. SCRIPTS ESPECÍFICOS DE TOUR
// ============================================================
include __DIR__ . '/tour_scripts.php';

// ============================================================
// 1. OBTENER LA CONFIGURACIÓN DE LA BASE DE DATOS
// ============================================================
if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
}

// Detectamos el idioma desde la sesión
$is_en = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'en');

// --- Footer via procedure (footer_config + footer_links) ---
$f = [];
$links_info = [];
$stmt_fc = $db->query("CALL obtener_datos_footer()");

// Result 1: footer_config
while ($row = $stmt_fc->fetch(PDO::FETCH_ASSOC)) {
    $f[$row['clave']] = $row['valor'];
}
$stmt_fc->nextRowset();

// Result 2: footer_links
while ($row = $stmt_fc->fetch(PDO::FETCH_ASSOC)) {
    $links_info[] = [
        'enlace' => $row['enlace'],
        'nombre' => $is_en ? $row['nombre_en'] : $row['nombre_es']
    ];
}
$stmt_fc->closeCursor();

$premios_lista = array_filter(array_map('trim', explode(',', $f['premios_logos'] ?? '')));
$asociaciones_lista = array_filter(array_map('trim', explode(',', $f['asociaciones_logos'] ?? '')));

// ============================================================
// 2. VARIABLES Y CONSULTAS AUTOMÁTICAS BILINGÜES
// ============================================================

// Consultamos títulos bilingües de los tours
$stmt_destinos = $db->query("SELECT id, titulo, titulo_en FROM tours WHERE estado = 'activo' ORDER BY id DESC LIMIT 5");
$destinos = $stmt_destinos->fetchAll(PDO::FETCH_ASSOC);

$links_empresa = [
    ['enlace' => 'nosotros.php', 'nombre' => $is_en ? 'About Us' : 'Quiénes Somos'],
    ['enlace' => 'contacto.php', 'nombre' => $is_en ? 'Contact Us' : 'Contáctanos'],
    ['enlace' => 'terminos-y-condiciones.php', 'nombre' => $is_en ? 'Terms & Conditions' : 'Términos y Condiciones']
];
?>

<style>
    /* MANTENEMOS TUS ESTILOS Y KEYFRAMES ORIGINALES 
       NO SE HA ELIMINADO NINGUNA REGLA PARA NO AFECTAR LA ESTRUCTURA 
    */
    :root {
        --color-primario-azul: var(--ip-primary);
        --color-secundario-amarillo: var(--ip-accent) ;
        --color-fondo-blanco: var(--ip-bg);
        --color-texto-oscuro: var(--ip-text);
        --color-texto-claro: var(--ip-text-light);
    }

    .footer-dark-premium {
        background-color: var(--color-primario-azul);
        color: var(--color-texto-claro);
        font-family: 'Segoe UI', Roboto, Arial, sans-serif;
        position: relative;
        border-top: 4px solid var(--color-secundario-amarillo);
    }

    .footer-certifications-wrap {
        background-color: var(--color-fondo-blanco);
        text-align: center;
        padding: 50px 20px 0 20px;
    }

    .premios-container {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 25px;
    }

    .premios-container a img {
        height: 100px;
        object-fit: contain;
        transition: transform 0.3s ease;
    }

    .premios-container a:hover img {
        transform: scale(1.08);
    }

    .btn-ver-mas-premios {
        background-color: var(--ip-accent);
        color: var(--ip-bg);
        padding: 10px 30px;
        border-radius: 25px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 50px;
        transition: all 0.3s ease;
    }

    .certificaciones-titulo {
        color: #b0b0b0;
        font-size: 1.5rem;
        font-weight: 300;
        margin-bottom: 30px;
        letter-spacing: 0.5px;
    }

    .asociaciones-container {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 20px;
    }

    .asociaciones-container a img {
        height: 45px;
        object-fit: contain;
        filter: grayscale(100%) opacity(0.5);
        transition: all 0.3s ease;
    }

    .asociaciones-container a:hover img {
        filter: grayscale(0%) opacity(1);
    }

    .footer-mountain-top {
        background-size: cover;
        background-position: bottom center;
        text-align: center;
        padding: 80px 20px 60px 20px;
        background-color: var(--color-fondo-blanco);
    }

    .footer-mountain-top .mountain-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .footer-mountain-top h3 {
        color: var(--color-texto-oscuro);
        font-size: clamp(1.2rem, 4vw, 1.8rem);
        font-weight: 700;
        margin-bottom: 5px;
    }

    .footer-mountain-top p {
        color: var(--ip-text-muted);
        font-size: 1rem;
    }

    .footer-main-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 50px 20px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 30px;
    }

    .col-footer-dark h4 {
        color: var(--color-fondo-blanco);
        font-size: 0.85rem;
        font-weight: bold;
        margin-bottom: 25px;
        text-transform: uppercase;
        position: relative;
    }

    .col-footer-dark h4::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 35px;
        height: 2px;
        background-color: var(--color-secundario-amarillo);
    }

    .col-footer-dark ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .col-footer-dark ul li {
        margin-bottom: 12px;
    }

    .col-footer-dark ul li a {
        color: var(--color-texto-claro);
        text-decoration: none;
        font-size: 0.88rem;
        transition: 0.3s ease;
        opacity: 0.85;
    }

    .col-footer-dark ul li a:hover {
        color: var(--color-secundario-amarillo);
        padding-left: 5px;
        opacity: 1;
    }

    .btn-consulte {
        background-color: var(--color-secundario-amarillo);
        color: var(--color-primario-azul);
        border: none;
        padding: 12px 25px;
        font-size: 0.8rem;
        font-weight: bold;
        border-radius: 4px;
        width: 100%;
        margin-top: 15px;
        transition: 0.3s;
        cursor: pointer;
    }

    .btn-consulte:hover {
        background-color: var(--color-fondo-blanco);
        transform: translateY(-2px);
    }

    .footer-social-wrapper {
        text-align: center;
        padding: 30px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .social-pill {
        display: inline-flex;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50px;
        padding: 8px 25px;
        gap: 20px;
    }

    .social-pill a {
        color: var(--color-fondo-blanco);
        font-size: 1.2rem;
        transition: 0.3s;
    }

    .social-pill a:hover {
        color: var(--color-secundario-amarillo);
        transform: scale(1.2);
    }

    .footer-bottom-bar {
        background-color: rgba(0, 0, 0, 0.2);
        padding: 25px 20px;
    }

    .bottom-flex {
        max-width: 1300px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .footer-legal-links a {
        color: var(--color-texto-claro);
        text-decoration: none;
        font-size: 0.75rem;
        margin-left: 15px;
        font-weight: bold;
        opacity: 0.8;
        transition: opacity 0.3s;
    }

    .footer-legal-links a:hover {
        color: var(--color-secundario-amarillo);
        opacity: 1;
    }

    .btn-whatsapp-flotante {
        position: fixed !important;
        bottom: 30px !important;
        right: 30px !important;
        background-color: var(--ip-btn-whatsapp) !important;
        color: var(--ip-bg) !important;
        width: 60px !important;
        height: 60px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 35px !important;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3) !important;
        z-index: 999999 !important;
        transition: all 0.3s ease !important;
        animation: latido-wa 2s infinite;
    }

    /* KEYFRAMES ORIGINALES REPETIDOS SIN CAMBIOS */
    @keyframes latido-wa {
        0% { transform: scale(1); }
        10% { transform: scale(1.05); }
        20% { transform: scale(1); }
        100% { transform: scale(1); }
    }

    @media (max-width: 1100px) {
        .footer-main-container { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 768px) {
        .footer-main-container { grid-template-columns: repeat(2, 1fr); }
        .bottom-flex { flex-direction: column; text-align: center; }
        .footer-legal-links { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
        .footer-legal-links a { margin-left: 0; }
        .btn-whatsapp-flotante {
            bottom: 20px !important;
            right: 15px !important;
            width: 50px !important;
            height: 50px !important;
            font-size: 28px !important;
        }
    }

    @media (max-width: 480px) {
        .footer-main-container { grid-template-columns: 1fr; text-align: center; }
        .col-footer-dark h4::after { left: 50%; transform: translateX(-50%); }
    }
</style>

<footer class="footer-dark-premium">

    <div class="footer-certifications-wrap">
        <div class="premios-container">
            <?php
            if (!empty($premios_lista)):
                foreach ($premios_lista as $item):
                    $data = explode('|', $item);
                    $imagen = trim($data[0]);
                    $enlace = isset($data[1]) ? trim($data[1]) : '#';
                    if (!empty($imagen)):
            ?>
                        <a href="<?php echo htmlspecialchars($enlace); ?>" target="_blank">
                            <img src="assets/img/certificaciones/<?php echo htmlspecialchars($imagen); ?>" alt="Award">
                        </a>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
        </div>

        <a href="certificaciones.php" class="btn-ver-mas-premios"><?= $is_en ? 'View more' : 'Ver más' ?></a>
        <h3 class="certificaciones-titulo"><?= $is_en ? 'Travel Associations and Certifications' : 'Asociaciones y Certificaciones de Viajes' ?></h3>

        <div class="asociaciones-container">
            <?php
            if (!empty($asociaciones_lista)):
                foreach ($asociaciones_lista as $item):
                    $data = explode('|', $item);
                    $imagen = trim($data[0]);
                    $enlace = isset($data[1]) ? trim($data[1]) : '#';
                    if (!empty($imagen)):
            ?>
                        <a href="<?php echo htmlspecialchars($enlace); ?>" target="_blank">
                            <img src="assets/img/certificaciones/<?php echo htmlspecialchars($imagen); ?>" alt="Association">
                        </a>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
        </div>
    </div>

    <?php $mtn_bg = !empty($f['mountain_img']) ? 'assets/img/' . htmlspecialchars($f['mountain_img']) : 'assets/img/bg-montanas-footer.png'; ?>
    <div class="footer-mountain-top" data-bg-lazy="<?= $mtn_bg ?>">
        <div class="mountain-content">
            <h3><?= !empty($f['mountain_title_' . ($is_en ? 'en' : 'es')]) ? htmlspecialchars($f['mountain_title_' . ($is_en ? 'en' : 'es')]) : ($is_en ? 'Life is short and the world is wide' : 'La vida es corta y el mundo es amplio') ?></h3>
            <p><?= !empty($f['mountain_subtitle_' . ($is_en ? 'en' : 'es')]) ? htmlspecialchars($f['mountain_subtitle_' . ($is_en ? 'en' : 'es')]) : ($is_en ? 'the sooner you start exploring it, the better.' : 'cuanto antes empieces a explorarlo, mejor.') ?></p>
        </div>
    </div>

    <div class="footer-main-container">

        <div class="col-footer-dark">
            <h4><?= $is_en ? 'COMPANY' : 'EMPRESA' ?></h4>
            <ul>
                <?php foreach ($links_empresa as $l): ?>
                    <li><a href="<?php echo $l['enlace']; ?>"><?php echo $l['nombre']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-footer-dark">
            <h4><?= $is_en ? 'DESTINATIONS' : 'DESTINOS' ?></h4>
            <ul>
                <?php foreach ($destinos as $dest): 
                    $tit_dest = ($is_en && !empty($dest['titulo_en'])) ? $dest['titulo_en'] : $dest['titulo'];
                ?>
                    <li><a href="detalle_tour.php?id=<?php echo $dest['id']; ?>"><?php echo htmlspecialchars($tit_dest); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-footer-dark">
            <h4><?= $is_en ? 'USEFUL INFO' : 'INFO ÚTIL' ?></h4>
            <ul>
                <?php foreach ($links_info as $l): ?>
                    <li><a href="<?php echo $l['enlace']; ?>"><?php echo $l['nombre']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-footer-dark">
            <h4><?= $is_en ? 'OFFICE' : 'OFICINA' ?></h4>
            <p><i class="fas fa-map-marker-alt" style="color:var(--color-secundario-amarillo)"></i> <?php echo !empty($f['direccion']) ? htmlspecialchars($f['direccion']) : 'Cusco, Perú'; ?></p>
            <p class="mt-3"><strong><?= $is_en ? 'Opening Hours:' : 'Horarios:' ?></strong></p>
            <p><i class="far fa-clock"></i> <?php echo !empty($f['horario']) ? htmlspecialchars($f['horario']) : '9:00 am - 7:00 pm'; ?></p>
        </div>

        <div class="col-footer-dark">
            <h4><?= $is_en ? 'CONTACT US' : 'CONTÁCTENOS' ?></h4>
            <p><i class="fas fa-envelope"></i> <?php echo !empty($f['email']) ? htmlspecialchars($f['email']) : 'info@intipathtours.com'; ?></p>
            <p><i class="fas fa-phone"></i> <?php echo !empty($f['telefono']) ? htmlspecialchars($f['telefono']) : ''; ?></p>
            <div style="border-top: 1px dotted rgba(255,255,255,0.2); margin: 15px 0;"></div>
            <p><small style="color:var(--color-secundario-amarillo)"><?= $is_en ? 'Partner Agencies:' : 'Agencias Partners:' ?></small><br>travel.partners@intipathtours.com</p>
            <button class="btn-consulte" onclick="window.location.href='contacto.php'"><?= $is_en ? 'Inquire Now' : 'Consulte Ahora' ?></button>
        </div>
    </div>

    <div class="footer-social-wrapper">
        <div class="social-pill">
            <?php if (!empty($f['facebook'])): ?>
                <a href="<?php echo htmlspecialchars($f['facebook']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if (!empty($f['instagram'])): ?>
                <a href="<?php echo htmlspecialchars($f['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
            <?php if (!empty($f['tiktok'])): ?>
                <a href="<?php echo htmlspecialchars($f['tiktok']); ?>" target="_blank"><i class="fab fa-tiktok"></i></a>
            <?php endif; ?>
            <?php if (!empty($f['youtube'])): ?>
                <a href="<?php echo htmlspecialchars($f['youtube']); ?>" target="_blank"><i class="fab fa-youtube"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer-bottom-bar">
        <div class="bottom-flex">
            <div class="copy-area" style="display: flex; align-items: center;">
                <img src="assets/img/<?php echo isset($f['logo']) ? htmlspecialchars($f['logo']) : 'logo.png'; ?>" alt="Logo" style="height: 35px; margin-right: 15px;">
                <div style="display: flex; flex-direction: column; align-items: flex-start;">
                    <span style="color: var(--ip-bg); font-size: 14px; line-height: 1.2;">
                        &copy; <?php echo date('Y'); ?> 
                        <strong><?php echo isset($f['razon_social']) ? htmlspecialchars($f['razon_social']) : 'IntiPath Tours Peru S.A.C.'; ?></strong>
                    </span>
                    <small style="opacity: 0.8; color: var(--ip-bg); font-size: 12px; margin-top: 2px;">RUC: <?php echo isset($f['ruc']) ? htmlspecialchars($f['ruc']) : '20606083182'; ?></small>
                </div>
            </div>
            <div class="footer-legal-links">
                <a href="libro-de-reclamaciones.php"><?= $is_en ? 'COMPLAINTS BOOK' : 'LIBRO DE RECLAMACIONES' ?></a>
                <a href="terminos-y-condiciones.php"><?= $is_en ? 'TERMS & CONDITIONS' : 'TÉRMINOS & CONDICIONES' ?></a>
                <a href="politica-privacidad.php"><?= $is_en ? 'PRIVACY POLICY' : 'POLÍTICA DE PRIVACIDAD' ?></a>
            </div>
        </div>
    </div>
</footer>

<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $f['whatsapp'] ?? ''); ?>?text=<?= $is_en ? 'Hello%20IntiPath%20Tours' : 'Hola%20IntiPath%20Tours' ?>" class="btn-whatsapp-flotante" target="_blank">
    <i class="fab fa-whatsapp"></i>
</a>

<?php
// Banner de cookies + GA4 condicional (solo si hay consentimiento)
require_once __DIR__ . '/cookie_consent.php';
renderCookieBanner();
?>