<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$busqueda = trim($_GET['busqueda'] ?? '');
$filtro_estado = $_GET['estado'] ?? '';
$filtro_metodo = $_GET['metodo'] ?? '';
$fecha_desde = $_GET['desde'] ?? '';
$fecha_hasta = $_GET['hasta'] ?? '';
$filtro_tour = (int)($_GET['tour'] ?? 0);
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Validar fecha desde/hasta
if ($fecha_desde && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = '';
if ($fecha_hasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = '';

$where = [];
$params = [];
if ($busqueda) {
    $where[] = "(r.codigo LIKE ? OR r.email LIKE ? OR r.telefono LIKE ? OR r.whatsapp LIKE ? OR r.nombre LIKE ? OR r.apellido LIKE ?)";
    $term = "%$busqueda%";
    $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
}
if ($filtro_estado) {
    $where[] = "r.estado = ?";
    $params[] = $filtro_estado;
}
if ($filtro_metodo) {
    $where[] = "r.metodo_pago = ?";
    $params[] = $filtro_metodo;
}
if ($filtro_tour > 0) {
    $where[] = "r.id_tour = ?";
    $params[] = $filtro_tour;
}
if ($fecha_desde) {
    $where[] = "r.fecha_viaje >= ?";
    $params[] = $fecha_desde;
}
if ($fecha_hasta) {
    $where[] = "r.fecha_viaje <= ?";
    $params[] = $fecha_hasta;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$where_ing = $where ? ' AND ' . implode(' AND ', $where) : '';

// ---- KPIs (sin paginación) ----
$stmt_kpi = $db->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN r.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
    SUM(CASE WHEN r.estado IN ('pagado','parcial') THEN 1 ELSE 0 END) AS confirmadas,
    SUM(CASE WHEN r.estado = 'pagado' THEN 1 ELSE 0 END) AS pagadas,
    SUM(CASE WHEN r.estado IN ('pendiente','parcial') AND (r.email_recordatorio_1_enviado = 0 OR r.email_recordatorio_2_enviado = 0) AND r.created_at <= NOW() - INTERVAL 1 DAY THEN 1 ELSE 0 END) AS recordatorios_pendientes
    FROM reservas r $where_sql");
$stmt_kpi->execute($params);
$kpi = $stmt_kpi->fetch(PDO::FETCH_ASSOC);
$kpi_total = (int)$kpi['total'];
$kpi_pendientes = (int)$kpi['pendientes'];
$kpi_confirmadas = (int)$kpi['confirmadas'];
$kpi_recordatorios = (int)$kpi['recordatorios_pendientes'];

require_once __DIR__ . '/../includes/tipo_cambio_helper.php';
$tipo_cambio = obtenerTipoCambio($db);

// Ingresos pagados (USD directo + PEN convertido)
$stmt_ing = $db->prepare("SELECT
    COALESCE(SUM(CASE WHEN p.moneda = 'USD' THEN p.monto ELSE 0 END), 0) AS usd,
    COALESCE(SUM(CASE WHEN p.moneda = 'PEN' THEN p.monto ELSE 0 END), 0) AS pen
    FROM pagos p
    INNER JOIN reservas r ON r.id = p.id_reserva
    WHERE p.estado = 'pagado' $where_ing");
$stmt_ing->execute($params);
$ing = $stmt_ing->fetch(PDO::FETCH_ASSOC);
$ingresos_usd = (float)$ing['usd'] + (float)$ing['pen'] / $tipo_cambio;

$total = $db->prepare("SELECT COUNT(*) FROM reservas r $where_sql");
$total->execute($params);
$total_reservas = $total->fetchColumn();

$sql = "SELECT r.*, t.titulo,
           (SELECT COALESCE(SUM(p.monto), 0) FROM pagos p WHERE p.id_reserva = r.id AND p.estado = 'pagado') AS monto_pagado
        FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id $where_sql
        ORDER BY r.id DESC LIMIT $por_pagina OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_paginas = ceil($total_reservas / $por_pagina);

$estados = ['pendiente', 'parcial', 'pagado', 'cancelado'];
$metodos = ['tarjeta', 'yape'];
$badge_estado = [
    'pendiente' => '<span style="background:#fff3cd;color:#856404;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Pendiente</span>',
    'parcial' => '<span style="background:#cce5ff;color:#004085;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Parcial</span>',
    'pagado' => '<span style="background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Pagado</span>',
    'cancelado' => '<span style="background:#f8d7da;color:#721c24;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Cancelado</span>',
];
$icono_metodo = [
    'por_definir' => ['fa-question-circle', '#9aa5b1', 'Por definir'],
    'tarjeta' => ['fa-credit-card', '#15305D', 'Tarjeta'],
    'culqi_tarjeta' => ['fa-credit-card', '#15305D', 'Tarjeta'],
    'izipay_tarjeta' => ['fa-credit-card', '#15305D', 'Tarjeta'],
    'yape' => ['fa-mobile-screen', '#27ae60', 'Yape'],
    'yape_manual' => ['fa-mobile-screen', '#27ae60', 'Yape Manual'],
    'yape_izipay' => ['fa-mobile-screen', '#8B1D8B', 'Yape'],
    'paypal' => ['fa-paypal', '#003087', 'PayPal'],
    'efectivo' => ['fa-money-bill', '#b8860b', 'Efectivo'],
    'transferencia' => ['fa-building-columns', '#1a73e8', 'Transferencia'],
];
function iconoMetodoHtml($metodo_pago) {
    global $icono_metodo;
    $icono = $icono_metodo[$metodo_pago] ?? ['fa-circle-info', '#64748b', $metodo_pago ?: 'Por definir'];
    return '<span title="' . htmlspecialchars($icono[2]) . '" style="cursor:help;color:' . $icono[1] . ';font-size:16px;"><i class="fas ' . $icono[0] . '"></i></span>';
}

// Tours para el filtro
$tours_stmt = $db->query("SELECT id, titulo FROM tours ORDER BY titulo");
$tours_filtro = $tours_stmt->fetchAll(PDO::FETCH_ASSOC);

// Query string para exportaciones/paginación
function qs($extra = []) {
    $base = [
        'busqueda' => $_GET['busqueda'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'metodo' => $_GET['metodo'] ?? '',
        'desde' => $_GET['desde'] ?? '',
        'hasta' => $_GET['hasta'] ?? '',
        'tour' => $_GET['tour'] ?? '',
    ];
    $base = array_merge($base, $extra);
    $base = array_filter($base, fn($v) => $v !== '');
    return http_build_query($base);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 18px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid var(--admin-blue); }
        .stat-card .num { font-size: 1.7rem; font-weight: 800; color: var(--admin-blue); }
        .stat-card .lbl { font-size: 12px; color: #888; text-transform: uppercase; font-weight: 700; }
        .stat-card.pend { border-left-color: #f39c12; } .stat-card.pend .num { color: #b8860b; }
        .stat-card.ok { border-left-color: #27ae60; } .stat-card.ok .num { color: #27ae60; }
        .stat-card.ing { border-left-color: #1a73e8; } .stat-card.ing .num { color: #1a73e8; }
        .filtros-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05); margin-bottom:20px; border:1px solid #e0e0e0; }
        .filtros-grid { display:grid; grid-template-columns:repeat(8,1fr); gap:14px 16px; align-items:end; }
        .filtros-grid .campo { display:flex; flex-direction:column; gap:6px; min-width:0; }
        .filtros-grid .campo.busqueda { grid-column:span 2; }
        .filtros-grid label { font-size:11px; font-weight:700; color:var(--admin-blue); text-transform:uppercase; letter-spacing:0.5px; }
        .filtros-grid input, .filtros-grid select { width:100%; height:38px; padding:0 12px; border:1px solid #d0d7dd; border-radius:8px; font-size:13px; background:#fff; color:#1e293b; outline:none; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
        .filtros-grid input:focus, .filtros-grid select:focus { border-color:#0C9A9E; box-shadow:0 0 0 3px rgba(12,154,158,.15); }
        .filtros-acciones { display:flex; gap:10px; margin-top:16px; flex-wrap:wrap; align-items:center; }
        .filtros-acciones .espaciador { flex:1; }
        .btn-filtro { height:38px; padding:0 18px; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px; text-decoration:none; color:#fff; background:var(--admin-blue); transition:opacity .15s; }
        .btn-filtro:hover { opacity:.9; }
        .btn-filtro.limpiar { background:#fff; color:#666; border:1px solid #ccc; }
        .btn-export { height:38px; padding:0 15px; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#27ae60; color:#fff; transition:opacity .15s; }
        .btn-export:hover { opacity:.9; }
        .btn-export.pdf { background:#c0392b; }
        @media (max-width:1500px){ .filtros-grid { grid-template-columns:repeat(4,1fr); } }
        @media (max-width:1100px){ .filtros-grid { grid-template-columns:repeat(3,1fr); } }
        @media (max-width:800px){ .filtros-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:560px){ .filtros-grid { grid-template-columns:1fr; } .filtros-grid .campo.busqueda { grid-column:span 1; } }
        .table-wrap { background:#fff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow-x:auto; border:1px solid #e0e0e0; }
        table { width:100%; border-collapse:collapse; min-width:720px; }
        th { background:var(--admin-blue); color:#fff; padding:12px 15px; text-align:left; font-size:12px; text-transform:uppercase; position:sticky; top:0; z-index:1; white-space:nowrap; }
        td { padding:12px 15px; border-bottom:1px solid #eee; font-size:13px; }
        tr:hover { background:#f5f7fa; }
        @media (max-width:1300px){ .col-metodo { display:none; } }
        @media (max-width:1100px){ .col-saldo, .col-fecha { display:none; } table { min-width:640px; } }
        @media (max-width:900px){ .col-pagado { display:none; } table { min-width:600px; } }
        @media (max-width:700px){ table { min-width:560px; } }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.55); z-index:10000; justify-content:center; align-items:flex-start; padding:30px 15px; overflow-y:auto; }
        .modal-overlay.abierto { display:flex; }
        .modal-vista { background:#fff; border-radius:14px; width:100%; max-width:900px; box-shadow:0 20px 60px rgba(0,0,0,0.3); overflow:hidden; animation:modalIn .2s ease-out; }
        @keyframes modalIn { from { opacity:0; transform:translateY(15px); } to { opacity:1; transform:translateY(0); } }
        .modal-head { background:var(--admin-blue); color:#fff; padding:18px 24px; display:flex; align-items:center; gap:14px; }
        .modal-head .cerrar { margin-left:auto; background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:15px; }
        .modal-head .cerrar:hover { background:rgba(255,255,255,0.3); }
        .modal-head h3 { margin:0; font-size:17px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; }
        .modal-head .sub { font-size:12px; opacity:0.85; }
        .modal-body { padding:24px; }
        .m-seccion { margin-bottom:22px; }
        .m-seccion-titulo { color:var(--admin-blue); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:1px; border-bottom:2px solid var(--admin-accent); padding-bottom:6px; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .m-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px 18px; }
        .m-item .lbl { font-size:10.5px; color:#8aa0ad; text-transform:uppercase; font-weight:700; letter-spacing:0.4px; }
        .m-item .val { font-size:13.5px; color:#1e293b; font-weight:600; margin-top:2px; }
        .m-tabla { width:100%; border-collapse:collapse; font-size:12.5px; }
        .m-tabla th { background:#f1f5f9; color:#475569; text-align:left; padding:8px 10px; font-size:10.5px; text-transform:uppercase; letter-spacing:0.4px; }
        .m-tabla td { padding:8px 10px; border-bottom:1px solid #eef2f6; color:#1e293b; }
        .m-totales { display:flex; flex-wrap:wrap; gap:12px; }
        .m-total { flex:1; min-width:150px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px; }
        .m-total .lbl { font-size:10.5px; color:#8aa0ad; text-transform:uppercase; font-weight:700; }
        .m-total .num { font-size:17px; font-weight:800; margin-top:3px; }
        .m-total.ok .num { color:#27ae60; } .m-total.pend .num { color:#b8860b; } .m-total.tot .num { color:var(--admin-blue); }
        .m-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .m-tour-img { width:100%; max-height:120px; object-fit:cover; border-radius:10px; margin-bottom:12px; }
        .m-cargando { text-align:center; padding:60px 0; color:#8aa0ad; font-size:14px; }
        .m-acciones { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; padding-top:18px; border-top:1px solid #eef2f6; }
        .m-btn { padding:10px 18px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:700; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:7px; color:#fff; }
        .m-btn.azul { background:var(--admin-blue); } .m-btn.verde { background:#27ae60; } .m-btn.rojo { background:#c0392b; }
        .m-btn:hover { opacity:0.9; }
        .paginacion { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .paginacion a { padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: var(--admin-blue); font-size: 13px; }
        .paginacion a.active { background: var(--admin-blue); color: #fff; border-color: var(--admin-blue); }
        .total-badge { background: var(--admin-accent); color: #000; padding: 3px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; }
        .acciones a { color: var(--admin-blue); text-decoration: none; margin: 0 3px; font-size: 14px; }
        .acciones a:hover { color: var(--admin-accent); }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fas fa-clipboard-list"></i> Reservas Recibidas <span class="total-badge"><?= $total_reservas ?></span></h1>

        <div class="grid-stats">
            <div class="stat-card"><div class="num"><?= $kpi_total ?></div><div class="lbl">Reservas totales</div></div>
            <div class="stat-card pend"><div class="num"><?= $kpi_pendientes ?></div><div class="lbl">Pendientes de pago</div></div>
            <div class="stat-card ok"><div class="num"><?= $kpi_confirmadas ?></div><div class="lbl">Confirmadas (pago recibido)</div></div>
            <div class="stat-card ing"><div class="num">$<?= number_format($ingresos_usd, 2) ?></div><div class="lbl">Ingresos pagados (USD equiv.)</div></div>
            <div class="stat-card" style="border-left:4px solid #0C9A9E;"><div class="num" style="color:#0C9A9E;"><?= $kpi_recordatorios ?></div><div class="lbl">Recordatorios pendientes</div></div>
        </div>

        <form method="GET" class="filtros-card">
            <div class="filtros-grid">
                <div class="campo busqueda">
                    <label for="busqueda"><i class="fas fa-search"></i> Buscar</label>
                    <div style="position:relative;">
                        <input type="text" id="busqueda" name="busqueda" placeholder="Nombre, código, email, teléfono..." value="<?= htmlspecialchars($busqueda) ?>" autocomplete="off">
                        <div id="busqueda-sugerencias" class="busqueda-sugerencias" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ddd;border-radius:0 0 8px 8px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:100;max-height:320px;overflow-y:auto;"></div>
                    </div>
                </div>
                <div class="campo">
                    <label for="f_estado">Estado</label>
                    <select name="estado" id="f_estado">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e ?>" <?= $filtro_estado === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="f_metodo">Método de pago</label>
                    <select name="metodo" id="f_metodo">
                        <option value="">Todos los métodos</option>
                        <?php foreach ($metodos as $m): ?>
                            <option value="<?= $m ?>" <?= $filtro_metodo === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="f_tour">Tour</label>
                    <select name="tour" id="f_tour">
                        <option value="">Todos los tours</option>
                        <?php foreach ($tours_filtro as $tf): ?>
                            <option value="<?= $tf['id'] ?>" <?= $filtro_tour === (int)$tf['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tf['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="f_desde">Fecha viaje desde</label>
                    <input type="date" id="f_desde" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>">
                </div>
                <div class="campo">
                    <label for="f_hasta">Fecha viaje hasta</label>
                    <input type="date" id="f_hasta" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
                </div>
            </div>
            <div class="filtros-acciones">
                <span class="espaciador"></span>
                <button type="submit" class="btn-filtro"><i class="fas fa-search"></i> Filtrar</button>
                <?php if ($busqueda || $filtro_estado || $filtro_metodo || $filtro_tour || $fecha_desde || $fecha_hasta): ?>
                    <a href="reservas.php" class="btn-filtro limpiar"><i class="fas fa-eraser"></i> Limpiar</a>
                <?php endif; ?>
                <a href="exportar_reservas.php?<?= qs() ?>" class="btn-export"><i class="fas fa-file-excel"></i> Exportar Excel</a>
                <a href="reporte_reservas_pdf.php?<?= qs() ?>" class="btn-export pdf" target="_blank"><i class="fas fa-file-pdf"></i> Reporte PDF</a>
            </div>
        </form>

        <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Codigo</th><th>Cliente</th><th>Tour</th><th class="col-fecha">Fecha Viaje</th><th>Total</th><th class="col-pagado">Pagado</th><th class="col-saldo">Saldo</th><th class="col-metodo">Metodo</th><th>Estado</th><th>Accion</th></tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="10" style="text-align:center;padding:40px;color:#999;">No hay reservas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservas as $r):
                        $pagado = (float)$r['monto_pagado'];
                        $saldo = max(0, (float)$r['monto_total'] - $pagado);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['codigo']) ?></strong></td>
                        <td>
                            <div style="font-weight:bold;color:var(--admin-blue);"><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></div>
                            <div style="font-size:11px;color:#888;"><?= htmlspecialchars($r['email']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($r['titulo'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_viaje'])) ?></td>
                        <td><strong>$<?= number_format($r['monto_total'], 2) ?></strong></td>
                        <td class="col-pagado" style="color:#27ae60;font-weight:700;">$<?= number_format($pagado, 2) ?></td>
                        <td class="col-saldo" style="color:#b8860b;font-weight:700;">$<?= number_format($saldo, 2) ?></td>
                        <td class="col-metodo"><?= iconoMetodoHtml($r['metodo_pago']) ?></td>
                        <td><?= $badge_estado[$r['estado']] ?? $r['estado'] ?></td>
                        <td class="acciones">
                            <a href="#" onclick="verDetalle(<?= $r['id'] ?>); return false;" title="Ver visualización del paquete"><i class="fas fa-eye"></i></a>
                            <a href="reserva_ver.php?id=<?= $r['id'] ?>" title="Ver/Editar"><i class="fas fa-edit"></i></a>
                            <a href="ver_pdf.php?id=<?= $r['id'] ?>" title="Imprimir PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="paginacion">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?<?= qs(['pagina' => $i]) ?>" class="<?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- MODAL VISUALIZACION DEL PAQUETE -->
<div class="modal-overlay" id="modalVista" onclick="if(event.target===this)cerrarVista()">
    <div class="modal-vista">
        <div class="modal-head">
            <div>
                <h3><i class="fas fa-binoculars"></i> Visualización del Paquete</h3>
                <div class="sub" id="mv-sub">Cargando...</div>
            </div>
            <button class="cerrar" onclick="cerrarVista()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="mv-body">
            <div class="m-cargando"><i class="fas fa-spinner fa-spin"></i> Cargando datos de la reserva...</div>
        </div>
    </div>
</div>

<script>
function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
function badgeEstado(e) {
    var colores = {pendiente:'#fff3cd:#856404', parcial:'#cce5ff:#004085', pagado:'#d4edda:#155724', cancelado:'#f8d7da:#721c24'};
    var c = colores[e] || '#e2e8f0:#475569';
    var p = c.split(':');
    return '<span class="m-badge" style="background:'+p[0]+';color:'+p[1]+';">'+escapeHtml(e)+'</span>';
}
function fmtMoney(n) {
    return '$' + Number(n).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function verDetalle(id) {
    var modal = document.getElementById('modalVista');
    document.getElementById('mv-sub').textContent = 'Reserva #' + id;
    document.getElementById('mv-body').innerHTML = '<div class="m-cargando"><i class="fas fa-spinner fa-spin"></i> Cargando datos de la reserva...</div>';
    modal.classList.add('abierto');
    document.body.style.overflow = 'hidden';

    fetch('reserva_detalle_ajax.php?id=' + id)
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(d) {
            if (d.error) throw new Error(d.error);
            renderVista(d);
        })
        .catch(function(err) {
            document.getElementById('mv-body').innerHTML = '<div class="m-cargando" style="color:#c0392b;"><i class="fas fa-exclamation-triangle"></i> Error al cargar: ' + escapeHtml(err.message) + '</div>';
        });
}
function renderVista(d) {
    var r = d.reserva, t = r.tour;
    document.getElementById('mv-sub').textContent = 'Código ' + r.codigo + ' · ' + r.estado_display;

    var img = '';
    if (t.imagen) img = '<img class="m-tour-img" src="../assets/img/tours/' + escapeHtml(t.imagen) + '" onerror="this.style.display=\'none\'">';

    var paxRows = d.pasajeros.map(function(p, i) {
        return '<tr><td>' + (i+1) + '</td><td>' + escapeHtml(p.tipo) + '</td><td>' + escapeHtml(p.nombres) + '</td><td>' + escapeHtml(p.apellidos) + '</td><td>' + escapeHtml(p.documento) + '</td><td>' + escapeHtml(p.pais) + '</td></tr>';
    }).join('') || '<tr><td colspan="6" style="text-align:center;color:#999;">Sin pasajeros registrados</td></tr>';

    var pagoRows = d.pagos.map(function(p) {
        var col = p.estado === 'pagado' ? '#27ae60' : '#b8860b';
        var monedaBadge = (p.moneda === 'USD') 
            ? '<span style="display:inline-block;padding:2px 8px;border-radius:12px;background:#dbeafe;color:#1e40af;font-size:0.75rem;font-weight:700;">US$</span>'
            : '<span style="display:inline-block;padding:2px 8px;border-radius:12px;background:#d1fae5;color:#065f46;font-size:0.75rem;font-weight:700;">S/</span>';
        return '<tr><td>' + escapeHtml(p.fecha) + '</td><td>' + fmtMoney(p.monto) + '</td><td>' + monedaBadge + '</td><td>' + escapeHtml(p.metodo) + '</td><td style="color:' + col + ';font-weight:700;">' + escapeHtml(p.estado) + '</td></tr>';
    }).join('') || '<tr><td colspan="5" style="text-align:center;color:#999;">Sin pagos registrados</td></tr>';

    document.getElementById('mv-body').innerHTML =
        '<div class="m-seccion">' + img +
            '<div class="m-grid">' +
                '<div class="m-item"><div class="lbl">Estado</div><div class="val">' + badgeEstado(r.estado_display) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Código</div><div class="val">' + escapeHtml(r.codigo) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Fecha viaje</div><div class="val">' + escapeHtml(r.fecha_viaje) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Creada</div><div class="val">' + escapeHtml(r.fecha_creacion) + '</div></div>' +
            '</div>' +
        '</div>' +
        '<div class="m-seccion">' +
            '<div class="m-seccion-titulo"><i class="fas fa-mountain"></i> Tour</div>' +
            '<div class="m-grid">' +
                '<div class="m-item"><div class="lbl">Paquete</div><div class="val">' + escapeHtml(t.titulo) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Duración</div><div class="val">' + escapeHtml(t.duracion) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Adultos</div><div class="val">' + r.adultos + '</div></div>' +
                '<div class="m-item"><div class="lbl">Niños</div><div class="val">' + r.ninos + '</div></div>' +
                '<div class="m-item"><div class="lbl">Método de pago</div><div class="val">' + escapeHtml(r.metodo_display) + '</div></div>' +
            '</div>' +
        '</div>' +
        '<div class="m-seccion">' +
            '<div class="m-seccion-titulo"><i class="fas fa-user"></i> Cliente</div>' +
            '<div class="m-grid">' +
                '<div class="m-item"><div class="lbl">Nombre completo</div><div class="val">' + escapeHtml(r.nombre) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Email</div><div class="val">' + escapeHtml(r.email) + '</div></div>' +
                '<div class="m-item"><div class="lbl">Teléfono</div><div class="val">' + escapeHtml(r.telefono) + '</div></div>' +
                '<div class="m-item"><div class="lbl">WhatsApp</div><div class="val">' + escapeHtml(r.whatsapp) + '</div></div>' +
                '<div class="m-item"><div class="lbl">País</div><div class="val">' + escapeHtml(r.pais) + '</div></div>' +
                (r.mensaje ? '<div class="m-item"><div class="lbl">Mensaje</div><div class="val">' + escapeHtml(r.mensaje) + '</div></div>' : '') +
            '</div>' +
        '</div>' +
        '<div class="m-seccion">' +
            '<div class="m-seccion-titulo"><i class="fas fa-users"></i> Pasajeros (' + d.pasajeros.length + ')</div>' +
            '<table class="m-tabla"><thead><tr><th>#</th><th>Tipo</th><th>Nombres</th><th>Apellidos</th><th>Documento</th><th>País</th></tr></thead><tbody>' + paxRows + '</tbody></table>' +
        '</div>' +
        '<div class="m-seccion">' +
            '<div class="m-seccion-titulo"><i class="fas fa-credit-card"></i> Pagos (' + d.pagos.length + ')</div>' +
            '<table class="m-tabla"><thead><tr><th>Fecha</th><th>Monto</th><th>Moneda</th><th>Método</th><th>Estado</th></tr></thead><tbody>' + pagoRows + '</tbody></table>' +
            '<div style="margin-top:14px;" class="m-totales">' +
                '<div class="m-total tot"><div class="lbl">Total del viaje</div><div class="num">' + fmtMoney(r.monto_total) + '</div></div>' +
                (function() {
                    var pagadoUSD = 0, pagadoPEN = 0;
                    d.pagos.forEach(function(p) {
                        if (p.estado === 'pagado') {
                            if (p.moneda === 'USD') pagadoUSD += p.monto;
                            else if (p.moneda === 'PEN') pagadoPEN += p.monto;
                        }
                    });
                    var parts = [];
                    if (pagadoUSD > 0) parts.push('<span style="color:#1e40af;font-weight:700;">US$ ' + pagadoUSD.toFixed(2) + '</span>');
                    if (pagadoPEN > 0) parts.push('<span style="color:#065f46;font-weight:700;">S/ ' + pagadoPEN.toFixed(2) + '</span>');
                    var desglose = parts.length ? ' (' + parts.join(' + ') + ')' : '';
                    return '<div class="m-total ok"><div class="lbl">Pagado</div><div class="num">' + fmtMoney(r.total_pagado) + desglose + '</div></div>';
                })() +
                '<div class="m-total pend"><div class="lbl">Saldo en Cusco</div><div class="num">' + fmtMoney(r.saldo_real) + '</div></div>' +
            '</div>' +
        '</div>' +
        '<div class="m-acciones">' +
            '<a class="m-btn azul" href="reserva_ver.php?id=' + r.id + '"><i class="fas fa-edit"></i> Ver / Editar</a>' +
            '<a class="m-btn verde" href="ver_pdf.php?id=' + r.id + '" target="_blank"><i class="fas fa-file-pdf"></i> Imprimir PDF</a>' +
            '<a class="m-btn rojo" href="seleccionar_pago.php?t=' + encodeURIComponent(r.token || '') + '" target="_blank"><i class="fas fa-credit-card"></i> Página de pago</a>' +
        '</div>';
}
function cerrarVista() {
    document.getElementById('modalVista').classList.remove('abierto');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarVista(); });

// BÚSQUEDA AJAX EN TIEMPO REAL
(function() {
    var input = document.getElementById('busqueda');
    var tbody = document.querySelector('.table-wrap tbody');
    var timer = null;
    var currentPage = 1;

    function buildUrl(page) {
        var params = new URLSearchParams();
        params.set('ajax', '1');
        params.set('pagina', page);
        var val = input.value.trim();
        if (val) params.set('busqueda', val);
        var estado = document.getElementById('f_estado').value;
        if (estado) params.set('estado', estado);
        var metodo = document.getElementById('f_metodo').value;
        if (metodo) params.set('metodo', metodo);
        var tour = document.getElementById('f_tour').value;
        if (tour) params.set('tour', tour);
        var desde = document.getElementById('f_desde').value;
        if (desde) params.set('desde', desde);
        var hasta = document.getElementById('f_hasta').value;
        if (hasta) params.set('hasta', hasta);
        return 'reservas.php?' + params.toString();
    }

    // AUTOCOMPLETE DROPDOWN EN EL BUSCADOR
(function() {
    var input = document.getElementById('busqueda');
    var dropdown = document.getElementById('busqueda-sugerencias');
    var timer = null;
    var selectedIndex = -1;
    var suggestions = [];

    function hideDropdown() {
        dropdown.style.display = 'none';
        selectedIndex = -1;
    }

    function showDropdown(items) {
        if (!items.length) {
            hideDropdown();
            return;
        }
        suggestions = items;
        dropdown.innerHTML = items.map(function(r, i) {
            var nombre = r.nombre + ' ' + r.apellido;
            var badge = r.estado === 'pagado' ? '#27ae60' : (r.estado === 'pendiente' ? '#f39c12' : (r.estado === 'parcial' ? '#3498db' : '#e74c3c'));
            return '<div class="sug-item" data-index="' + i + '" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:10px;">' +
                '<div style="flex:1;min-width:0;">' +
                '<div style="font-weight:600;color:#15305D;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(r.codigo) + '</div>' +
                '<div style="font-size:12px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(nombre) + ' &middot; ' + escapeHtml(r.email) + '</div>' +
                '</div>' +
                '<span style="background:' + badge + ';color:#fff;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;white-space:nowrap;">' + escapeHtml(r.estado) + '</span>' +
                '</div>';
        }).join('');
        dropdown.style.display = 'block';
        selectedIndex = -1;

        // Click en sugerencia
        dropdown.querySelectorAll('.sug-item').forEach(function(el) {
            el.addEventListener('click', function() {
                var idx = parseInt(this.dataset.index, 10);
                selectSuggestion(suggestions[idx]);
            });
            el.addEventListener('mouseenter', function() {
                dropdown.querySelectorAll('.sug-item').forEach(function(e) { e.style.background = ''; });
                this.style.background = '#f5f7fa';
                selectedIndex = parseInt(this.dataset.index, 10);
            });
        });
    }

    function selectSuggestion(r) {
        // Al clickear sugerencia: filtrar la tabla con el texto completo del input
        var q = input.value.trim();
        if (q) {
            window.location.href = 'reservas.php?busqueda=' + encodeURIComponent(q);
        }
    }

    function highlightIndex(idx) {
        var items = dropdown.querySelectorAll('.sug-item');
        items.forEach(function(el, i) { el.style.background = i === idx ? '#f5f7fa' : ''; });
        selectedIndex = idx;
    }

    input.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(timer);
        if (q.length < 2) {
            hideDropdown();
            return;
        }
        timer = setTimeout(function() {
            fetch('reservas_busqueda_ajax.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) { showDropdown(data); })
                .catch(function(err) { console.error('Error autocomplete:', err); hideDropdown(); });
        }, 200);
    });

    input.addEventListener('keydown', function(e) {
        if (!dropdown.style.display || dropdown.style.display === 'none') return;
        var items = dropdown.querySelectorAll('.sug-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            var next = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
            highlightIndex(next);
            items[next].scrollIntoView({block: 'nearest'});
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var prev = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
            highlightIndex(prev);
            items[prev].scrollIntoView({block: 'nearest'});
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            selectSuggestion(suggestions[selectedIndex]);
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== input) hideDropdown();
    });

    input.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && suggestions.length) showDropdown(suggestions);
    });
})();
</script>
</body>
</html>