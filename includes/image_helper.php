<?php
/**
 * image_helper.php - Helper para conversion de imagenes a WEBP
 * 
 * Convierte JPG/PNG/WEBP a WEBP usando GD para optimizacion web.
 * Calidad por defecto: 82 (balance ideal calidad/tamano).
 * Si GD falla, guarda la imagen original como fallback.
 */

/**
 * Convierte una imagen a formato WEBP.
 *
 * @param string $tmp_path   Ruta temporal del archivo subido
 * @param string $dest_dir   Directorio destino
 * @param string $base_name  Nombre base sin extension
 * @return string|false      Nombre del archivo .webp guardado, o false en caso de error
 */
function convertir_a_webp($tmp_path, $dest_dir, $base_name) {
    if (!extension_loaded('gd')) {
        error_log("IMAGE_HELPER: GD not loaded");
        return false;
    }

    $gd_info = gd_info();
    if (empty($gd_info['WebP Support'])) {
        error_log("IMAGE_HELPER: WebP support not available");
        return false;
    }

    if (!file_exists($tmp_path) || filesize($tmp_path) === 0) {
        error_log("IMAGE_HELPER: tmp file missing or empty: $tmp_path");
        return false;
    }

    $raw = file_get_contents($tmp_path);
    if ($raw === false) {
        error_log("IMAGE_HELPER: file_get_contents failed: $tmp_path");
        return false;
    }

    $source_image = @imagecreatefromstring($raw);
    if ($source_image === false) {
        error_log("IMAGE_HELPER: imagecreatefromstring failed for $tmp_path");
        return false;
    }

    if (imageistruecolor($source_image)) {
        imagealphablending($source_image, false);
        imagesavealpha($source_image, true);
    }

    $dest_path = rtrim(str_replace('\\', '/', $dest_dir), '/') . '/' . $base_name . '.webp';
    $quality = 82;

    $result = @imagewebp($source_image, $dest_path, $quality);
    $file_size = (file_exists($dest_path)) ? filesize($dest_path) : 0;
    imagedestroy($source_image);

    if ($result === false || $file_size === 0) {
        @unlink($dest_path);
        error_log("IMAGE_HELPER: imagewebp failed or 0 bytes for $dest_path");
        return false;
    }

    error_log("IMAGE_HELPER: saved $dest_path ($file_size bytes)");
    return $base_name . '.webp';
}

/**
 * Procesa una subida de imagen y la guarda como WEBP.
 * Si la conversion falla, guarda la imagen original como fallback.
 *
 * @param array  $file_info  Elemento de $_FILES
 * @param string $dest_dir   Directorio destino
 * @param string $base_name  Nombre base sin extension
 * @return string            Nombre del archivo guardado (webp o extension original)
 */
function procesar_imagen_upload($file_info, $dest_dir, $base_name) {
    if ($file_info['error'] !== UPLOAD_ERR_OK) {
        error_log("IMAGE_HELPER: upload error=" . $file_info['error'] . " name=" . $file_info['name']);
        return '';
    }

    if (!is_dir($dest_dir)) {
        @mkdir($dest_dir, 0777, true);
    }

    $tmp_path = $file_info['tmp_name'];
    $original_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));

    error_log("IMAGE_HELPER: procesar_imagen_upload name=" . $file_info['name'] . " tmp=$tmp_path ext=$original_ext dest=$dest_dir base=$base_name");

    $webp_nombre = convertir_a_webp($tmp_path, $dest_dir, $base_name);

    if ($webp_nombre !== false) {
        error_log("IMAGE_HELPER: convertido a webp=$webp_nombre");
        return $webp_nombre;
    }

    error_log("IMAGE_HELPER: conversion fallo, fallback a original ext=$original_ext");
    $fallback_ext = $original_ext;
    if (!in_array($fallback_ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $fallback_ext = 'jpg';
    }
    $fallback_name = $base_name . '.' . $fallback_ext;
    $dest_path = rtrim($dest_dir, '/') . '/' . $fallback_name;

    if (move_uploaded_file($tmp_path, $dest_path)) {
        error_log("IMAGE_HELPER: fallback guardado=$fallback_name");
        return $fallback_name;
    }

    error_log("IMAGE_HELPER: fallback move_uploaded_file fallo");
    return '';
}
