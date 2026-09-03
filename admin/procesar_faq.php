<?php
/**
 * procesar_faq.php
 * Procesa las peticiones de guardado, actualización y eliminación de FAQs
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('faqs');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// --- 1. ELIMINAR PREGUNTA ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    try {
        $stmt = $db->prepare("DELETE FROM preguntas_frecuentes WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: preguntas_frecuentes.php?res=success");
    } catch (PDOException $e) {
        header("Location: preguntas_frecuentes.php?res=error");
    }
    exit;
}

// --- 2. GUARDAR O ACTUALIZAR ---
if (isset($_POST)) {
    $id           = $_POST['id'];
    $pregunta     = $_POST['pregunta'];
    $pregunta_en  = $_POST['pregunta_en'];
    $respuesta    = $_POST['respuesta'];
    $respuesta_en = $_POST['respuesta_en'];
    $orden        = $_POST['orden'];
    $estado       = $_POST['estado'];

    try {
        if (!empty($id)) {
            // ACTUALIZAR REGISTRO EXISTENTE
            $sql = "UPDATE preguntas_frecuentes 
                    SET pregunta = ?, pregunta_en = ?, respuesta = ?, respuesta_en = ?, orden = ?, estado = ? 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$pregunta, $pregunta_en, $respuesta, $respuesta_en, $orden, $estado, $id]);
        } else {
            // INSERTAR NUEVO REGISTRO
            $sql = "INSERT INTO preguntas_frecuentes (pregunta, pregunta_en, respuesta, respuesta_en, orden, estado) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$pregunta, $pregunta_en, $respuesta, $respuesta_en, $orden, $estado]);
        }
        
        header("Location: preguntas_frecuentes.php?res=success");
    } catch (PDOException $e) {
        // En caso de error, puedes debugear con: die($e->getMessage());
        header("Location: preguntas_frecuentes.php?res=error");
    }
    exit;
} else {
    // Si alguien intenta entrar directamente al archivo sin POST
    header("Location: preguntas_frecuentes.php");
    exit;
}