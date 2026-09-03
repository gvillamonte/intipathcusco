<?php
// retorno_paypal.php
// Retorno de PayPal tras aprobar la orden: captura el pago, lo registra de forma
// idempotente y envía el correo de confirmación UNA sola vez.

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/paypal_helper.php';
require_once __DIR__ . '/includes/izipay_helper.php';   // izipayObtenerReservaPorToken / izipayMontoACobrar
require_once __DIR__ . '/includes/pago_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

$db = (new Database())->getConnection();

$token_res = $_GET['t'] ?? '';
$order_id  = $_GET['token'] ?? ''; // PayPal devuelve la orden en ?token=ORDERID

$reserva = izipayObtenerReservaPorToken($db, $token_res);
if (!$reserva) { header("Location: index.php"); exit; }

// Ya pagado -> éxito directamente
if ($reserva['estado'] === 'pagado') {
    header("Location: pago_exitoso.php?t=" . urlencode($token_res));
    exit;
}

if (empty($order_id)) {
    $error = 'No se recibió la orden de PayPal.';
} else {
    try {
        $captura = paypalCapturarOrden($order_id);

        if ($captura['status'] === 'COMPLETED') {
            $id_reserva = $reserva['id'];
            $resultado = registrarPagoConfirmado($db, $id_reserva, $captura['amount'], $captura['currency'], 'paypal', $captura['capture_id']);

            if ($resultado && $resultado['nuevo_pago']) {
                enviarCorreoPagoConfirmado($db, $id_reserva);
            }

            header("Location: pago_exitoso.php?t=" . urlencode($token_res));
            exit;
        }

        header("Location: pago_exitoso.php?t=" . urlencode($token_res) . "&pendiente=1");
        exit;
    } catch (Exception $e) {
        $error = 'Error al confirmar el pago: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado del Pago | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/pago_izipay.css">
</head>
<body style="display:flex;align-items:center;min-height:100vh;">
    <div class="pw-container" style="max-width:520px;margin:0 auto;width:100%;">
        <div class="pw-card">
            <div class="text-center p-5" style="background:#f8d7da;color:#721c24;">
                <i class="fas fa-exclamation-triangle" style="font-size:44px;margin-bottom:15px;"></i>
                <h4 class="fw-bold" style="color:#721c24;">No pudimos confirmar tu pago</h4>
            </div>
            <div class="p-4 text-center">
                <p class="text-muted small">
                    <?= htmlspecialchars($error ?? 'Ocurrió un error al procesar la respuesta de PayPal.') ?>
                    <br>Si ya realizaste el pago, llegará la confirmación por correo en unos minutos.
                </p>
                <a href="index.php" class="pw-btn pw-btn-primary"><i class="fas fa-home me-2"></i> Volver al Inicio</a>
            </div>
        </div>
    </div>
</body>
</html>