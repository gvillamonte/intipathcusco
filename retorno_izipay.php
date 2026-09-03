<?php
// retorno_izipay.php
// Página de retorno de la pasarela IZIPAY: valida la firma del kr-answer,
// registra el pago (idempotente) y redirige al usuario a la página de éxito.

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/izipay.php';
require_once __DIR__ . '/includes/izipay_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

$db = (new Database())->getConnection();

$kr_answer = $_POST['kr-answer'] ?? $_GET['kr-answer'] ?? '';
$kr_hash = $_POST['kr-hash'] ?? $_GET['kr-hash'] ?? '';

$error = null;

if (empty($kr_answer) || empty($kr_hash)) {
    $error = 'Respuesta vacía de la pasarela.';
} elseif (!izipayVerificarFirma($kr_answer, $kr_hash, IZIPAY_HMAC_SHA256)) {
    $error = 'Firma inválida en la respuesta de pago.';
} else {
    try {
        $answer = json_decode($kr_answer, true);
        $orderStatus = $answer['orderStatus'] ?? 'UNPAID';
        $orderId = $answer['orderDetails']['orderId'] ?? '';
        $tx = $answer['transactions'][0] ?? [];

        $codigo = preg_replace('/^RES-/', '', $orderId);

        $stmt = $db->prepare("SELECT id FROM reservas WHERE codigo = ? LIMIT 1");
        $stmt->execute([$codigo]);
        $id_reserva = (int)$stmt->fetchColumn();

        if (!$id_reserva) {
            $error = 'Reserva no encontrada.';
        } elseif ($orderStatus === 'PAID') {
            $monto = (float)($tx['amount'] ?? 0) / 100;
            $moneda = $tx['currency'] ?? 'USD';
            $uuid = $tx['uuid'] ?? '';
            $metodo_pago = (isset($tx['paymentMethodType']) && stripos($tx['paymentMethodType'], 'YAPE') !== false) ? 'yape_izipay' : 'izipay_tarjeta';

            if (empty($uuid)) {
                $error = 'Transacción sin identificador.';
            } else {
                $resultado = izipayRegistrarPago($db, $id_reserva, $monto, $moneda, $metodo_pago, $uuid);

                if ($resultado && $resultado['nuevo_pago']) {
                    // Primer registro de este pago -> enviar correo de confirmación (una sola vez)
                    enviarCorreoPagoConfirmado($db, $id_reserva);
                }

                $stmt_tok = $db->prepare("SELECT token FROM reservas WHERE id = ?");
                $stmt_tok->execute([$id_reserva]);
                $token = $stmt_tok->fetchColumn();

                header("Location: pago_exitoso.php?t=" . urlencode($token));
                exit;
            }
        } else {
            // Pago no completado o en proceso -> mostrar mensaje
            $stmt_tok = $db->prepare("SELECT token FROM reservas WHERE id = ?");
            $stmt_tok->execute([$id_reserva]);
            $token = $stmt_tok->fetchColumn();
            header("Location: pago_exitoso.php?t=" . urlencode($token) . "&pendiente=1");
            exit;
        }
    } catch (Exception $e) {
        $error = 'Error interno: ' . $e->getMessage();
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
</head>
<body style="background:#f0f2f5; font-family:'Segoe UI', sans-serif; min-height:100vh; display:flex; align-items:center;">
    <div class="container" style="max-width:520px;">
        <div class="card border-0 shadow" style="border-radius:16px; overflow:hidden;">
            <div class="text-center p-5" style="background:#f8d7da; color:#721c24;">
                <i class="fas fa-exclamation-triangle" style="font-size:44px; margin-bottom:15px;"></i>
                <h4 class="fw-bold">No pudimos confirmar tu pago</h4>
            </div>
            <div class="p-4 text-center">
                <p class="text-muted small">
                    <?= htmlspecialchars($error ?? 'Ocurrió un error al procesar la respuesta de la pasarela.') ?>
                    <br>Si ya realizaste el pago, llegará la confirmación por correo en unos minutos.
                </p>
                <a href="index.php" class="btn" style="background:#15305D; color:#fff; border-radius:8px; padding:12px 28px; font-weight:700;">
                    <i class="fas fa-home me-2"></i> Volver al Inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>