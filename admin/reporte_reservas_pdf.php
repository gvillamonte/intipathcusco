<?php
/**
 * reporte_reservas_pdf.php
 * Genera un PDF con el listado de reservas según los filtros (mismo query string que reservas.php).
 */
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estado_nombre = [
    'pendiente' => 'Pendiente',
    'parcial' => 'Parcial',
    'pagado' => 'Pagado',
    'cancelado' => 'Cancelado',
];
$metodo_nombre = ['tarjeta' => 'Tarjeta', 'yape' => 'Yape'];

$filtro_desc = [];
if ($filtro_estado) $filtro_desc[] = 'Estado: ' . $estado_nombre[$filtro_estado] ?? $filtro_estado;
if ($filtro_metodo) $filtro_desc[] = 'Metodo: ' . ($metodo_nombre[$filtro_metodo] ?? $filtro_metodo);
if ($filtro_tour > 0) $filtro_desc[] = 'Tour ID: ' . $filtro_tour;
if ($fecha_desde) $filtro_desc[] = 'Viaje desde: ' . $fecha_desde;
if ($fecha_hasta) $filtro_desc[] = 'Viaje hasta: ' . $fecha_hasta;
if ($busqueda) $filtro_desc[] = 'Busqueda: ' . $busqueda;
$filtro_txt = $filtro_desc ? implode(' | ', $filtro_desc) : 'Todos';

$filas = '';
foreach ($reservas as $r) {
    $pagado = (float)$r['monto_pagado'];
    $saldo = max(0, (float)$r['monto_total'] - $pagado);
    $filas .= '<tr>
        <td>' . htmlspecialchars($r['codigo']) . '</td>
        <td>' . htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) . '</td>
        <td>' . htmlspecialchars($r['titulo'] ?? '-') . '</td>
        <td>' . date('d/m/Y', strtotime($r['fecha_viaje'])) . '</td>
        <td style="text-align:right;">$' . number_format($r['monto_total'], 2) . '</td>
        <td style="text-align:right;">$' . number_format($pagado, 2) . '</td>
        <td style="text-align:right;">$' . number_format($saldo, 2) . '</td>
        <td>' . ($metodo_nombre[$r['metodo_pago']] ?? '-') . '</td>
        <td>' . ($estado_nombre[$r['estado']] ?? $r['estado']) . '</td>
    </tr>';
}

$html = '
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; }
    h1 { color: #15305D; font-size: 18px; margin: 0 0 4px; }
    .sub { color: #666; font-size: 11px; margin-bottom: 14px; }
    .filtros { background: #f4f7f6; border-left: 4px solid #E8AC18; padding: 8px 12px; font-size: 10px; margin-bottom: 14px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #15305D; color: #fff; padding: 7px 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 6px; border-bottom: 1px solid #e3e6ea; }
    tr:nth-child(even) td { background: #f7f9fb; }
    .totales { margin-top: 14px; font-size: 11px; }
    .totales strong { color: #15305D; }
    .footer { margin-top: 20px; font-size: 9px; color: #999; text-align: center; }
</style>
<h1>IntiPath Tours - Reporte de Reservas</h1>
<div class="sub">Generado el ' . date('d/m/Y H:i') . ' | ' . count($reservas) . ' reserva(s)</div>
<div class="filtros">Filtros aplicados: ' . $filtro_txt . '</div>
<table>
    <thead>
        <tr>
            <th>Codigo</th><th>Cliente</th><th>Tour</th><th>Fecha Viaje</th>
            <th>Total</th><th>Pagado</th><th>Saldo</th><th>Metodo</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>' . ($filas ?: '<tr><td colspan="9" style="text-align:center;color:#999;">No hay reservas para los filtros seleccionados.</td></tr>') . '</tbody>
</table>
<div class="footer">IntiPath Tours - Cusco, Peru</div>';

if (ob_get_length()) ob_end_clean();
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Reporte_Reservas_IntiPath_" . date('d-m-Y') . ".pdf", ['Attachment' => true]);