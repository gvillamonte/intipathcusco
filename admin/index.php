<?php
// ============================================================
// 1. LÓGICA DE SESIÓN Y CONEXIÓN
// ============================================================
require_once __DIR__ . '/../includes/auth_helper.php';
iniciarSesionAdmin();

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// ============================================================
// 2. CONSULTAS SQL DINÁMICAS (KPIs)
// ============================================================

// A. Mensajes recibidos hoy [cite: 2026-03-31]
$hoy = date('Y-m-d');
$stmtMsg = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE DATE(fecha_creacion) = ?");
$stmtMsg->execute([$hoy]);
$totalMensajesHoy = $stmtMsg->fetchColumn();

// B. Cantidad de Tours Activos [cite: 2026-03-31]
$stmtTours = $db->query("SELECT COUNT(*) FROM tours WHERE estado = 'activo'");
$totalTours = $stmtTours->fetchColumn();

// C. Cantidad de Colaboradores activos [cite: 2026-03-31]
$stmtUser = $db->query("SELECT COUNT(*) FROM usuarios WHERE estado = 1");
$totalColaboradores = $stmtUser->fetchColumn();

// D. Listado de las últimas 5 consultas para la tabla [cite: 2026-03-31]
$stmtUltimos = $db->query("SELECT * FROM mensajes ORDER BY id DESC LIMIT 5");
$ultimosMensajes = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);



// E. KPIs de reservas [2026-08-17]

$stmtRes = $db->prepare("SELECT COUNT(*) FROM reservas WHERE DATE(created_at) = ?");

$stmtRes->execute([$hoy]);

$totalReservasHoy = $stmtRes->fetchColumn();



$stmtResPend = $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'");

$totalReservasPendientes = $stmtResPend->fetchColumn();



