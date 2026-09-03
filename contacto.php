<?php
include 'includes/header.php';
include 'includes/moneda_helper.php';
require_once __DIR__ . '/includes/csrf_helper.php';

$is_en = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'en');

// Obtener tours activos
$tours_stmt = $db->query("CALL obtener_tours_activos()");
$tours = $tours_stmt->fetchAll(PDO::FETCH_ASSOC);
$tours_stmt->closeCursor();

// Array de países
$paises_es = [
    "AF" => "Afganistán", "AL" => "Albania", "DE" => "Alemania", "AD" => "Andorra", "AO" => "Angola",
    "AR" => "Argentina", "AM" => "Armenia", "AU" => "Australia", "AT" => "Austria", "AZ" => "Azerbaiyán",
    "BE" => "Bélgica", "BO" => "Bolivia", "BR" => "Brasil", "CA" => "Canadá", "CL" => "Chile",
    "CN" => "China", "CO" => "Colombia", "CR" => "Costa Rica", "CU" => "Cuba", "EC" => "Ecuador",
    "ES" => "España", "US" => "Estados Unidos", "FR" => "Francia", "GT" => "Guatemala", "HN" => "Honduras",
    "IT" => "Italia", "MX" => "México", "NI" => "Nicaragua", "PA" => "Panamá", "PY" => "Paraguay",
    "PE" => "Perú", "PR" => "Puerto Rico", "GB" => "Reino Unido", "DO" => "República Dominicana",
    "UY" => "Uruguay", "VE" => "Venezuela"
];
$paises_en = [
    "AF" => "Afghanistan", "AL" => "Albania", "DE" => "Germany", "AD" => "Andorra", "AO" => "Angola",
    "AR" => "Argentina", "AM" => "Armenia", "AU" => "Australia", "AT" => "Austria", "AZ" => "Azerbaijan",
    "BE" => "Belgium", "BO" => "Bolivia", "BR" => "Brazil", "CA" => "Canada", "CL" => "Chile",
    "CN" => "China", "CO" => "Colombia", "CR" => "Costa Rica", "CU" => "Cuba", "EC" => "Ecuador",
    "ES" => "Spain", "US" => "United States", "FR" => "France", "GT" => "Guatemala", "HN" => "Honduras",
    "IT" => "Italy", "MX" => "Mexico", "NI" => "Nicaragua", "PA" => "Panama", "PY" => "Paraguay",
    "PE" => "Peru", "PR" => "Puerto Rico", "GB" => "United Kingdom", "DO" => "Dominican Republic",
    "UY" => "Uruguay", "VE" => "Venezuela"
];
$paises = $is_en ? $paises_en : $paises_es;
asort($paises);

$paises_opts = '';
foreach ($paises as $codigo => $nombre) {
    $selected = ($codigo == 'PE') ? ' selected' : '';
    $prefix = ($codigo == 'PE') ? '🇵🇪 ' : '';
    $paises_opts .= '<option value="' . htmlspecialchars($nombre) . '"' . $selected . '>' . $prefix . $nombre . '</option>';
}

// Tours JSON para JavaScript
$tours_json = json_encode($tours);
?>

