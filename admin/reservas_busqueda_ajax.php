<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$term = "%$q%";
$stmt = $db->prepare("
    SELECT r.id, r.codigo, r.nombre, r.apellido, r.email, r.telefono, r.whatsapp, r.fecha_viaje, r.estado, r.monto_total,
           t.titulo as tour_titulo
    FROM reservas r
    LEFT JOIN tours t ON r.id_tour = t.id
    WHERE (r.codigo LIKE ? OR r.email LIKE ? OR r.telefono LIKE ? OR r.whatsapp LIKE ? OR r.nombre LIKE ? OR r.apellido LIKE ?)
    ORDER BY r.id DESC
    LIMIT 10
");
$stmt->execute([$term, $term, $term, $term, $term, $term]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);