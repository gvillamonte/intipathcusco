<?php
// includes/cookie_consent.php — Política de cookies del sitio
// - cookiesPermitidas($tipo): helper PHP (esenciales | preferencias | analiticas)
// - renderCookieBanner(): banner inferior + modal de configuración + carga condicional de GA4

function cookiesPermitidas($tipo = 'esenciales') {
    $c = $_COOKIE['intipath_consent'] ?? '';
    if ($c === '') return false;
    $d = json_decode($c, true);
    if (!is_array($d)) return false;
    if ($tipo === 'esenciales') return true;
    return !empty($d[$tipo]);
}

function renderCookieBanner() {
    // GA4 configurable desde Admin -> Configuración
    $ga4_id = '';
    if (isset($GLOBALS['db']) && $GLOBALS['db']) {
        try {
            $st = $GLOBALS['db']->query("SELECT ga4_id FROM configuracion WHERE id = 1");
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $st->closeCursor();
            $ga4_id = trim($row['ga4_id'] ?? '');
        } catch (Exception $e) { /* sin GA4 */ }
    }
    $ga4_js = $ga4_id !== '' ? json_encode($ga4_id) : 'null';
    ?>
    <style>
        #ip-cb-banner, #ip-cb-modal { font-family: inherit; }
        #ip-cb-banner { position: fixed; bottom: 0; left: 0; right: 0; z-index: 99999; background: #0d1a33; color: #fff; padding: 14px 20px; display: none; box-shadow: 0 -4px 20px rgba(0,0,0,.25); }
        #ip-cb-banner.mostrado { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; justify-content: space-between; }
        #ip-cb-banner p { margin: 0; font-size: 13.5px; line-height: 1.5; color: #dbe3ef; flex: 1; min-width: 260px; }
        #ip-cb-banner a { color: #c6d544; text-decoration: underline; }
        .ip-cb-btn { border: none; border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .ip-cb-btn-ok { background: #c6d544; color: #0d1a33; }
        .ip-cb-btn-esencial { background: transparent; color: #fff; border: 1px solid #55607a; }
        .ip-cb-btn-config { background: #1e3355; color: #fff; }
        #ip-cb-modal { position: fixed; inset: 0; z-index: 100000; background: rgba(0,0,0,.55); display: none; align-items: center; justify-content: center; padding: 20px; }
        #ip-cb-modal.mostrado { display: flex; }
        .ip-cb-caja { background: #fff; color: #1e293b; max-width: 520px; width: 100%; border-radius: 14px; padding: 24px; max-height: 85vh; overflow: auto; }
        .ip-cb-caja h3 { margin: 0 0 6px; color: #15305D; font-size: 18px; }
        .ip-cb-caja p { color: #64748b; font-size: 13px; line-height: 1.6; margin: 0 0 14px; }
        .ip-cb-tipo { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; display: flex; gap: 12px; align-items: flex-start; }
        .ip-cb-tipo input { margin-top: 3px; accent-color: #15305D; width: 16px; height: 16px; }
        .ip-cb-tipo strong { display: block; font-size: 14px; color: #15305D; }
        .ip-cb-tipo small { color: #94a3b8; font-size: 12px; line-height: 1.5; display: block; margin-top: 2px; }
        .ip-cb-botones { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; flex-wrap: wrap; }
    </style>

    <div id="ip-cb-banner" role="dialog" aria-label="Aviso de cookies">
        <p>Usamos cookies propias para que el sitio funcione y recordar tus preferencias (idioma y moneda). Con tu permiso también usamos <a href="politica-privacidad.php#cookies" target="_blank">analíticas</a> para mejorar el sitio. Puedes configurarlo:</p>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="ip-cb-btn ip-cb-btn-config" onclick="ipCbAbrirConfig()">Configurar</button>
            <button class="ip-cb-btn ip-cb-btn-esencial" onclick="ipCbGuardar({esenciales:1,preferencias:0,analiticas:0},true)">Solo esenciales</button>
            <button class="ip-cb-btn ip-cb-btn-ok" onclick="ipCbGuardar({esenciales:1,preferencias:1,analiticas:1},true)">Aceptar todo</button>
        </div>
    </div>

    <div id="ip-cb-modal">
        <div class="ip-cb-caja">
            <h3>Configuración de cookies</h3>
            <p>Las esenciales son necesarias para navegar. Las de preferencias recuerdan tu idioma y moneda. Las analíticas nos ayudan a saber qué páginas visitas (datos anónimos).</p>
            <div class="ip-cb-tipo"><input type="checkbox" checked disabled><div><strong>Esenciales (siempre activas)</strong><small>Inicio de sesión y sesión de navegación (PHPSESSID). No se pueden desactivar.</small></div></div>
            <div class="ip-cb-tipo"><input type="checkbox" id="ip-cb-pref"><div><strong>Preferencias</strong><small>Recuerda tu idioma (intipath_lang) y moneda de pago (intipath_moneda) durante 12 meses.</small></div></div>
            <div class="ip-cb-tipo"><input type="checkbox" id="ip-cb-anal"><div><strong>Analíticas (Google Analytics)</strong><small>Estadísticas anónimas de visitas para mejorar la web.</small></div></div>
            <div class="ip-cb-botones">
                <button class="ip-cb-btn ip-cb-btn-esencial" style="color:#334155;border-color:#cbd5e1;" onclick="ipCbCerrar()">Cancelar</button>
                <button class="ip-cb-btn ip-cb-btn-ok" onclick="ipCbGuardar({esenciales:1,preferencias:document.getElementById('ip-cb-pref').checked?1:0,analiticas:document.getElementById('ip-cb-anal').checked?1:0},true)">Guardar</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var GA4_ID = <?= $ga4_js ?>;
        var NOMBRE = 'intipath_consent';

        function leer() {
            var m = document.cookie.match(new RegExp('(^| )' + NOMBRE + '=([^;]+)'));
            if (!m) return null;
            try { return JSON.parse(decodeURIComponent(m[2])); } catch (e) { return null; }
        }
        function guardarCookies(d) {
            document.cookie = NOMBRE + '=' + encodeURIComponent(JSON.stringify(d)) + '; max-age=' + (365 * 24 * 3600) + '; path=/; SameSite=Lax';
        }
        function cargarGA4() {
            if (!GA4_ID || window.__ipGa4Cargado) return;
            window.__ipGa4Cargado = 1;
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA4_ID;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { dataLayer.push(arguments); };
            gtag('js', new Date());
            gtag('config', GA4_ID, { anonymize_ip: true });
        }
        var consentimiento = leer();
        if (consentimiento) {
            document.getElementById('ip-cb-banner').classList.remove('mostrado');
            if (consentimiento.analiticas) cargarGA4();
        } else {
            document.getElementById('ip-cb-banner').classList.add('mostrado');
        }
        window.ipCbGuardar = function (d, recargar) {
            guardarCookies(d);
            document.getElementById('ip-cb-banner').classList.remove('mostrado');
            document.getElementById('ip-cb-modal').classList.remove('mostrado');
            if (d.analiticas) cargarGA4();
        };
        window.ipCbAbrirConfig = function () {
            document.getElementById('ip-cb-modal').classList.add('mostrado');
        };
        window.ipCbCerrar = function () {
            document.getElementById('ip-cb-modal').classList.remove('mostrado');
        };
        document.getElementById('ip-cb-modal').addEventListener('click', function (e) {
            if (e.target === this) ipCbCerrar();
        });
    })();
    </script>
    <?php
}