<style>
    :root { --azul-intipath: #c6d544; --naranja-intipath: #0f9b9e; --naranja-hover: #c6d544; --gris-fondo: #f8fafc; --blanco: #ffffff; --texto-gris: #64748b; --sombra: 0 20px 50px rgba(0, 0, 0, 0.1); }
    .ip-page-container { background-color: var(--gris-fondo); font-family: 'Poppins', sans-serif; padding-bottom: 80px; }
    .ip-hero-mini { background: linear-gradient(rgba(21, 48, 93, 0.8), rgba(21, 48, 93, 0.8)), url('assets/img/Machu-Picchu.jpg') center/cover no-repeat fixed; height: 350px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: var(--blanco); border-radius: 0 0 50px 50px; }
    .ip-hero-mini h1 { font-size: 48px; font-weight: 800; margin: 0; }
    .ip-hero-mini p { font-size: 18px; opacity: 0.9; }
    .ip-contact-card { max-width: 1250px; margin: -80px auto 0; display: grid; grid-template-columns: 1fr 2fr; background: var(--blanco); border-radius: 30px; overflow: hidden; box-shadow: var(--sombra); }
    .ip-sidebar-blue { background-image: linear-gradient(rgba(21, 48, 93, 0.85), rgba(21, 48, 93, 0.85)), url('assets/img/Machu-Picchu.jpg'); color: var(--blanco); padding: 60px 45px; display: flex; flex-direction: column; gap: 25px; }
    .ip-contact-item { display: flex; align-items: center; gap: 15px; text-decoration: none; color: var(--blanco); transition: 0.3s; }
    .ip-contact-item i { font-size: 20px; color: var(--naranja-intipath); width: 25px; }
    .ip-social-circles { display: flex; gap: 15px; margin-top: 10px; }
    .ip-social-circles a { width: 45px; height: 45px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--blanco); transition: 0.3s; }
    .ip-social-circles a:hover { background: var(--naranja-intipath); transform: translateY(-5px); }
    .ip-form-body { padding: 50px 60px; background: var(--blanco); }
    .ip-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .ip-full-width { grid-column: span 2; }
    .ip-input-group { display: flex; flex-direction: column; gap: 6px; }
    .ip-input-group label { font-size: 11px; font-weight: 700; color: var(--texto-gris); text-transform: uppercase; }
    .ip-input-group input, .ip-input-group select { border: none; border-bottom: 2px solid #e2e8f0; padding: 10px 0; font-size: 14px; outline: none; transition: 0.3s; }
    .ip-input-group input:focus, .ip-input-group select:focus { border-bottom: 2px solid var(--naranja-intipath); }
    .ip-input-group select { background: transparent; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 30px; }
    .ip-section-title { grid-column: span 2; font-size: 13px; font-weight: 800; color: #15305D; text-transform: uppercase; border-left: 4px solid var(--naranja-intipath); padding-left: 12px; margin-top: 10px; }
    .pasajero-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 10px; position: relative; }
    .pasajero-block .remove-btn { position: absolute; top: 8px; right: 8px; background: #fee2e2; color: #dc2626; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; }
    .pasajero-block .remove-btn:hover { background: #fecaca; }
    .pasajero-row { display: grid; grid-template-columns: 100px 1fr 1fr 120px 140px; gap: 8px; align-items: end; }
    .pasajero-row .ip-input-group input, .pasajero-row .ip-input-group select { font-size: 12px; padding: 8px 0; }
    .pasajero-label { font-size: 10px; font-weight: 700; color: var(--texto-gris); }
    .add-pax-btn { display: inline-block; padding: 6px 14px; border: 2px dashed #ccc; border-radius: 8px; background: transparent; color: #888; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-right: 8px; }
    .add-pax-btn:hover { border-color: var(--naranja-intipath); color: var(--naranja-intipath); }
    .resumen-pago { background: #f0fdf4; border: 2px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-top: 10px; }
    .resumen-pago .line { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
    .resumen-pago .total { font-weight: 800; font-size: 16px; color: #15305D; border-top: 2px solid #bbf7d0; padding-top: 8px; margin-top: 8px; }
    .resumen-pago .adelanto { color: #059669; font-weight: 700; }
    .resumen-pago .saldo { color: #d97706; font-weight: 700; }
    .ip-submit-btn { grid-column: span 2; background: var(--naranja-intipath); color: var(--blanco); border: none; padding: 18px; border-radius: 50px; font-size: 16px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: 0.3s; }
    .ip-submit-btn:hover { background: var(--naranja-hover); transform: translateY(-3px); }
    .ip-submit-btn.secondary { background: #15305D; }
    .ip-submit-btn.secondary:hover { background: #1a3a6a; }
    .btn-group-submit { grid-column: span 2; display: flex; gap: 15px; }
    .btn-group-submit button { flex: 1; }
    .tour-info-card { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; display: none; margin-top: 5px; }
    .tour-info-card.active { display: flex; gap: 15px; align-items: center; }
    .tour-info-card img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
    .tour-info-card .info h4 { margin: 0; font-size: 14px; color: #15305D; }
    .tour-info-card .info p { margin: 2px 0 0; font-size: 12px; color: #666; }

    /* Tablet */
    @media (max-width: 900px) {
        .ip-contact-card { grid-template-columns: 1fr; margin: -50px 20px 0; }
        .ip-form-grid { grid-template-columns: 1fr; }
        .ip-full-width, .ip-section-title, .btn-group-submit { grid-column: span 1; }
        .pasajero-row { grid-template-columns: 1fr 1fr; }
        .pasajero-row .ip-input-group:nth-child(4), .pasajero-row .ip-input-group:nth-child(5) { grid-column: span 1; }
        .ip-sidebar-blue { padding: 40px 30px; }
        .ip-form-body { padding: 40px 30px; }
    }

    /* Mobile */
    @media (max-width: 600px) {
        .ip-hero-mini { height: 250px; border-radius: 0 0 30px 30px; }
        .ip-hero-mini h1 { font-size: 28px; }
        .ip-hero-mini p { font-size: 14px; }
        .ip-contact-card { margin: -40px 15px 0; border-radius: 20px; }
        .ip-sidebar-blue { padding: 30px 20px; }
        .ip-form-body { padding: 30px 20px; }
        .pasajero-row { grid-template-columns: 1fr; }
        .btn-group-submit { flex-direction: column; gap: 10px; }
        .ip-submit-btn { padding: 14px; font-size: 14px; }
        .tour-info-card { flex-direction: column; text-align: center; }
        .tour-info-card img { width: 100%; height: 120px; }
        .ip-loading-spinner { width: 45px; height: 45px; }
        .ip-loading-text { font-size: 15px; }
        .ip-loading-sub { font-size: 12px; }
        .ip-cancel-btn { padding: 8px 24px; font-size: 13px; }
        .ip-success-card { padding: 35px 25px; border-radius: 18px; }
        .ip-success-icon { width: 65px; height: 65px; }
        .ip-success-icon i { font-size: 28px; }
        .ip-success-card h3 { font-size: 18px; }
        .ip-success-card p { font-size: 13px; }
        .ip-success-card .reserva-code { font-size: 20px; }
        .ip-success-actions { flex-direction: column; gap: 8px; }
        .ip-success-actions a, .ip-success-actions button { padding: 10px 20px; font-size: 13px; }
    }

    /* Loading Overlay */
    .ip-loading-overlay { position: fixed; inset: 0; background: rgba(21, 48, 93, 0.92); z-index: 9999; display: none; align-items: center; justify-content: center; flex-direction: column; backdrop-filter: blur(4px); }
    .ip-loading-overlay.active { display: flex; }
    .ip-loading-spinner { width: 60px; height: 60px; border: 4px solid rgba(255,255,255,0.2); border-top-color: #c6d544; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 20px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .ip-loading-text { color: #fff; font-size: 18px; font-weight: 600; margin-bottom: 8px; }
    .ip-loading-sub { color: rgba(255,255,255,0.7); font-size: 13px; margin-bottom: 25px; }
    .ip-cancel-btn { background: transparent; border: 2px solid rgba(255,255,255,0.4); color: #fff; padding: 10px 30px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .ip-cancel-btn:hover { border-color: #fff; background: rgba(255,255,255,0.1); }

    /* Success Modal */
    .ip-success-overlay { position: fixed; inset: 0; background: rgba(21, 48, 93, 0.92); z-index: 9999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .ip-success-overlay.active { display: flex; }
    .ip-success-card { background: #fff; border-radius: 24px; padding: 50px 40px; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 30px 60px rgba(0,0,0,0.3); animation: popIn 0.4s ease; }
    @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .ip-success-icon { width: 80px; height: 80px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .ip-success-icon i { font-size: 36px; color: #16a34a; }
    .ip-success-card h3 { font-size: 22px; font-weight: 800; color: #15305D; margin-bottom: 10px; }
    .ip-success-card p { color: #64748b; font-size: 14px; line-height: 1.6; margin-bottom: 8px; }
    .ip-success-card .reserva-code { font-size: 24px; font-weight: 800; color: #0f9b9e; margin: 15px 0; letter-spacing: 2px; }
    .ip-success-actions { display: flex; gap: 12px; margin-top: 25px; justify-content: center; }
    .ip-success-actions a, .ip-success-actions button { padding: 12px 28px; border-radius: 30px; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; transition: 0.3s; border: none; }
    .ip-success-actions .btn-primary { background: #0f9b9e; color: #fff; }
    .ip-success-actions .btn-primary:hover { background: #0d8a8d; }
    .ip-success-actions .btn-secondary { background: #f0f2f5; color: #15305D; }
    .ip-success-actions .btn-secondary:hover { background: #e2e8f0; }
</style>

<div class="ip-page-container">
    <section class="ip-hero-mini">
        <h1><?= $is_en ? "Book Your Adventure" : "Reserva Tu Aventura" ?></h1>
        <p><?= $is_en ? "Select your tour and complete your reservation." : "Selecciona tu tour y completa tu reserva." ?></p>
    </section>

    <div class="ip-contact-card">
        <div class="ip-sidebar-blue">
            <img src="assets/img/logo-inti-1e06.png" style="max-width: 170px; margin-bottom: 10px; filter: brightness(0) invert(1);">
            <h2 style="color: var(--naranja-intipath); font-size: 28px;"><?= $is_en ? "Direct Contact" : "Contacto Directo" ?></h2>

            <a href="https://wa.me/51920307331" target="_blank" class="ip-contact-item">
                <i class="fab fa-whatsapp"></i>
                <span><strong>920 307 331</strong><br><small>WhatsApp Business</small></span>
            </a>

            <a href="mailto:intipathtourstrekkinperu@gmail.com" class="ip-contact-item">
                <i class="far fa-envelope"></i>
                <span style="font-size: 14px;"><strong><?= $is_en ? "Write to us:" : "Escríbenos:" ?></strong><br>intipathtourstrekkinperu@gmail.com</span>
            </a>

            <div class="ip-social-circles">
                <a href="https://www.facebook.com/profile.php?id=61575450278086" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/inti.path.tours/" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@inti.path.tours" target="_blank"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>

        <div class="ip-form-body">
            <div id="ip-mensaje-ajax" style="display:none;"></div>

            <h2 style="color: var(--azul-intipath); font-size: 28px; margin-bottom: 30px; font-weight: 800;">
                <?= $is_en ? "Complete Reservation" : "Reserva Completa" ?>
            </h2>

            <form id="formContacto" action="admin/enviar_consulta.php" method="POST" class="ip-form-grid">
                <input type="hidden" name="accion" id="accion-input" value="consultar">
                <input type="hidden" name="whatsapp" id="whatsapp-input" value="">
                <input type="hidden" name="ajax" value="1">
                <?php campoCSRF(); ?>
                <!-- Honeypot anti-bots (invisible para humanos) -->
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px; opacity:0; height:0; width:0;" aria-hidden="true">

                <!-- SELECCIONAR TOUR -->
                <div class="ip-input-group ip-full-width">
                    <label><?= $is_en ? "Select Tour *" : "Seleccionar Tour *" ?></label>
                    <select name="id_tour" id="select-tour" required onchange="cargarInfoTour()">
                        <option value=""><?= $is_en ? "-- Select a tour --" : "-- Selecciona un tour --" ?></option>
                        <?php foreach ($tours as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                data-precio="<?= montoMoneda($t, $idioma) ?>"
                                data-precio-nino="<?= montoNinoMoneda($t, $idioma) ?>"
                                data-duracion="<?= $is_en ? $t['duracion_en'] : $t['duracion'] ?>"
                                data-porc-adelanto="<?= $t['porcentaje_adelanto'] ?>"
                                data-max-personas="<?= $t['max_personas'] ?>"
                                data-imagen="<?= $t['imagen_principal'] ?>"
                                data-nombre-es="<?= htmlspecialchars($t['titulo_es']) ?>"
                                data-nombre-en="<?= htmlspecialchars($t['titulo_en']) ?>">
                                <?= htmlspecialchars($is_en ? $t['titulo_en'] : $t['titulo_es']) ?> - <?= precioFormato($t, $idioma) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Info del tour seleccionado -->
                <div class="tour-info-card" id="tour-info-card">
                    <div style="position:relative;">
                        <img id="tour-img" src="" alt="">
                        <button type="button" id="btn-clear-tour" onclick="limpiarTour()" style="position:absolute; top:-6px; right:-6px; background:#dc2626; color:#fff; border:none; border-radius:50%; width:22px; height:22px; cursor:pointer; font-size:11px; display:none; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(0,0,0,0.3); transition:0.3s;" title="<?= $is_en ? 'Change tour' : 'Cambiar tour' ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="info">
                        <h4 id="tour-nombre"></h4>
                        <p><i class="fas fa-clock"></i> <span id="tour-duracion"></span> | <i class="fas fa-tag"></i> <?= simboloMoneda($idioma) ?><span id="tour-precio"></span>/persona</p>
                    </div>
                </div>

                <!-- DATOS PERSONALES -->
                <div class="ip-section-title"><?= $is_en ? "Personal Data" : "Datos Personales" ?></div>

                <div class="ip-input-group">
                    <label><?= $is_en ? "First Name *" : "Nombre *" ?></label>
                    <input type="text" name="nombre" placeholder="<?= $is_en ? 'Ex. John' : 'Ej. Juan' ?>" required>
                </div>
                <div class="ip-input-group">
                    <label><?= $is_en ? "Last Name *" : "Apellido *" ?></label>
                    <input type="text" name="apellido" placeholder="<?= $is_en ? 'Ex. Doe' : 'Ej. Sanchez' ?>" required>
                </div>
                <div class="ip-input-group ip-full-width">
                    <label><?= $is_en ? "Email *" : "Correo Electrónico *" ?></label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="ip-input-group">
                    <label><?= $is_en ? "Country *" : "País *" ?></label>
                    <select name="pais"><?= $paises_opts ?></select>
                </div>
                <div class="ip-input-group">
                    <label><?= $is_en ? "WhatsApp / Phone" : "WhatsApp / Teléfono" ?></label>
                    <input type="tel" name="telefono" placeholder="+51 987..." oninput="document.getElementById('whatsapp-input').value=this.value">
                </div>

                <!-- FECHA Y PASAJEROS -->
                <div class="ip-section-title"><?= $is_en ? "Trip Details" : "Detalles del Viaje" ?></div>

                <div class="ip-input-group">
                    <label><?= $is_en ? "Travel Date *" : "Fecha de Viaje *" ?></label>
                    <input type="date" name="fecha_viaje" required>
                </div>
                <div class="ip-input-group">
                    <label><?= $is_en ? "Adults *" : "Adultos *" ?></label>
                    <input type="number" name="adultos" id="input-adultos" value="2" min="1" max="20" onchange="actualizarPasajeros()">
                </div>
                <div class="ip-input-group">
                    <label><?= $is_en ? "Children" : "Niños" ?></label>
                    <input type="number" name="ninos" id="input-ninos" value="0" min="0" max="20" onchange="actualizarPasajeros()">
                </div>

                <!-- PASAJEROS -->
                <div class="ip-section-title"><?= $is_en ? "Passenger Data" : "Datos de Pasajeros" ?></div>

                <div class="ip-full-width" id="pasajeros-container">
                    <!-- Se llena con JS -->
                </div>

                <div class="ip-full-width">
                    <button type="button" class="add-pax-btn" onclick="agregarPasajero('adulto')">+ <?= $is_en ? "Add Adult" : "Agregar Adulto" ?></button>
                    <button type="button" class="add-pax-btn" onclick="agregarPasajero('nino')">+ <?= $is_en ? "Add Child" : "Agregar Niño" ?></button>
                </div>

                <!-- RESUMEN DE PAGO -->
                <div class="ip-section-title" id="resumen-title" style="display:none;"><?= $is_en ? "Payment Summary" : "Resumen de Pago" ?></div>

                <div class="ip-full-width resumen-pago" id="resumen-pago" style="display:none;">
                    <div class="line"><span><?= $is_en ? "Adults" : "Adultos" ?> <span id="res-adultos">2</span> × <?= simboloMoneda($idioma) ?><span id="res-precio-adulto">0</span></span> <span><?= simboloMoneda($idioma) ?><span id="res-total-adultos">0.00</span></span></div>
                    <div class="line"><span><?= $is_en ? "Children" : "Niños" ?> <span id="res-ninos">0</span> × <?= simboloMoneda($idioma) ?><span id="res-precio-nino">0</span></span> <span><?= simboloMoneda($idioma) ?><span id="res-total-ninos">0.00</span></span></div>
                    <div class="line total"><span><?= $is_en ? "TOTAL" : "TOTAL" ?></span> <span><?= simboloMoneda($idioma) ?><span id="res-total">0.00</span></span></div>
                    <div class="line adelanto"><span><?= $is_en ? "Advance" : "Adelanto" ?> (<span id="res-porc">30</span>%)</span> <span><?= simboloMoneda($idioma) ?><span id="res-adelanto">0.00</span></span></div>
                    <div class="line saldo"><span><?= $is_en ? "Balance in Cusco" : "Saldo en Cusco" ?></span> <span><?= simboloMoneda($idioma) ?><span id="res-saldo">0.00</span></span></div>
                </div>

                <!-- MENSAJE -->
                <div class="ip-input-group ip-full-width">
                    <label><?= $is_en ? "Message" : "Mensaje" ?></label>
                    <textarea name="mensaje" rows="3" placeholder="<?= $is_en ? 'Questions or special requirements?' : '¿Dudas o requerimientos especiales?' ?>" style="border:none; border-bottom:2px solid #e2e8f0; padding:10px 0; font-size:14px; outline:none; resize:none; font-family:inherit;"></textarea>
                </div>

                <!-- BOTONES -->
                <div class="btn-group-submit">
                    <button type="submit" class="ip-submit-btn secondary" onclick="document.getElementById('accion-input').value='consultar'">
                        <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> <?= $is_en ? 'SEND INQUIRY' : 'SOLO CONSULTAR' ?>
                    </button>
                    <button type="submit" class="ip-submit-btn" onclick="document.getElementById('accion-input').value='pagar'">
                        <i class="fas fa-credit-card" style="margin-right: 8px;"></i> <?= $is_en ? 'PAY NOW' : 'PAGAR AHORA' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="ip-loading-overlay" id="loadingOverlay">
    <div class="ip-loading-spinner"></div>
    <div class="ip-loading-text" id="loadingText"><?= $is_en ? 'Sending your reservation...' : 'Enviando tu reserva...' ?></div>
    <div class="ip-loading-sub" id="loadingSub"><?= $is_en ? 'Please don\'t close this window' : 'Por favor, no cierres esta ventana' ?></div>
    <button class="ip-cancel-btn" onclick="cancelarEnvio()"><?= $is_en ? 'Cancel' : 'Cancelar' ?></button>
</div>

<!-- Success Modal -->
<div class="ip-success-overlay" id="successOverlay">
    <div class="ip-success-card">
        <div class="ip-success-icon"><i class="fas fa-check"></i></div>
        <h3 id="successTitle"></h3>
        <p id="successMsg1"></p>
        <div class="reserva-code" id="successCode"></div>
        <p id="successMsg2" style="font-size:12px;"></p>
        <div class="ip-success-actions">
            <a href="index.php" class="btn-primary" id="successBtnHome"><?= $is_en ? 'Back to Home' : 'Volver al Inicio' ?></a>
            <button class="btn-secondary" onclick="document.getElementById('successOverlay').classList.remove('active');"><?= $is_en ? 'Close' : 'Cerrar' ?></button>
        </div>
    </div>
</div>

<script>
const toursData = <?= $tours_json ?>;
const isEn = <?= json_encode($is_en) ?>;
let pasajeroIdx = 0;
let formSubmitting = false;

function cargarInfoTour() {
    const select = document.getElementById('select-tour');
    const card = document.getElementById('tour-info-card');
    const resumen = document.getElementById('resumen-pago');
    const resumenTitle = document.getElementById('resumen-title');
    const btnClear = document.getElementById('btn-clear-tour');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) {
        card.classList.remove('active');
        resumen.style.display = 'none';
        resumenTitle.style.display = 'none';
        btnClear.style.display = 'none';
        return;
    }
    
    document.getElementById('tour-img').src = 'assets/img/tours/' + option.dataset.imagen;
    document.getElementById('tour-nombre').textContent = isEn ? option.dataset.nombreEn : option.dataset.nombreEs;
    document.getElementById('tour-duracion').textContent = option.dataset.duracion;
    document.getElementById('tour-precio').textContent = parseFloat(option.dataset.precio).toFixed(2);
    card.classList.add('active');
    resumen.style.display = 'block';
    resumenTitle.style.display = 'block';
    btnClear.style.display = 'flex';
    
    actualizarPasajeros();
}

function limpiarTour() {
    const select = document.getElementById('select-tour');
    select.selectedIndex = 0;
    cargarInfoTour();
}

function actualizarPasajeros() {
    const select = document.getElementById('select-tour');
    const option = select.options[select.selectedIndex];
    if (!option.value) return;
    
    const precioAdulto = parseFloat(option.dataset.precio);
    const precioNino = parseFloat(option.dataset.precioNino);
    const porcAdelanto = parseInt(option.dataset.porcAdelanto);
    const adultos = parseInt(document.getElementById('input-adultos').value) || 0;
    const ninos = parseInt(document.getElementById('input-ninos').value) || 0;
    
    const totalAdultos = adultos * precioAdulto;
    const totalNinos = ninos * precioNino;
    const total = totalAdultos + totalNinos;
    const adelanto = total * (porcAdelanto / 100);
    const saldo = total - adelanto;
    
    document.getElementById('res-adultos').textContent = adultos;
    document.getElementById('res-ninos').textContent = ninos;
    document.getElementById('res-precio-adulto').textContent = precioAdulto.toFixed(2);
    document.getElementById('res-precio-nino').textContent = precioNino.toFixed(2);
    document.getElementById('res-total-adultos').textContent = totalAdultos.toFixed(2);
    document.getElementById('res-total-ninos').textContent = totalNinos.toFixed(2);
    document.getElementById('res-total').textContent = total.toFixed(2);
    document.getElementById('res-porc').textContent = porcAdelanto;
    document.getElementById('res-adelanto').textContent = adelanto.toFixed(2);
    document.getElementById('res-saldo').textContent = saldo.toFixed(2);
    
    reconstruirPasajeros();
}

function reconstruirPasajeros() {
    const container = document.getElementById('pasajeros-container');
    const adultos = parseInt(document.getElementById('input-adultos').value) || 0;
    const ninos = parseInt(document.getElementById('input-ninos').value) || 0;
    const total = adultos + ninos;
    
    const existing = container.querySelectorAll('.pasajero-block').length;
    
    if (total > existing) {
        for (let i = existing; i < total; i++) {
            const tipo = i < adultos ? 'adulto' : 'nino';
            agregarPasajero(tipo, true);
        }
    } else if (total < existing) {
        const blocks = container.querySelectorAll('.pasajero-block');
        for (let i = existing - 1; i >= total; i--) {
            blocks[i].remove();
        }
    }
    
    reindexarPasajeros();
}

function agregarPasajero(tipo, silent) {
    const container = document.getElementById('pasajeros-container');
    const idx = container.querySelectorAll('.pasajero-block').length;
    const tipoLabel = tipo === 'adulto' ? (isEn ? 'Adult' : 'Adulto') : (isEn ? 'Child' : 'Niño');
    
    const html = `
    <div class="pasajero-block" data-tipo="${tipo}">
        <button type="button" class="remove-btn" onclick="this.parentElement.remove(); reindexarPasajeros();">&times;</button>
        <div class="pasajero-label">${isEn ? 'Passenger' : 'Pasajero'} #${idx + 1} (${tipoLabel})</div>
        <div class="pasajero-row" style="margin-top: 6px;">
            <div class="ip-input-group">
                <select name="pasajeros[${idx}][tipo]">
                    <option value="adulto" ${tipo === 'adulto' ? 'selected' : ''}>${isEn ? 'Adult' : 'Adulto'}</option>
                    <option value="nino" ${tipo === 'nino' ? 'selected' : ''}>${isEn ? 'Child' : 'Niño'}</option>
                </select>
            </div>
            <div class="ip-input-group">
                <input type="text" name="pasajeros[${idx}][nombres]" placeholder="${isEn ? 'Names' : 'Nombres'}" required>
            </div>
            <div class="ip-input-group">
                <input type="text" name="pasajeros[${idx}][apellidos]" placeholder="${isEn ? 'Last Names' : 'Apellidos'}" required>
            </div>
            <div class="ip-input-group">
                <input type="text" name="pasajeros[${idx}][documento]" placeholder="${isEn ? 'ID/Passport' : 'DNI/Pasaporte'}" required>
            </div>
            <div class="ip-input-group">
                <select name="pasajeros[${idx}][pais]"><?= $paises_opts ?></select>
            </div>
        </div>
    </div>`;
    
    container.insertAdjacentHTML('beforeend', html);
    if (!silent) reindexarPasajeros();
}

function reindexarPasajeros() {
    const container = document.getElementById('pasajeros-container');
    const blocks = container.querySelectorAll('.pasajero-block');
    blocks.forEach((block, i) => {
        block.querySelector('.pasajero-label').textContent = (isEn ? 'Passenger' : 'Pasajero') + ' #' + (i + 1);
        block.querySelectorAll('input, select').forEach(el => {
            const name = el.getAttribute('name');
            if (name) el.setAttribute('name', name.replace(/pasajeros\[\d+\]/, 'pasajeros[' + i + ']'));
        });
    });
}

// Init
document.addEventListener('DOMContentLoaded', function() {
    actualizarPasajeros();
    document.getElementById('formContacto').addEventListener('submit', function(e) {
        if (formSubmitting) {
            e.preventDefault();
            return;
        }
        bloquearBotonesFormulario(this);
        enviarFormulario(this);
    });
});

function bloquearBotonesFormulario(form) {
    form.querySelectorAll('button[type="submit"]').forEach(function(btn) {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    });
}

function rearmarBotonesFormulario(form) {
    form.querySelectorAll('button[type="submit"]').forEach(function(btn) {
        btn.disabled = false;
        btn.style.opacity = '';
        btn.style.cursor = '';
    });
}

function enviarFormulario(form) {
    formSubmitting = true;
    const accion = document.getElementById('accion-input').value;
    const overlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const loadingSub = document.getElementById('loadingSub');

    if (accion === 'pagar') {
        loadingText.textContent = isEn ? 'Processing your reservation...' : 'Procesando tu reserva...';
        loadingSub.textContent = isEn ? 'Redirecting to secure payment...' : 'Redirigiendo a pago seguro...';
    } else {
        loadingText.textContent = isEn ? 'Sending your inquiry...' : 'Enviando tu consulta...';
        loadingSub.textContent = isEn ? 'We\'ll respond shortly' : 'Te responderemos pronto';
    }
    overlay.classList.add('active');

    const formData = new FormData(form);

    fetch(form.action, { method: 'POST', body: formData })
        .then(async r => {
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(text.trim().substring(0, 300));
            }
        })
        .then(data => {
            overlay.classList.remove('active');
            formSubmitting = false;
            if (data.exito) {
                mostrarExito(data, accion);
            } else {
                rearmarBotonesFormulario(form);
                alert(data.error || (isEn ? 'An error occurred. Please try again.' : 'Ocurrió un error. Por favor, intenta de nuevo.'));
            }
        })
        .catch(err => {
            overlay.classList.remove('active');
            formSubmitting = false;
            rearmarBotonesFormulario(form);
            let msg = isEn ? 'Connection error. Please try again.' : 'Error de conexión. Por favor, intenta de nuevo.';
            if (err && err.message && err.message.indexOf('{') !== 0) {
                msg = (isEn ? 'Server error: ' : 'Error del servidor: ') + err.message;
            }
            alert(msg);
        });
}

function cancelarEnvio() {
    formSubmitting = false;
    rearmarBotonesFormulario(document.getElementById('formContacto'));
    document.getElementById('loadingOverlay').classList.remove('active');
}

function mostrarExito(data, accion) {
    const overlay = document.getElementById('successOverlay');
    const title = document.getElementById('successTitle');
    const msg1 = document.getElementById('successMsg1');
    const code = document.getElementById('successCode');
    const msg2 = document.getElementById('successMsg2');
    const btnHome = document.getElementById('successBtnHome');

    if (accion === 'pagar') {
        title.textContent = isEn ? 'Reservation Created!' : '¡Reserva Creada!';
        msg1.textContent = isEn
            ? 'Your reservation has been registered. You\'ll be redirected to complete the payment.'
            : 'Tu reserva ha sido registrada. Serás redirigido para completar el pago.';
        code.textContent = '#' + data.codigo;
        msg2.textContent = isEn
            ? 'Save this code for your records. You can use it to track your reservation.'
            : 'Guarda este código para tu registro. Puedes usarlo para rastrear tu reserva.';
        btnHome.href = data.redirect || ('seleccionar_pago.php?t=' + (data.token || ''));
        btnHome.textContent = isEn ? 'Continue to Payment' : 'Ir a Pagar';
    } else {
        title.textContent = isEn ? 'Inquiry Sent!' : '¡Consulta Enviada!';
        msg1.textContent = isEn
            ? 'We\'ve received your inquiry. Our team will contact you shortly.'
            : 'Hemos recibido tu consulta. Nuestro equipo te contactará pronto.';
        code.textContent = '#' + data.codigo;
        msg2.textContent = isEn
            ? 'You can also contact us via WhatsApp for faster response.'
            : 'También puedes contactarnos por WhatsApp para respuesta más rápida.';
        btnHome.href = 'index.php';
        btnHome.textContent = isEn ? 'Back to Home' : 'Volver al Inicio';
    }

    overlay.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
