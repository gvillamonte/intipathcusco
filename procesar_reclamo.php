<?php
/**
 * procesar_reclamo.php
 * Procesa y guarda las hojas de reclamación de IntiPath Tours
 */

require_once 'config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- PROTECCIÓN CSRF ---
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        header("Location: libro-de-reclamaciones.php?res=error");
        exit;
    }

    // --- HONEYPOT ANTI-BOTS ---
    if (esHoneypotLlenado()) {
        header("Location: libro-de-reclamaciones.php");
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        if (!$db) {
            die("Error de conexión.");
        }

        // 1. Capturar datos del formulario (con saneamiento)
        $nombre      = trim(substr($_POST['nombre'] ?? '', 0, 150));
        $tipo_doc    = trim(substr($_POST['tipo_doc'] ?? '', 0, 20));
        $num_doc     = trim(substr($_POST['num_doc'] ?? '', 0, 30));
        $email       = trim($_POST['email'] ?? '');
        $telefono    = trim(substr($_POST['telefono'] ?? '', 0, 30));
        $domicilio   = trim(substr($_POST['domicilio'] ?? '', 0, 255));
        $tipo_bien   = trim(substr($_POST['tipo_bien'] ?? '', 0, 30));
        $monto       = !empty($_POST['monto']) ? floatval($_POST['monto']) : 0;
        $desc_bien   = trim(substr($_POST['desc_bien'] ?? '', 0, 255));
        $detalle     = trim(substr($_POST['detalle'] ?? '', 0, 3000));
        $pedido      = trim(substr($_POST['pedido'] ?? '', 0, 3000));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: libro-de-reclamaciones.php?res=error");
            exit;
        }

        // 2. Generar Código Correlativo Único (Ej: LR-2026-001)
        // Primero contamos cuántos hay hoy para el correlativo
        $stmt_count = $db->query("SELECT COUNT(*) FROM libro_reclamaciones");
        $cantidad = $stmt_count->fetchColumn();
        $codigo_reclamo = "LR-" . date("Y") . "-" . str_pad($cantidad + 1, 3, "0", STR_PAD_LEFT);

        // 3. Preparar el INSERT
        $query = "INSERT INTO libro_reclamaciones (
                    codigo_reclamo, nombre, tipo_documento, numero_documento, 
                    email, telefono, domicilio, tipo_bien, monto_reclamado, 
                    descripcion_bien, detalle_reclamo, pedido_cliente, estado, fecha_registro
                  ) VALUES (
                    :cod, :nom, :tdoc, :ndoc, :em, :tel, :dom, :tbien, :mon, :desc, :det, :ped, 'Iniciado', NOW()
                  )";

        $stmt = $db->prepare($query);

        $resultado = $stmt->execute([
            ':cod'   => $codigo_reclamo,
            ':nom'   => $nombre,
            ':tdoc'  => $tipo_doc,
            ':ndoc'  => $num_doc,
            ':em'    => $email,
            ':tel'   => $telefono,
            ':dom'   => $domicilio,
            ':tbien' => $tipo_bien,
            ':mon'   => $monto,
            ':desc'  => $desc_bien,
            ':det'   => $detalle,
            ':ped'   => $pedido
        ]);

        if ($resultado) {
            // OPCIONAL: Aquí podrías usar PHPMailer para enviarte un correo avisándote del reclamo
            
            // Redirigir con éxito y el código generado
            header("Location: libro-de-reclamaciones.php?res=success&num=" . $codigo_reclamo);
            exit;
        } else {
            header("Location: libro-de-reclamaciones.php?res=error");
            exit;
        }

    } catch (PDOException $e) {
        // En caso de error crítico
        die("Error al procesar el reclamo: " . $e->getMessage());
    }
} else {
    // Si alguien intenta entrar directamente al archivo sin POST
    header("Location: libro-de-reclamaciones.php");
    exit;
}