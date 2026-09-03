<?php
ob_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$id_tour = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = [];

if ($db) {
    $sql = "SELECT c.id, t.id as tour_id, t.titulo as title, c.fecha_salida as start, 
                   t.imagen_principal, t.precio, t.moneda, t.itinerario_resumen
            FROM calendario_salidas c 
            JOIN tours t ON c.id_tour = t.id
            WHERE t.estado = 'activo'";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($eventos as $row) {
        $data[] = [
            'id'    => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'extendedProps' => [
                'tour_id' => $row['tour_id'], // Asegúrate de que este campo exista
                'imagen'  => $row['imagen_principal'],
                'resumen' => mb_strimwidth(strip_tags($row['itinerario_resumen']), 0, 80, "..."),
                'precio'  => $row['moneda'] . " " . number_format($row['precio'], 2)
            ]
        ];
    }
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
exit;
