<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('calendario');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// --- AÑADIR AL PRINCIPIO PARA ELIMINAR ---
if (isset($_GET['eliminar_id'])) {
    $id_eliminar = (int)$_GET['eliminar_id'];
    $sql = "DELETE FROM calendario_salidas WHERE id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$id_eliminar])) {
        header("Location: calendario_admin.php?res=deleted");
    } else {
        echo "Error al eliminar";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_salida'])) {
    // Forzamos que los IDs sean enteros para evitar errores de HostGator
    $id_tour = (int)$_POST['id_tour'];
    $fecha   = $_POST['fecha_salida'];
    $color   = $_POST['color_evento'] ?? '#28a745';

    if ($id_tour > 0 && !empty($fecha)) {
        $sql = "INSERT INTO calendario_salidas (id_tour, fecha_salida, color_evento) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([$id_tour, $fecha, $color])) {
            header("Location: calendario_admin.php?res=success");
        } else {
            echo "Error SQL: "; print_r($stmt->errorInfo()); exit;
        }
    } else {
        header("Location: calendario_admin.php?res=empty");
    }
}