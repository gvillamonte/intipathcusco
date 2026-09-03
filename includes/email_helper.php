<?php
// includes/email_helper.php
// Envío centralizado de correos con PHPMailer (credenciales reales de IntiPath).
// Un solo lugar: enviar_consulta.php, reserva_ver.php y el flujo de pagos lo usan.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('IP_CORREO_FROM', 'intipathtourstrekkinperu@gmail.com');
define('IP_CORREO_NOMBRE', 'IntiPath Tours');
define('IP_CORREO_PASS', 'aifgiwxedwogpsgq');

/**
 * Envía un correo genérico. Devuelve true/false.
 * $adjunto: ruta absoluta opcional del archivo a adjuntar.
 */
function enviarCorreoIntipath($para_email, $para_nombre, $asunto, $cuerpo_html, $adjunto = null, $adjunto_nombre = null, $reply_to_email = null, $reply_to_nombre = null) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = IP_CORREO_FROM;
        $mail->Password   = IP_CORREO_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(IP_CORREO_FROM, IP_CORREO_NOMBRE);
        $mail->addAddress($para_email, $para_nombre);
        $mail->addBCC(IP_CORREO_FROM);

        if ($reply_to_email) {
            $mail->addReplyTo($reply_to_email, $reply_to_nombre ?: $para_nombre);
        } else {
            $mail->addReplyTo(IP_CORREO_FROM, IP_CORREO_NOMBRE);
        }

        if ($adjunto && file_exists($adjunto)) {
            $mail->addAttachment($adjunto, $adjunto_nombre ?: basename($adjunto));
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Envía el correo de "Pago Confirmado" con el voucher actualizado.
 * Controla reservas.email_pago_enviado para no repetirlo jamás.
 * Devuelve true si se envió, false si ya estaba enviado o falló.
 */
function enviarCorreoPagoConfirmado($db, $id_reserva) {
    $stmt = $db->prepare("SELECT r.*, t.titulo FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) return false;

    if ((int)$reserva['email_pago_enviado'] === 1) {
        return false; // ya se envió una vez
    }

    $stmt_p = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
    $stmt_p->execute([$id_reserva]);
    $total_pagado = (float)$stmt_p->fetchColumn();

    $cuerpo = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin:0 auto; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
            <div style="background: #27ae60; padding: 30px; text-align: center;">
                <h1 style="color: #fff; margin:0; font-size:22px;">IntiPath Tours</h1>
                <p style="color: #fff; margin:5px 0 0; opacity:0.9;">¡PAGO CONFIRMADO!</p>
            </div>
            <div style="background: #fff; padding: 30px;">
                <p style="font-size:14px; color:#333;">Hola <strong>' . htmlspecialchars($reserva['nombre']) . '</strong>, hemos confirmado tu pago. Aquí el resumen actualizado:</p>
                <div style="background:#f8fbff;border:1px solid #d1e3fa;border-radius:10px;padding:15px;margin:20px 0;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <tr><td style="padding:6px 0;color:#888;">Código de Reserva:</td><td style="padding:6px 0;text-align:right;font-weight:bold;">#' . htmlspecialchars($reserva['codigo']) . '</td></tr>
                        <tr><td style="padding:6px 0;color:#888;">Tour:</td><td style="padding:6px 0;text-align:right;">' . htmlspecialchars($reserva['titulo'] ?? '-') . '</td></tr>
                        <tr><td style="padding:6px 0;color:#888;">Fecha de Viaje:</td><td style="padding:6px 0;text-align:right;">' . htmlspecialchars($reserva['fecha_viaje']) . '</td></tr>
                        <tr><td style="padding:6px 0;color:#888;">Total del Viaje:</td><td style="padding:6px 0;text-align:right;">$' . number_format((float)$reserva['monto_total'], 2) . '</td></tr>
                        <tr><td style="padding:6px 0;color:#27ae60;font-weight:bold;">Monto Pagado:</td><td style="padding:6px 0;text-align:right;color:#27ae60;font-weight:bold;">$' . number_format($total_pagado, 2) . '</td></tr>
                        <tr><td style="padding:6px 0;color:#E8AC18;font-weight:bold;">Saldo en Cusco:</td><td style="padding:6px 0;text-align:right;color:#E8AC18;font-weight:bold;">$' . number_format(max(0, (float)$reserva['monto_total'] - $total_pagado), 2) . '</td></tr>
                    </table>
                </div>
                <p style="margin-top:20px;text-align:center;font-size:13px;color:#888;">Se adjunta tu comprobante de reserva actualizado.</p>
            </div>
            <div style="background:#f9f9f9;padding:15px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;">
                IntiPath Tours - Cusco, Peru<br>
                Este correo es generado automaticamente.
            </div>
        </div>';

    // Adjuntar el PDF del voucher actualizado
    $adjunto = null;
    $adjunto_nombre = null;
    if (file_exists(__DIR__ . '/pdf_helper.php')) {
        require_once __DIR__ . '/pdf_helper.php';
        if (function_exists('generarPdfReservaParaEmail')) {
            $stmt_p_adj = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
            $stmt_p_adj->execute([$id_reserva]);
            $pasajeros_adj = $stmt_p_adj->fetchAll(PDO::FETCH_ASSOC);
            $r = generarPdfReservaParaEmail($reserva, $pasajeros_adj, $db);
            if (is_array($r) && !empty($r['path']) && file_exists($r['path'])) {
                $adjunto = $r['path'];
                $adjunto_nombre = $r['filename'] ?? null;
            }
        }
    }

    $ok = enviarCorreoIntipath(
        $reserva['email'],
        $reserva['nombre'] . ' ' . $reserva['apellido'],
        'Pago Confirmado - Reserva #' . $reserva['codigo'] . ' | IntiPath Tours',
        $cuerpo,
        $adjunto,
        $adjunto_nombre
    );

    if ($ok) {
        $stmt_up = $db->prepare("UPDATE reservas SET email_pago_enviado = 1 WHERE id = ?");
        $stmt_up->execute([$id_reserva]);
    }

    // Limpiar PDF temporal
    if ($adjunto && file_exists($adjunto) && strpos($adjunto, 'pdfs/') !== false) {
        @unlink($adjunto);
    }

    return $ok;
}

/**
 * Envía correo de cancelación al cliente.
 */
function enviarCorreoCancelacion($db, $id_reserva, $motivo = '') {
    require_once __DIR__ . '/pdf_helper.php';

    $stmt_r = $db->prepare("SELECT r.*, t.titulo, t.titulo_en FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
    $stmt_r->execute([$id_reserva]);
    $reserva = $stmt_r->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) return false;

    $stmt_p = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
    $stmt_p->execute([$id_reserva]);
    $pasajeros = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    $pdf_res = generarPdfReservaParaEmail($reserva, $pasajeros, $db);

    // Calcular total pagado dinámicamente desde la tabla pagos (no depender de saldo)
    require_once __DIR__ . '/tipo_cambio_helper.php';
    $stmt_tp = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
    $stmt_tp->execute([$id_reserva]);
    $total_pagado_raw = (float)$stmt_tp->fetchColumn();

    // Normalizar a USD
    $total_pagado_usd = 0;
    $stmt_pd = $db->prepare("SELECT monto, moneda FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
    $stmt_pd->execute([$id_reserva]);
    $tipo_cambio = obtenerTipoCambio($db);
    while ($p = $stmt_pd->fetch(PDO::FETCH_ASSOC)) {
        if (strtoupper($p['moneda'] ?? 'USD') === 'PEN' && $tipo_cambio > 0) {
            $total_pagado_usd += round((float)$p['monto'] / $tipo_cambio, 2);
        } else {
            $total_pagado_usd += round((float)$p['monto'], 2);
        }
    }

    $total_pagado = $total_pagado_usd;
    $titulo_tour = $reserva['titulo_en'] && $reserva['titulo_en'] !== $reserva['titulo']
        ? $reserva['titulo'] . ' / ' . $reserva['titulo_en']
        : $reserva['titulo'];

    $motivo_html = !empty($motivo)
        ? "<div style='background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:15px;margin:20px 0;'>
            <strong style='color:#856404;'>Motivo de cancelación:</strong>
            <p style='color:#856404;margin:8px 0 0;line-height:1.5;'>" . nl2br(htmlspecialchars($motivo)) . "</p>
          </div>"
        : "<div style='background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:15px;margin:20px 0;'>
            <p style='color:#666;margin:0;font-style:italic;'>No se especificó motivo de cancelación.</p>
          </div>";

    $reembolso_html = $total_pagado > 0
        ? "<div style='background:#e3f2fd;border:1px solid #90caf9;border-radius:8px;padding:15px;margin:20px 0;'>
            <strong style='color:#1565c0;'><i class='fas fa-exclamation-triangle'></i> Monto ya pagado: $" . number_format($total_pagado, 2) . "</strong>
            <p style='color:#1565c0;margin:8px 0 0;font-size:13px;'>Este monto será devuelto según las políticas de la agencia. Te contactaremos pronto para coordinar la devolución.</p>
          </div>"
        : "";

    $cuerpo = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);'>
            <div style='background:#c62828;padding:30px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:22px;'>IntiPath Tours</h1>
                <p style='color:#fff;margin:5px 0 0;opacity:0.9;'>RESERVA CANCELADA</p>
            </div>
            <div style='background:#fff;padding:30px;'>
                <p style='font-size:16px;color:#333;'>Hola <strong>" . htmlspecialchars($reserva['nombre']) . "</strong>,</p>
                <p style='color:#666;line-height:1.6;'>Tu reserva <strong>#" . $reserva['codigo'] . "</strong> ha sido <strong style='color:#c62828;'>CANCELADA</strong>.</p>

                <div style='background:#ffebee;border:1px solid #ef9a9a;border-radius:12px;padding:20px;margin:25px 0;'>
                    <h3 style='margin:0 0 15px;font-size:14px;color:#c62828;text-transform:uppercase;'>Detalles de la Reserva</h3>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Tour:</strong></td><td style='padding:8px 0;text-align:right;'>" . htmlspecialchars($titulo_tour) . "</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Fecha del viaje:</strong></td><td style='padding:8px 0;text-align:right;'>" . date('d/m/Y', strtotime($reserva['fecha_viaje'])) . "</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Pasajeros:</strong></td><td style='padding:8px 0;text-align:right;'>" . ($reserva['total_adultos'] + $reserva['total_ninos']) . " persona(s)</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Total del tour:</strong></td><td style='padding:8px 0;text-align:right;font-weight:bold;'>\$" . number_format($reserva['monto_total'], 2) . "</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Monto pagado:</strong></td><td style='padding:8px 0;text-align:right;color:#2d8a56;font-weight:bold;'>\$" . number_format($total_pagado, 2) . "</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Saldo pendiente:</strong></td><td style='padding:8px 0;text-align:right;color:#c62828;font-weight:bold;'>\$" . number_format(max(0, (float)$reserva['monto_total'] - $total_pagado), 2) . " (no cobrado)</td></tr>
                    </table>
                </div>

                {$motivo_html}
                {$reembolso_html}

                <p style='color:#666;font-size:13px;margin-top:20px;'>Si tienes alguna consulta, no dudes en contactarnos.</p>

                <p style='text-align:center;margin-top:25px;'>
                    <a href='https://wa.me/51920307331' style='background:#25d366;color:#fff;padding:12px 25px;border-radius:10px;text-decoration:none;font-weight:bold;font-size:14px;'>Contactar por WhatsApp</a>
                </p>
            </div>
            <div style='background:#f9f9f9;padding:20px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;'>
                <strong>IntiPath Tours Peru S.A.C.</strong><br>
                Agencia de Viajes y Operador de Turismo<br>
                Cusco, Perú
            </div>
        </div>";

    $adjunto = null;
    $adjunto_nombre = null;
    if (is_array($pdf_res) && !empty($pdf_res['path']) && file_exists($pdf_res['path'])) {
        $adjunto = $pdf_res['path'];
        $adjunto_nombre = $pdf_res['filename'] ?? null;
    }

    $ok = enviarCorreoIntipath(
        $reserva['email'],
        $reserva['nombre'],
        'Reserva #' . $reserva['codigo'] . ' - CANCELADA - IntiPath Tours',
        $cuerpo,
        $adjunto,
        $adjunto_nombre
    );

    if ($adjunto && file_exists($adjunto) && strpos($adjunto, 'pdfs/') !== false) {
        @unlink($adjunto);
    }

    return $ok;
}