<?php
if (ob_get_length()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/pdf_engine.php';

requierePermiso('reservas');

$id_reserva = intval($_GET['id'] ?? 0);
if (!$id_reserva) {
    die("Error: ID de reserva no proporcionado.");
}

try {
    $db = (new Database())->getConnection();

    $query = "SELECT r.*, t.titulo, t.titulo_en, t.duracion, t.imagen_principal 
              FROM reservas r 
              LEFT JOIN tours t ON r.id_tour = t.id 
              WHERE r.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        die("Error: Reserva no encontrada.");
    }

    $stmt_p = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
    $stmt_p->execute([$id_reserva]);
    $pasajeros = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Reserva_Admin_" . $reserva['codigo'] . ".pdf";
    $path = __DIR__ . '/../temp_pdfs/' . $filename;

    if (!is_dir(__DIR__ . '/../temp_pdfs/')) {
        mkdir(__DIR__ . '/../temp_pdfs/', 0777, true);
    }

    $output = generarPdfReservaBinario($reserva, $pasajeros, $db, $path, $filename);

    ob_end_clean();

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $output;
    exit;

} catch (\Exception $e) {
    if (ob_get_length()) ob_end_clean();
    die("Error al generar el PDF: " . $e->getMessage());
}