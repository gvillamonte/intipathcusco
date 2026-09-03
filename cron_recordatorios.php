<?php
// cron_recordatorios.php
// Script CLI para enviar recordatorios de pago automáticos
// Ejecutar cada hora vía cron: 0 * * * * php /home2/yosherhu/public_html/cron_recordatorios.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/email_helper.php';
require_once __DIR__ . '/includes/recordatorio_helper.php';

echo "=== Cron Recordatorios - " . date('Y-m-d H:i:s') . " ===\n";

try {
    $db = (new Database())->getConnection();

    // Verificar si los recordatorios están activos
    $stmt_config = $db->query("SELECT recordatorios_activo, dias_recordatorio_1, dias_recordatorio_2, dias_cancelacion FROM configuracion WHERE id = 1");
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);

    if (!$config || !(int)$config['recordatorios_activo']) {
        echo "Recordatorios desactivados. Saliendo.\n";
        exit;
    }

    $dias_r1 = (int)($config['dias_recordatorio_1'] ?? 1);
    $dias_r2 = (int)($config['dias_recordatorio_2'] ?? 3);
    $dias_cancel = (int)($config['dias_cancelacion'] ?? 7);

    // 1. Enviar recordatorio 1 (24h+ sin pago)
    echo "\n--- Recordatorio 1 (>{$dias_r1} día(s) sin pago) ---\n";
    $stmt_r1 = $db->prepare("
        SELECT r.id FROM reservas r
        WHERE r.estado IN ('pendiente','parcial')
          AND r.email_recordatorio_1_enviado = 0
          AND r.created_at <= NOW() - INTERVAL ? DAY
          AND r.fecha_viaje > NOW()
          AND r.token IS NOT NULL
    ");
    $stmt_r1->execute([$dias_r1]);
    $reservas_r1 = $stmt_r1->fetchAll(PDO::FETCH_ASSOC);
    $enviados_r1 = 0;

    foreach ($reservas_r1 as $r) {
        if (enviarRecordatorio1($db, $r['id'])) {
            $enviados_r1++;
            echo "  ✓ Recordatorio 1 enviado para reserva ID={$r['id']}\n";
        }
    }
    echo "  Total enviados: {$enviados_r1}\n";

    // 2. Enviar recordatorio 2 (72h+ sin pago)
    echo "\n--- Recordatorio 2 (>{$dias_r2} día(s) sin pago) ---\n";
    $stmt_r2 = $db->prepare("
        SELECT r.id FROM reservas r
        WHERE r.estado IN ('pendiente','parcial')
          AND r.email_recordatorio_2_enviado = 0
          AND r.email_recordatorio_1_enviado = 1
          AND r.created_at <= NOW() - INTERVAL ? DAY
          AND r.fecha_viaje > NOW()
          AND r.token IS NOT NULL
    ");
    $stmt_r2->execute([$dias_r2]);
    $reservas_r2 = $stmt_r2->fetchAll(PDO::FETCH_ASSOC);
    $enviados_r2 = 0;

    foreach ($reservas_r2 as $r) {
        if (enviarRecordatorio2($db, $r['id'])) {
            $enviados_r2++;
            echo "  ✓ Recordatorio 2 enviado para reserva ID={$r['id']}\n";
        }
    }
    echo "  Total enviados: {$enviados_r2}\n";

    // 3. Cancelar reservas vencidas (7+ días sin pago)
    echo "\n--- Cancelación automática (>{$dias_cancel} días sin pago) ---\n";
    $canceladas = cancelarReservasVencidas($db, $dias_cancel);
    echo "  Reservas canceladas: {$canceladas}\n";

    echo "\n=== Cron completado ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
