<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reclamos');

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Reclamaciones_IntiPath_" . date('d-m-Y') . ".xls");

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Traer todos los campos de la tabla
$reclamos = $db->query("SELECT * FROM libro_reclamaciones ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<table border="1">
    <thead>
        <tr style="background-color: #15305D; color: #FFFFFF; font-weight: bold;">
            <th>CÓDIGO RECLAMO</th>
            <th>ESTADO</th>
            <th>FECHA DE REGISTRO</th>
            <th>NOMBRE COMPLETO</th>
            <th>TIPO DOCUMENTO</th>
            <th>NÚMERO DOCUMENTO</th>
            <th>EMAIL</th>
            <th>TELÉFONO</th>
            <th>DOMICILIO</th>
            <th>TIPO DE BIEN</th>
            <th>MONTO RECLAMADO</th>
            <th>DESCRIPCIÓN DEL BIEN</th>
            <th>DETALLE DEL RECLAMO</th>
            <th>PEDIDO DEL CLIENTE</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($reclamos as $r): ?>
        <tr>
            <td style="font-weight: bold; background-color: #F8FAFC;"><?= $r['codigo_reclamo'] ?></td>
            <td><?= $r['estado'] ?></td>
            <td><?= $r['fecha_registro'] ?></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= $r['tipo_documento'] ?></td>
            <td><?= $r['numero_documento'] ?></td>
            <td><?= $r['email'] ?></td>
            <td><?= $r['telefono'] ?></td>
            <td><?= htmlspecialchars($r['domicilio']) ?></td>
            <td><?= $r['tipo_bien'] ?></td>
            <td><?= number_format($r['monto_reclamado'], 2) ?></td>
            <td><?= htmlspecialchars($r['descripcion_bien']) ?></td>
            <td><?= htmlspecialchars($r['detalle_reclamo']) ?></td>
            <td><?= htmlspecialchars($r['pedido_cliente']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>