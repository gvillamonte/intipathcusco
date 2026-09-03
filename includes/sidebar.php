<?php
// Evitar errores si la sesión no se ha iniciado en el archivo principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar el nombre del archivo actual
$pagina_actual = basename($_SERVER['PHP_SELF']);
$en_resenas = ($pagina_actual == 'admin_index.php');

// UNIFICAMOS NOMBRES Y LIMPIAMOS ESPACIOS
$usuario_nombre = trim($_SESSION['admin_nombre'] ?? 'Administrador');

// Permisos iniciales
$mis_accesos = (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) ? $_SESSION['permisos'] : [];

/**
 * REGLA DE ORO PARA ADMIN: 
 */
if (strtolower($usuario_nombre) == 'admin' || strtolower($usuario_nombre) == 'administrador') {
    $mis_accesos = [
        'mensajes', 'header_footer', 'footer_links', 'licencias',
        'sliders', 'tours', 'usuarios',
        'config', 'barra_movil', 'contenido_index', 'colores',
        'tipos_tours', 'info_viaje', 'calendario',
        'reclamos', 'terminos', 'privacidad', 'faqs', 'nosotros', 'blog', 'unete',
        'equipo', 'grupos', 'seguridad', 'garantia',
        'info_previa', 'niveles_dificultad', 'clima', 'equipaje',
        'seguridad_viaje', 'como_reservar', 'alquiler', 'disponibilidad', 'preguntas',
        'reservas_info',
                'reservas', 'pagos', 'plantilla_pdf', 'config_pagos', 'config_bancos',
        'paginas', 'seo', 'fundacion'
    ];
}
?>

<style>
/* ===== SIDEBAR TOGGLE - Autocontenido, sin depender de admin.css ===== */
#sidebar { transition: width 0.35s ease, opacity 0.35s ease; min-width: 0; }

/* Header del sidebar: base para posicionar el botón */
.sidebar-header { position: relative; }

/* Botón hamburguesa dentro del header (esquina sup. derecha) */
#sidebarToggle {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: rgba(255,255,255,0.12);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: background 0.2s, color 0.2s;
    z-index: 10;
}
#sidebarToggle:hover {
    background: #E8AC18;
    color: #15305D;
}

/* Botón flotante fijo para volver a abrir el sidebar (arriba-izquierda) */
#sidebarToggleFloat {
    position: fixed !important;
    top: 16px;
    left: 16px;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 2px solid #E8AC18;
    background: #15305D;
    color: #E8AC18;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.35);
    z-index: 2000;
    transition: transform 0.2s, box-shadow 0.2s;
}
#sidebarToggleFloat:hover {
    transform: scale(1.06);
    box-shadow: 0 6px 24px rgba(0,0,0,0.45);
}

/* Estado oculto: colapso por width=0 (nunca genera scroll horizontal) */
#sidebar.sidebar-oculto {
    width: 0 !important;
    overflow: hidden !important;
    opacity: 0;
    pointer-events: none;
    min-width: 0;
}

