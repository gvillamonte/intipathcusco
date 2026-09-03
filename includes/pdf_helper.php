<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/pdf_engine.php';

function generarPdfReservaParaEmail($reserva, $pasajeros, $db) {
    $filename = "Reserva_" . $reserva['codigo'] . ".pdf";
    $path = __DIR__ . '/../pdfs/' . $filename;

    if (!is_dir(__DIR__ . '/../pdfs/')) {
        mkdir(__DIR__ . '/../pdfs/', 0777, true);
    }

    generarPdfReservaBinario($reserva, $pasajeros, $db, $path, $filename);

    return ['path' => $path, 'filename' => $filename];
}