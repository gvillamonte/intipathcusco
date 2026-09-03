<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/recordatorio_helper.php';

$db = (new Database())->getConnection();

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_reserva = trim($_GET['reserva'] ?? '');

// Construir WHERE
$where = [];
$params = [];

if ($filtro_estado !== '') {
    $where[] = "r.estado = ?";
    $params[] = $filtro_estado;
}
if ($filtro_reserva !== '') {
    $where[] = "(r.codigo LIKE ? OR r.nombre LIKE ? OR r.email LIKE ?)";
    $like = "%{$filtro_reserva}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// KPIs
$stmt_kpi = $db->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN r.estado IN ('pendiente','parcial') AND r.email_recordatorio_1_enviado = 0 AND r.created_at <= NOW() - INTERVAL 1 DAY THEN 1 ELSE 0 END) AS r1_pendientes,
    SUM(CASE WHEN r.estado IN ('pendiente','parcial') AND r.email_recordatorio_2_enviado = 0 AND r.email_recordatorio_1_enviado = 1 AND r.created_at <= NOW() - INTERVAL 3 DAY THEN 1 ELSE 0 END) AS r2_pendientes,
    SUM(CASE WHEN r.estado = 'cancelado' THEN 1 ELSE 0 END) AS canceladas
    FROM reservas r");
$kpi = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

// Lista de reservas con info de recordatorios
$stmt = $db->prepare("
    SELECT r.id, r.codigo, r.nombre, r.apellido, r.email, r.estado, r.created_at, r.fecha_viaje,
           r.email_recordatorio_1_enviado, r.email_recordatorio_2_enviado,
           r.fecha_recordatorio_1, r.fecha_recordatorio_2,
           t.titulo
    FROM reservas r
    LEFT JOIN tours t ON r.id_tour = t.id
    {$where_sql}
    ORDER BY r.id DESC
    LIMIT 50
");
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorios | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --ip-turq: #0C9A9E; }
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .admin-title { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .stats { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex: 1 1 180px; min-width: 180px; border: 1px solid #e0e0e0; }
        .stat-card .num { font-size: 28px; font-weight: 800; }
        .stat-card .label { font-size: 12px; color: #888; text-transform: uppercase; }
        .filtros { background: #fff; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .filtros select, .filtros input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; }
        .filtros .btn-filtrar { background: var(--admin-blue); color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        th { background: var(--admin-blue); color: #fff; padding: 12px 15px; text-align: left; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:hover { background: #f5f7fa; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-enviado { background: #d4edda; color: #155724; }
        .badge-cancelado { background: #f8d7da; color: #721c24; }
        .badge-estado-p { background: #fff3cd; color: #856404; }
        .badge-estado-pp { background: #cce5ff; color: #004085; }
        .badge-estado-pg { background: #d4edda; color: #155724; }
        .badge-estado-c { background: #f8d7da; color: #721c24; }
        .check-yes { color: #27ae60; font-weight: bold; }
        .check-no { color: #ccc; }
        @media (max-width: 768px) {
            .admin-title { font-size: 1.1rem; }
            .main-content { padding: 15px !important; }
            th, td { padding: 8px 10px; font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding: 30px;">
        <h1 class="admin-title"><i class="fas fa-bell"></i> Recordatorios de Pago</h1>

        <!-- KPIs -->
        <div class="stats">
            <div class="stat-card">
                <div class="num" style="color:var(--ip-turq);"><?= (int)$kpi['r1_pendientes'] ?></div>
                <div class="label">R1 Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="num" style="color:var(--admin-accent);"><?= (int)$kpi['r2_pendientes'] ?></div>
                <div class="label">R2 Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="num" style="color:#e74c3c;"><?= (int)$kpi['canceladas'] ?></div>
                <div class="label">Canceladas</div>
            </div>
            <div class="stat-card">
                <div class="num" style="color:var(--admin-blue);"><?= (int)$kpi['total'] ?></div>
                <div class="label">Total Reservas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;width:100%;">
                <input type="text" name="reserva" placeholder="Buscar por código, nombre o email..." value="<?= htmlspecialchars($filtro_reserva) ?>" style="flex:1;min-width:200px;">
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="parcial" <?= $filtro_estado === 'parcial' ? 'selected' : '' ?>>Parcial</option>
                    <option value="pagado" <?= $filtro_estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                    <option value="cancelado" <?= $filtro_estado === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
                <button type="submit" class="btn-filtrar"><i class="fas fa-search"></i> Filtrar</button>
                <a href="recordatorios.php" style="color:var(--admin-blue);text-decoration:none;font-size:13px;"><i class="fas fa-times"></i> Limpiar</a>
            </form>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Tour</th>
                    <th>Fecha Viaje</th>
                    <th>Estado</th>
                    <th>R1 (24h)</th>
                    <th>R2 (72h)</th>
                    <th>Creada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#999;">No se encontraron reservas.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><a href="reserva_ver.php?id=<?= $r['id'] ?>" style="color:var(--admin-blue);font-weight:700;"><?= htmlspecialchars($r['codigo']) ?></a></td>
                        <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                        <td><?= htmlspecialchars($r['titulo'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($r['fecha_viaje'])) ?></td>
                        <td>
                            <?php
                            $estado_class = [
                                'pendiente' => 'badge-estado-p',
                                'parcial' => 'badge-estado-pp',
                                'pagado' => 'badge-estado-pg',
                                'cancelado' => 'badge-estado-c',
                            ];
                            $cls = $estado_class[$r['estado']] ?? '';
                            ?>
                            <span class="badge <?= $cls ?>"><?= ucfirst($r['estado']) ?></span>
                        </td>
                        <td>
                            <?php if ((int)$r['email_recordatorio_1_enviado']): ?>
                                <span class="check-yes"><i class="fas fa-check-circle"></i> Enviado</span>
                                <br><small style="color:#888;"><?= date('d/m H:i', strtotime($r['fecha_recordatorio_1'])) ?></small>
                            <?php else: ?>
                                <span class="check-no"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$r['email_recordatorio_2_enviado']): ?>
                                <span class="check-yes"><i class="fas fa-check-circle"></i> Enviado</span>
                                <br><small style="color:#888;"><?= date('d/m H:i', strtotime($r['fecha_recordatorio_2'])) ?></small>
                            <?php else: ?>
                                <span class="check-no"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </main>
</div>
</body>
</html>
