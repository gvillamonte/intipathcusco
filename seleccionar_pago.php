<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/izipay_helper.php';
require_once __DIR__ . '/config/izipay.php';
require_once __DIR__ . '/includes/pago_brand.php';
require_once __DIR__ . '/includes/cookie_consent.php';

$token = $_GET['t'] ?? '';
if (empty($token)) { header("Location: index.php"); exit; }

$db = (new Database())->getConnection();
$reserva = izipayObtenerReservaPorToken($db, $token);
if (!$reserva) { header("Location: index.php"); exit; }

$id_reserva = $reserva['id'];

// Restricción: Si ya está pagado, redirigir al éxito
if ($reserva['estado'] === 'pagado') {
    header("Location: pago_exitoso.php?t=" . urlencode($token));
    exit;
}

$monto_cobrar = izipayMontoACobrar($db, $reserva);

// Tipo de cambio para mostrar el equivalente en PEN
require_once __DIR__ . '/includes/tipo_cambio_helper.php';
$tipo_cambio = obtenerTipoCambio($db);

// Moneda: prioridad GET > cookie de preferencias (recordada) > USD
if (isset($_GET['moneda']) && ($_GET['moneda'] === 'PEN' || $_GET['moneda'] === 'USD')) {
    $moneda_seleccion = $_GET['moneda'];
    if (cookiesPermitidas('preferencias')) {
        setcookie('intipath_moneda', $moneda_seleccion, time() + 31536000, '/', '', false, true);
    }
} elseif (!empty($_COOKIE['intipath_moneda'])) {
    $moneda_seleccion = ($_COOKIE['intipath_moneda'] === 'PEN') ? 'PEN' : 'USD';
} else {
    $moneda_seleccion = 'USD';
}
$monto_pen = $monto_cobrar * $tipo_cambio;
$titulo_tour = $reserva['titulo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Completar Reserva - <?= htmlspecialchars($titulo_tour) ?> | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pago_izipay.css">
</head>
<body>
    <div class="pw-header">
        <a class="pw-brand" href="index.php"><?= pagoLogoHtml($db) ?></a>
        <h1>Finalizar Reserva</h1>
        <p class="sub">Elige cómo quieres pagar tu aventura en Cusco</p>
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
        <div class="pw-row">
            <!-- Izquierda: Métodos de Pago -->
            <div class="pw-col-main">
                <div class="pw-card">
                    <div class="pw-card-head">
                        <span class="pw-head-ico"><i class="fas fa-credit-card"></i></span>
                        <h3>Selecciona cómo pagar</h3>
                    </div>
                    <div class="pw-card-body">
                        <div style="margin-bottom:20px;">
                            <label style="font-weight:800;color:var(--ip-primary);font-size:0.85rem;display:block;margin-bottom:10px;"><i class="fas fa-coins me-1"></i> Moneda de pago</label>
                            <div class="pw-currency">
                                <a href="seleccionar_pago.php?t=<?= urlencode($token) ?>&moneda=USD" class="pw-option <?= $moneda_seleccion === 'USD' ? 'selected' : '' ?>">
                                    <div class="pw-option-info">
                                        <h4>USD $</h4>
                                        <p><?= '$' . number_format($monto_cobrar, 2) ?></p>
                                    </div>
                                </a>
                                <a href="seleccionar_pago.php?t=<?= urlencode($token) ?>&moneda=PEN" class="pw-option <?= $moneda_seleccion === 'PEN' ? 'selected' : '' ?>">
                                    <div class="pw-option-info">
                                        <h4>PEN S/</h4>
                                        <p><?= 'S/ ' . number_format($monto_pen, 2) ?></p>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <a href="checkout_izipay.php?t=<?= urlencode($token) ?>&metodo=tarjeta&moneda=<?= $moneda_seleccion ?>" class="pw-option">
                            <div class="pw-option-ico ico-card"><i class="fas fa-credit-card"></i></div>
                            <div class="pw-option-info">
                                <h4>Tarjeta de Crédito / Débito</h4>
                                <p>Pago instantáneo y seguro con Visa, Mastercard, AMEX y más (procesado por IZIPAY).</p>
                            </div>
                            <i class="fas fa-chevron-right pw-option-arrow"></i>
                        </a>

                        <a href="checkout_izipay.php?t=<?= urlencode($token) ?>&metodo=yape&moneda=<?= $moneda_seleccion ?>" class="pw-option">
                            <div class="pw-option-ico ico-yape"><i class="fas fa-mobile-alt"></i></div>
                            <div class="pw-option-info">
                                <h4>Yape</h4>
                                <p>Pago seguro vía Yape. Confirmación automática al instante.</p>
                            </div>
                            <i class="fas fa-chevron-right pw-option-arrow"></i>
                        </a>

                        <a href="checkout_paypal.php?t=<?= urlencode($token) ?>" class="pw-option">
                            <div class="pw-option-ico ico-paypal"><i class="fab fa-paypal"></i></div>
                            <div class="pw-option-info">
                                <h4>PayPal</h4>
                                <p>Paga con tu cuenta PayPal o tarjeta asociada. Cómodo y seguro (en USD).</p>
                            </div>
                            <i class="fas fa-chevron-right pw-option-arrow"></i>
                        </a>

                        <div class="pw-secure-box">
                            <i class="fas fa-shield-check me-1"></i> <strong>Pago Seguro:</strong> Procesado por IZIPAY o PayPal. Tus datos bancarios nunca pasan por nuestro servidor.
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="detalle_tour.php?id=<?= $reserva['id_tour'] ?>" class="pw-btn-back">
                        <i class="fas fa-arrow-left me-1"></i> Modificar datos de la reserva
                    </a>
                </div>
            </div>

            <!-- Derecha: Resumen de Reserva -->
            <div class="pw-col-side">
                <div class="pw-card">
                    <div class="pw-tour-pic">
                        <img src="assets/img/tours/<?= htmlspecialchars($reserva['imagen_principal']) ?>" alt="<?= htmlspecialchars($titulo_tour) ?>" loading="lazy">
                        <div class="pw-overlay">
                            <h4><?= htmlspecialchars($titulo_tour) ?></h4>
                            <small><i class="far fa-calendar-alt me-1"></i> <?= date('d M, Y', strtotime($reserva['fecha_viaje'])) ?></small>
                        </div>
                    </div>
                    <div class="pw-card-body">
                        <div class="pw-sum-row">
                            <span>Pasajeros</span>
                            <b><?= $reserva['total_adultos'] ?> Adulto(s)<?= $reserva['total_ninos'] > 0 ? ', ' . $reserva['total_ninos'] . ' Niño(s)' : '' ?></b>
                        </div>
                        <div class="pw-sum-row">
                            <span>Duración</span>
                            <b><?= htmlspecialchars($reserva['duracion']) ?></b>
                        </div>
                        <div class="pw-sum-row">
                            <span>Código de Reserva</span>
                            <b style="color:var(--ip-turq);">#<?= $reserva['codigo'] ?></b>
                        </div>

                        <div class="pw-sum-total pw-sum-row">
                            <span>Total del Viaje</span>
                            <span>$<?= number_format($reserva['monto_total'], 2) ?></span>
                        </div>

                        <div class="pw-sum-adelanto">
                            <span>Pago hoy (Adelanto)</span>
                            <span><?= $moneda_seleccion === 'PEN' ? 'S/ ' . number_format($monto_pen, 2) : '$' . number_format($monto_cobrar, 2) ?></span>
                        </div>

                        <?php if ($reserva['saldo'] > 0): ?>
                            <span class="pw-sum-saldo">Saldo de $<?= number_format($reserva['saldo'], 2) ?> a pagar en Cusco</span>
                        <?php endif; ?>

                        <div class="pw-note">
                            <i class="fas fa-info-circle me-1"></i> Al completar el pago, recibirás un correo electrónico con tu confirmación y el itinerario detallado.
                        </div>
                    </div>
                </div>

                <div class="pw-trust">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-amex"></i>
                    <i class="fab fa-paypal"></i>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="pw-loading" id="loadingOverlay">
        <div class="pw-loading-box">
            <div class="pw-spinner"></div>
            <div class="pw-loading-title">REDIRIGIENDO A PAGO SEGURO</div>
            <div class="pw-loading-sub">Por favor, no cierres esta ventana</div>
            <button class="pw-btn pw-btn-outline" style="margin-top:22px;background:transparent;color:#fff;border-color:rgba(255,255,255,0.5);" onclick="document.getElementById('loadingOverlay').classList.remove('active');">Cancelar</button>
        </div>
    </div>

    <script>
    document.querySelectorAll('.pw-option[href]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            document.getElementById('loadingOverlay').classList.add('active');
            setTimeout(function() { window.location.href = href; }, 700);
        });
    });
    </script>
</body>
</html>