/* En móvil (<= 768px) ocultar ambos botones: el sidebar ya es horizontal/colapsado */
@media (max-width: 768px) {
    #sidebarToggle,
    #sidebarToggleFloat { display: none !important; }
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php">
            <img src="../assets/img/logo_intipath.png" alt="IntiPath Tours Logo" class="logo-admin" style="filter: brightness(0) invert(1); max-width: 160px;">
        </a>
        <button type="button" id="sidebarToggle" title="Ocultar menú" aria-label="Ocultar menú lateral">
            <i class="fas fa-bars"></i>
        </button>
        <div style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <p style="font-size: 0.8rem; color: #E8AC18; margin: 0;">Bienvenido,</p>
            <p style="font-weight: bold; color: #fff; margin: 0;"><?php echo htmlspecialchars($usuario_nombre); ?></p>
        </div>
    </div>

    <div class="sidebar-buscador" style="padding: 10px 14px 4px;">
        <input type="text" id="sidebarBuscar" placeholder="Buscar en el menú…" style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.08); color:#fff; font-size:13px; outline:none;">
    </div>

    <ul class="sidebar-menu" id="sidebarMenu">
        <li>
            <a href="index.php" class="<?php echo ($pagina_actual == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>

        <li>
            <button type="button" class="acordeon-trigger" onclick="toggleAcordeon('grupo-contenido', this, event)">
                <span>CONTENIDO</span>
                <i class="fas fa-chevron-down acordeon-flecha <?php echo (in_array($pagina_actual, ['tours.php', 'admin_blog.php', 'admin_unete.php', 'edit_sliders.php', 'tipos_tours.php', 'info_viaje.php', 'calendario_admin.php', 'admin_fundacion.php']) || $en_resenas) ? 'rotada' : ''; ?>"></i>
            </button>
            <ul id="grupo-contenido" class="acordeon-contenido <?php echo (in_array($pagina_actual, ['tours.php', 'admin_blog.php', 'admin_unete.php', 'edit_sliders.php', 'tipos_tours.php', 'info_viaje.php', 'calendario_admin.php', 'admin_fundacion.php']) || $en_resenas) ? 'abierto' : ''; ?>">
                <?php if (in_array('tours', $mis_accesos)): ?>
                    <li><a href="tours.php" class="<?php echo ($pagina_actual == 'tours.php') ? 'active' : ''; ?>"><i class="fas fa-map-marked-alt"></i> Gestionar Tours</a></li>
                <?php endif; ?>

                <?php if (in_array('tipos_tours', $mis_accesos)): ?>
                    <li><a href="tipos_tours.php" class="<?php echo ($pagina_actual == 'tipos_tours.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Tipos de Tours</a></li>
                <?php endif; ?>

                <?php if (in_array('calendario', $mis_accesos)): ?>
                    <li><a href="calendario_admin.php" class="<?php echo ($pagina_actual == 'calendario_admin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Calendario de Salidas</a></li>
                <?php endif; ?>

                <?php if (in_array('blog', $mis_accesos)): ?>
                    <li><a href="admin_blog.php" class="<?php echo ($pagina_actual == 'admin_blog.php') ? 'active' : ''; ?>"><i class="fas fa-feather-alt"></i> Gestionar Blog</a></li>
                <?php endif; ?>

                <?php if (in_array('paginas', $mis_accesos)): ?>
                    <li><a href="admin_paginas.php" class="<?php echo ($pagina_actual == 'admin_paginas.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Páginas del sitio</a></li>
                <?php endif; ?>

                <?php if (in_array('fundacion', $mis_accesos)): ?>
                    <li><a href="admin_fundacion.php" class="<?php echo ($pagina_actual == 'admin_fundacion.php') ? 'active' : ''; ?>"><i class="fas fa-hands-helping"></i> Fundación</a></li>
                <?php endif; ?>

                <?php if (in_array('seo', $mis_accesos)): ?>
                    <li><a href="admin_seo.php" class="<?php echo ($pagina_actual == 'admin_seo.php') ? 'active' : ''; ?>"><i class="fas fa-search"></i> SEO / Metadatos</a></li>
                <?php endif; ?>

                <?php if (in_array('sliders', $mis_accesos)): ?>
                    <li><a href="edit_sliders.php" class="<?php echo ($pagina_actual == 'edit_sliders.php') ? 'active' : ''; ?>"><i class="fas fa-images"></i> Gestionar Sliders</a></li>
                <?php endif; ?>

                <?php if (in_array('info_viaje', $mis_accesos)): ?>
                    <li><a href="info_viaje.php" class="<?php echo ($pagina_actual == 'info_viaje.php') ? 'active' : ''; ?>"><i class="fas fa-info-circle"></i> Info de Viaje (Cards)</a></li>
                <?php endif; ?>

                <?php if (in_array('contenido_index', $mis_accesos)): ?>
                    <li><a href="admin_index.php?tab=reviews" class="<?php echo $en_resenas ? 'active' : ''; ?>"><i class="fas fa-star"></i> Reseñas</a></li>
                <?php endif; ?>

                <?php if (in_array('unete', $mis_accesos)): ?>
                    <li><a href="admin_unete.php" class="<?php echo ($pagina_actual == 'admin_unete.php') ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> Gestionar Vacantes</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <li>
            <button type="button" class="acordeon-trigger" onclick="toggleAcordeon('grupo-info-viaje', this, event)">
                <span>INFO VIAJE</span>
                <i class="fas fa-chevron-down acordeon-flecha <?php echo (in_array($pagina_actual, ['admin_info-previa.php', 'admin_niveles-dificultad.php', 'admin_clima.php', 'admin_equipaje.php', 'admin_seguridad-viaje.php', 'admin_como-reservar.php', 'admin_alquiler.php', 'admin_disponibilidad.php', 'admin_reservas-info.php'])) ? 'rotada' : ''; ?>"></i>
            </button>
            <ul id="grupo-info-viaje" class="acordeon-contenido <?php echo (in_array($pagina_actual, ['admin_info-previa.php', 'admin_niveles-dificultad.php', 'admin_clima.php', 'admin_equipaje.php', 'admin_seguridad-viaje.php', 'admin_como-reservar.php', 'admin_alquiler.php', 'admin_disponibilidad.php', 'admin_reservas-info.php'])) ? 'abierto' : ''; ?>">
                <?php if (in_array('info_previa', $mis_accesos)): ?>
                    <li><a href="admin_info-previa.php" class="<?php echo ($pagina_actual == 'admin_info-previa.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-check"></i> Info Previa</a></li>
                <?php endif; ?>
                <?php if (in_array('como_reservar', $mis_accesos)): ?>
                    <li><a href="admin_como-reservar.php" class="<?php echo ($pagina_actual == 'admin_como-reservar.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Cómo Reservar</a></li>
                <?php endif; ?>
                <?php if (in_array('reservas_info', $mis_accesos)): ?>
                    <li><a href="admin_reservas-info.php" class="<?php echo ($pagina_actual == 'admin_reservas-info.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Reservas Info</a></li>
                <?php endif; ?>
                <?php if (in_array('niveles_dificultad', $mis_accesos)): ?>
                    <li><a href="admin_niveles-dificultad.php" class="<?php echo ($pagina_actual == 'admin_niveles-dificultad.php') ? 'active' : ''; ?>"><i class="fas fa-mountain"></i> Niveles Dificultad</a></li>
                <?php endif; ?>
                <?php if (in_array('clima', $mis_accesos)): ?>
                    <li><a href="admin_clima.php" class="<?php echo ($pagina_actual == 'admin_clima.php') ? 'active' : ''; ?>"><i class="fas fa-cloud-sun"></i> Clima</a></li>
                <?php endif; ?>
                <?php if (in_array('equipaje', $mis_accesos)): ?>
                    <li><a href="admin_equipaje.php" class="<?php echo ($pagina_actual == 'admin_equipaje.php') ? 'active' : ''; ?>"><i class="fas fa-suitcase"></i> Equipaje</a></li>
                <?php endif; ?>
                <?php if (in_array('seguridad_viaje', $mis_accesos)): ?>
                    <li><a href="admin_seguridad-viaje.php" class="<?php echo ($pagina_actual == 'admin_seguridad-viaje.php') ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Seguridad Viaje</a></li>
                <?php endif; ?>
                <?php if (in_array('alquiler', $mis_accesos)): ?>
                    <li><a href="admin_alquiler.php" class="<?php echo ($pagina_actual == 'admin_alquiler.php') ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> Alquiler</a></li>
                <?php endif; ?>
                <?php if (in_array('disponibilidad', $mis_accesos)): ?>
                    <li><a href="admin_disponibilidad.php" class="<?php echo ($pagina_actual == 'admin_disponibilidad.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Disponibilidad</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <li>
            <button type="button" class="acordeon-trigger" onclick="toggleAcordeon('grupo-personalizar', this, event)">
                <span>PERSONALIZACIÓN</span>
                <i class="fas fa-chevron-down acordeon-flecha <?php echo (in_array($pagina_actual, ['admin_footer.php', 'licencias.php', 'admin_terminos.php', 'admin_privacidad.php', 'configuracion.php', 'admin_footer_links.php', 'colores.php'])) ? 'rotada' : ''; ?>"></i>
            </button>
            <ul id="grupo-personalizar" class="acordeon-contenido <?php echo (in_array($pagina_actual, ['admin_footer.php', 'licencias.php', 'admin_terminos.php', 'admin_privacidad.php', 'configuracion.php', 'admin_footer_links.php', 'colores.php'])) ? 'abierto' : ''; ?>">
                <?php if (in_array('header_footer', $mis_accesos)): ?>
                    <li><a href="admin_footer.php" class="<?php echo ($pagina_actual == 'admin_footer.php') ? 'active' : ''; ?>"><i class="fas fa-paint-roller"></i> Identidad y Footer</a></li>
                <?php endif; ?>
                <?php if (in_array('footer_links', $mis_accesos)): ?>
                    <li><a href="admin_footer_links.php" class="<?php echo ($pagina_actual == 'admin_footer_links.php') ? 'active' : ''; ?>"><i class="fas fa-link"></i> Enlaces Footer</a></li>
                <?php endif; ?>
                <?php if (in_array('colores', $mis_accesos)): ?>
                    <li><a href="colores.php" class="<?php echo ($pagina_actual == 'colores.php') ? 'active' : ''; ?>"><i class="fas fa-palette"></i> Colores</a></li>
                <?php endif; ?>
                <?php if (in_array('barra_movil', $mis_accesos)): ?>
                    <li><a href="admin_barra_responsive.php" class="<?php echo ($pagina_actual == 'admin_barra_responsive.php') ? 'active' : ''; ?>"><i class="fas fa-mobile-alt"></i> Barra Móvil</a></li>
                <?php endif; ?>
                <?php if (in_array('config', $mis_accesos)): ?>
                    <li><a href="configuracion.php" class="<?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i> Configuración</a></li>
                <?php endif; ?>
                <?php if (in_array('licencias', $mis_accesos)): ?>
                    <li><a href="licencias.php" class="<?php echo ($pagina_actual == 'licencias.php') ? 'active' : ''; ?>"><i class="fas fa-certificate"></i> Licencias y Permisos</a></li>
                <?php endif; ?>
                <?php if (in_array('terminos', $mis_accesos)): ?>
                    <li><a href="admin_terminos.php" class="<?php echo ($pagina_actual == 'admin_terminos.php') ? 'active' : ''; ?>"><i class="fas fa-file-contract"></i> Términos y Cond.</a></li>
                <?php endif; ?>
                <?php if (in_array('privacidad', $mis_accesos)): ?>
                    <li><a href="admin_privacidad.php" class="<?php echo ($pagina_actual == 'admin_privacidad.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Pol. Privacidad</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <li>
            <button type="button" class="acordeon-trigger" onclick="toggleAcordeon('grupo-nosotros', this, event)">
                <span>NOSOTROS</span>
                <i class="fas fa-chevron-down acordeon-flecha <?php echo (in_array($pagina_actual, ['gestionar_nosotros.php', 'admin_menu_nosotros.php', 'preguntas_frecuentes.php', 'faq_editar.php', 'editar_nosotros.php', 'admin_equipo-guia.php', 'admin_grupos.php', 'admin_seguridad.php', 'admin_garantia.php', 'preguntas.php'])) ? 'rotada' : ''; ?>"></i>
            </button>
            <ul id="grupo-nosotros" class="acordeon-contenido <?php echo (in_array($pagina_actual, ['gestionar_nosotros.php', 'admin_menu_nosotros.php', 'preguntas_frecuentes.php', 'faq_editar.php', 'editar_nosotros.php', 'admin_equipo-guia.php', 'admin_grupos.php', 'admin_seguridad.php', 'admin_garantia.php', 'preguntas.php'])) ? 'abierto' : ''; ?>">
                <?php if (in_array('nosotros', $mis_accesos)): ?>
                    <li><a href="gestionar_nosotros.php" class="<?php echo ($pagina_actual == 'gestionar_nosotros.php' || $pagina_actual == 'editar_nosotros.php') ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> Gestionar Nosotros</a></li>
                <?php endif; ?>

                <?php if (in_array('nosotros', $mis_accesos)): ?>
                    <li><a href="admin_menu_nosotros.php" class="<?php echo ($pagina_actual == 'admin_menu_nosotros.php') ? 'active' : ''; ?>"><i class="fas fa-list-ul"></i> Contenido Nosotros</a></li>
                <?php endif; ?>

                <?php if (in_array('equipo', $mis_accesos)): ?>
                    <li><a href="admin_equipo-guia.php" class="<?php echo ($pagina_actual == 'admin_equipo-guia.php') ? 'active' : ''; ?>"><i class="fas fa-id-badge"></i> Nuestro Equipo</a></li>
                <?php endif; ?>

                <?php if (in_array('grupos', $mis_accesos)): ?>
                    <li><a href="admin_grupos.php" class="<?php echo ($pagina_actual == 'admin_grupos.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Nuestros Grupos</a></li>
                <?php endif; ?>
                <?php if (in_array('seguridad', $mis_accesos)): ?>
                    <li><a href="admin_seguridad.php" class="<?php echo ($pagina_actual == 'admin_seguridad.php') ? 'active' : ''; ?>"><i class="fas fa-shield-virus"></i> Seguridad</a></li>
                <?php endif; ?>
                <?php if (in_array('garantia', $mis_accesos)): ?>
                    <li><a href="admin_garantia.php" class="<?php echo ($pagina_actual == 'admin_garantia.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Garantía</a></li>
                <?php endif; ?>

                <?php if (in_array('faqs', $mis_accesos)): ?>
                    <li><a href="preguntas_frecuentes.php" class="<?php echo ($pagina_actual == 'preguntas_frecuentes.php' || $pagina_actual == 'faq_editar.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Preguntas Frecuentes</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <li>
            <button type="button" class="acordeon-trigger" onclick="toggleAcordeon('grupo-metodos-pago', this, event)">
                <span>MÉTODOS DE PAGO</span>
                <i class="fas fa-chevron-down acordeon-flecha <?php echo (in_array($pagina_actual, ['config_izipay.php', 'config_paypal.php', 'config_pagos.php', 'config_logo_pagos.php'])) ? 'rotada' : ''; ?>"></i>
            </button>
            <ul id="grupo-metodos-pago" class="acordeon-contenido <?php echo (in_array($pagina_actual, ['config_izipay.php', 'config_paypal.php', 'config_pagos.php', 'config_logo_pagos.php'])) ? 'abierto' : ''; ?>">
                <?php if (in_array('config_pagos', $mis_accesos)): ?>
                    <li><a href="config_logo_pagos.php" class="<?php echo ($pagina_actual == 'config_logo_pagos.php') ? 'active' : ''; ?>"><i class="fas fa-image"></i> Logo de Pagos</a></li>
                <?php endif; ?>
                <?php if (in_array('config_pagos', $mis_accesos)): ?>
                    <li><a href="config_izipay.php" class="<?php echo ($pagina_actual == 'config_izipay.php') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Config. IZIPAY</a></li>
                <?php endif; ?>
                <?php if (in_array('config_pagos', $mis_accesos)): ?>
                    <li><a href="config_paypal.php" class="<?php echo ($pagina_actual == 'config_paypal.php') ? 'active' : ''; ?>"><i class="fab fa-paypal"></i> Config. PayPal</a></li>
                <?php endif; ?>
                <?php if (in_array('config_pagos', $mis_accesos)): ?>
                    <li><a href="config_pagos.php" class="<?php echo ($pagina_actual == 'config_pagos.php') ? 'active' : ''; ?>"><i class="fas fa-mobile-alt"></i> Config. Yape/Plin</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <li>
            <button type="button" class="acordeon-trigger" onclick="toggleAcordeon('grupo-admin', this, event)">
                <span>ADMINISTRACIÓN</span>
                <i class="fas fa-chevron-down acordeon-flecha <?php echo (in_array($pagina_actual, ['mensajes.php', 'mensajes_ver.php', 'usuarios_crear.php', 'reclamos.php', 'reservas.php', 'reserva_ver.php', 'pagos.php', 'plantilla_pdf.php', 'config_bancos.php', 'recordatorios.php'])) ? 'rotada' : ''; ?>"></i>
            </button>
            <ul id="grupo-admin" class="acordeon-contenido <?php echo (in_array($pagina_actual, ['mensajes.php', 'mensajes_ver.php', 'usuarios_crear.php', 'reclamos.php', 'reservas.php', 'reserva_ver.php', 'pagos.php', 'plantilla_pdf.php', 'config_bancos.php', 'recordatorios.php'])) ? 'abierto' : ''; ?>">
                <?php if (in_array('mensajes', $mis_accesos)): ?>
                    <li><a href="mensajes.php" class="<?php echo (in_array($pagina_actual, ['mensajes.php', 'mensajes_ver.php'])) ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Mensajes Recibidos</a></li>
                <?php endif; ?>
                <?php if (in_array('reclamos', $mis_accesos)): ?>
                    <li><a href="reclamos.php" class="<?php echo ($pagina_actual == 'reclamos.php') ? 'active' : ''; ?>"><i class="fas fa-book"></i> Libro Reclamaciones</a></li>
                <?php endif; ?>
                <?php if (in_array('reservas', $mis_accesos)): ?>
                    <li><a href="reservas.php" class="<?php echo ($pagina_actual == 'reservas.php' || $pagina_actual == 'reserva_ver.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Reservas</a></li>
                <?php endif; ?>
                <?php if (in_array('pagos', $mis_accesos)): ?>
                    <li><a href="pagos.php" class="<?php echo ($pagina_actual == 'pagos.php') ? 'active' : ''; ?>"><i class="fas fa-dollar-sign"></i> Pagos</a></li>
                <?php endif; ?>
                <?php if (in_array('reservas', $mis_accesos)): ?>
                    <li><a href="recordatorios.php" class="<?php echo ($pagina_actual == 'recordatorios.php') ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Recordatorios</a></li>
                <?php endif; ?>
                <?php if (in_array('plantilla_pdf', $mis_accesos)): ?>
                    <li><a href="plantilla_pdf.php" class="<?php echo ($pagina_actual == 'plantilla_pdf.php') ? 'active' : ''; ?>"><i class="fas fa-file-code"></i> Plantilla PDF</a></li>
                <?php endif; ?>
                <?php if (in_array('config_bancos', $mis_accesos)): ?>
                    <li><a href="config_bancos.php" class="<?php echo ($pagina_actual == 'config_bancos.php') ? 'active' : ''; ?>"><i class="fas fa-university"></i> Bancos</a></li>
                <?php endif; ?>
                <?php if (in_array('usuarios', $mis_accesos)): ?>
                    <li><a href="usuarios_crear.php" class="<?php echo ($pagina_actual == 'usuarios_crear.php') ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Crear Usuarios</a></li>
                <?php endif; ?>
                <?php if (function_exists('esAdminSuper') && esAdminSuper()): ?>
                    <li><a href="eliminar_sql.php" class="<?php echo ($pagina_actual == 'eliminar_sql.php') ? 'active' : ''; ?>"><i class="fas fa-trash-can"></i> Eliminar SQL</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <li class="logout-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="logout.php" style="color: #ff7675;"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </li>
    </ul>
