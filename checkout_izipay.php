<?php
// checkout_izipay.php
// Crea el pago en IZIPAY (formToken) y muestra la pasarela de pago segura.

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

function checkoutLog($msg) {
    $line = date('Y-m-d H:i:s') . ' | ' . $msg . PHP_EOL;
    @file_put_contents(__DIR__ . '/logs/checkout_debug.log', $line, FILE_APPEND | LOCK_EX);
}

function checkoutShowError($titulo, $mensaje, $extra = '') {
    ob_end_clean();
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error de Pago | IntiPath Tours</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .error-box { background: #fff; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); padding: 40px; max-width: 600px; width: 90%; border-top: 6px solid #dc3545; }
            .error-box h2 { color: #dc3545; font-weight: 800; }
            .error-box .detail { background: #f8f9fa; border-radius: 8px; padding: 15px; font-size: 0.85rem; margin: 15px 0; border: 1px solid #e9ecef; }
            .error-box .detail code { color: #dc3545; font-weight: 600; }
            .btn-back { display: inline-block; padding: 12px 28px; background: #15305D; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 700; margin-top: 15px; transition: background 0.3s; }
            .btn-back:hover { background: #0f2340; color: #fff; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2><i class="fas fa-exclamation-triangle me-2"></i><?= $titulo ?></h2>
            <p style="font-size:1.05rem;color:#333;"><?= $mensaje ?></p>
            <?php if ($extra): ?>
            <div class="detail"><?= $extra ?></div>
            <?php endif; ?>
            <p style="font-size:0.85rem;color:#888;margin-top:10px;">
                <i class="fas fa-info-circle me-1"></i>El error ha sido registrado en el servidor.
            </p>
            <a href="seleccionar_pago.php?t=<?= htmlspecialchars($_GET['t'] ?? '') ?>" class="btn-back">
                <i class="fas fa-arrow-left me-1"></i> Volver al pago
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Capturar errores fatales al final del script
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        checkoutLog('FATAL [' . $err['type'] . ']: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        checkoutShowError(
            'Error Fatal del Servidor',
            'Ocurrio un error interno al procesar el pago.',
            '<strong>Error:</strong> ' . $err['message'] . '<br>'
            . '<strong>Archivo:</strong> ' . $err['file'] . '<br>'
            . '<strong>Linea:</strong> ' . $err['line']
        );
    }
    ob_end_flush();
});

set_error_handler(function($severity, $message, $file, $line) {
    checkoutLog('PHP ERROR [' . $severity . ']: ' . $message . ' in ' . $file . ':' . $line);
    return false;
});

set_exception_handler(function($e) {
    checkoutLog('EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    checkoutShowError(
        'Error al Procesar el Pago',
        $e->getMessage(),
        '<strong>Detalle tecnico:</strong> ' . $e->getFile() . ':' . $e->getLine()
    );
});

checkoutLog('=== INICIO checkout_izipay.php | Moneda: ' . ($_GET['moneda'] ?? 'N/A') . ' | Token: ' . substr($_GET['t'] ?? '', 0, 10));

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/izipay.php';
require_once __DIR__ . '/includes/izipay_helper.php';
require_once __DIR__ . '/includes/pago_brand.php';

checkoutLog('Includes cargados OK');

$token = $_GET['t'] ?? '';
$moneda = strtoupper($_GET['moneda'] ?? IZIPAY_MONEDA);
if (!in_array($moneda, ['PEN', 'USD'])) $moneda = IZIPAY_MONEDA;
$metodo = ($_GET['metodo'] ?? 'tarjeta') === 'yape' ? 'yape' : 'tarjeta';

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
checkoutLog('Monto USD: ' . $monto_cobrar_usd . ' | Moneda: ' . $moneda . ' | Reserva ID: ' . ($reserva['id'] ?? 'N/A'));

if ($monto_cobrar_usd <= 0) {
    header("Location: pago_exitoso.php?t=" . urlencode($token));
    exit;
}

$monto_cobrar = izipayConvertirMoneda($db, $monto_cobrar_usd, $moneda);
checkoutLog('Monto convertido: ' . $monto_cobrar . ' ' . $moneda);

// Crear el pago en IZIPAY (con fallback automático de moneda)
try {
    checkoutLog('Intentando izipayCrearPago...');
    $pago = izipayCrearPago($db, $reserva, $monto_cobrar_usd, $moneda, $monto_cobrar);
    $formToken = $pago['formToken'];
    $publicKey = $pago['publicKey'];
    $moneda_real = $pago['moneda'];
    $monto_real = $pago['monto'];
    checkoutLog('Pago creado OK | moneda_real: ' . $moneda_real);
} catch (Exception $e) {
    $error_pago = $e->getMessage();
    checkoutLog('ERROR IZIPAY: ' . $error_pago);
}

checkoutLog('=== FIN - renderizando HTML ===');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago Seguro - Reserva #<?= htmlspecialchars($reserva['codigo']) ?> | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pago_izipay.css">
    <?php if (isset($formToken) && !empty($formToken)): ?>
    <link rel="stylesheet" href="<?= IZIPAY_STATIC_URL ?>/static/js/krypton-client/V4.0/ext/classic.css">
    <script type="text/javascript" src="<?= IZIPAY_STATIC_URL ?>/static/js/krypton-client/V4.0/ext/classic.js"></script>
    <script type="text/javascript"
        src="<?= IZIPAY_STATIC_URL ?>/static/js/krypton-client/V4.0/stable/kr-payment-form.min.js"
        kr-public-key="<?= htmlspecialchars($publicKey) ?>"
        kr-post-url-success="retorno_izipay.php"
        kr-language="es-Es">
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="pw-loading active" id="pwLoading">
        <div class="pw-loading-box">
            <div class="pw-spinner"></div>
            <div class="pw-loading-title">PREPARANDO PAGO SEGURO</div>
            <div class="pw-loading-sub">Estamos generando tu pago con IZIPAY. La pasarela aparecerá en un momento...</div>
            <div class="pw-loading-brand"><?= pagoLogoLoader($db) ?></div>
        </div>
    </div>

    <?php if (defined('IZIPAY_MODO') && IZIPAY_MODO === 'TEST'): ?>
    <div style="background:#ffc107;color:#000;text-align:center;padding:6px;font-size:13px;font-weight:700;letter-spacing:0.5px;">
        <i class="fas fa-flask"></i> MODO PRUEBA — No se cobrará dinero real
    </div>
    <?php endif; ?>

    <div class="pw-header">
        <a class="pw-brand" href="index.php"><?= pagoLogoHtml($db) ?></a>
        <h1>Pago Seguro</h1>
        <?php 
            $moneda_show = isset($moneda_real) ? $moneda_real : $moneda;
            $monto_show = isset($monto_real) ? $monto_real : $monto_cobrar;
            $fallback_note = (isset($moneda_real) && $moneda_real !== $moneda) 
                ? ' <span style="font-size:0.75rem;color:var(--ip-turq);font-weight:600;">(moneda ajustada automáticamente)</span>' 
                : '';
        ?>
        <p class="sub">Reserva #<?= htmlspecialchars($reserva['codigo']) ?> | <?= $metodo === 'yape' ? 'Yape' : 'Tarjeta' ?> | <?= $moneda_show ?> <?= number_format($monto_show, 2) ?><?= $fallback_note ?></p>
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
        <div class="pw-card" style="max-width:560px;margin:0 auto;">
            <div class="pw-card-head">
                <span class="pw-head-ico"><i class="fas fa-lock"></i></span>
                <h3><?= $metodo === 'yape' ? 'Pago con Yape' : 'Pago con Tarjeta' ?></h3>
            </div>
            <div class="pw-card-body">
                <div class="text-center" style="margin-bottom:6px;">
                    <div style="font-weight:800;color:var(--ip-primary);font-size:1.5rem;"><?= $moneda_show ?> <?= number_format($monto_show, 2) ?></div>
                    <div style="color:var(--ip-muted);font-size:0.85rem;">
                        <?= $reserva['estado'] === 'parcial' ? 'Pago del saldo restante' : 'Monto por adelanto requerido' ?>
                    </div>
                </div>

                <?php if (isset($error_pago) && !empty($error_pago)): ?>
                    <div class="alert alert-danger" style="border-radius:12px;font-size:0.9rem;">
                        <i class="fas fa-exclamation-triangle me-2"></i> No se pudo iniciar el pago. Por favor, intenta nuevamente.
                        <div style="font-size:0.78rem;opacity:0.8;margin-top:6px;word-break:break-word;"><?= htmlspecialchars($error_pago) ?></div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="seleccionar_pago.php?t=<?= urlencode($token) ?>" class="pw-btn pw-btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Volver a métodos de pago
                        </a>
                    </div>
                <?php elseif (isset($formToken)): ?>
                    <div id="micuentawebstd_rest_wrapper" class="pw-gateway">
                        <div class="kr-embedded" kr-popin kr-form-token="<?= htmlspecialchars($formToken) ?>"></div>
                    </div>
                <?php endif; ?>

                <div class="pw-secure-box">
                    <i class="fas fa-shield-check me-1"></i> Transacción procesada por <strong>IZIPAY</strong> con cifrado SSL de 256 bits. Tus datos bancarios nunca pasan por nuestro servidor.
                </div>

                <div class="text-center mt-3">
                    <a href="seleccionar_pago.php?t=<?= urlencode($token) ?>" class="pw-btn-back">
                        <i class="fas fa-arrow-left me-1"></i> Elegir otro método de pago
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var overlay = document.getElementById('pwLoading');
            if (overlay) setTimeout(function() { overlay.classList.remove('active'); }, 1200);
        });
    </script>
</body>
</html>
