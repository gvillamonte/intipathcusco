<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reclamos');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// LÓGICA: Actualizar Estado del Reclamo
if(isset($_POST['cambiar_estado'])){
    $stmt = $db->prepare("UPDATE libro_reclamaciones SET estado = ? WHERE id = ?");
    $stmt->execute([$_POST['nuevo_estado'], $_POST['id_reclamo']]);
    $msg_update = true;
}

// Consulta de todos los reclamos
$reclamos = $db->query("SELECT * FROM libro_reclamaciones ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Reclamaciones | IntiPath Tours</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos específicos para la tabla de reclamos */
        .ip-tabla-container { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-top: 20px; }
        .tabla-reclamos { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .tabla-reclamos th { background: #15305D; color: #fff; padding: 15px; text-align: left; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
        .tabla-reclamos td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        /* Badge del Código (Cuadro Rojo pedido) */
        .codigo-highlight { 
            background: #fef2f2; 
            color: #dc2626; 
            padding: 5px 10px; 
            border-radius: 6px; 
            font-family: 'Courier New', monospace; 
            font-weight: 800; 
            border: 1px solid #fee2e2;
            display: inline-block;
        }

        /* Badges de Estado */
        .st-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .Iniciado { background: #e0f2fe; color: #0369a1; }
        .En-Trámite { background: #fef3c7; color: #92400e; }
        .Finalizado { background: #dcfce7; color: #166534; }

        .btn-excel-full { 
            background: #1D6F42; 
            color: #fff; 
            padding: 12px 20px; 
            border-radius: 8px; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            font-weight: 700; 
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .btn-excel-full:hover { background: #155733; transform: translateY(-2px); }
        
        .form-status { display: flex; gap: 8px; }
        .select-status { padding: 6px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.8rem; }
        .btn-update { background: #15305D; color: #fff; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1 class="titulo-pagina">Gestión del Libro de Reclamaciones</h1>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <p class="text-muted">Lista oficial de quejas y reclamos registrados por los clientes.</p>
                <a href="exportar_reclamos.php" class="btn-excel-full">
                    <i class="fas fa-file-excel"></i> DESCARGAR EXCEL (FULL DATOS)
                </a>
            </div>

            <div class="ip-tabla-container">
                <table class="tabla-reclamos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Cliente / Documento</th>
                            <th>Tipo / Bien Reclamado</th>
                            <th>Estado Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reclamos as $r): ?>
                        <tr>
                            <td><span class="codigo-highlight"><?= $r['codigo_reclamo'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_registro'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['nombre']) ?></strong><br>
                                <small class="text-muted"><?= $r['tipo_documento'] ?>: <?= $r['numero_documento'] ?></small>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #64748b;"><?= $r['tipo_bien'] ?></span><br>
                                <small><?= htmlspecialchars($r['descripcion_bien']) ?></small>
                            </td>
                            <td>
                                <span class="st-badge <?= str_replace(' ', '-', $r['estado']) ?>">
                                    <?= $r['estado'] ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="form-status">
                                    <input type="hidden" name="id_reclamo" value="<?= $r['id'] ?>">
                                    <select name="nuevo_estado" class="select-status">
                                        <option value="Iniciado" <?= $r['estado'] == 'Iniciado' ? 'selected' : '' ?>>Iniciado</option>
                                        <option value="En Trámite" <?= $r['estado'] == 'En Trámite' ? 'selected' : '' ?>>En Trámite</option>
                                        <option value="Finalizado" <?= $r['estado'] == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                                    </select>
                                    <button type="submit" name="cambiar_estado" class="btn-update" title="Actualizar Estado">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>