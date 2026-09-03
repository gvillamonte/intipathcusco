<?php
/**
 * actualizar_bd.php
 * Ejecuta sp_tours_admin.sql + actualizar_resenas.sql contra la BD actual,
 * desde el panel. Botón para eliminar ambos archivos SQL del servidor.
 * Idempotente: ejecutar varias veces es seguro.
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('tours');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// --- Archivos SQL a ejecutar ---
$raiz = realpath(__DIR__ . '/..');
$sql_files = [
    'sp_tours_admin.sql'     => $raiz . DIRECTORY_SEPARATOR . 'sp_tours_admin.sql',
    'actualizar_resenas.sql' => $raiz . DIRECTORY_SEPARATOR . 'actualizar_resenas.sql',
    'actualizar_bancos.sql'  => $raiz . DIRECTORY_SEPARATOR . 'actualizar_bancos.sql',
];

// --- Estado de objetos tours (routines + vistas) ---
$objetos_esperados = [
    'vista_tours_admin', 'sp_obtener_tour_editar', 'sp_obtener_categorias_tours',
    'sp_obtener_tours_principales_admin', 'sp_obtener_caminatas_admin',
    'sp_obtener_confianza_admin', 'sp_obtener_tours_padre', 'sp_obtener_archivos_tour',
    'sp_obtener_precio_tour', 'sp_obtener_tours_activos_admin', 'sp_eliminar_archivo_tour',
];
$existentes = [];
if ($db) {
    $st = $db->query("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE'");
    $existentes = $st->fetchAll(PDO::FETCH_COLUMN);
    $st->closeCursor();
    $st = $db->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()");
    $existentes = array_merge($existentes, $st->fetchAll(PDO::FETCH_COLUMN));
    $st->closeCursor();
}

// --- Estado de infraestructura reseñas ---
$resenas_status = ['tabla' => false, 'trustindex_id' => false, 'fuente' => false, 'extra_json' => false, 'reviews_row' => false];
if ($db) {
    try {
        $st = $db->query("SHOW TABLES LIKE 'resenas'");
        $resenas_status['tabla'] = (bool)$st->fetch();
        $st->closeCursor();
        if ($resenas_status['tabla']) {
            $st = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resenas' AND COLUMN_NAME IN ('trustindex_id','fuente')");
            $cols = $st->fetchAll(PDO::FETCH_COLUMN);
            $st->closeCursor();
            $resenas_status['trustindex_id'] = in_array('trustindex_id', $cols);
            $resenas_status['fuente'] = in_array('fuente', $cols);
        }
        $st = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'secciones_index' AND COLUMN_NAME = 'extra_json'");
        $resenas_status['extra_json'] = (bool)$st->fetch();
        $st->closeCursor();
        if ($resenas_status['extra_json']) {
            $st = $db->query("SELECT COUNT(*) FROM secciones_index WHERE seccion = 'reviews'");
            $resenas_status['reviews_row'] = ((int)$st->fetchColumn() > 0);
            $st->closeCursor();
        }
    } catch (PDOException $e) { /* ignorar */ }
}

// --- Estado de infraestructura bancos ---
$bancos_status = ['tabla' => false, 'sp_obtener_bancos' => false, 'datos_migrados' => false];
if ($db) {
    try {
        $st = $db->query("SHOW TABLES LIKE 'bancos'");
        $bancos_status['tabla'] = (bool)$st->fetch();
        $st->closeCursor();
        if ($bancos_status['tabla']) {
            $st = $db->query("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE TABLE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_NAME = 'obtener_bancos'");
            $bancos_status['sp_obtener_bancos'] = (bool)$st->fetch();
            $st->closeCursor();
            $st = $db->query("SELECT COUNT(*) FROM bancos WHERE activo = 1");
            $bancos_status['datos_migrados'] = ((int)$st->fetchColumn() > 0);
            $st->closeCursor();
        }
    } catch (PDOException $e) { /* ignorar */ }
}

