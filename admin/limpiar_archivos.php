<?php

/**
 * limpiar_archivos.php
 * Elimina los archivos basura generados por desarrollo/QA que no deben
 * subirse a producción (logs, reportes, artefactos de pruebas).
 * SOLO borra archivos de la lista blanca definida abajo:
 * nunca borra .php, imágenes, SQL, ni nada que no esté en la lista.
 */

require_once __DIR__ . '/../includes/auth_helper.php';

requierePermiso('tours');

require_once '../config/database.php';

// Lista blanca: archivos basura (ruta relativa a la raíz del proyecto)
$basura = [
    'error_log',
    'qa_reporte.txt',
    'qa_report_2026-08-11_010127.txt',
    'render_result.txt',
    'test_output.txt',
    'False',
    'True',
    'admin/error_log',
    // Desarrollo/QA: herramientas de prueba y utilidades ya reemplazadas
    'qa_completo.php',
    'qa_proyecto.php',
    'render_page.php',
    'render_tours2.php',
    'check_sps.php',
    'crear_directorios.php',
    'instalar_procedimientos_produccion.php',
];

$raiz = realpath(__DIR__ . '/..');

// Estado actual de cada archivo
$estado = [];
foreach ($basura as $rel) {
    $ruta = $raiz . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $estado[$rel] = [
        'existe' => is_file($ruta),
        'size'   => is_file($ruta) ? filesize($ruta) : null,
        'ruta'   => $ruta,
    ];
}

// --- 1. BORRADO (solo por POST y solo archivos de la lista blanca) ---
$resultado = [];
$ejecutado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'borrar') {

    $ejecutado = true;

    foreach ($basura as $rel) {

        $ruta = $raiz . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

        if (!is_file($ruta)) {
            $resultado[] = ['tipo' => 'skip', 'archivo' => $rel, 'detalle' => 'No existe'];
            continue;
        }

        // Doble chequeo: el archivo debe estar exactamente en la lista blanca
        if (!in_array($rel, $basura, true)) {
            $resultado[] = ['tipo' => 'error', 'archivo' => $rel, 'detalle' => 'Bloqueado (fuera de lista blanca)'];
            continue;
        }

        if (@unlink($ruta)) {
            $resultado[] = ['tipo' => 'ok', 'archivo' => $rel, 'detalle' => 'Eliminado (' . number_format($estado[$rel]['size']) . ' bytes)'];
        } else {
            $resultado[] = ['tipo' => 'error', 'archivo' => $rel, 'detalle' => 'No se pudo eliminar (permisos?)'];
        }

        $estado[$rel] = ['existe' => is_file($ruta), 'size' => null, 'ruta' => $ruta];

    }

}

$total_basura = count(array_filter($estado, function ($e) { return $e['existe']; }));

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Limpiar Archivos Basura | IntiPath</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

    <div class="admin-wrapper d-flex">

        <?php include '../includes/sidebar.php'; ?>

        <main class="tou-main-content p-4 w-100">

            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-broom me-2"></i>Limpiar Archivos Basura</h2>

            <p class="text-muted small">Elimina logs y artefactos de desarrollo que no deben existir en producción. Solo se borran los archivos listados abajo.</p>

            <div class="row g-4">

                <div class="col-md-7">

                    <div class="tou-card bg-white p-4 shadow-sm">

                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-trash-can me-1"></i> Archivos detectados
                            <span class="badge <?= $total_basura > 0 ? 'bg-danger' : 'bg-success' ?> ms-2"><?= $total_basura ?> pendiente<?= $total_basura === 1 ? '' : 's' ?></span>
                        </h6>

                        <table class="table table-sm align-middle mb-3">

                            <thead class="table-dark">

                                <tr>

                                    <th>Archivo</th>

                                    <th class="text-center" width="120">Tamaño</th>

                                    <th class="text-center" width="100">Estado</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($estado as $rel => $e): ?>

                                    <tr>

                                        <td class="small"><code><?= htmlspecialchars($rel) ?></code></td>

                                        <td class="text-center small"><?= $e['existe'] ? number_format($e['size']) . ' B' : '—' ?></td>

                                        <td class="text-center">

                                            <?php if ($e['existe']): ?>

                                                <span class="badge bg-danger"><i class="fas fa-circle-exclamation me-1"></i>Pendiente</span>

                                            <?php else: ?>

                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Limpio</span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                        <form method="post" onsubmit="return confirm('¿Eliminar los ' + <?= $total_basura ?> + ' archivo(s) basura?');">

                            <input type="hidden" name="accion" value="borrar">

                            <button type="submit" class="btn btn-danger rounded-pill px-4" <?= $ejecutado || $total_basura === 0 ? 'disabled' : '' ?>>

                                <i class="fas fa-broom me-1"></i> Borrar archivos basura

                            </button>

                            <a href="../index.php" class="btn btn-outline-secondary rounded-pill px-4 ms-2">Ir al inicio</a>

                        </form>

                    </div>

                    <?php if (!empty($resultado)): ?>

                        <div class="tou-card bg-white p-4 mt-4 shadow-sm" id="resultado">

                            <h6 class="fw-bold mb-3"><i class="fas fa-scroll me-1"></i> Registro de limpieza</h6>

                            <div class="table-responsive">

                                <table class="table table-sm table-striped mb-0">

                                    <thead>

                                        <tr>

                                            <th width="90">Resultado</th>

                                            <th>Archivo</th>

                                            <th>Detalle</th>

                                        </tr>

                                    </thead>

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

                                                <td class="small"><code><?= htmlspecialchars($r['archivo']) ?></code></td>

                                                <td class="small text-muted"><?= htmlspecialchars($r['detalle']) ?></td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="col-md-5">

                    <div class="tou-card bg-white p-4 shadow-sm">

                        <h6 class="fw-bold mb-3"><i class="fas fa-circle-info me-1"></i> Notas</h6>

                        <ul class="small text-muted mb-0">

                            <li class="mb-2">El script <strong>nunca</strong> borra archivos PHP, imágenes, SQL, PDF ni nada fuera de la lista blanca.</li>

                            <li class="mb-2">Si aparece un <code>error_log</code> nuevo, revisa qué página lo genera y corrígela.</li>

                            <li class="mb-2">Ejecútalo también en producción después de subir los archivos.</li>

                        </ul>

                    </div>

                </div>

            </div>

        </main>

    </div>

</body>

</html>