</aside>

<!-- Botón flotante para mostrar el sidebar cuando está oculto -->
<button type="button" id="sidebarToggleFloat" title="Mostrar menú" aria-label="Mostrar menú lateral" style="display: none;">
    <i class="fas fa-bars"></i>
</button>
<script>
    // Buscador global del menú lateral
    (function () {
        var input = document.getElementById('sidebarBuscar');
        if (!input) return;
        var menu = document.getElementById('sidebarMenu');
        if (!menu) return;
        input.addEventListener('input', function () {
            var q = input.value.toLowerCase().trim();
            var grupos = menu.querySelectorAll('li');
            grupos.forEach(function (li) {
                var esGrupo = !!li.querySelector('button.acordeon-trigger');
                if (esGrupo) {
                    var items = li.querySelectorAll('li a');
                    var alguno = false;
                    items.forEach(function (a) {
                        var coincide = a.textContent.toLowerCase().indexOf(q) !== -1;
                        a.closest('li').style.display = coincide ? '' : 'none';
                        if (coincide) alguno = true;
                    });
                    li.style.display = (q === '' || alguno) ? '' : 'none';
                } else {
                    li.style.display = (q === '' || li.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
                }
            });
        });
    })();
</script>
<script>
    // Toggle sidebar (ocultar/mostrar) con persistencia en localStorage
    (function () {
        var sidebar = document.getElementById('sidebar');
        var toggleBtn = document.getElementById('sidebarToggle');
        var floatBtn = document.getElementById('sidebarToggleFloat');
        if (!sidebar || !toggleBtn || !floatBtn) return;

        var STORAGE_KEY = 'sidebarOculto';
        var CLASS_OCULTO = 'sidebar-oculto';

        // Aplicar estado guardado al cargar
        if (localStorage.getItem(STORAGE_KEY) === 'true') {
            sidebar.classList.add(CLASS_OCULTO);
            toggleBtn.style.display = 'none';
            floatBtn.style.display = 'flex';
        }

        function setOculto(oculto) {
            if (oculto) {
                sidebar.classList.add(CLASS_OCULTO);
                toggleBtn.style.display = 'none';
                floatBtn.style.display = 'flex';
                localStorage.setItem(STORAGE_KEY, 'true');
            } else {
                sidebar.classList.remove(CLASS_OCULTO);
                toggleBtn.style.display = 'flex';
                floatBtn.style.display = 'none';
                localStorage.setItem(STORAGE_KEY, 'false');
            }
        }

        toggleBtn.addEventListener('click', function () {
            setOculto(true);
        });

        floatBtn.addEventListener('click', function () {
            setOculto(false);
        });
    })();
</script>
<script src="../assets/js/main.js"></script>