<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

header('Content-Type: application/json; charset=utf-8');

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$stmt = $db->prepare("SELECT r.*, t.titulo, t.titulo_en, t.duracion, t.precio, t.precio_nino, t.porcentaje_adelanto, t.imagen_principal FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
$stmt->execute([$id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    http_response_code(404);
    echo json_encode(['error' => 'Reserva no encontrada']);
    exit;
}

$stmt_p = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ? ORDER BY id ASC");
$stmt_p->execute([$id]);
$pasajeros = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$stmt_pagos = $db->prepare("SELECT * FROM pagos WHERE id_reserva = ? ORDER BY id ASC");
$stmt_pagos->execute([$id]);
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

$total_pagado = 0;
foreach ($pagos as $p) {
    if ($p['estado'] === 'pagado') {
        $total_pagado += (float)$p['monto'];
    }
}

$metodo_map = [
    'por_definir' => 'Por definir',
    'tarjeta' => 'Tarjeta',
    'yape' => 'Yape',
    'yape_manual' => 'Yape Manual',
    'efectivo' => 'Efectivo',
    'transferencia' => 'Transferencia',
    'culqi_tarjeta' => 'Tarjeta',
    'izipay_tarjeta' => 'Tarjeta',
    'yape_izipay' => 'Yape',
    'paypal' => 'PayPal',
];
$estado_map = ['pendiente' => 'Pendiente', 'parcial' => 'Parcial', 'pagado' => 'Pagado', 'cancelado' => 'Cancelado'];

echo json_encode([
    'reserva' => [
        'id' => (int)$reserva['id'],
        'codigo' => $reserva['codigo'],
        'estado' => $reserva['estado'],
        'estado_display' => $estado_map[$reserva['estado']] ?? $reserva['estado'],
        'metodo_pago' => $reserva['metodo_pago'],
        'metodo_display' => $metodo_map[$reserva['metodo_pago']] ?? ($reserva['metodo_pago'] ?? 'Por definir'),
        'nombre' => trim(($reserva['nombre'] ?? '') . ' ' . ($reserva['apellido'] ?? '')),
        'email' => $reserva['email'],
        'telefono' => $reserva['telefono'],
        'whatsapp' => $reserva['whatsapp'],
        'pais' => $reserva['pais'],
        'mensaje' => $reserva['mensaje'],
        'fecha_viaje' => date('d/m/Y', strtotime($reserva['fecha_viaje'])),
        'fecha_creacion' => date('d/m/Y H:i', strtotime($reserva['created_at'])),
        'adultos' => (int)$reserva['total_adultos'],
        'ninos' => (int)$reserva['total_ninos'],
        'monto_total' => (float)$reserva['monto_total'],
        'adelanto' => (float)$reserva['adelanto'],
        'saldo' => (float)$reserva['saldo'],
        'total_pagado' => $total_pagado,
        'saldo_real' => max(0, (float)$reserva['monto_total'] - $total_pagado),
        'tour' => [
            'titulo' => $reserva['titulo'] ?? 'Sin tour',
            'duracion' => $reserva['duracion'] ?? '',
            'precio' => $reserva['precio'],
            'precio_nino' => $reserva['precio_nino'],
            'porcentaje_adelanto' => $reserva['porcentaje_adelanto'],
            'imagen' => $reserva['imagen_principal'],
        ],
        'token' => $reserva['token'],
    ],
    'pasajeros' => array_map(function ($p) {
        return [
            'id' => (int)$p['id'],
            'tipo' => $p['tipo'],
            'nombres' => $p['nombres'],
            'apellidos' => $p['apellidos'],
            'documento' => $p['documento'],
            'pais' => $p['pais'],
        ];
    }, $pasajeros),
    'pagos' => array_map(function ($p) {
        return [
            'id' => (int)$p['id'],
            'monto' => (float)$p['monto'],
            'moneda' => $p['moneda'],
            'metodo' => $p['metodo'],
            'estado' => $p['estado'],
            'fecha' => date('d/m/Y H:i', strtotime($p['fecha_pago'])),
            'transaction_id' => $p['transaction_id'] ?? '',
        ];
    }, $pagos),
], JSON_UNESCAPED_UNICODE);