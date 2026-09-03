<?php
// admin/mensajes.php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('mensajes');

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// --- EXPORTAR A EXCEL ---
// --- EXPORTAR A EXCEL (MODIFICADO) ---
if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Reporte_Reservas_IntiPath.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Incluimos pais, adultos, ninos, fecha_viaje y tour_interes
    $stmt_ex = $db->query("SELECT fecha_creacion, nombre, email, telefono, pais, adultos, ninos, fecha_viaje, tour_interes, mensaje FROM mensajes ORDER BY fecha_creacion DESC");
    echo "<table border='1'>";
    echo "<tr style='background-color: #15305D; color: white;'><th>Fecha</th><th>Nombre</th><th>Email</th><th>Telefono</th><th>Pais</th><th>Adultos</th><th>Ninos</th><th>Fecha Viaje</th><th>Tour</th><th>Mensaje</th></tr>";
    while ($row = $stmt_ex->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['fecha_creacion'] . "</td>";
        echo "<td>" . mb_convert_encoding($row['nombre'], 'UTF-8') . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['telefono'] . "</td>";
        echo "<td>" . mb_convert_encoding($row['pais'], 'UTF-8') . "</td>";
        echo "<td>" . $row['adultos'] . "</td>";
        echo "<td>" . $row['ninos'] . "</td>";
        echo "<td>" . $row['fecha_viaje'] . "</td>";
        echo "<td>" . mb_convert_encoding($row['tour_interes'], 'UTF-8') . "</td>";
        echo "<td>" . mb_convert_encoding($row['mensaje'], 'UTF-8') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// --- ELIMINAR MENSAJE ---
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $db->prepare("DELETE FROM mensajes WHERE id = ?")->execute([$id_eliminar]);
    header("Location: mensajes.php?msg=eliminado");
    exit;
}

// CONSULTA DE DATOS
$query = "SELECT * FROM mensajes ORDER BY fecha_creacion DESC";
$stmt = $db->query($query);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensajes | IntiPath Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --color-primario-azul: #15305D;
            --color-secundario-amarillo: #E8AC18;
            --color-fondo-blanco: #FFFFFF;
            --color-texto-oscuro: #333333;
            --color-texto-claro: #ECF0F1;
            --sidebar-width: 280px;
            --color-exito: #27ae60;
            --color-error: #e74c3c;
        }

        body { 
            background-color: #f4f6f7; 
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            color: var(--color-texto-oscuro);
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Ajuste para que el contenido no se meta debajo del sidebar */
        .main-content {
            flex: 1;
            
            padding: 40px;
            box-sizing: border-box;
            width: calc(100% - var(--sidebar-width));
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--color-secundario-amarillo);
            padding-bottom: 15px;
        }

        .page-header h1 {
            margin: 0;
            color: var(--color-primario-azul);
            font-weight: 700;
        }

        .btn-excel {
            background-color: var(--color-exito);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-excel:hover {
            background-color: #219150;
            transform: translateY(-2px);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background-color: var(--color-primario-azul);
            color: white;
        }

        th {
            padding: 18px;
            text-align: left;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: top;
            font-size: 0.9rem;
        }

        .row-unread { background-color: #fffdf2; font-weight: 600; }

        .actions-cell { display: flex; gap: 15px; }
        .btn-action { text-decoration: none; transition: transform 0.2s; }
        .btn-action:hover { transform: scale(1.2); }
        .btn-reply { color: #3498db; }
        .btn-view { color: var(--color-primario-azul); }
        .btn-delete { color: var(--color-error); }

        .badge-contact {
            display: block;
            margin-bottom: 5px;
            color: var(--color-primario-azul);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>

    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Bandeja de <span>Mensajes</span></h1>
                <a href="mensajes.php?exportar=excel" class="btn-excel">
                    <i class="fas fa-file-excel"></i> Exportar Datos
                </a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert-success">✅ Mensaje eliminado correctamente.</div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Mensaje</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($mensajes): ?>
                            <?php foreach ($mensajes as $m): ?>
                                <tr class="<?php echo (isset($m['leido']) && $m['leido'] == 0) ? 'row-unread' : ''; ?>">
                                    <td style="width: 140px; color: #7f8c8d;">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($m['fecha_creacion'])); ?><br>
                                        <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($m['fecha_creacion'])); ?>
                                    </td>
                                    <td style="width: 240px;">
                                        <span class="badge-contact"><strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($m['nombre']); ?></strong></span>
                                        <span class="badge-contact"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($m['email']); ?></span>
                                        <span class="badge-contact"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($m['telefono']); ?></span>
                                    </td>
                                    <td>
                                        <div style="line-height: 1.5; color: #444;">
                                            <?php echo nl2br(htmlspecialchars($m['mensaje'])); ?>
                                        </div>
                                    </td>
                                    <td style="width: 80px;">
                                        <div class="actions-cell">
                                            <a href="mensajes_ver.php?id=<?php echo $m['id']; ?>" class="btn-action btn-view" title="Ver detalle">
                                                <i class="fas fa-eye fa-lg"></i>
                                            </a>
                                            <a href="mailto:<?php echo $m['email']; ?>" class="btn-action btn-reply" title="Responder">
                                                <i class="fas fa-paper-plane fa-lg"></i>
                                            </a>
                                            <a href="mensajes.php?eliminar=<?php echo $m['id']; ?>" class="btn-action btn-delete" title="Borrar" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash-alt fa-lg"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 50px;">No hay mensajes.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>