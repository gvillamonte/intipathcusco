<?php

/**
 * tours.php
 * Dashboard de gestion de tours - IntiPath Tours
 * Vista moderna con cards, filtros y panel de mensajes.
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('tours');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$bd_error = '';
$categorias = [];
$tours_principales = [];
$caminatas = [];
$hijos_por_padre = [];
$mensajes_recientes = [];
$total_mensajes = 0;
$mensajes_no_leidos = 0;

try {
    $stmt_cat = $db->query("CALL sp_obtener_categorias_tours()");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    $stmt_cat->closeCursor();

    $stmt_tp = $db->query("CALL sp_obtener_tours_principales_admin()");
    $tours_principales = $stmt_tp->fetchAll(PDO::FETCH_ASSOC);
    $stmt_tp->closeCursor();

    $stmt_cam = $db->query("CALL sp_obtener_caminatas_admin()");
    $caminatas = $stmt_cam->fetchAll(PDO::FETCH_ASSOC);
    $stmt_cam->closeCursor();

    foreach ($caminatas as $c) {
        $hijos_por_padre[$c['parent_id']][] = $c;
    }

    $stmt_msg = $db->query("SELECT * FROM mensajes ORDER BY fecha_creacion DESC LIMIT 8");
    $mensajes_recientes = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
    $stmt_msg->closeCursor();

    $total_mensajes = $db->query("SELECT COUNT(*) FROM mensajes")->fetchColumn();
    $mensajes_no_leidos = $db->query("SELECT COUNT(*) FROM mensajes WHERE leido = 0")->fetchColumn();

} catch (PDOException $e) {
    $bd_error = $e->getMessage();
}

$total_tours = count($tours_principales) + count($caminatas);
$total_activos = 0;
foreach ($tours_principales as $t) if (!empty($t['estado']) && $t['estado'] === 'activo') $total_activos++;
foreach ($caminatas as $c) if (!empty($c['estado']) && $c['estado'] === 'activo') $total_activos++;
$total_caminatas = count($caminatas);
$total_principales = count($tours_principales);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Tours | Admin IntiPath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin_tours.css">
    <style>
        :root {
            --p-blue: #15305D;
            --s-orange: #E8AC18;
            --accent: #0f9b9e;
            --danger: #dc2626;
            --success: #16a34a;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        body { background: var(--bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-header h2 { font-weight: 800; color: var(--p-blue); margin: 0; font-size: 1.5rem; }
        .page-header .btn-new { padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 0.9rem; box-shadow: var(--shadow); }

        .bd-error-alert { position: fixed; top: 15px; right: 15px; z-index: 9999; max-width: 480px; box-shadow: var(--shadow-hover); }

        /* Messages Panel */
        .msg-panel { background: var(--card); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; margin-bottom: 24px; }
        .msg-panel-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid var(--border); }
        .msg-panel-header h5 { font-weight: 800; color: var(--p-blue); margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .msg-panel-header .badge-unread { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .msg-panel-header .btn-view-all { font-size: 0.8rem; font-weight: 600; color: var(--accent); text-decoration: none; transition: color 0.2s; }
        .msg-panel-header .btn-view-all:hover { color: var(--p-blue); }

        .msg-list { list-style: none; padding: 0; margin: 0; }
        .msg-item { display: flex; align-items: flex-start; gap: 14px; padding: 14px 22px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; cursor: pointer; }
        .msg-item:last-child { border-bottom: none; }
        .msg-item:hover { background: #f8fafc; }
        .msg-item.unread { background: #fefce8; }
        .msg-item.unread:hover { background: #fef9c3; }

        .msg-avatar { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; color: #fff; flex-shrink: 0; }
        .msg-avatar.bg-1 { background: linear-gradient(135deg, #667eea, #764ba2); }
        .msg-avatar.bg-2 { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .msg-avatar.bg-3 { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .msg-avatar.bg-4 { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .msg-avatar.bg-5 { background: linear-gradient(135deg, #fa709a, #fee140); }

        .msg-content { flex: 1; min-width: 0; }
        .msg-content .msg-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; margin-bottom: 2px; }
        .msg-content .msg-tour { font-size: 0.78rem; color: var(--accent); font-weight: 600; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-content .msg-preview { font-size: 0.78rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
        .msg-meta .msg-time { font-size: 0.7rem; color: #94a3b8; white-space: nowrap; }
        .msg-meta .msg-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc2626; }
        .msg-meta .msg-dot.read { background: transparent; }

        .msg-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .msg-empty i { font-size: 2.5rem; margin-bottom: 10px; color: #cbd5e1; }

        /* Quick View Modal */
        .modal-msg .modal-header { border-bottom: 1px solid var(--border); padding: 16px 24px; }
        .modal-msg .modal-title { font-weight: 800; color: var(--p-blue); font-size: 1.1rem; }
        .modal-msg .modal-body { padding: 24px; }
        .modal-msg .msg-detail-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 4px; }
        .modal-msg .msg-detail-value { font-size: 0.92rem; color: #1e293b; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .modal-msg .msg-detail-value:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .modal-msg .msg-detail-value.mensaje-text { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border); white-space: pre-wrap; }

        /* Dashboard Grid */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
        @media (max-width: 1200px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="admin-wrapper d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <?php if (!empty($bd_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm bd-error-alert">
                <strong><i class="fas fa-database"></i> Error de base de datos</strong>
                <div class="small mt-1"><?= htmlspecialchars($bd_error) ?></div>
                <small class="d-block mt-2 text-muted">Ejecute <code>admin/actualizar_bd.php</code> para sincronizar.</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <main class="tou-main-content p-4 w-100">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-hiking me-2 text-warning"></i>Dashboard de Tours</h2>
                    <p class="text-muted small mb-0">Administre sus paquetes turisticos y responda mensajes</p>
                </div>
                <a href="tour_editar.php" class="btn btn-primary btn-new"><i class="fas fa-plus me-2"></i> Nuevo Tour</a>
            </div>

            <!-- KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card kpi-blue">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="kpi-icon"><i class="fas fa-hiking"></i></div>
                            <div class="kpi-data">
                                <div class="kpi-numero"><?= $total_tours ?></div>
                                <div class="kpi-label">Total Tours</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-green">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="kpi-data">
                                <div class="kpi-numero"><?= $total_activos ?></div>
                                <div class="kpi-label">Activos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-orange">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="kpi-icon"><i class="fas fa-shoe-prints"></i></div>
                            <div class="kpi-data">
                                <div class="kpi-numero"><?= $total_caminatas ?></div>
                                <div class="kpi-label">Caminatas</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-purple">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="kpi-icon"><i class="fas fa-envelope"></i></div>
                            <div class="kpi-data">
                                <div class="kpi-numero"><?= $total_mensajes ?></div>
                                <div class="kpi-label">Mensajes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid: Tours + Mensajes -->
            <div class="dashboard-grid">
                <!-- Left: Filtros + Tours Grid -->
                <div>
                    <!-- Filters -->
                    <div class="filters-bar">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-search me-1"></i>Buscar</label>
                                <input type="search" id="filterSearch" class="form-control" placeholder="Nombre, codigo, categoria...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-folder me-1"></i>Categoria</label>
                                <select id="filterCategoria" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= mb_strtoupper($cat['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-layer-group me-1"></i>Tipo</label>
                                <select id="filterTipo" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="principal">Tour Principal</option>
                                    <option value="caminata">Caminata</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-toggle-on me-1"></i>Estado</label>
                                <select id="filterEstado" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-bars me-1"></i>En Menu</label>
                                <select id="filterEnMenu" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label"><i class="fas fa-coins me-1"></i>Moneda</label>
                                <select id="filterMoneda" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="USD">USD</option>
                                    <option value="PEN">PEN</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tours Grid -->
                    <div id="toursGrid" class="tours-grid">
                        <?php if (empty($tours_principales) && empty($caminatas)): ?>
                            <div class="empty-state" style="grid-column: 1 / -1;">
                                <i class="fas fa-hiking"></i>
                                <h4>No hay tours registrados</h4>
                                <p>Comience creando su primer paquete turistico</p>
                                <a href="tour_editar.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Crear Primer Tour</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tours_principales as $t): $is_active = (!empty($t['estado']) && $t['estado'] === 'activo'); ?>
                                <article class="tour-card" data-id="<?= $t['id'] ?>" data-tipo="principal" data-categoria="<?= $t['id_categoria'] ?>" data-estado="<?= $is_active ? 'activo' : 'inactivo' ?>" data-enmenu="<?= $t['en_menu'] ? 1 : 0 ?>" data-moneda="<?= $t['moneda'] ?>">
                                    <div class="card-img-wrapper">
                                        <img src="../assets/img/tours/<?= $t['imagen_principal'] ?: 'placeholder.jpg' ?>" alt="<?= htmlspecialchars($t['titulo']) ?>" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlMmU4ZjAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQ9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk0YTNiOCIgZm9udC1zaXplPSIxNCIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiPlNpbiBpbWFnZW48L3RleHQ+PC9zdmc+'">
                                        <span class="card-type-badge principal"><i class="fas fa-mountain me-1"></i>Principal</span>
                                        <span class="card-status <?= $is_active ? 'activo' : 'inactivo' ?>"><?= $is_active ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                    <div class="card-body">
                                        <h4 class="card-title"><?= mb_strtoupper($t['titulo_corto'] ?: $t['titulo']) ?></h4>
                                        <div class="card-meta">
                                            <span><i class="far fa-clock"></i> <?= $t['duracion'] ?></span>
                                            <span><i class="fas fa-tag"></i> <?= $t['nombre_cat'] ?></span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="price-info">
                                            <span class="price-label">Precio desde</span>
                                            <span class="price-value"><?= $t['moneda'] ?> <?= number_format($t['precio'], 2) ?></span>
                                        </div>
                                        <div class="actions">
                                            <a href="tour_editar.php?editar=<?= $t['id'] ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" onclick="confirmarEliminar(<?= $t['id'] ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </article>
                                <?php if (!empty($hijos_por_padre[$t['id']])): ?>
                                    <?php foreach ($hijos_por_padre[$t['id']] as $h): $is_active = (!empty($h['estado']) && $h['estado'] === 'activo'); ?>
                                        <article class="tour-card" data-id="<?= $h['id'] ?>" data-tipo="caminata" data-categoria="<?= $h['id_categoria'] ?>" data-estado="<?= $is_active ? 'activo' : 'inactivo' ?>" data-enmenu="<?= $h['en_menu'] ? 1 : 0 ?>" data-moneda="<?= $h['moneda'] ?>">
                                            <div class="card-img-wrapper" style="opacity: 0.95;">
                                                <img src="../assets/img/tours/<?= $h['imagen_principal'] ?: 'placeholder.jpg' ?>" alt="<?= htmlspecialchars($h['titulo']) ?>" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlMmU4ZjAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQ9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzk0YTNiOCIgZm9udC1zaXplPSIxNCIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiPlNpbiBpbWFnZW48L3RleHQ+PC9zdmc+'">
                                                <span class="card-type-badge caminata"><i class="fas fa-shoe-prints me-1"></i>Caminata</span>
                                                <span class="card-status <?= $is_active ? 'activo' : 'inactivo' ?>"><?= $is_active ? 'Activo' : 'Inactivo' ?></span>
                                            </div>
                                            <div class="card-body">
                                                <div style="font-size: 0.7rem; color: #64748b; margin-bottom: 4px;"><i class="fas fa-link me-1"></i>Enlazada a: <?= htmlspecialchars($t['titulo_corto'] ?: $t['titulo']) ?></div>
                                                <h4 class="card-title" style="color: var(--success);"><?= mb_strtoupper($h['titulo_corto'] ?: $h['titulo']) ?></h4>
                                                <div class="card-meta">
                                                    <span><i class="far fa-clock"></i> <?= $h['duracion'] ?></span>
                                                    <span><i class="fas fa-tag"></i> <?= $h['nombre_cat'] ?></span>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <div class="price-info">
                                                    <span class="price-label">Precio desde</span>
                                                    <span class="price-value" style="color: var(--success);"><?= $h['moneda'] ?> <?= number_format($h['precio'], 2) ?></span>
                                                </div>
                                                <div class="actions">
                                                    <a href="tour_editar.php?editar=<?= $h['id'] ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                                    <button type="button" class="btn-action btn-delete" title="Eliminar" onclick="confirmarEliminar(<?= $h['id'] ?>)"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Panel de Mensajes -->
                <div>
                    <div class="msg-panel">
                        <div class="msg-panel-header">
                            <h5><i class="fas fa-envelope"></i> Mensajes Recientes <?php if ($mensajes_no_leidos > 0): ?><span class="badge-unread"><?= $mensajes_no_leidos ?> nuevos</span><?php endif; ?></h5>
                        </div>
                        <?php if (empty($mensajes_recientes)): ?>
                            <div class="msg-empty">
                                <i class="fas fa-inbox"></i>
                                <p>No hay mensajes aun</p>
                            </div>
                        <?php else: ?>
                            <ul class="msg-list">
                                <?php
                                $avatar_classes = ['bg-1', 'bg-2', 'bg-3', 'bg-4', 'bg-5'];
                                $av_idx = 0;
                                foreach ($mensajes_recientes as $msg):
                                    $initials = strtoupper(substr($msg['nombre'], 0, 1));
                                    $avatar_cls = $avatar_classes[$av_idx % 5];
                                    $av_idx++;
                                    $is_unread = $msg['leido'] == 0;
                                    $fecha = date('d M', strtotime($msg['fecha_creacion']));
                                    $hora = date('H:i', strtotime($msg['fecha_creacion']));
                                    $preview = mb_strimwidth($msg['mensaje'] ?? '', 0, 60, '...');
                                ?>
                                    <li class="msg-item <?= $is_unread ? 'unread' : '' ?>" onclick="verMensaje(<?= htmlspecialchars(json_encode($msg, JSON_UNESCAPED_UNICODE)) ?>)">
                                        <div class="msg-avatar <?= $avatar_cls ?>"><?= $initials ?></div>
                                        <div class="msg-content">
                                            <div class="msg-name"><?= htmlspecialchars($msg['nombre']) ?></div>
                                            <div class="msg-tour"><i class="fas fa-mountain me-1"></i><?= htmlspecialchars($msg['tour_interes'] ?: 'Sin tour especifico') ?></div>
                                            <div class="msg-preview"><?= htmlspecialchars($preview) ?></div>
                                        </div>
                                        <div class="msg-meta">
                                            <span class="msg-time"><?= $fecha ?></span>
                                            <span class="msg-dot <?= $is_unread ? '' : 'read' ?>"></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="py-3"></div>
        </main>
    </div>

    <!-- Modal Vista Rapida de Mensaje -->
    <div class="modal fade modal-msg" id="modalMensaje" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Detalle del Mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalMsgBody">
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/admin_tours.js"></script>
    <script>
    function verMensaje(msg) {
        var body = document.getElementById('modalMsgBody');
        var fecha = new Date(msg.fecha_creacion);
        var fechaFmt = fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'long', year: 'numeric' });
        var horaFmt = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

        body.innerHTML = ''
            + '<div class="msg-detail-label">Nombre</div>'
            + '<div class="msg-detail-value"><i class="fas fa-user me-2 text-primary"></i>' + msg.nombre + '</div>'
            + '<div class="msg-detail-label">Email</div>'
            + '<div class="msg-detail-value"><i class="fas fa-envelope me-2 text-primary"></i>' + (msg.email || '-') + '</div>'
            + '<div class="msg-detail-label">Telefono</div>'
            + '<div class="msg-detail-value"><i class="fas fa-phone me-2 text-success"></i>' + (msg.telefono || '-') + '</div>'
            + '<div class="msg-detail-label">Pais</div>'
            + '<div class="msg-detail-value"><i class="fas fa-globe me-2 text-warning"></i>' + (msg.pais || '-') + '</div>'
            + '<div class="msg-detail-label">Tour de Interes</div>'
            + '<div class="msg-detail-value"><i class="fas fa-mountain me-2" style="color:var(--accent);"></i><strong>' + (msg.tour_interes || 'Sin especificar') + '</strong></div>'
            + '<div class="msg-detail-label">Fecha de Viaje</div>'
            + '<div class="msg-detail-value"><i class="fas fa-calendar me-2 text-danger"></i>' + (msg.fecha_viaje || '-') + '</div>'
            + '<div class="msg-detail-label">Personas</div>'
            + '<div class="msg-detail-value"><i class="fas fa-users me-2 text-info"></i>' + (msg.adultos || 0) + ' adultos, ' + (msg.ninos || 0) + ' ninos</div>'
            + '<div class="msg-detail-label">Mensaje</div>'
            + '<div class="msg-detail-value mensaje-text">' + (msg.mensaje || 'Sin mensaje') + '</div>'
            + '<div class="msg-detail-label">Recibido</div>'
            + '<div class="msg-detail-value"><i class="fas fa-clock me-2"></i>' + fechaFmt + ' a las ' + horaFmt + '</div>';

        var modal = new bootstrap.Modal(document.getElementById('modalMensaje'));
        modal.show();
    }

    document.querySelectorAll('.tour-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('a, button')) return;
            var id = this.getAttribute('data-id');
            if (id) window.location.href = 'tour_editar.php?editar=' + id;
        });
    });
    </script>
</body>
</html>
