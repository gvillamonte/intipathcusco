<?php
// includes/tipo_cambio_helper.php
// Tipo de cambio centralizado: API automática + cache en BD + fallback manual.

define('TIPO_CAMBIO_FALLBACK', 3.75);

/**
 * Obtiene el tipo de cambio USD→PEN (S/ por US$).
 * Flujo:
 *   1. Cache en BD si es de hoy (<24h)
 *   2. API externa (open.er-api.com → exchangerate.fun)
 *   3. Fallback: valor manual en BD o constante
 */
function obtenerTipoCambio($db) {
    // 1. Intentar cache de hoy
    try {
        $stmt = $db->query("SELECT tipo_cambio, tipo_cambio_fecha FROM configuracion WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row && !empty($row['tipo_cambio']) && (float)$row['tipo_cambio'] > 0) {
            $fecha_cache = $row['tipo_cambio_fecha'] ?? null;
            if ($fecha_cache && strtotime($fecha_cache) > strtotime('-24 hours')) {
                return (float)$row['tipo_cambio'];
            }
        }
    } catch (Exception $e) {
        // Tabla puede no tener la columna tipo_cambio_fecha aún
    }

    // 2. Intentar API externa
    $tipo = null;

    // Fuente primaria: open.er-api.com (gratis, sin key)
    $tipo = tipoCambioDesdeApi('https://open.er-api.com/v6/latest/USD', 'PEN');

    // Fuente secundaria: exchangerate.fun (gratis, sin key)
    if ($tipo === null) {
        $tipo = tipoCambioDesdeApi('https://api.exchangerate.fun/latest?base=USD', 'PEN');
    }

    if ($tipo !== null && $tipo > 0) {
        // Guardar en cache
        try {
            $stmt = $db->prepare("UPDATE configuracion SET tipo_cambio = ?, tipo_cambio_fecha = NOW() WHERE id = 1");
            $stmt->execute([$tipo]);
            $stmt->closeCursor();
        } catch (Exception $e) {
            // Si la columna tipo_cambio_fecha no existe, al menos guardar el valor
            try {
                $stmt = $db->prepare("UPDATE configuracion SET tipo_cambio = ? WHERE id = 1");
                $stmt->execute([$tipo]);
                $stmt->closeCursor();
            } catch (Exception $e2) {}
        }
        return $tipo;
    }

    // 3. Fallback: valor en BD o constante
    if (isset($row['tipo_cambio']) && (float)$row['tipo_cambio'] > 0) {
        return (float)$row['tipo_cambio'];
    }

    return TIPO_CAMBIO_FALLBACK;
}

/**
 * Consulta una API y extrae el tipo de cambio PEN.
 * Devuelve float o null si falla.
 */
function tipoCambioDesdeApi($url, $monedaObjetivo) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $respuesta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($respuesta)) return null;

    $data = json_decode($respuesta, true);
    if (!is_array($data)) return null;

    // open.er-api.com: { "rates": { "PEN": 3.35 } }
    if (isset($data['rates'][$monedaObjetivo])) {
        $tipo = (float)$data['rates'][$monedaObjetivo];
        return ($tipo > 0) ? $tipo : null;
    }

    // exchangerate.fun: { "rates": { "PEN": 3.35 } }
    if (isset($data['rates'][$monedaObjetivo])) {
        $tipo = (float)$data['rates'][$monedaObjetivo];
        return ($tipo > 0) ? $tipo : null;
    }

    return null;
}

/**
 * Convierte un monto de USD a PEN.
 */
function convertirUsdAPen($db, $monto_usd) {
    $tipo = obtenerTipoCambio($db);
    return round($monto_usd * $tipo, 2);
}

/**
 * Convierte un monto de PEN a USD.
 */
function convertirPenAUsd($db, $monto_pen) {
    $tipo = obtenerTipoCambio($db);
    if ($tipo <= 0) $tipo = TIPO_CAMBIO_FALLBACK;
    return round($monto_pen / $tipo, 2);
}

/**
 * Normaliza un monto a USD según la moneda del pago.
 * Si el pago fue en PEN, divide por tipo_cambio.
 * Si fue en USD, devuelve tal cual.
 */
function normalizarMontoAUsd($db, $monto, $moneda) {
    $moneda = strtoupper($moneda);
    if ($moneda === 'PEN') {
        return convertirPenAUsd($db, $monto);
    }
    return round($monto, 2);
}
