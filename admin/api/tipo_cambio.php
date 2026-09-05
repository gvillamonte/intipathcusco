<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/tipo_cambio_helper.php';

session_start();
if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$tipo = obtenerTipoCambio($db);
echo json_encode(['tipo_cambio' => $tipo]);
