<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/izipay_helper.php';
require_once __DIR__ . '/includes/pago_brand.php';

$token = $_GET['t'] ?? '';
if (empty($token)) { header("Location: index.php"); exit; }

$db = (new Database())->getConnection();
$reserva = izipayObtenerReservaPorToken($db, $token);

if (!$reserva) {
    header("Location: index.php");
    exit;
}

$id_reserva = $reserva['id'];

// Monto total realmente pagado + moneda
$stmt_p = $db->prepare("SELECT COALESCE(SUM(monto), 0), COALESCE(MAX(moneda), 'USD') FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
$stmt_p->execute([$id_reserva]);
$row_pago = $stmt_p->fetch(PDO::FETCH_ASSOC);
$total_pagado = (float)$row_pago[0];
$moneda_pago = strtoupper($row_pago[1]);
$simbolo = ($moneda_pago === 'PEN') ? 'S/.' : '$';

require_once __DIR__ . '/includes/tipo_cambio_helper.php';

// Tipo de cambio automático (API SUNAT + cache)
$tipo_cambio = ($moneda_pago === 'PEN') ? obtenerTipoCambio($db) : 3.75;

// Si el pago es en PEN, convertir todos los montos USD a PEN
$monto_total_original = (float)$reserva['monto_total'];
$adelanto_original = (float)$reserva['adelanto'];
if ($moneda_pago === 'PEN') {
    $monto_total_conv = $monto_total_original * $tipo_cambio;
    $adelanto_conv = $adelanto_original * $tipo_cambio;
} else {
    $monto_total_conv = $monto_total_original;
    $adelanto_conv = $adelanto_original;
}

$pago_confirmado = ($reserva['estado'] === 'pagado' || $reserva['estado'] === 'parcial');
$saldo_db = (float)($reserva['saldo'] ?? 0);

// Si el pago fue en PEN, convertir saldo para mostrar
if ($moneda_pago === 'PEN') {
    $saldo_pendiente = convertirUsdAPen($db, $saldo_db);
    $monto_pagado = $monto_total_conv - $saldo_pendiente;
} else {
    $saldo_pendiente = $saldo_db;
    $monto_pagado = $monto_total_original - $saldo_db;
}

$monto_pagado = max(0, $monto_pagado);
$pendiente = isset($_GET['pendiente']);
$correo_enviado = ((int)($reserva['email_pago_enviado'] ?? 0) === 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= ($pendiente || !$pago_confirmado) ? 'Reserva Recibida' : '¡Pago Exitoso!' ?> | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pago_izipay.css">
</head>
<body style="display:flex;align-items:center;min-height:100vh;padding:36px 0;">
    <div style="width:100%;">
        <div class="pw-header">
            <a class="pw-brand" href="index.php"><?= pagoLogoHtml($db) ?></a>
        </div>

        <div class="pw-wizard">
            <div class="pw-steps">
                <div class="pw-step done"><span class="pw-num"><i class="fas fa-check pw-ico"></i></span><span>Datos</span></div>
                <div class="pw-track"><span class="pw-progress" style="width:100%"></span></div>
                <div class="pw-step done"><span class="pw-num"><i class="fas fa-check pw-ico"></i></span><span>Pago</span></div>
                <div class="pw-track"><span class="pw-progress" style="width:100%"></span></div>
                <div class="pw-step done"><span class="pw-num"><i class="fas fa-check pw-ico"></i></span><span>Confirmación</span></div>
            </div>
        </div>

        <div class="pw-container">
            <div class="pw-card pw-success-card">
                <div class="pw-success-head <?= ($pendiente || !$pago_confirmado) ? 'wait' : 'ok' ?>">
                    <div class="pw-success-ico">
                        <i class="fas <?= ($pendiente || !$pago_confirmado) ? 'fa-clock' : 'fa-check' ?>"></i>
                    </div>
                    <h2><?= ($pendiente || !$pago_confirmado) ? 'Reserva Recibida' : '¡Pago Exitoso!' ?></h2>
                    <p><?= ($pendiente || !$pago_confirmado) ? 'Estamos verificando tu pago. Te confirmaremos por correo.' : 'Tu aventura en Cusco comienza ahora.' ?></p>
                </div>

                <div class="pw-card-body">
                    <div style="display:flex;gap:18px;background:#f8fafc;padding:18px;border-radius:14px;align-items:center;margin-bottom:8px;flex-wrap:wrap;">
                        <img src="assets/img/tours/<?= htmlspecialchars($reserva['imagen_principal']) ?>" style="width:80px;height:80px;border-radius:12px;object-fit:cover;" alt="Tour" loading="lazy">
                        <div>
                            <h4 style="margin:0;font-weight:800;color:var(--ip-primary);font-size:1.05rem;"><?= htmlspecialchars($reserva['titulo']) ?></h4>
                            <p style="margin:4px 0 0;color:var(--ip-muted);font-size:0.9rem;"><i class="far fa-calendar-alt me-1"></i> <?= date('d M, Y', strtotime($reserva['fecha_viaje'])) ?></p>
                        </div>
                    </div>

                    <div class="pw-grid-details">
                        <div class="pw-detail">
                            <span>Código de Reserva</span>
                            <b>#<?= htmlspecialchars($reserva['codigo']) ?></b>
                        </div>
                        <div class="pw-detail">
                            <span>Pasajeros</span>
                            <b><?= $reserva['total_adultos'] ?> Adulto(s)<?= $reserva['total_ninos'] > 0 ? ', ' . $reserva['total_ninos'] . ' Niño(s)' : '' ?></b>
                        </div>
                        <div class="pw-detail">
                            <span>Estado de Pago</span>
                            <b style="color: <?= ($pendiente || !$pago_confirmado) ? 'var(--ip-yape)' : 'var(--ip-success)' ?>;">
                                <?= ($pendiente || !$pago_confirmado) ? 'Pendiente' : 'Confirmado' ?>
                            </b>
                        </div>
                        <div class="pw-detail">
                            <span>Fecha de Compra</span>
                            <b><?= date('d/m/Y H:i') ?></b>
                        </div>
                    </div>

                    <div class="pw-price-break">
                        <div class="pw-price-row">
                            <span>Costo Total del Tour</span>
                            <span><?= $simbolo ?><?= number_format($monto_total_conv, 2) ?></span>
                        </div>
                        <div class="pw-price-row paid">
                            <span><?= ($pendiente || !$pago_confirmado) ? 'Monto a Verificar' : 'Monto Pagado Hoy' ?></span>
                            <span><?= $simbolo ?><?= number_format($monto_pagado, 2) ?></span>
                        </div>
                        <?php if ($saldo_pendiente > 0): ?>
                            <div class="pw-price-row balance">
                                <span>Saldo a pagar en Cusco</span>
                                <span><?= $simbolo ?><?= number_format($saldo_pendiente, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="pw-price-row total">
                            <span>Total de la Reserva</span>
                            <span><?= $simbolo ?><?= number_format($monto_total_conv, 2) ?></span>
                        </div>
                    </div>

                    <div class="pw-mail-note">
                        <i class="fas fa-info-circle me-1" style="color:var(--ip-turq);"></i>
                        <?php if ($correo_enviado): ?>
                            Hemos enviado el comprobante de pago a <strong><?= htmlspecialchars($reserva['email']) ?></strong>.
                        <?php elseif ($pago_confirmado): ?>
                            En breve recibirás el comprobante de pago en <strong><?= htmlspecialchars($reserva['email']) ?></strong>.
                        <?php else: ?>
                            Tu reserva está registrada. Te contactaremos para coordinar el pago.
                        <?php endif; ?>
                    </div>

                    <div class="pw-actions">
                        <a href="index.php" class="pw-btn pw-btn-primary"><i class="fas fa-home"></i> Volver al Inicio</a>
                        <a href="includes/generar_pdf_reserva.php?id_reserva=<?= $id_reserva ?>" target="_blank" class="pw-btn pw-btn-outline"><i class="fas fa-file-pdf"></i> Descargar Comprobante</a>
                    </div>
                </div>
            </div>

            <p class="text-center mt-4 text-muted small" style="color:var(--ip-muted);">
                ¿Tienes alguna duda? <a href="contacto.php" style="color:var(--ip-primary);font-weight:800;text-decoration:none;">Contáctanos</a>
            </p>
        </div>
    </div>
</body>
</html>