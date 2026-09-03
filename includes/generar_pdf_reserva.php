<?php
if (ob_get_length()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/funcion_pdf_reserva.php';

$id_reserva = intval($_GET['id_reserva'] ?? 0);
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

    $pdf_resultado = generarPdfReserva($reserva, $pasajeros, $db);

    ob_end_clean();

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf_resultado['filename'] . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_resultado['output'];
    exit;

} catch (\Exception $e) {
    ob_end_clean();
    die("Error al generar el PDF: " . $e->getMessage());
}
