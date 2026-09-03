<?php
/**
 * eliminar_sql.php
 * Elimina todos los archivos .sql de la raíz del sitio (no backups).
 * Seguridad: requiere permiso tours.
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('tours');

$raiz = realpath(__DIR__ . '/..');

// --- Acción: eliminar (POST) ---
$eliminados = 0;
$errores_list = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_todos') {
    $archivos = glob($raiz . DIRECTORY_SEPARATOR . '*.sql');
    foreach ($archivos as $archivo) {
        $nombre = basename($archivo);
        $size = filesize($archivo);
        if (@unlink($archivo)) {
            $eliminados++;
        } else {
            $errores_list[] = $nombre;
        }
    }
}

// --- Escanear archivos actuales ---
$archivos_sql = glob($raiz . DIRECTORY_SEPARATOR . '*.sql');
$total = count($archivos_sql);
$detalles = [];
foreach ($archivos_sql as $archivo) {
    $detalles[] = [
        'nombre' => basename($archivo),
        'size'   => filesize($archivo),
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar archivos SQL | IntiPath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper d-flex">
    <?php include '../includes/sidebar.php'; ?>
    <main class="tou-main-content p-4 w-100">
        <h2 class="fw-bold text-dark mb-1"><i class="fas fa-trash-can me-2"></i>Eliminar archivos SQL</h2>
        <p class="text-muted small">Borra los archivos <code>.sql</code> de la raíz del sitio. No afecta backups ni otros directorios.</p>

        <!-- RESULTADO -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="tou-card bg-white p-4 shadow-sm mb-4">
            <?php if ($eliminados > 0 && empty($errores_list)): ?>
                <div class="alert alert-success py-2 mb-0">
                    <i class="fas fa-circle-check me-1"></i> <?= $eliminados ?> archivo(s) SQL eliminado(s) correctamente.
                </div>
            <?php elseif ($eliminados > 0 && !empty($errores_list)): ?>
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fas fa-triangle-exclamation me-1"></i> <?= $eliminados ?> eliminado(s), <?= count($errores_list) ?> fallido(s): <?= implode(', ', $errores_list) ?>
                </div>
            <?php else: ?>
                <div class="alert alert-danger py-2 mb-0">
                    <i class="fas fa-circle-exclamation me-1"></i> No se pudieron eliminar. Verifica permisos del servidor.
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="tou-card bg-white p-4 shadow-sm">
                    <h6 class="fw-bold mb-3"><i class="fas fa-file-code me-1"></i> Archivos SQL en la raíz del sitio</h6>

                    <?php if ($total === 0): ?>
                        <div class="alert alert-success py-2 mb-0">
                            <i class="fas fa-circle-check me-1"></i> No hay archivos <code>.sql</code> en la raíz. Todo limpio.
                        </div>
                    <?php else: ?>
                        <table class="table table-sm align-middle mb-3">
                            <thead class="table-dark">
                                <tr><th>Archivo</th><th class="text-end" width="100">Tamaño</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $d): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($d['nombre']) ?></code></td>
                                    <td class="text-end text-muted small"><?= number_format($d['size']) ?> B</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <form method="post" onsubmit="return confirm('¿Eliminar los <?= $total ?> archivo(s) SQL de la raíz del sitio?\n\nEsta acción no se puede deshacer.');">
                            <input type="hidden" name="accion" value="eliminar_todos">
                            <button type="submit" class="btn btn-danger rounded-pill px-4">
                                <i class="fas fa-trash-can me-1"></i> Eliminar todos (<?= $total ?>)
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="tou-card bg-white p-4 shadow-sm">
                    <h6 class="fw-bold mb-3"><i class="fas fa-circle-info me-1"></i> Nota</h6>
                    <p class="small text-muted mb-0">
                        Solo se eliminan archivos <code>.sql</code> en la <strong>raíz</strong> del sitio.
                        Los backups en <code>backups/</code> no se tocan.
                    </p>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