$stmtIng = $db->query("SELECT
    COALESCE(SUM(CASE WHEN p.moneda = 'USD' THEN p.monto ELSE 0 END), 0) AS usd,
    COALESCE(SUM(CASE WHEN p.moneda = 'PEN' THEN p.monto ELSE 0 END), 0) AS pen
    FROM pagos p WHERE p.estado = 'pagado'");

$ing = $stmtIng->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/tipo_cambio_helper.php';
$tipoCambio = obtenerTipoCambio($db);

$totalIngresosUsd = (float)$ing['usd'] + (float)$ing['pen'] / $tipoCambio;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profesional | IntiPath Tours</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        
        <div class="header-main">
            <h1>Resumen del <span>Sistema</span></h1>
            <div class="user-info-top" style="display: flex; align-items: center; gap: 10px; color: var(--color-primario-azul); font-weight: 700;">
                <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i> 
                <span><?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <div class="panel-bienvenida" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 6px solid var(--color-secundario-amarillo); margin-bottom: 30px;">
            <div class="bienvenida-texto">
                <h3 style="color: var(--color-primario-azul); margin-bottom: 8px; font-size: 1.4rem;">
                    <i class="fas fa-rocket"></i> ¡Bienvenido, <?php echo explode(' ', ($_SESSION['admin_nombre'] ?? 'Administrador'))[0]; ?>!
                </h3>
                <p style="color: #666; font-size: 1rem;">
                    Gestiona los contenidos de tu agencia desde aquí. Tienes <strong style="color: var(--color-primario-azul);"><?php echo $totalMensajesHoy; ?></strong> consultas nuevas hoy.
                </p>
            </div>
        </div>

        <div class="grid-stats" style="margin-bottom: 25px;">
            <a href="tours.php" style="text-decoration:none;color:inherit;"><div class="card-stat" style="border-left: 5px solid #15305D;"><div class="stat-icon" style="color:#15305D;"><i class="fas fa-map-marked-alt"></i></div><div class="stat-data"><span class="stat-titulo">Tours</span><span class="stat-numero"><?= (int)$totalTours ?></span></div></div></a>
            <a href="admin_paginas.php" style="text-decoration:none;color:inherit;"><div class="card-stat" style="border-left: 5px solid #0f9b9e;"><div class="stat-icon" style="color:#0f9b9e;"><i class="fas fa-file-alt"></i></div><div class="stat-data"><span class="stat-titulo">P�ginas</span><span class="stat-numero"><?= (int)$db->query("SELECT COUNT(*) FROM paginas WHERE activo = 1")->fetchColumn() ?></span></div></div></a>
            <a href="admin_blog.php" style="text-decoration:none;color:inherit;"><div class="card-stat" style="border-left: 5px solid #8e44ad;"><div class="stat-icon" style="color:#8e44ad;"><i class="fas fa-feather-alt"></i></div><div class="stat-data"><span class="stat-titulo">Blog</span><span class="stat-numero"><?= (int)$db->query("SELECT COUNT(*) FROM blog WHERE estado = 'activo'")->fetchColumn() ?></span></div></div></a>
            <a href="reservas.php" style="text-decoration:none;color:inherit;"><div class="card-stat" style="border-left: 5px solid #e67e22;"><div class="stat-icon" style="color:#e67e22;"><i class="fas fa-calendar-check"></i></div><div class="stat-data"><span class="stat-titulo">Reservas pend.</span><span class="stat-numero"><?= (int)$totalReservasPendientes ?></span></div></div></a>
            <a href="configuracion.php" style="text-decoration:none;color:inherit;"><div class="card-stat" style="border-left: 5px solid #27ae60;"><div class="stat-icon" style="color:#27ae60;"><i class="fas fa-cog"></i></div><div class="stat-data"><span class="stat-titulo">Configuraci�n</span><span class="stat-numero"><i class="fas fa-arrow-right"></i></span></div></div></a>
        </div>

        <?php if (isset($_GET['res']) && $_GET['res'] === 'sin_permiso'): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffc107; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> No tienes permisos para acceder a esa sección.
            </div>
        <?php endif; ?>

        <div class="grid-stats">
            
            <div class="card-stat stat-mensajes" style="border-left: 5px solid #3498db;">
                <div class="stat-icon" style="color: #3498db;"><i class="fas fa-envelope-open-text"></i></div>
                <div class="stat-data">
                    <span class="stat-titulo">Mensajes Hoy</span>
                    <span class="stat-numero"><?php echo $totalMensajesHoy; ?></span> 
                </div>
            </div>

            <div class="card-stat stat-tours" style="border-left: 5px solid var(--color-secundario-amarillo);">
                <div class="stat-icon" style="color: var(--color-secundario-amarillo);"><i class="fas fa-map-marked-alt"></i></div>
                <div class="stat-data">
                    <span class="stat-titulo">Tours Activos</span>
                    <span class="stat-numero"><?php echo $totalTours; ?></span> 
                </div>
            </div>

            <div class="card-stat stat-visitas" style="border-left: 5px solid #2ecc71;">
                <div class="stat-icon" style="color: #2ecc71;"><i class="fas fa-chart-line"></i></div>
                <div class="stat-data">
                    <span class="stat-titulo">Visitas Web</span>
                    <span class="stat-numero">0</span> 
                </div>
            </div>

            <div class="card-stat stat-usuarios" style="border-left: 5px solid var(--color-primario-azul);">
                <div class="stat-icon" style="color: var(--color-primario-azul);"><i class="fas fa-users"></i></div>
                <div class="stat-data">
                    <span class="stat-titulo">Equipo</span>
                    <span class="stat-numero"><?php echo $totalColaboradores; ?></span>
                </div>
            </div>
<div class="card-stat" style="border-left: 5px solid #15305D;">

                <div class="stat-icon" style="color: #15305D;"><i class="fas fa-clipboard-list"></i></div>

                <div class="stat-data">

                    <span class="stat-titulo">Reservas Hoy</span>

                    <span class="stat-numero"><?php echo $totalReservasHoy; ?></span>

                </div>

            </div>



            <div class="card-stat" style="border-left: 5px solid #f39c12;">

                <div class="stat-icon" style="color: #f39c12;"><i class="fas fa-clock"></i></div>

                <div class="stat-data">

                    <span class="stat-titulo">Pendientes Pago</span>

                    <span class="stat-numero"><?php echo $totalReservasPendientes; ?></span>

                </div>

            </div>



            <div class="card-stat" style="border-left: 5px solid #1a73e8;">

                <div class="stat-icon" style="color: #1a73e8;"><i class="fas fa-dollar-sign"></i></div>

                <div class="stat-data">

                    <span class="stat-titulo">Ingresos Pagados (USD)</span>

                    <span class="stat-numero">$<?php echo number_format($totalIngresosUsd, 2); ?></span>

                </div>

            </div>

        </div>

        <div class="admin-contenedor" style="margin-top: 40px;">
            <h3 style="color: var(--color-primario-azul); margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-history"></i> Últimas Consultas Recibidas
            </h3>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Email / Contacto</th>
                            <th>Estado</th>
                            <th style="text-align: center;">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($ultimosMensajes)): ?>
                            <?php foreach($ultimosMensajes as $m): ?>
                            <tr>
                                <td style="font-size: 0.85rem; color: #888;">
                                    <?php echo date('d/m/Y', strtotime($m['fecha_creacion'])); ?>
                                </td>
                                <td style="font-weight: 700; color: var(--color-primario-azul);">
                                    <?php echo htmlspecialchars($m['nombre']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($m['email']); ?></td>
                                <td>
                                    <?php if($m['leido'] == 0): ?>
                                        <span class="badge" style="background: #fff3cd; color: #856404; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">PENDIENTE</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #d4edda; color: #155724; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">REVISADO</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="mensajes_ver.php?id=<?php echo $m['id']; ?>" style="color: var(--color-primario-azul); font-size: 1.2rem;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 30px; color: #999;">No hay consultas registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>