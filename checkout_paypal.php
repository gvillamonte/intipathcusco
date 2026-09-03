<?php
// checkout_paypal.php
// Crea la orden en PayPal y redirige al login/checkout de PayPal.
// Paso 2 del wizard: seleccionar_pago.php -> checkout_paypal.php -> PayPal -> retorno_paypal.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/izipay_helper.php'; // izipayObtenerReservaPorToken / izipayMontoACobrar
require_once __DIR__ . '/includes/paypal_helper.php';
require_once __DIR__ . '/includes/pago_brand.php';

$token = $_GET['t'] ?? '';
if (empty($token)) { header("Location: index.php"); exit; }

$db = (new Database())->getConnection();
$reserva = izipayObtenerReservaPorToken($db, $token);
if (!$reserva) { header("Location: index.php"); exit; }

// Si ya está pagado, ir al éxito directamente
if ($reserva['estado'] === 'pagado') {
    header("Location: pago_exitoso.php?t=" . urlencode($token));
    exit;
}

$monto_cobrar_usd = izipayMontoACobrar($db, $reserva);
if ($monto_cobrar_usd <= 0) {
    header("Location: pago_exitoso.php?t=" . urlencode($token));
    exit;
}

$error_pago = null;
try {
    $orden = paypalCrearOrden($db, $reserva, $monto_cobrar_usd, 'USD');
} catch (Exception $e) {
    $error_pago = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago con PayPal - Reserva #<?= htmlspecialchars($reserva['codigo']) ?> | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pago_izipay.css">
    <?php if (isset($orden) && !empty($orden['approve_url'])): ?>
    <meta http-equiv="refresh" content="0; url=<?= htmlspecialchars($orden['approve_url']) ?>">
    <?php endif; ?>
</head>
<body>
    <div class="pw-loading active" id="pwLoading">
        <div class="pw-loading-box">
            <div class="pw-spinner"></div>
            <div class="pw-loading-title">REDIRIGIENDO A PAYPAL</div>
            <div class="pw-loading-sub">Estamos creando tu orden de pago segura. Serás llevado a PayPal para autorizar el pago...</div>
            <div class="pw-loading-brand"><?= pagoLogoLoader($db) ?></div>
        </div>
    </div>

    <div class="pw-header">
        <a class="pw-brand" href="index.php"><?= pagoLogoHtml($db) ?></a>
        <h1>Pago con PayPal</h1>
        <p class="sub">Reserva #<?= htmlspecialchars($reserva['codigo']) ?> | Monto: USD <?= number_format($monto_cobrar_usd, 2) ?></p>
    </div>

    <div class="pw-wizard">
        <div class="pw-steps">
            <div class="pw-step done"><span class="pw-num"><i class="fas fa-check pw-ico"></i></span><span>Datos</span></div>
            <div class="pw-track"><span class="pw-progress" style="width:100%"></span></div>
            <div class="pw-step active"><span class="pw-num">2</span><span>Pago</span></div>
            <div class="pw-track"><span class="pw-progress" style="width:0%"></span></div>
            <div class="pw-step"><span class="pw-num">3</span><span>Confirmación</span></div>
        </div>
    </div>

    <div class="pw-container">
        <div class="pw-card" style="max-width:520px;margin:0 auto;">
            <div class="pw-card-head">
                <span class="pw-head-ico"><i class="fab fa-paypal"></i></span>
                <h3>PayPal</h3>
            </div>
            <div class="pw-card-body">
                <?php if (isset($error_pago) && !empty($error_pago)): ?>
                    <div class="alert alert-danger" style="border-radius:12px;font-size:0.9rem;">
                        <i class="fas fa-exclamation-triangle me-2"></i> No se pudo iniciar el pago con PayPal.
                        <div style="font-size:0.78rem;opacity:0.8;margin-top:6px;word-break:break-word;"><?= htmlspecialchars($error_pago) ?></div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="seleccionar_pago.php?t=<?= urlencode($token) ?>" class="pw-btn pw-btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Volver a métodos de pago
                        </a>
                    </div>
                <?php else: ?>
                    <div class="pw-inline-loader">
                        <div class="pw-spinner"></div>
                        <p>Creando tu orden en PayPal...<br><small style="color:#9aa5b1;">Redirigiendo automáticamente. Si no eres redirigido, usa el botón.</small></p>
                    </div>
                    <div class="text-center">
                        <a href="<?= htmlspecialchars($orden['approve_url']) ?>" class="pw-btn pw-btn-paypal">
                            <i class="fab fa-paypal me-1"></i> Continuar con PayPal
                        </a>
                        <div class="mt-3">
                            <a href="seleccionar_pago.php?t=<?= urlencode($token) ?>" class="pw-btn-back">
                                <i class="fas fa-arrow-left me-1"></i> Elegir otro método de pago
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="pw-secure-box" style="max-width:520px;margin:18px auto 0;">
            <i class="fas fa-shield-check me-1"></i> <strong>Pago Seguro:</strong> serás redirigido a PayPal para autorizar el pago. Tus datos bancarios nunca pasan por nuestro servidor.
        </div>
    </div>

    <script>
        var pwBtn = document.getElementById('pwLoading');
        if (pwBtn) setTimeout(function () { pwBtn.classList.remove('active'); }, 2500);
    </script>
</body>
</html>