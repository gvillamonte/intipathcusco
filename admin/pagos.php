<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('pagos');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT p.*, r.codigo, r.email FROM pagos p LEFT JOIN reservas r ON p.id_reserva = r.id ORDER BY p.id DESC");
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_recaudado = 0;
$total_pendiente = 0;
foreach ($pagos as $p) {
    if ($p['estado'] === 'pagado') $total_recaudado += $p['monto'];
}
$stmt_pend = $db->query("SELECT SUM(saldo) FROM reservas WHERE estado IN ('pendiente','parcial')");
$total_pendiente = $stmt_pend->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .stats { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex: 1 1 200px; min-width: 200px; border: 1px solid #e0e0e0; }
        .stat-card .num { font-size: 28px; font-weight: 800; }
        .stat-card .label { font-size: 12px; color: #888; text-transform: uppercase; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        th { background: var(--admin-blue); color: #fff; padding: 12px 15px; text-align: left; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:hover { background: #f5f7fa; }
        .badge-ok { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
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
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fas fa-dollar-sign"></i> Historial de Pagos</h1>

        <div class="stats">
            <div class="stat-card">
                <div class="num" style="color:#27ae60;">$<?= number_format($total_recaudado, 2) ?></div>
                <div class="label">Total Recaudado</div>
            </div>
            <div class="stat-card">
                <div class="num" style="color:#e74c3c;">$<?= number_format($total_pendiente, 2) ?></div>
                <div class="label">Pendiente de Cobro</div>
            </div>
            <div class="stat-card">
                <div class="num" style="color:var(--admin-blue);"><?= count($pagos) ?></div>
                <div class="label">Transacciones</div>
            </div>
        </div>

        <div class="table-responsive">
        <table>
            <thead>
                <tr><th>#</th><th>Reserva</th><th>Cliente</th><th>Monto</th><th>Moneda</th><th>Metodo</th><th>Fecha</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php if (empty($pagos)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#999;">No hay pagos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagos as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><a href="reserva_ver.php?id=<?= $p['id_reserva'] ?>" style="color:var(--admin-blue);font-weight:700;"><?= htmlspecialchars($p['codigo'] ?? '') ?></a></td>
                        <td><?= htmlspecialchars($p['email'] ?? '') ?></td>
                        <td><strong>$<?= number_format($p['monto'], 2) ?></strong></td>
                        <td><?= $p['moneda'] ?></td>
                        <td><?= htmlspecialchars(str_replace('_', ' ', $p['metodo'] ?? '')) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_pago'] ?? $p['created_at'])) ?></td>
                        <td><span class="badge-ok"><?= ucfirst($p['estado']) ?></span></td>
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
