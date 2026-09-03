<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Relacionamos las tablas para traer el título del tour
$sql = "SELECT c.id, t.titulo as title, c.fecha_salida as start, c.color_evento as color 
        FROM calendario_salidas c 
        JOIN tours t ON c.id_tour = t.id";

$stmt = $db->prepare($sql);
$stmt->execute();
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Importante para el servidor: Cabecera JSON
header('Content-Type: application/json');
echo json_encode($eventos);