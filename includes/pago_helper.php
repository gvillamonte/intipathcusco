<?php
// includes/pago_helper.php
// Registro de pagos confirmados: motor común e idempotente para todas las pasarelas
// (IZIPAY y PayPal). transaction_id único evita duplicados entre retorno/IPN/webhook.

require_once __DIR__ . '/tipo_cambio_helper.php';

/**
 * Registra un pago confirmado de forma IDEMPOTENTE (transaction_id único),
 * con bloqueo de fila (FOR UPDATE) para evitar condiciones de carrera
 * entre el retorno del navegador y la notificación servidor-a-servidor.
 *
 * Normaliza todos los montos a USD antes de comparar con monto_total.
 * Actualiza saldo y estado en la reserva.
 *
 * Devuelve ['nuevo_pago'=>bool, 'nuevo_estado'=>string, 'total_pagado_usd'=>float] o null.
 */
function registrarPagoConfirmado($db, $id_reserva, $monto, $moneda, $metodo, $transaction_id) {
    if (empty($transaction_id)) return null;
    $monto = (float)$monto;
    if ($monto <= 0) return null;

    $moneda = strtoupper($moneda);
    if (!in_array($moneda, ['USD', 'PEN'])) $moneda = 'USD';

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT monto_total, estado FROM reservas WHERE id = ? FOR UPDATE");
        $stmt->execute([$id_reserva]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reserva) {
            $db->rollBack();
            return null;
        }

        // Idempotencia: no registrar el mismo transaction_id dos veces
        $chk = $db->prepare("SELECT COUNT(*) FROM pagos WHERE transaction_id = ?");
        $chk->execute([$transaction_id]);
        if ($chk->fetchColumn() > 0) {
            $db->rollBack();
            return ['nuevo_pago' => false, 'nuevo_estado' => $reserva['estado'], 'total_pagado_usd' => 0];
        }

        // Insertar el pago con la moneda original
        $stmt_i = $db->prepare("INSERT INTO pagos (id_reserva, monto, moneda, metodo, transaction_id, culqi_charge_id, estado, fecha_pago)
                                VALUES (?, ?, ?, ?, ?, NULL, 'pagado', NOW())");
        $stmt_i->execute([$id_reserva, $monto, $moneda, $metodo, $transaction_id]);

        // Sumar todos los pagos y normalizar a USD para comparar con monto_total
        $monto_total_usd = (float)$reserva['monto_total'];

        // Obtener el tipo de cambio una sola vez
        $tipo_cambio = obtenerTipoCambio($db);

        $stmt_t = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
        $stmt_t->execute([$id_reserva]);
        $total_pagado_raw = (float)$stmt_t->fetchColumn();

        // Sumar pagos individuales, normalizando cada uno a USD
        $stmt_detail = $db->prepare("SELECT monto, moneda FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
        $stmt_detail->execute([$id_reserva]);
        $total_pagado_usd = 0;
        while ($pago = $stmt_detail->fetch(PDO::FETCH_ASSOC)) {
            $pago_monto = (float)$pago['monto'];
            $pago_moneda = strtoupper($pago['moneda'] ?? 'USD');
            if ($pago_moneda === 'PEN' && $tipo_cambio > 0) {
                $total_pagado_usd += round($pago_monto / $tipo_cambio, 2);
            } else {
                $total_pagado_usd += round($pago_monto, 2);
            }
        }

        // Determinar nuevo estado y saldo
        $nuevo_estado = ($total_pagado_usd >= $monto_total_usd) ? 'pagado' : (($total_pagado_usd > 0) ? 'parcial' : 'pendiente');
        $nuevo_saldo = max(0, round($monto_total_usd - $total_pagado_usd, 2));

        // Actualizar reserva: estado, método de pago Y saldo
        $stmt_up = $db->prepare("UPDATE reservas SET estado = ?, metodo_pago = ?, saldo = ?, updated_at = NOW() WHERE id = ?");
        $stmt_up->execute([$nuevo_estado, $metodo, $nuevo_saldo, $id_reserva]);

        $db->commit();
        return ['nuevo_pago' => true, 'nuevo_estado' => $nuevo_estado, 'total_pagado_usd' => $total_pagado_usd];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
