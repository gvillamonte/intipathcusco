<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Reservas_IntiPath_" . date('d-m-Y') . ".xls");

require_once '../config/database.php';
$db = (new Database())->getConnection();

$busqueda = trim($_GET['busqueda'] ?? '');
$filtro_estado = $_GET['estado'] ?? '';
$filtro_metodo = $_GET['metodo'] ?? '';
$fecha_desde = $_GET['desde'] ?? '';
$fecha_hasta = $_GET['hasta'] ?? '';
$filtro_tour = (int)($_GET['tour'] ?? 0);

if ($fecha_desde && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = '';
if ($fecha_hasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = '';

$where = [];
$params = [];
if ($busqueda) {
    $where[] = "(r.codigo LIKE ? OR r.email LIKE ? OR r.telefono LIKE ? OR r.nombre LIKE ? OR r.apellido LIKE ?)";
    $term = "%$busqueda%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}
if ($filtro_estado) { $where[] = "r.estado = ?"; $params[] = $filtro_estado; }
if ($filtro_metodo) { $where[] = "r.metodo_pago = ?"; $params[] = $filtro_metodo; }
if ($filtro_tour > 0) { $where[] = "r.id_tour = ?"; $params[] = $filtro_tour; }
if ($fecha_desde) { $where[] = "r.fecha_viaje >= ?"; $params[] = $fecha_desde; }
if ($fecha_hasta) { $where[] = "r.fecha_viaje <= ?"; $params[] = $fecha_hasta; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT r.*, t.titulo,
           (SELECT COALESCE(SUM(p.monto), 0) FROM pagos p WHERE p.id_reserva = r.id AND p.estado = 'pagado') AS monto_pagado
        FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id $where_sql
        ORDER BY r.id DESC";
$reservas = $db->prepare($sql);
$reservas->execute($params);
$reservas = $reservas->fetchAll(PDO::FETCH_ASSOC);

$estado_nombre = [
    'pendiente' => 'Pendiente',
    'parcial' => 'Parcial',
    'pagado' => 'Pagado',
    'cancelado' => 'Cancelado',
];
$metodo_nombre = ['tarjeta' => 'Tarjeta', 'yape' => 'Yape'];
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<table border="1">
    <thead>
        <tr style="background-color: #15305D; color: #FFFFFF; font-weight: bold;">
            <th>ID</th>
            <th>CODIGO</th>
            <th>FECHA REGISTRO</th>
            <th>CLIENTE</th>
            <th>EMAIL</th>
            <th>TELEFONO</th>
            <th>PAIS</th>
            <th>TOUR</th>
            <th>FECHA VIAJE</th>
            <th>ADULTOS</th>
            <th>NIÑOS</th>
            <th>TOTAL (USD)</th>
            <th>ADELANTO (USD)</th>
            <th>PAGADO (USD)</th>
            <th>SALDO (USD)</th>
            <th>METODO PAGO</th>
            <th>ESTADO</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reservas)): ?>
            <tr><td colspan="17">No hay reservas para los filtros seleccionados.</td></tr>
        <?php else: ?>
            <?php foreach ($reservas as $r):
                $pagado = (float)$r['monto_pagado'];
                $saldo = max(0, (float)$r['monto_total'] - $pagado);
            ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['codigo']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['telefono']) ?></td>
                <td><?= htmlspecialchars($r['pais'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['titulo'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha_viaje'])) ?></td>
                <td><?= $r['adultos'] ?></td>
                <td><?= $r['ninos'] ?></td>
                <td><?= number_format($r['monto_total'], 2) ?></td>
                <td><?= number_format($r['adelanto'], 2) ?></td>
                <td><?= number_format($pagado, 2) ?></td>
                <td><?= number_format($saldo, 2) ?></td>
                <td><?= isset($metodo_nombre[$r['metodo_pago']]) ? $metodo_nombre[$r['metodo_pago']] : '' ?></td>
                <td><?= $estado_nombre[$r['estado']] ?? $r['estado'] ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>