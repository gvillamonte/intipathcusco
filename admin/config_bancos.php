<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('header_footer');

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// --- Auto-instalar tabla bancos + migrar ---
try {
    $db->query("SELECT COUNT(*) FROM bancos");
} catch (PDOException $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS bancos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_banco VARCHAR(100) NOT NULL DEFAULT '',
        titular VARCHAR(200) NOT NULL DEFAULT '',
        numero_cuenta VARCHAR(50) NOT NULL DEFAULT '',
        cci VARCHAR(50) NOT NULL DEFAULT '',
        moneda VARCHAR(10) NOT NULL DEFAULT 'soles',
        logo VARCHAR(255) NOT NULL DEFAULT '',
        orden INT NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Migrar de config_bancos si vacia
$count = (int)$db->query("SELECT COUNT(*) FROM bancos")->fetchColumn();
if ($count === 0) {
    try {
        $db->exec("INSERT INTO bancos (nombre_banco, titular, numero_cuenta, cci, moneda, activo, orden)
                    SELECT nombre_banco, titular, cuenta_soles, cci, 'soles', 1, 1
                    FROM config_bancos WHERE id = 1 AND cuenta_soles IS NOT NULL AND TRIM(cuenta_soles) != ''");
        $db->exec("INSERT INTO bancos (nombre_banco, titular, numero_cuenta, cci, moneda, activo, orden)
                    SELECT nombre_banco, titular, cuenta_dolares, cci, 'dolares', 1, 2
                    FROM config_bancos WHERE id = 1 AND cuenta_dolares IS NOT NULL AND TRIM(cuenta_dolares) != ''");
    } catch (PDOException $e) {}
}

// --- Directorio de logos ---
$logo_dir = __DIR__ . '/../assets/img/bancos';
if (!is_dir($logo_dir)) mkdir($logo_dir, 0755, true);

// --- Guardar banco (agregar/editar) ---
if (isset($_POST['guardar_banco'])) {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre_banco'] ?? '');
    $titular = trim($_POST['titular'] ?? '');
    $cuenta = trim($_POST['numero_cuenta'] ?? '');
    $cci = trim($_POST['cci'] ?? '');
    $moneda = in_array($_POST['moneda'] ?? '', ['soles', 'dolares']) ? $_POST['moneda'] : 'soles';
    $orden = max(0, (int)($_POST['orden'] ?? 0));
    $activo = isset($_POST['activo']) ? 1 : 0;

    $logo_guardado = '';
    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $logo_nombre = 'banco_' . ($id ?: time()) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_dir . '/' . $logo_nombre)) {
                if ($id) {
                    $old = $db->query("SELECT logo FROM bancos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
                    if (!empty($old['logo']) && is_file($logo_dir . '/' . $old['logo'])) {
                        @unlink($logo_dir . '/' . $old['logo']);
                    }
                }
                $logo_guardado = $logo_nombre;
            }
        }
    }

    if ($id) {
        if ($logo_guardado) {
            $stmt = $db->prepare("UPDATE bancos SET nombre_banco=?, titular=?, numero_cuenta=?, cci=?, moneda=?, logo=?, orden=?, activo=? WHERE id=?");
            $stmt->execute([$nombre, $titular, $cuenta, $cci, $moneda, $logo_guardado, $orden, $activo, $id]);
        } else {
            $stmt = $db->prepare("UPDATE bancos SET nombre_banco=?, titular=?, numero_cuenta=?, cci=?, moneda=?, orden=?, activo=? WHERE id=?");
            $stmt->execute([$nombre, $titular, $cuenta, $cci, $moneda, $orden, $activo, $id]);
        }
    } else {
        $stmt = $db->prepare("INSERT INTO bancos (nombre_banco, titular, numero_cuenta, cci, moneda, logo, orden, activo) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$nombre, $titular, $cuenta, $cci, $moneda, $logo_guardado, $orden, $activo]);
    }
    header("Location: config_bancos.php?res=ok");
    exit;
}

// --- Toggle activo (AJAX/POST) ---
if (isset($_POST['toggle_activo'])) {
    $id = (int)$_POST['banco_id'];
    $db->prepare("UPDATE bancos SET activo = NOT activo WHERE id=?")->execute([$id]);
    header("Location: config_bancos.php?res=toggle");
    exit;
}

// --- Eliminar banco ---
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $old = $db->query("SELECT logo FROM bancos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
    if ($old) {
        if (!empty($old['logo']) && is_file($logo_dir . '/' . $old['logo'])) {
            @unlink($logo_dir . '/' . $old['logo']);
        }
        $db->prepare("DELETE FROM bancos WHERE id=?")->execute([$id]);
    }
    header("Location: config_bancos.php?res=deleted");
    exit;
}

// --- Datos ---
$bancos = $db->query("SELECT * FROM bancos ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$edit_banco = ['id'=>0, 'nombre_banco'=>'', 'titular'=>'', 'numero_cuenta'=>'', 'cci'=>'', 'moneda'=>'soles', 'logo'=>'', 'orden'=>0, 'activo'=>1];
if (isset($_GET['edit'])) {
    foreach ($bancos as $b) {
        if ((int)$b['id'] === (int)$_GET['edit']) {
            $edit_banco = $b;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bancos | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; }
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .admin-title { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #ddd; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; margin-bottom: 10px; }
        .btn-admin { background: var(--admin-blue); color: #fff; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .btn-admin:hover { background: #0e2245; }
        .btn-edit { background: var(--admin-accent); color: #000; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; display: inline-block; }
        .btn-del { background: #e74c3c; color: #fff; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: var(--admin-blue); color: #fff; font-size: 0.85rem; text-transform: uppercase; }
        tr:hover { background: #f5f5f5; }
        .logo-preview { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid #eee; background: #f8f8f8; }
        .moneda-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .moneda-s { background: #d1fae5; color: #065f46; }
        .moneda-d { background: #dbeafe; color: #1e40af; }
        .info-box { background: #eef2ff; border-left: 4px solid var(--admin-blue); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; color: #333; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-full { grid-column: 1 / -1; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .btn-edit { padding: 10px 16px; min-height: 44px; }
        .btn-del { padding: 10px 16px; min-height: 44px; }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
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
        <h1 class="admin-title"><i class="fas fa-university"></i> Bancos</h1>
        <div class="info-box">
            <i class="fas fa-info-circle" style="color:var(--admin-blue);"></i>
            Configura los bancos que aparecen en la sección "Datos Bancarios" del PDF de reserva.
            Puedes agregar varios bancos, activar/desactivar cada uno, o eliminarlos. El PDF genera las filas dinámicamente según los bancos activos.
        </div>

        <?php if (isset($_GET['res'])): ?>
            <script>Swal.fire({icon:'success',title:'<?= $_GET['res'] === 'deleted' ? 'Banco eliminado' : 'Guardado' ?>',timer:1500,showConfirmButton:false}).then(()=>{history.replaceState({},'',window.location.pathname);});</script>
        <?php endif; ?>

        <!-- AGREGAR / EDITAR -->
        <div class="card">
            <h3 style="border-left:5px solid var(--admin-blue);padding-left:15px;color:var(--admin-blue);margin-bottom:20px;text-transform:uppercase;font-size:1rem;">
                <?= $edit_banco['id'] ? 'Editar Banco' : 'Agregar Nuevo Banco' ?>
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $edit_banco['id'] ?>">
                <input type="hidden" name="guardar_banco" value="1">
                <div class="grid-2">
                    <div>
                        <label><strong>Nombre del Banco</strong></label>
                        <input type="text" name="nombre_banco" class="form-control" value="<?= htmlspecialchars($edit_banco['nombre_banco']) ?>" placeholder="Ej: INTERBANK" required>
                    </div>
                    <div>
                        <label><strong>Titular de la Cuenta</strong></label>
                        <input type="text" name="titular" class="form-control" value="<?= htmlspecialchars($edit_banco['titular']) ?>" placeholder="Ej: INTI PATH TOURS PERU S.A.C.">
                    </div>
                    <div>
                        <label><strong>Nº de Cuenta</strong></label>
                        <input type="text" name="numero_cuenta" class="form-control" value="<?= htmlspecialchars($edit_banco['numero_cuenta']) ?>" placeholder="Ej: 420-429-8224216" required>
                    </div>
                    <div>
                        <label><strong>CCI</strong></label>
                        <input type="text" name="cci" class="form-control" value="<?= htmlspecialchars($edit_banco['cci']) ?>" placeholder="Ej: 003-420-90308224216-72">
                    </div>
                    <div>
                        <label><strong>Moneda</strong></label>
                        <select name="moneda" class="form-control">
                            <option value="soles" <?= $edit_banco['moneda'] === 'soles' ? 'selected' : '' ?>>Soles (S/)</option>
                            <option value="dolares" <?= $edit_banco['moneda'] === 'dolares' ? 'selected' : '' ?>>Dólares (US$)</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>Orden</strong></label>
                        <input type="number" name="orden" class="form-control" value="<?= $edit_banco['orden'] ?>" min="0">
                    </div>
                    <div class="grid-full">
                        <label><strong>Logo del Banco</strong> (JPG o PNG)</label>
                        <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png" style="padding:8px;">
                        <?php if (!empty($edit_banco['logo'])): ?>
                            <img src="../assets/img/bancos/<?= htmlspecialchars($edit_banco['logo']) ?>?v=<?= filemtime($logo_dir . '/' . $edit_banco['logo']) ?>" style="margin-top:8px;height:40px;" alt="Logo actual">
                            <small style="color:#888;margin-left:8px;">Logo actual (sube uno nuevo para cambiarlo)</small>
                        <?php endif; ?>
                    </div>
                    <div class="grid-full">
                        <label style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="activo" <?= $edit_banco['activo'] ? 'checked' : '' ?>>
                            <strong>Activo (aparece en el PDF)</strong>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-admin" style="margin-top:15px;"><i class="fas fa-save"></i> <?= $edit_banco['id'] ? 'Actualizar' : 'Guardar' ?> Banco</button>
                <?php if ($edit_banco['id']): ?>
                    <a href="config_bancos.php" class="btn-edit" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- LISTA DE BANCOS -->
        <div class="card">
            <h3 style="border-left:5px solid var(--admin-blue);padding-left:15px;color:var(--admin-blue);margin-bottom:20px;text-transform:uppercase;font-size:1rem;">
                Bancos configurados (<?= count($bancos) ?>)
            </h3>
            <?php if (empty($bancos)): ?>
                <p style="text-align:center;color:#999;padding:30px;">No hay bancos configurados aún.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Logo</th><th>Banco</th><th>Titular</th><th>Nº Cuenta</th><th>CCI</th><th>Moneda</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bancos as $b): ?>
                        <tr>
                            <td>
                                <?php if (!empty($b['logo'])): ?>
                                    <img src="../assets/img/bancos/<?= htmlspecialchars($b['logo']) ?>?v=<?= filemtime($logo_dir . '/' . $b['logo']) ?>" class="logo-preview" alt="Logo">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($b['nombre_banco']) ?></strong></td>
                            <td><?= htmlspecialchars($b['titular']) ?></td>
                            <td><code><?= htmlspecialchars($b['numero_cuenta']) ?></code></td>
                            <td><code><?= htmlspecialchars($b['cci']) ?></code></td>
                            <td>
                                <span class="moneda-badge <?= $b['moneda'] === 'soles' ? 'moneda-s' : 'moneda-d' ?>">
                                    <?= $b['moneda'] === 'soles' ? 'S/' : 'US$' ?>
                                </span>
                            </td>
                            <td><?= $b['orden'] ?></td>
                            <td>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="toggle_activo" value="1">
                                    <input type="hidden" name="banco_id" value="<?= $b['id'] ?>">
                                    <button type="submit" style="border:none;background:none;cursor:pointer;font-size:1.1rem;<?= $b['activo'] ? 'color:#059669' : 'color:#ccc' ?>">
                                        <i class="fas fa-toggle-<?= $b['activo'] ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="?edit=<?= $b['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                <button onclick="confDel(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['nombre_banco'])) ?>')" class="btn-del" style="margin-left:5px;"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
function confDel(id, nombre) {
    Swal.fire({
        title: '¿Eliminar banco "' + nombre + '"?',
        text: 'Se eliminará el logo y todos los datos de este banco.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'Sí, eliminar'
    }).then((r) => { if (r.isConfirmed) window.location.href = '?del=' + id; });
}
</script>
</body>
</html>
