<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/pdf_engine.php';

function generarPdfReserva($reserva, $pasajeros, $db) {
    $filename = "Reserva_" . $reserva['codigo'] . ".pdf";
    $path = __DIR__ . '/../temp_pdfs/' . $filename;

    if (!is_dir(__DIR__ . '/../temp_pdfs/')) {
        mkdir(__DIR__ . '/../temp_pdfs/', 0777, true);
    }

    $output = generarPdfReservaBinario($reserva, $pasajeros, $db, $path, $filename);

    return [
        'path' => $path,
        'filename' => $filename,
        'output' => $output
    ];
}