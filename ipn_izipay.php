<?php
// ipn_izipay.php
// Notificación servidor a servidor (IPN) de IZIPAY.
// Valida la firma con la clave PASSWORD (API REST), registra el pago de forma
// idempotente y envía el correo de confirmación UNA sola vez.

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/izipay.php';
require_once __DIR__ . '/includes/izipay_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

$db = (new Database())->getConnection();

$kr_answer = $_POST['kr-answer'] ?? '';
$kr_hash = $_POST['kr-hash'] ?? '';

// 1. Validar firma (clave PASSWORD para IPN según documentación IZIPAY)
if (empty($kr_answer) || empty($kr_hash) || !izipayVerificarFirma($kr_answer, $kr_hash, IZIPAY_PASSWORD)) {
    http_response_code(403);
    echo 'INVALID SIGNATURE';
    exit;
}

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
        http_response_code(400);
        echo 'RESERVA NO ENCONTRADA';
        exit;
    }

    if ($orderStatus === 'PAID') {
        $monto = (float)($tx['amount'] ?? 0) / 100;
        $moneda = $tx['currency'] ?? 'USD';
        $uuid = $tx['uuid'] ?? '';
        $metodo_pago = (isset($tx['paymentMethodType']) && stripos($tx['paymentMethodType'], 'YAPE') !== false) ? 'yape_izipay' : 'izipay_tarjeta';

        $resultado = izipayRegistrarPago($db, $id_reserva, $monto, $moneda, $metodo_pago, $uuid);

        if ($resultado && $resultado['nuevo_pago']) {
            // Pago registrado por primera vez -> correo de confirmación (flag evita duplicados)
            enviarCorreoPagoConfirmado($db, $id_reserva);
        }

        echo 'OK! OrderStatus is PAID';
        exit;
    }

    echo 'OK! OrderStatus is ' . $orderStatus;
} catch (Exception $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}