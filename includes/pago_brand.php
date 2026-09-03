<?php
// includes/pago_brand.php
// Logo para las páginas de pago (wizard): prioridad al logo subido desde
// Admin > Métodos de Pago > Logo de Pagos (config_pagos.pago_logo) y, si no
// existe, al logo del sitio (configuracion.logo). Sin logo -> texto INTI PATH.

/**
 * Devuelve la URL relativa del logo a usar en las páginas de pago,
 * o null si no hay ninguno configurado/archivo presente.
 */
function pagoLogoArchivo($db) {
    if ($db instanceof PDO) {
        try {
            $stmt = $db->query("SELECT valor FROM config_pagos WHERE clave = 'pago_logo' LIMIT 1");
            $valor = trim((string)$stmt->fetchColumn());
            $stmt->closeCursor();
            if ($valor !== '' && is_file(__DIR__ . '/../assets/img/pagos/' . $valor)) {
                return 'assets/img/pagos/' . $valor;
            }
        } catch (Exception $e) { /* sin logo especifico */ }

        try {
            $stmt = $db->query("SELECT logo FROM configuracion WHERE id = 1 LIMIT 1");
            $logo = trim((string)$stmt->fetchColumn());
            $stmt->closeCursor();
            if ($logo !== '' && is_file(__DIR__ . '/../assets/img/' . $logo)) {
                return 'assets/img/' . $logo;
            }
        } catch (Exception $e) { /* sin logo del sitio */ }
    }
    return null;
}

/**
 * Marca de cabecera del wizard: <img> con el logo o el texto INTI PATH.
 */
function pagoLogoHtml($db, $alt = 'IntiPath Tours', $clase = '') {
    $archivo = pagoLogoArchivo($db);
    if ($archivo) {
        $cl = $clase !== '' ? ' class="' . htmlspecialchars($clase) . '"' : '';
        return '<img src="' . htmlspecialchars($archivo) . '" alt="' . htmlspecialchars($alt) . '"' . $cl . '>';
    }
    return '<i class="fas fa-sun"></i><span>INTI PATH<small>TOURS CUSCO</small></span>';
}

/**
 * Marca del loader de redirección: logo pequeño o el texto.
 */
function pagoLogoLoader($db) {
    $archivo = pagoLogoArchivo($db);
    if ($archivo) {
        return '<img src="' . htmlspecialchars($archivo) . '" alt="IntiPath Tours" class="pw-loading-brand-img">';
    }
    return 'INTI PATH TOURS';
}