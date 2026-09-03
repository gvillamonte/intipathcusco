<?php
// includes/moneda_helper.php
// Helper para mostrar precios según idioma: ES = PEN (S/), EN = USD (US$)

require_once __DIR__ . '/tipo_cambio_helper.php';

/**
 * Devuelve el símbolo de moneda según el idioma.
 */
function simboloMoneda($idioma = 'es') {
    return ($idioma === 'en') ? 'US$' : 'S/';
}

/**
 * Devuelve el monto a mostrar según idioma.
 * ES → precio_soles (si existe, si no calcula)
 * EN → precio USD
 */
function montoMoneda($tour, $idioma = 'es') {
    if ($idioma === 'en') {
        return (float)($tour['precio'] ?? 0);
    }
    // Español: usar precio_soles si existe, si no calcular
    if (!empty($tour['precio_soles'])) {
        return (float)$tour['precio_soles'];
    }
    // Fallback: calcular desde USD
    return calcularPrecioSoles((float)($tour['precio'] ?? 0));
}

/**
 * Calcula precio en soles desde USD usando tipo de cambio.
 */
function calcularPrecioSoles($precio_usd, $db = null) {
    if ($db === null) {
        require_once __DIR__ . '/../config/database.php';
        $db = (new Database())->getConnection();
    }
    return round($precio_usd * obtenerTipoCambio($db), 2);
}

/**
 * Devuelve precio del niño en la moneda correcta.
 * ES → precio_nino_soles (o 70% de precio_soles)
 * EN → precio_nino USD (o 70% de precio USD)
 */
function montoNinoMoneda($tour, $idioma = 'es') {
    if ($idioma === 'en') {
        return (float)($tour['precio_nino'] ?? ($tour['precio'] ?? 0) * 0.7);
    }
    // Español
    if (!empty($tour['precio_soles'])) {
        return (float)($tour['precio_nino'] ?? $tour['precio_soles'] * 0.7);
    }
    return calcularPrecioSoles((float)($tour['precio_nino'] ?? ($tour['precio'] ?? 0) * 0.7));
}

/**
 * Formatea precio completo: símbolo + monto formateado.
 * Ejemplo: "S/ 380" o "US$ 100"
 */
function precioFormato($tour, $idioma = 'es', $decimales = 0) {
    $simbolo = simboloMoneda($idioma);
    $monto = montoMoneda($tour, $idioma);
    return $simbolo . ' ' . number_format($monto, $decimales);
}
