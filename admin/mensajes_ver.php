<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('mensajes');

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: mensajes.php"); exit; }

// Obtener mensaje
$stmt = $db->prepare("SELECT * FROM mensajes WHERE id = ?");
$stmt->execute([$id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$msg) { header("Location: mensajes.php"); exit; }

// Marcar como leído automáticamente
if (!$msg['leido']) {
    $db->prepare("UPDATE mensajes SET leido = 1 WHERE id = ?")->execute([$id]);
    $msg['leido'] = 1;
}

// Acciones
$accion = $_GET['accion'] ?? '';
if ($accion === 'toggle_leido') {
    $nuevo = $msg['leido'] ? 0 : 1;
    $db->prepare("UPDATE mensajes SET leido = ? WHERE id = ?")->execute([$nuevo, $id]);
    header("Location: mensajes_ver.php?id=$id");
    exit;
}
if ($accion === 'eliminar') {
    $db->prepare("DELETE FROM mensajes WHERE id = ?")->execute([$id]);
    header("Location: mensajes.php?msg=eliminado");
    exit;
}

$fecha = date('d/m/Y', strtotime($msg['fecha_creacion']));
$hora = date('h:i A', strtotime($msg['fecha_creacion']));
$dias = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo',
];
$meses = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
    'September' => 'Setiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre',
];
$fecha_legible = $dias[date('l', strtotime($msg['fecha_creacion']))] . ', ' . date('j', strtotime($msg['fecha_creacion'])) . ' de ' . $meses[date('F', strtotime($msg['fecha_creacion']))] . ' del ' . date('Y', strtotime($msg['fecha_creacion']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensaje de <?= htmlspecialchars($msg['nombre']) ?> | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --azul: #15305D; --amarillo: #E8AC18; --gris-bg: #f4f6f7;
            --exito: #27ae60; --peligro: #e74c3c; --info: #3498db;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--gris-bg); font-family: 'Inter', sans-serif; color: #333; }
        .admin-wrapper { display: flex; min-height: 100vh; }

        .main-content { flex: 1; padding: 35px 40px; max-width: 1100px; }

        /* Encabezado */
        .top-bar { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; color: #555; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s; }
        .btn-back:hover { border-color: #bbb; background: #f8f8f8; }
        .page-title { font-size: 1.3rem; font-weight: 700; color: var(--azul); margin: 0; }

        /* Barra de acciones */
        .action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }
        .action-bar a, .action-bar button { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; }
        .action-bar .btn-reply { background: var(--info); color: #fff; }
        .action-bar .btn-reply:hover { background: #2980b9; }
        .action-bar .btn-toggle { background: <?= $msg['leido'] ? '#f0f0f0' : 'var(--amarillo)' ?>; color: <?= $msg['leido'] ? '#666' : '#fff' ?>; }
        .action-bar .btn-toggle:hover { opacity: 0.85; }
        .action-bar .btn-delete { background: var(--peligro); color: #fff; }
        .action-bar .btn-delete:hover { background: #c0392b; }

        /* Grid de tarjetas */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 22px; }
        @media (max-width: 850px) { .grid-2 { grid-template-columns: 1fr; } }

        .card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); overflow: hidden; }
        .card-header { padding: 16px 20px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f0f0f0; }
        .card-body { padding: 20px; }

        /* Info del cliente */
        .client-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
        .client-row:last-child { border-bottom: none; }
        .client-row .icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.9rem; flex-shrink: 0; }
        .client-row .label { font-size: 0.75rem; color: #999; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; }
        .client-row .value { font-size: 0.95rem; font-weight: 600; color: #222; word-break: break-all; }
        .client-row a.value { color: var(--info); text-decoration: none; }
        .client-row a.value:hover { text-decoration: underline; }

        .icon-email { background: var(--info); }
        .icon-phone { background: var(--exito); }
        .icon-user { background: var(--azul); }
        .icon-globe { background: var(--amarillo); color: #333 !important; }

        /* Detalles del viaje */
        .travel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .travel-item { background: var(--gris-bg); border-radius: 10px; padding: 14px; text-align: center; }
        .travel-item .num { font-size: 1.4rem; font-weight: 800; color: var(--azul); }
        .travel-item .lbl { font-size: 0.72rem; color: #999; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 3px; }
        .travel-item .detail { font-size: 0.9rem; font-weight: 600; color: #444; margin-top: 2px; }

        /* Mensaje */
        .msg-card { margin-bottom: 22px; }
        .msg-content { font-size: 0.95rem; line-height: 1.7; color: #444; white-space: pre-wrap; }
        .msg-content:empty::before { content: '(Sin mensaje)'; color: #bbb; font-style: italic; }
        .msg-meta { display: flex; gap: 20px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0f0f0; font-size: 0.8rem; color: #999; }
        .msg-meta i { margin-right: 5px; }
        .badge-leido { display: inline-flex; align-items: center; gap: 5px; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-leido.leido { background: #e8f8f0; color: var(--exito); }
        .badge-leido.no-leido { background: #fff8e1; color: #f57f17; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <!-- Encabezado -->
        <div class="top-bar">
            <a href="mensajes.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
            <h1 class="page-title"><i class="fas fa-envelope-open-text" style="margin-right:8px"></i>Mensaje</h1>
        </div>

        <!-- Acciones -->
        <div class="action-bar">
            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Respuesta%20IntiPath%20Tours" class="btn-reply">
                <i class="fas fa-reply"></i> Responder
            </a>
            <a href="mensajes_ver.php?id=<?= $id ?>&accion=toggle_leido" class="btn-toggle">
                <i class="fas <?= $msg['leido'] ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                Marcar como <?= $msg['leido'] ? 'no leído' : 'leído' ?>
            </a>
            <a href="mensajes_ver.php?id=<?= $id ?>&accion=eliminar" class="btn-delete" onclick="return confirm('¿Eliminar este mensaje permanentemente?')">
                <i class="fas fa-trash-alt"></i> Eliminar
            </a>
        </div>

        <!-- Grid info -->
        <div class="grid-2">
            <!-- Info del cliente -->
            <div class="card">
                <div class="card-header" style="color:var(--azul)"><i class="fas fa-user-circle"></i> Información del Cliente</div>
                <div class="card-body">
                    <div class="client-row">
                        <div class="icon icon-user"><i class="fas fa-user"></i></div>
                        <div>
                            <div class="label">Nombre completo</div>
                            <div class="value"><?= htmlspecialchars($msg['nombre']) ?></div>
                        </div>
                    </div>
                    <div class="client-row">
                        <div class="icon icon-email"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="label">Correo electrónico</div>
                            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="value"><?= htmlspecialchars($msg['email']) ?></a>
                        </div>
                    </div>
                    <div class="client-row">
                        <div class="icon icon-phone"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <div class="label">Teléfono / WhatsApp</div>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $msg['telefono']) ?>" target="_blank" class="value"><?= htmlspecialchars($msg['telefono']) ?></a>
                        </div>
                    </div>
                    <?php if (!empty($msg['pais'])): ?>
                    <div class="client-row">
                        <div class="icon icon-globe"><i class="fas fa-globe-americas"></i></div>
                        <div>
                            <div class="label">País</div>
                            <div class="value"><?= htmlspecialchars($msg['pais']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalles del viaje -->
            <div class="card">
                <div class="card-header" style="color:var(--azul)"><i class="fas fa-plane-departure"></i> Detalles del Viaje</div>
                <div class="card-body">
                    <div class="travel-grid">
                        <?php if (!empty($msg['tour_interes'])): ?>
                        <div class="travel-item" style="grid-column:1/-1">
                            <div class="lbl">Tour de interés</div>
                            <div class="detail"><?= htmlspecialchars($msg['tour_interes']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="travel-item">
                            <div class="lbl">Adultos</div>
                            <div class="num"><?= (int)$msg['adultos'] ?></div>
                        </div>
                        <div class="travel-item">
                            <div class="lbl">Niños</div>
                            <div class="num"><?= (int)$msg['ninos'] ?></div>
                        </div>
                        <?php if (!empty($msg['fecha_viaje'])): ?>
                        <div class="travel-item" style="grid-column:1/-1">
                            <div class="lbl">Fecha de viaje</div>
                            <div class="detail"><?= date('d/m/Y', strtotime($msg['fecha_viaje'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje -->
        <div class="card msg-card">
            <div class="card-header" style="color:var(--azul)"><i class="fas fa-comment-dots"></i> Mensaje del Cliente</div>
            <div class="card-body">
                <div class="msg-content"><?= htmlspecialchars($msg['mensaje']) ?></div>
                <div class="msg-meta">
                    <span><i class="far fa-calendar-alt"></i> <?= $fecha_legible ?></span>
                    <span><i class="far fa-clock"></i> <?= $hora ?></span>
                    <span>
                        <span class="badge-leido <?= $msg['leido'] ? 'leido' : 'no-leido' ?>">
                            <i class="fas <?= $msg['leido'] ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                            <?= $msg['leido'] ? 'Leído' : 'No leído' ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Atajo para responder -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:5px">
            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Respuesta%20IntiPath%20Tours&body=Hola%20<?= urlencode($msg['nombre']) ?>%2C" class="btn-reply" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;font-weight:600;font-size:0.9rem;text-decoration:none;background:var(--info);color:#fff;transition:0.2s">
                <i class="fas fa-paper-plane"></i> Responder por correo
            </a>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $msg['telefono']) ?>?text=Hola%20<?= urlencode($msg['nombre']) ?>%2C%20recibimos%20tu%20consulta%20desde%20IntiPath%20Tours." target="_blank" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;font-weight:600;font-size:0.9rem;text-decoration:none;background:#25d366;color:#fff;transition:0.2s">
                <i class="fab fa-whatsapp"></i> Responder por WhatsApp
            </a>
        </div>
    </main>
</div>
</body>
</html>
