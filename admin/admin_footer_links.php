<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('footer_links');

require_once '../config/database.php';
$db = (new Database())->getConnection();

// Procesar formulario
if (isset($_POST['guardar'])) {
    $id = $_POST['id'];
    $enlace = $_POST['enlace'];
    $nombre_es = $_POST['nombre_es'];
    $nombre_en = $_POST['nombre_en'];
    $posicion = $_POST['posicion'];
    $estado = $_POST['estado'];
    
    if ($id) {
        $stmt = $db->prepare("UPDATE footer_links SET enlace = ?, nombre_es = ?, nombre_en = ?, posicion = ?, estado = ? WHERE id = ?");
        $stmt->execute([$enlace, $nombre_es, $nombre_en, $posicion, $estado, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO footer_links (enlace, nombre_es, nombre_en, posicion, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$enlace, $nombre_es, $nombre_en, $posicion, $estado]);
    }
    header("Location: admin_footer_links.php?res=ok");
    exit;
}

// Eliminar
if (isset($_GET['del'])) {
    $db->prepare("DELETE FROM footer_links WHERE id = ?")->execute([$_GET['del']]);
    header("Location: admin_footer_links.php?res=deleted");
    exit;
}

// Obtener enlaces
$links = $db->query("SELECT * FROM footer_links ORDER BY posicion ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Footer Links | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title-inti { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; }
        .card-admin { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05); margin-bottom: 30px; border: 1px solid #ddd; }
        .card-admin h3 { border-left: 5px solid var(--admin-blue); padding-left: 15px; color: var(--admin-blue); margin-bottom: 20px; text-transform: uppercase; font-size: 1rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; margin-bottom: 10px; }
        .btn-admin { background: var(--admin-blue); color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-admin:hover { background: #0e2245; }
        .btn-editar { background: var(--admin-accent); color: #000; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; }
        .btn-eliminar { background: #e74c3c; color: #fff; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: var(--admin-blue); color: #fff; }
        tr:hover { background: #f5f5f5; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
        .badge-activo { background: #27ae60; color: #fff; }
        .badge-inactivo { background: #95a5a6; color: #fff; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content" style="padding: 30px;">
            <h1 class="admin-title-inti"><i class="fas fa-link"></i> Gestionar Enlaces del Footer</h1>

            <!-- Formulario para agregar/editar -->
            <div class="card-admin">
                <h3><?= isset($_GET['edit']) ? 'Editar Enlace' : 'Agregar Nuevo Enlace' ?></h3>
                <?php 
                $edit = ['id'=>'','enlace'=>'','nombre_es'=>'','nombre_en'=>'','posicion'=>'','estado'=>'activo'];
                if (isset($_GET['edit'])) {
                    foreach ($links as $l) {
                        if ($l['id'] == $_GET['edit']) $edit = $l;
                    }
                }
                ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Archivo (ej: info-previa.php)</label>
                            <input type="text" name="enlace" value="<?= $edit['enlace'] ?>" class="form-control" required>
                        </div>
                        <div>
                            <label>Posición (número)</label>
                            <input type="number" name="posicion" value="<?= $edit['posicion'] ?: count($links)+1 ?>" class="form-control" required>
                        </div>
                        <div>
                            <label>Nombre (Español)</label>
                            <input type="text" name="nombre_es" value="<?= $edit['nombre_es'] ?>" class="form-control" required>
                        </div>
                        <div>
                            <label>Nombre (Inglés)</label>
                            <input type="text" name="nombre_en" value="<?= $edit['nombre_en'] ?>" class="form-control" required>
                        </div>
                        <div>
                            <label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="activo" <?= ($edit['estado']=='activo')?'selected':'' ?>>Activo</option>
                                <option value="inactivo" <?= ($edit['estado']=='inactivo')?'selected':'' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="guardar" class="btn-admin" style="margin-top: 15px;">
                        <i class="fas fa-save"></i> Guardar Enlace
                    </button>
                    <?php if(isset($_GET['edit'])): ?>
                        <a href="admin_footer_links.php" class="btn-admin" style="background: #95a5a6; margin-left: 10px; text-decoration: none; display: inline-block;">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Lista de enlaces -->
            <div class="card-admin">
                <h3>Enlaces Actuales (<?= count($links) ?>)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Archivo</th>
                            <th>Nombre ES</th>
                            <th>Nombre EN</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($links as $l): ?>
                        <tr>
                            <td><?= $l['posicion'] ?></td>
                            <td><?= $l['enlace'] ?></td>
                            <td><?= $l['nombre_es'] ?></td>
                            <td><?= $l['nombre_en'] ?></td>
                            <td>
                                <span class="badge <?= $l['estado']=='activo'?'badge-activo':'badge-inactivo' ?>">
                                    <?= $l['estado'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?= $l['id'] ?>" class="btn-editar"><i class="fas fa-edit"></i> Editar</a>
                                <button onclick="confDel(<?= $l['id'] ?>)" class="btn-eliminar" style="margin-left: 5px;"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function confDel(id) {
            Swal.fire({
                title: '¿Eliminar este enlace?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = '?del=' + id;
            });
        }
        const res = new URLSearchParams(window.location.search).get('res');
        if (res === 'ok' || res === 'deleted') {
            Swal.fire({ icon: 'success', title: '¡Guardado correctamente!', confirmButtonColor: '#15305D' }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>