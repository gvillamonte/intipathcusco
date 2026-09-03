<?php
function renderPdfTemplate($reserva, $pasajeros, $db, $config, $logo_base64, $html_override = null) {

    // --- Logo ---
    $logo_tag = '';
    if ($logo_base64) {
        $logo_tag = '<img src="' . $logo_base64 . '" style="max-width:160px;">';
    }

    // --- Marca de agua condicional según estado de pago ---
    $marca_agua = '';
    $estadoPago = strtolower($reserva['estado'] ?? '');
    if ($estadoPago === 'pendiente') {
        $marca_agua = '<div style="position:fixed;top:45%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:68px;font-weight:900;color:rgba(12,154,158,0.08);letter-spacing:8px;white-space:nowrap;z-index:0;pointer-events:none;font-family:Helvetica,Arial,sans-serif;">PENDIENTE DE PAGO</div>';
    } elseif ($estadoPago === 'parcial') {
        $marca_agua = '<div style="position:fixed;top:45%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:68px;font-weight:900;color:rgba(212,168,67,0.10);letter-spacing:8px;white-space:nowrap;z-index:0;pointer-events:none;font-family:Helvetica,Arial,sans-serif;">PAGO PARCIAL</div>';
    } elseif ($estadoPago === 'cancelado') {
        $marca_agua = '<div style="position:fixed;top:45%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:76px;font-weight:900;color:rgba(192,57,43,0.12);letter-spacing:10px;white-space:nowrap;z-index:0;pointer-events:none;font-family:Helvetica,Arial,sans-serif;">CANCELADO</div>';
    }
    // pagado → sin marca de agua

    // --- Fechas formateadas ---
    $fecha_transaccion = date('d/m/Y', strtotime($reserva['created_at'] ?? 'now'));
    $fecha_viaje = date('d/m/Y', strtotime($reserva['fecha_viaje']));

    // --- Montos formateados ---
    $monto_total = '$' . number_format($reserva['monto_total'] ?? 0, 2);
    $adelanto = '$' . number_format($reserva['adelanto'] ?? 0, 2);
    $saldo = '$' . number_format($reserva['saldo'] ?? 0, 2);
    $precio_adulto = '$' . number_format($reserva['precio_adulto'] ?? 0, 2);

    // --- Pasajeros HTML (filas de tabla) ---
    $pasajeros_html = '';
    $it = 1;
    foreach ($pasajeros as $p) {
        $tipo_badge = strtoupper($p['tipo'] ?? '') === 'ADULTO' ? 'ADULTO' : 'NIÑO';
        $pasajeros_html .= '<tr>'
            . '<td style="text-align:center; font-weight:bold;">' . str_pad($it++, 2, '0', STR_PAD_LEFT) . '</td>'
            . '<td><span style="font-size:8px; background:#f1f5f9; padding:2px 8px; border-radius:4px; font-weight:bold; color:#475569;">' . $tipo_badge . '</span></td>'
            . '<td style="font-weight:bold;">' . htmlspecialchars($p['nombres'] ?? '') . '</td>'
            . '<td style="font-weight:bold;">' . htmlspecialchars($p['apellidos'] ?? '') . '</td>'
            . '<td>' . htmlspecialchars($p['documento'] ?? '') . '</td>'
            . '<td>' . htmlspecialchars($p['pais'] ?? '') . '</td>'
            . '</tr>';
    }

    // --- Estado y método de pago en español ---
    $estado_map = ['pendiente' => 'Pendiente', 'parcial' => 'Parcial', 'pagado' => 'Pagado', 'cancelado' => 'Cancelado'];
    $estado_display = $estado_map[$reserva['estado']] ?? ucfirst($reserva['estado'] ?? '');

    $metodo_map = ['por_definir' => 'Por definir', 'culqi_tarjeta' => 'Tarjeta', 'izipay_tarjeta' => 'Tarjeta', 'yape' => 'Yape', 'yape_manual' => 'Yape Manual', 'yape_izipay' => 'Yape', 'paypal' => 'PayPal', 'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia'];
    $metodo_display = $metodo_map[$reserva['metodo_pago']] ?? ($reserva['metodo_pago'] ?? 'Por definir');

    // --- Datos del tour ---
    $tour_nombre = $reserva['titulo'] ?? '';

    // --- Datos de contacto y empresa (desde footer_config, con fallback a config) ---
    $telefono_contacto = '';
    $email_contacto = '';
    $direccion_contacto = '';
    $razon_social = '';
    $ruc = '';
    try {
        $stmt_fc = $db->query("SELECT clave, valor FROM footer_config");
        $filas_fc = $stmt_fc->fetchAll(PDO::FETCH_ASSOC);
        $stmt_fc->closeCursor();
        foreach ($filas_fc as $fila_fc) {
            if ($fila_fc['clave'] === 'telefono') $telefono_contacto = $fila_fc['valor'];
            if ($fila_fc['clave'] === 'email') $email_contacto = $fila_fc['valor'];
            if ($fila_fc['clave'] === 'direccion') $direccion_contacto = $fila_fc['valor'];
            if ($fila_fc['clave'] === 'razon_social') $razon_social = $fila_fc['valor'];
            if ($fila_fc['clave'] === 'ruc') $ruc = $fila_fc['valor'];
        }
    } catch (Exception $e) {}
    if ($telefono_contacto === '') $telefono_contacto = $config['telefono'] ?? '';
    if ($email_contacto === '') $email_contacto = $config['email'] ?? '';
    if ($direccion_contacto === '') $direccion_contacto = 'Av. El Sol 948, Cusco, Peru';
    if ($razon_social === '') $razon_social = 'INTI PATH TOURS TREKKIN PERU S.A.C.';
    if ($ruc === '') $ruc = '20615665984';
    $web = 'www.intipathtours.com';

    // --- Términos y condiciones (desde BD) ---
    $terminos_condiciones = '';
    try {
        $stmt_tc = $db->query("SELECT contenido FROM terminos_condiciones WHERE id = 1 LIMIT 1");
        $fila_tc = $stmt_tc->fetch(PDO::FETCH_ASSOC);
        $stmt_tc->closeCursor();
        if (!empty($fila_tc['contenido'])) {
            $terminos_raw = $fila_tc['contenido'];
            $lineas = explode("\n", $terminos_raw);
            $terminos_html = '';
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if ($linea === '') continue;
                if (strpos($linea, '# ') === 0) {
                    $terminos_html .= '<div style="font-size:11px;font-weight:bold;color:#1E3A5F;margin-top:8px;">' . htmlspecialchars(substr($linea, 2)) . '</div>';
                } elseif (strpos($linea, '## ') === 0) {
                    $terminos_html .= '<div style="font-size:10px;font-weight:bold;color:#0C9A9E;margin-top:6px;">' . htmlspecialchars(substr($linea, 3)) . '</div>';
                } elseif (strpos($linea, '- ') === 0) {
                    $terminos_html .= '<div style="font-size:9px;color:#333;padding:2px 0 2px 12px;">&#10003; ' . htmlspecialchars(substr($linea, 2)) . '</div>';
                } else {
                    $terminos_html .= '<div style="font-size:9px;color:#555;line-height:1.5;padding:2px 0;">' . htmlspecialchars($linea) . '</div>';
                }
            }
            $terminos_condiciones = $terminos_html;
        }
    } catch (Exception $e) {}

    // --- Bancos activos (tabla bancos, multi-banco) ---
    $bancos_html = '';
    $primer_banco = null;
    $logo_dir = __DIR__ . '/../assets/img/bancos';

    try {
        $stmt_bancos = $db->query("SELECT * FROM bancos WHERE activo = 1 ORDER BY orden ASC, id ASC");
        $bancos_db = $stmt_bancos->fetchAll(PDO::FETCH_ASSOC);
        $stmt_bancos->closeCursor();
    } catch (Exception $e) {
        $bancos_db = [];
    }

    // Si la tabla bancos no existe, fallback a config_bancos (tabla vieja)
    if (empty($bancos_db)) {
        try {
            $stmt_old = $db->query("CALL obtener_datos_bancarios()");
            $old = $stmt_old->fetch(PDO::FETCH_ASSOC);
            $stmt_old->closeCursor();
            if ($old) {
                if (!empty($old['cuenta_soles']) && trim($old['cuenta_soles']) !== '') {
                    $bancos_db[] = ['nombre_banco'=>$old['nombre_banco'], 'titular'=>$old['titular'], 'numero_cuenta'=>$old['cuenta_soles'], 'cci'=>$old['cci'], 'moneda'=>'soles', 'logo'=>''];
                }
                if (!empty($old['cuenta_dolares']) && trim($old['cuenta_dolares']) !== '') {
                    $bancos_db[] = ['nombre_banco'=>$old['nombre_banco'], 'titular'=>$old['titular'], 'numero_cuenta'=>$old['cuenta_dolares'], 'cci'=>$old['cci'], 'moneda'=>'dolares', 'logo'=>''];
                }
                if (empty($bancos_db) && !empty($old['nombre_banco'])) {
                    $bancos_db[] = ['nombre_banco'=>$old['nombre_banco'], 'titular'=>$old['titular'], 'numero_cuenta'=>'', 'cci'=>$old['cci'], 'moneda'=>'soles', 'logo'=>''];
                }
            }
        } catch (Exception $e) {}
    }

    foreach ($bancos_db as $i => $b) {
        $moneda_badge = $b['moneda'] === 'dolares' ? 'US$' : 'S/';
        $logo_banco = '';
        if (!empty($b['logo'])) {
            $ruta_logo = $logo_dir . '/' . $b['logo'];
            if (is_file($ruta_logo)) {
                $ext = strtolower(pathinfo($ruta_logo, PATHINFO_EXTENSION));
                $logo_banco = '<img src="data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($ruta_logo)) . '" style="max-width:50px;max-height:30px;vertical-align:middle;margin-right:6px;">';
            }
        }
        $bancos_html .= '<tr>'
            . '<td style="font-weight:bold;">' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '</td>'
            . '<td>' . $logo_banco . '<strong>' . htmlspecialchars($b['nombre_banco'] ?? '') . '</strong></td>'
            . '<td>' . htmlspecialchars($b['titular'] ?? '') . '</td>'
            . '<td>' . htmlspecialchars($b['numero_cuenta'] ?? '') . '</td>'
            . '<td>' . htmlspecialchars($b['cci'] ?? '') . '</td>'
            . '<td><span style="font-size:8px; background:' . ($b['moneda'] === 'dolares' ? '#dbeafe' : '#d1fae5') . '; padding:2px 8px; border-radius:4px; font-weight:bold; color:' . ($b['moneda'] === 'dolares' ? '#1e40af' : '#065f46') . ';">' . $moneda_badge . '</span></td>'
            . '</tr>';

        if ($primer_banco === null) $primer_banco = $b;
    }

    // --- Filas de la tabla de pagos (una por pasajero) ---
    $filas_pago_html = '';
    $n_pago = 1;
    foreach ($pasajeros as $p) {
        $clase = ($n_pago % 2 === 0) ? 'row-teal' : '';
        $filas_pago_html .= '<tr class="' . $clase . '">'
            . '<td>' . str_pad($n_pago, 2, '0', STR_PAD_LEFT) . '</td>'
            . '<td>' . $precio_adulto . '</td>'
            . '<td>' . $adelanto . '</td>'
            . '<td>' . $saldo . '</td>'
            . '<td>' . $metodo_display . '</td>'
            . '<td>' . $estado_display . '</td>'
            . '</tr>';
        $n_pago++;
    }

    // --- Filas de la tabla bancaria (una por banco activo) ---
    $filas_bancos_html = '';
    $n_banco = 1;
    foreach ($bancos_db as $b) {
        $clase = ($n_banco % 2 === 0) ? 'row-navy' : '';
        $cuenta_soles = ($b['moneda'] === 'soles') ? ($b['numero_cuenta'] ?? '') : '';
        $cuenta_dolares = ($b['moneda'] === 'dolares') ? ($b['numero_cuenta'] ?? '') : '';
        $filas_bancos_html .= '<tr class="' . $clase . '">'
            . '<td>' . str_pad($n_banco, 2, '0', STR_PAD_LEFT) . '</td>'
            . '<td>' . htmlspecialchars($b['nombre_banco'] ?? '') . '</td>'
            . '<td>' . htmlspecialchars($b['titular'] ?? '') . '</td>'
            . '<td>' . htmlspecialchars($cuenta_soles) . '</td>'
            . '<td>' . htmlspecialchars($cuenta_dolares) . '</td>'
            . '<td>' . htmlspecialchars($b['cci'] ?? '') . '</td>'
            . '</tr>';
        $n_banco++;
    }

    // Variables de compatibilidad (primer banco activo)
    $primer_nombre = $primer_banco['nombre_banco'] ?? 'INTERBANK';
    $primer_titular = $primer_banco['titular'] ?? 'INTI PATH TOURS PERU S.A.C.';
    $primer_cuenta_soles = '';
    $primer_cuenta_dolares = '';
    $primer_cci = $primer_banco['cci'] ?? '';
    if ($primer_banco) {
        if ($primer_banco['moneda'] === 'soles') {
            $primer_cuenta_soles = $primer_banco['numero_cuenta'] ?? '';
        } else {
            $primer_cuenta_dolares = $primer_banco['numero_cuenta'] ?? '';
        }
    }

    // --- Variables para reemplazo ---
    $vars = [
        '{codigo}'             => $reserva['codigo'] ?? '',
        '{tour}'               => $tour_nombre,
        '{fecha_viaje}'        => $fecha_viaje,
        '{fecha_transaccion}'  => $fecha_transaccion,
        '{duracion}'           => $reserva['duracion'] ?? '',
        '{pasajeros}'          => $pasajeros_html,
        '{email}'              => !empty($reserva['email']) ? $reserva['email'] : $email_contacto,
        '{telefono}'           => !empty($reserva['telefono']) ? $reserva['telefono'] : $telefono_contacto,
        '{whatsapp}'           => $reserva['whatsapp'] ?? '',
        '{mensaje}'            => $reserva['mensaje'] ?? '',
        '{nombre}'             => trim(($reserva['nombre'] ?? '') . ' ' . ($reserva['apellido'] ?? '')),
        '{precio_adulto}'      => $precio_adulto,
        '{total}'              => $monto_total,
        '{adelanto}'           => $adelanto,
        '{saldo}'              => $saldo,
        '{metodo_pago}'        => $metodo_display,
        '{estado}'             => $estado_display,
        '{logo}'               => $logo_tag,
        '{marca_agua}'         => $marca_agua,
        '{bancos}'             => $bancos_html,
        '{filas_detalles_pago}'=> $filas_pago_html,
        '{filas_bancos}'       => $filas_bancos_html,
        '{banco}'              => $primer_nombre,
        '{titular}'            => $primer_titular,
        '{cuenta_soles}'       => $primer_cuenta_soles,
        '{cuenta_dolares}'     => $primer_cuenta_dolares,
        '{cci}'                => $primer_cci,
        '{telefono_contacto}'  => $telefono_contacto,
        '{email_contacto}'     => $email_contacto,
        '{web}'                => $web,
        '{razon_social}'       => $razon_social,
        '{ruc}'                => $ruc,
        '{direccion}'          => $direccion_contacto,
        '{terminos_condiciones}' => $terminos_condiciones,
    ];

    // --- Obtener plantilla desde BD (o usar la pasada por el editor) ---
    $html = '';
    if ($html_override !== null) {
        $html = $html_override;
    } else {
        try {
            $stmt = $db->query("SELECT contenido_html FROM plantilla_pdf WHERE id = 1 LIMIT 1");
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($plantilla['contenido_html'])) {
                $html = $plantilla['contenido_html'];
            }
        } catch (Exception $e) {}
    }

    // --- Fallback: plantilla por defecto embebida ---
    if (empty($html)) {
        require_once __DIR__ . '/plantilla_pdf_default.php';
        $html = plantillaPdfDefault();
    }

    // --- Reemplazar variables ---
    $html = str_replace(array_keys($vars), array_values($vars), $html);

    return $html;
}
