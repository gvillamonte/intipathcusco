<?php
// procesar_newsletter.php
require_once 'config/database.php';
$db = (new Database())->getConnection();

if(isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    try {
        $stmt = $db->prepare("INSERT INTO newsletter (email) VALUES (?)");
        if($stmt->execute([$email])) {
            echo json_encode(['status' => 'success', 'msg' => '¡Suscripción exitosa!']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'msg' => 'Este correo ya está registrado.']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al procesar.']);
        }
    }
}