<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/culqi.php';

$input = file_get_contents('php://input');
$evento = json_decode($input, true);

if (!$evento || !isset($evento['type'])) {
    http_response_code(400);
    exit;
}

if ($evento['type'] !== 'charge.successful') {
    http_response_code(200);
    exit;
}

$data = $evento['data'] ?? [];
$charge_id = $data['id'] ?? '';
$monto = ($data['amount'] ?? 0) / 100;
$moneda = $data['currency_code'] ?? 'PEN';
$metadata = $data['metadata'] ?? [];
$id_reserva = $metadata['id_reserva'] ?? 0;
$metodo = $data['source']['type'] ?? 'culqi_tarjeta';

if (!$id_reserva || !$charge_id) {
    http_response_code(400);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("INSERT INTO pagos (id_reserva, monto, moneda, metodo, culqi_charge_id, estado, fecha_pago) VALUES (?, ?, ?, ?, ?, 'pagado', NOW())");
$stmt->execute([$id_reserva, $monto, $moneda, $metodo, $charge_id]);

$stmt_r = $db->prepare("SELECT monto_total, adelanto, saldo FROM reservas WHERE id = ?");
$stmt_r->execute([$id_reserva]);
$reserva = $stmt_r->fetch(PDO::FETCH_ASSOC);

$stmt_p = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
$stmt_p->execute([$id_reserva]);
$total_pagado = $stmt_p->fetchColumn();

if ($total_pagado >= $reserva['monto_total']) {
    $nuevo_estado = 'pagado';
} elseif ($total_pagado > 0) {
    $nuevo_estado = 'parcial';
} else {
    $nuevo_estado = 'pendiente';
}

$stmt_up = $db->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
$stmt_up->execute([$nuevo_estado, $id_reserva]);

http_response_code(200);
echo json_encode(['status' => 'ok']);