// --- 1. EJECUCIÓN SQL (solo por POST) ---
$resultado = [];
$ejecutado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'ejecutar') {
    $ejecutado = true;
    foreach ($sql_files as $nombre => $ruta) {
        if (!file_exists($ruta)) {
            $resultado[] = ['tipo' => 'skip', 'sql' => $nombre, 'detalle' => 'No subido en esta ronda — omitido (sube solo el SQL de la actualización que necesitas)'];
            continue;
        }
        $sql = file_get_contents($ruta);
        $sql = preg_replace('/^DELIMITER\s+[^\r\n]+[\r\n]?/mi', '', $sql);
        $sentencias = array_map('trim', explode('//', $sql));
        foreach ($sentencias as $st) {
            $solo_comentario = trim(preg_replace('/^\s*--.*$/m', '', $st));
            if ($solo_comentario === '') continue;
            try {
                $db->exec($st);
                $resultado[] = ['tipo' => 'ok', 'sql' => substr(preg_replace('/\s+/', ' ', $st), 0, 100), 'detalle' => 'OK'];
            } catch (PDOException $e) {
                $resultado[] = ['tipo' => 'error', 'sql' => substr(preg_replace('/\s+/', ' ', $st), 0, 100), 'detalle' => $e->getMessage()];
            }
        }
    }
    // Refrescar objetos tours
    $existentes = [];
    $st = $db->query("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE'");
    $existentes = $st->fetchAll(PDO::FETCH_COLUMN);
    $st->closeCursor();
    $st = $db->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()");
    $existentes = array_merge($existentes, $st->fetchAll(PDO::FETCH_COLUMN));
    $st->closeCursor();
    // Refrescar estado reseñas
    try {
        $st = $db->query("SHOW TABLES LIKE 'resenas'");
        $resenas_status['tabla'] = (bool)$st->fetch();
        $st->closeCursor();
        if ($resenas_status['tabla']) {
            $st = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resenas' AND COLUMN_NAME IN ('trustindex_id','fuente')");
            $cols = $st->fetchAll(PDO::FETCH_COLUMN);
            $st->closeCursor();
            $resenas_status['trustindex_id'] = in_array('trustindex_id', $cols);
            $resenas_status['fuente'] = in_array('fuente', $cols);
        }
        $st = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'secciones_index' AND COLUMN_NAME = 'extra_json'");
        $resenas_status['extra_json'] = (bool)$st->fetch();
        $st->closeCursor();
        if ($resenas_status['extra_json']) {
            $st = $db->query("SELECT COUNT(*) FROM secciones_index WHERE seccion = 'reviews'");
            $resenas_status['reviews_row'] = ((int)$st->fetchColumn() > 0);
            $st->closeCursor();
        }
    } catch (PDOException $e) { /* ignorar */ }
    // Refrescar estado bancos
    try {
        $st = $db->query("SHOW TABLES LIKE 'bancos'");
        $bancos_status['tabla'] = (bool)$st->fetch();
        $st->closeCursor();
        if ($bancos_status['tabla']) {
            $st = $db->query("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE TABLE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_NAME = 'obtener_bancos'");
            $bancos_status['sp_obtener_bancos'] = (bool)$st->fetch();
            $st->closeCursor();
            $st = $db->query("SELECT COUNT(*) FROM bancos WHERE activo = 1");
            $bancos_status['datos_migrados'] = ((int)$st->fetchColumn() > 0);
            $st->closeCursor();
        }
    } catch (PDOException $e) { /* ignorar */ }
}

// --- 2. ELIMINAR ARCHIVOS SQL (solo por POST) ---
$limpiar_resultado = [];
$limpiado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'limpiar') {
    $limpiado = true;
    foreach ($sql_files as $nombre => $ruta) {
        if (!is_file($ruta)) {
            $limpiar_resultado[] = ['tipo' => 'skip', 'archivo' => $nombre, 'detalle' => 'No existe en el servidor'];
            continue;
        }
        $size = filesize($ruta);
        if (@unlink($ruta)) {
            $limpiar_resultado[] = ['tipo' => 'ok', 'archivo' => $nombre, 'detalle' => 'Eliminado (' . number_format($size) . ' bytes)'];
        } else {
            $limpiar_resultado[] = ['tipo' => 'error', 'archivo' => $nombre, 'detalle' => 'No se pudo eliminar (verificar permisos)'];
        }
    }
}

