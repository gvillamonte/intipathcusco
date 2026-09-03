<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/culqi.php';

use Culqi\Culqi;

$order_id = $_GET['order_id'] ?? '';
$id_reserva = intval($_GET['id_reserva'] ?? 0);

if (!$order_id || !$id_reserva) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros']);
    exit;
}

header('Content-Type: application/json');

try {
    $culqi = new Culqi(array('api_key' => CULQI_SECRET_KEY));
    $order = $culqi->Orders->get($order_id);

    if (!is_object($order)) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener la orden']);
        exit;
    }

    $order_status = $order->state ?? ($order->status ?? '');
    $charge_status = '';

    if (isset($order->charges) && is_array($order->charges) && count($order->charges) > 0) {
        $last_charge = end($order->charges);
        if (is_object($last_charge)) {
            $charge_status = $last_charge->status ?? '';
        }
    }

    $status = $order_status;
    if ($order_status === 'paid' || $order_status === 'payed' || $charge_status === 'captured') {
        $status = 'captured';
    } elseif ($order_status === 'expired' || $order_status === 'canceled') {
        $status = 'expired';
    }

    if ($status === 'captured') {
        $db = (new Database())->getConnection();

        $stmt_r = $db->prepare("SELECT * FROM reservas WHERE id = ?");
        $stmt_r->execute([$id_reserva]);
        $reserva = $stmt_r->fetch(PDO::FETCH_ASSOC);

        if ($reserva) {
            $monto_pagado = ($reserva['adelanto'] > 0) ? $reserva['adelanto'] : $reserva['monto_total'];

            $stmt_chk = $db->prepare("SELECT COUNT(*) FROM pagos WHERE culqi_charge_id = ?");
            $stmt_chk->execute([$order_id]);
            $existe = $stmt_chk->fetchColumn();

            if ($existe == 0) {
                $stmt_p = $db->prepare("INSERT INTO pagos (id_reserva, monto, moneda, metodo, culqi_charge_id, estado, fecha_pago) VALUES (?, ?, ?, 'yape', ?, 'pagado', NOW())");
                $stmt_p->execute([$id_reserva, $monto_pagado, CULQI_MONEDA, $order_id]);

                $stmt_t = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
                $stmt_t->execute([$id_reserva]);
                $total_pagado = $stmt_t->fetchColumn();

                $nuevo_estado = ($total_pagado >= $reserva['monto_total']) ? 'pagado' : 'parcial';
                $stmt_up = $db->prepare("UPDATE reservas SET estado = ?, metodo_pago = 'yape', updated_at = NOW() WHERE id = ?");
                $stmt_up->execute([$nuevo_estado, $id_reserva]);
            }
        }

        echo json_encode(['status' => 'captured']);
    } elseif ($status === 'expired') {
        echo json_encode(['status' => 'expired']);
    } else {
        echo json_encode(['status' => 'pending']);
    }

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
