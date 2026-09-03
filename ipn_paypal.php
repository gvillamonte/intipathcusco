<?php
// ipn_paypal.php
// Webhook de PayPal (notificación servidor a servidor).
// Verifica la firma de transmisión, registra el pago de forma idempotente y
// envía el correo de confirmación UNA sola vez (respaldo al retorno del navegador).

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/paypal_helper.php';
require_once __DIR__ . '/includes/izipay_helper.php'; // izipayObtenerReservaPorToken
require_once __DIR__ . '/includes/pago_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

$db = (new Database())->getConnection();

$body_bruto = file_get_contents('php://input');
$headers = [];
foreach (($_SERVER ?? []) as $k => $v) {
    if (str_starts_with($k, 'HTTP_PAYPAL_')) {
        $headers[strtolower(str_replace('_', '-', substr($k, 11)))] = $v;
    }
}

// 1. Verificar firma del webhook
if (!paypalVerificarWebhook($headers, $body_bruto)) {
    http_response_code(403);
    echo 'INVALID SIGNATURE';
    exit;
}

try {
    $evento = json_decode($body_bruto, true);
    $tipo = $evento['event_type'] ?? '';

    // Solo nos interesan las capturas completadas
    if ($tipo !== 'PAYMENT.CAPTURE.COMPLETED') {
        http_response_code(200);
        echo 'IGNORED (' . $tipo . ')';
        exit;
    }

    $recurso = $evento['resource'] ?? [];
    $purchase = $evento['resource']['purchase_units'][0] ?? [];
    $custom_id = $recurso['custom_id'] ?? $purchase['custom_id'] ?? '';

    $codigo = preg_replace('/^RES-/', '', $custom_id);
    if (empty($codigo)) {
        http_response_code(400);
        echo 'MISSING REFERENCE';
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM reservas WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codigo]);
    $id_reserva = (int)$stmt->fetchColumn();
    if (!$id_reserva) {
        http_response_code(404);
        echo 'RESERVA NOT FOUND';
        exit;
    }

    $capture_id = $recurso['id'] ?? '';
    $monto = (float)($recurso['amount']['value'] ?? 0);
    $moneda = $recurso['amount']['currency_code'] ?? 'USD';
    if (empty($capture_id) || $monto <= 0) {
        http_response_code(400);
        echo 'MISSING CAPTURE DATA';
        exit;
    }

    $resultado = registrarPagoConfirmado($db, $id_reserva, $monto, $moneda, 'paypal', $capture_id);

    if ($resultado && $resultado['nuevo_pago']) {
        enviarCorreoPagoConfirmado($db, $id_reserva);
    }

    http_response_code(200);
    echo 'OK';
} catch (Exception $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}