// ¿Quedan archivos SQL en el servidor?
$archivos_sql_quedan = 0;
foreach ($sql_files as $nombre => $ruta) {
    if (is_file($ruta)) $archivos_sql_quedan++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Base de Datos | IntiPath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper d-flex">
    <?php include '../includes/sidebar.php'; ?>
    <main class="tou-main-content p-4 w-100">
        <h2 class="fw-bold text-dark mb-1"><i class="fas fa-database me-2"></i>Actualizar Base de Datos</h2>
        <p class="text-muted small">Sincroniza stored procedures, vistas y estructura de reseñas con la BD. Idempotente: ejecutar varias veces no genera errores.</p>

        <div class="row g-4">

            <!-- COLUMNA IZQUIERDA -->
            <div class="col-md-7">

                <!-- ESTADO: TOURS -->
                <div class="tou-card bg-white p-4 shadow-sm mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-list-check me-1"></i> Stored procedures y vistas (Tours)</h6>
                    <table class="table table-sm align-middle mb-3">
                        <thead class="table-dark">
                            <tr><th>Objeto</th><th class="text-center" width="110">Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objetos_esperados as $nombre): ?>
                            <tr>
                                <td class="small"><code><?= htmlspecialchars($nombre) ?></code></td>
                                <td class="text-center">
                                    <?php if (in_array($nombre, $existentes, true)): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-xmark me-1"></i>Falta</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ESTADO: RESEÑAS -->
                <div class="tou-card bg-white p-4 shadow-sm mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-star me-1"></i> Infraestructura de Reseñas</h6>
                    <table class="table table-sm align-middle mb-3">
                        <thead class="table-dark">
                            <tr><th>Elemento</th><th class="text-center" width="110">Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $resenas_items = [
                                'Tabla resenas'         => $resenas_status['tabla'],
                                'Columna trustindex_id' => $resenas_status['trustindex_id'],
                                'Columna fuente'        => $resenas_status['fuente'],
                                'Columna extra_json (secciones_index)' => $resenas_status['extra_json'],
                                'Fila reviews (secciones_index)'      => $resenas_status['reviews_row'],
                            ];
                            foreach ($resenas_items as $label => $ok): ?>
                            <tr>
                                <td class="small"><?= $label ?></td>
                                <td class="text-center">
                                    <?php if ($ok): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-xmark me-1"></i>Falta</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ESTADO: BANCOS -->
                <div class="tou-card bg-white p-4 shadow-sm mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-university me-1"></i> Infraestructura de Bancos</h6>
                    <table class="table table-sm align-middle mb-3">
                        <thead class="table-dark">
                            <tr><th>Elemento</th><th class="text-center" width="110">Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $bancos_items = [
                                'Tabla bancos'           => $bancos_status['tabla'],
                                'SP obtener_bancos()'    => $bancos_status['sp_obtener_bancos'],
                                'Datos migrados (1+ banco activo)' => $bancos_status['datos_migrados'],
                            ];
                            foreach ($bancos_items as $label => $ok): ?>
                            <tr>
                                <td class="small"><?= $label ?></td>
                                <td class="text-center">
                                    <?php if ($ok): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Existe</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-xmark me-1"></i>Falta</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BOTÓN EJECUTAR -->
                <div class="tou-card bg-white p-4 shadow-sm mb-4">
                    <form method="post" onsubmit="return confirm('Se ejecutarán todos los archivos .sql que hayas subido a la raíz del sitio.\n\nLos que no estén se omiten (no son errores).\n\n¿Continuar?');">
                        <input type="hidden" name="accion" value="ejecutar">
                        <button type="submit" class="btn btn-primary rounded-pill px-4" <?= $ejecutado ? 'disabled' : '' ?>>
                            <i class="fas fa-play me-1"></i> Ejecutar sincronización
                        </button>
                        <a href="tours.php" class="btn btn-outline-secondary rounded-pill px-4 ms-2">Volver a Tours</a>
                    </form>
                </div>

                <!-- RESULTADO EJECUCIÓN -->
                <?php if (!empty($resultado)): ?>
                <div class="tou-card bg-white p-4 mt-4 shadow-sm" id="resultado">
                    <h6 class="fw-bold mb-3"><i class="fas fa-scroll me-1"></i> Registro de ejecución</h6>
                    <?php $errores = array_filter($resultado, function ($r) { return $r['tipo'] === 'error'; }); ?>
                    <?php $omitidos = array_filter($resultado, function ($r) { return $r['tipo'] === 'skip'; }); ?>
                    <?php if (count($errores) === 0): ?>
                        <div class="alert alert-success py-2">
                            <i class="fas fa-circle-check me-1"></i>
                            Todas las sentencias ejecutadas correctamente.
                            <?php if (count($omitidos) > 0): ?>
                                <span class="text-muted">(<strong><?= count($omitidos) ?></strong> archivo(s) omitido(s) por no estar subido(s) en esta ronda).</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger py-2"><i class="fas fa-circle-exclamation me-1"></i> <?= count($errores) ?> sentencia(s) con error real. Los archivos que faltan se omiten (no son errores).</div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th width="60">Resultado</th><th>Sentencia</th><th>Detalle</th></tr></thead>
                            <tbody>
                                <?php foreach ($resultado as $r): ?>
                                <tr>
                                    <td>
                                        <?php if ($r['tipo'] === 'ok'): ?>
                                            <span class="badge bg-success">OK</span>
                                        <?php elseif ($r['tipo'] === 'skip'): ?>
                                            <span class="badge bg-secondary">SKIP</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ERROR</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><code><?= htmlspecialchars($r['sql']) ?>...</code></td>
                                    <td class="small <?= $r['tipo'] === 'error' ? 'text-danger' : '' ?>"><code><?= htmlspecialchars($r['detalle']) ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ELIMINAR ARCHIVOS SQL -->
                <?php if ($archivos_sql_quedan > 0): ?>
                <div class="tou-card bg-white p-4 mt-4 shadow-sm" style="border-left:4px solid #dc3545;">
                    <h6 class="fw-bold mb-2 text-danger"><i class="fas fa-trash-can me-1"></i> Seguridad: eliminar archivos SQL</h6>
                    <p class="small text-muted mb-3">
                        Después de ejecutar, estos archivos <strong>no deberían quedar en el servidor</strong> (exponen la estructura de la BD). Elimínalos cuando ya no los necesites.
                    </p>
                    <?php
                    $sql_disponibles = [];
                    foreach ($sql_files as $nombre => $ruta) {
                        if (is_file($ruta)) {
                            $sql_disponibles[] = '<code>' . htmlspecialchars($nombre) . '</code> (' . number_format(filesize($ruta)) . ' B)';
                        }
                    }
                    ?>
                    <p class="small mb-3">Archivos detectados: <?= implode(', ', $sql_disponibles) ?></p>
                    <form method="post" onsubmit="return confirm('¿Eliminar los archivos SQL del servidor?\n\nEsta acción no se puede deshacer. Si necesitas ejecutar de nuevo, tendrás que subirlos por FTP.');">
                        <input type="hidden" name="accion" value="limpiar">
                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                            <i class="fas fa-trash-can me-1"></i> Eliminar archivos SQL del servidor
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- RESULTADO LIMPIEZA -->
                <?php if (!empty($limpiar_resultado)): ?>
                <div class="tou-card bg-white p-4 mt-4 shadow-sm" id="limpieza">
                    <h6 class="fw-bold mb-3"><i class="fas fa-broom me-1"></i> Registro de limpieza</h6>
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th width="80">Resultado</th><th>Archivo</th><th>Detalle</th></tr></thead>
                        <tbody>
                            <?php foreach ($limpiar_resultado as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r['tipo'] === 'ok'): ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php elseif ($r['tipo'] === 'skip'): ?>
                                        <span class="badge bg-secondary">SKIP</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ERROR</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><code><?= htmlspecialchars($r['archivo']) ?></code></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['detalle']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div>

            <!-- COLUMNA DERECHA: INSTRUCCIONES -->
            <div class="col-md-5">
                <div class="tou-card bg-white p-4 shadow-sm">
                    <h6 class="fw-bold mb-3"><i class="fas fa-circle-info me-1"></i> Instrucciones</h6>
                    <ol class="small text-muted mb-0">
                        <li class="mb-2">Revisa el <strong>Estado</strong>: cualquier objeto marcado <span class="badge bg-danger">Falta</span> puede causar errores en el sitio.</li>
                        <li class="mb-2">Sube a la <strong>raíz del sitio</strong> (junto a index.php) <strong>solo el SQL de la actualización que necesitas</strong>. No hace falta subir todos.</li>
                        <li class="mb-2">Pulsa <strong>Ejecutar sincronización</strong>. Los archivos que faltan se omiten (no son errores).</li>
                        <li class="mb-2">Después de ejecutar, usa <strong>Eliminar archivos SQL</strong> por seguridad.</li>
                        <li class="mb-2">El script es idempotente: ejecutarlo de nuevo no genera errores.</li>
                    </ol>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
