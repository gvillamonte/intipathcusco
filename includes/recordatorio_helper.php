<?php
// includes/recordatorio_helper.php
// Funciones para el sistema de recordatorios de pago

/**
 * Envía el recordatorio 1 (24h sin pago)
 */
function enviarRecordatorio1($db, $id_reserva) {
    $stmt = $db->prepare("SELECT r.*, t.titulo FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) return false;

    if ((int)$reserva['email_recordatorio_1_enviado'] === 1) return false;

    $nombre = htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']);
    $codigo = htmlspecialchars($reserva['codigo']);
    $tour = htmlspecialchars($reserva['titulo'] ?? '');
    $fecha = htmlspecialchars($reserva['fecha_viaje']);
    $token = $reserva['token'] ?? '';
    $link_pago = 'https://www.intipathtours.com/seleccionar_pago.php?t=' . urlencode($token);

    $cuerpo = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <div style="background:#0C9A9E;padding:30px;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:22px;">IntiPath Tours</h1>
                <p style="color:#fff;margin:5px 0 0;opacity:0.9;">RECORDATORIO DE PAGO</p>
            </div>
            <div style="background:#fff;padding:30px;">
                <p style="font-size:14px;color:#333;">Hola <strong>' . $nombre . '</strong>,</p>
                <p style="font-size:14px;color:#333;line-height:1.6;">Notamos que tu reserva <strong>#' . $codigo . '</strong> para el tour <strong>' . $tour . '</strong> el <strong>' . $fecha . '</strong> aún no ha sido completada.</p>
                <p style="font-size:14px;color:#333;line-height:1.6;">¿Deseas continuar con tu reserva? Completa el pago para confirmar tu lugar.</p>
                <div style="text-align:center;margin:25px 0;">
                    <a href="' . $link_pago . '" style="background:#0C9A9E;color:#fff;padding:14px 30px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;">Completar Pago</a>
                </div>
                <p style="font-size:12px;color:#888;text-align:center;">Si tienes dudas, escríbenos por WhatsApp o responde este correo.</p>
            </div>
            <div style="background:#f9f9f9;padding:15px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;">
                IntiPath Tours - Cusco, Peru<br>
                Este correo es generado automáticamente.
            </div>
        </div>';

    $ok = enviarCorreoIntipath($reserva['email'], $nombre, '¿Tu reserva sigue vigente? - IntiPath Tours', $cuerpo);

    if ($ok) {
        $stmt_up = $db->prepare("UPDATE reservas SET email_recordatorio_1_enviado = 1, fecha_recordatorio_1 = NOW() WHERE id = ?");
        $stmt_up->execute([$id_reserva]);

        $stmt_rec = $db->prepare("INSERT INTO recordatorios (id_reserva, tipo, asunto, programado_para, enviado_en, estado) VALUES (?, 'email', ?, NOW(), NOW(), 'enviado')");
        $stmt_rec->execute([$id_reserva, 'Recordatorio 1 - Pago pendiente']);
    }

    return $ok;
}

/**
 * Envía el recordatorio 2 (72h sin pago - último aviso)
 */
function enviarRecordatorio2($db, $id_reserva) {
    $stmt = $db->prepare("SELECT r.*, t.titulo FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) return false;

    if ((int)$reserva['email_recordatorio_2_enviado'] === 1) return false;

    $nombre = htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']);
    $codigo = htmlspecialchars($reserva['codigo']);
    $tour = htmlspecialchars($reserva['titulo'] ?? '');
    $fecha = htmlspecialchars($reserva['fecha_viaje']);
    $token = $reserva['token'] ?? '';
    $link_pago = 'https://www.intipathtours.com/seleccionar_pago.php?t=' . urlencode($token);

    $cuerpo = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <div style="background:#c0392b;padding:30px;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:22px;">IntiPath Tours</h1>
                <p style="color:#fff;margin:5px 0 0;opacity:0.9;">ÚLTIMO AVISO - PAGO PENDIENTE</p>
            </div>
            <div style="background:#fff;padding:30px;">
                <p style="font-size:14px;color:#333;">Hola <strong>' . $nombre . '</strong>,</p>
                <p style="font-size:14px;color:#333;line-height:1.6;">Tu reserva <strong>#' . $codigo . '</strong> para el tour <strong>' . $tour . '</strong> el <strong>' . $fecha . '</strong> será cancelada automáticamente en los próximos días si no se completa el pago.</p>
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px;margin:15px 0;">
                    <p style="font-size:13px;color:#856404;margin:0;"><strong>⚠️ IMPORTANTE:</strong> Si no realizas el pago pronto, tu reserva será cancelada y el lugar quedará disponible para otros viajeros.</p>
                </div>
                <div style="text-align:center;margin:25px 0;">
                    <a href="' . $link_pago . '" style="background:#c0392b;color:#fff;padding:14px 30px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;">Pagar Ahora</a>
                </div>
                <p style="font-size:12px;color:#888;text-align:center;">Si ya realizaste el pago, por favor ignora este mensaje.</p>
            </div>
            <div style="background:#f9f9f9;padding:15px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;">
                IntiPath Tours - Cusco, Peru<br>
                Este correo es generado automáticamente.
            </div>
        </div>';

    $ok = enviarCorreoIntipath($reserva['email'], $nombre, '⚠️ Último aviso: Tu reserva será cancelada - IntiPath Tours', $cuerpo);

    if ($ok) {
        $stmt_up = $db->prepare("UPDATE reservas SET email_recordatorio_2_enviado = 1, fecha_recordatorio_2 = NOW() WHERE id = ?");
        $stmt_up->execute([$id_reserva]);

        $stmt_rec = $db->prepare("INSERT INTO recordatorios (id_reserva, tipo, asunto, programado_para, enviado_en, estado) VALUES (?, 'email', ?, NOW(), NOW(), 'enviado')");
        $stmt_rec->execute([$id_reserva, 'Recordatorio 2 - Último aviso']);
    }

    return $ok;
}

/**
 * Cancela reservas vencidas (7 días sin pago)
 */
function cancelarReservasVencidas($db, $dias = 7) {
    $stmt = $db->prepare("
        UPDATE reservas
        SET estado = 'cancelado', updated_at = NOW()
        WHERE estado IN ('pendiente', 'parcial')
          AND created_at <= NOW() - INTERVAL ? DAY
          AND fecha_viaje > NOW()
    ");
    $stmt->execute([$dias]);
    return $stmt->rowCount();
}

/**
 * Obtiene los recordatorios de una reserva
 */
function obtenerRecordatoriosReserva($db, $id_reserva) {
    $stmt = $db->prepare("SELECT * FROM recordatorios WHERE id_reserva = ? ORDER BY created_at DESC");
    $stmt->execute([$id_reserva]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Programa un recordatorio manual
 */
function programarRecordatorio($db, $id_reserva, $tipo, $fecha, $asunto = '', $mensaje = '') {
    $stmt = $db->prepare("INSERT INTO recordatorios (id_reserva, tipo, asunto, mensaje, programado_para, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$id_reserva, $tipo, $asunto, $mensaje, $fecha]);
    return $db->lastInsertId();
}
