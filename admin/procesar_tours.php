<?php



/**

 * procesar_tours.php

 * Procesa la creación y actualización de tours de IntiPath Tours.

 */

require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/image_helper.php';
require_once __DIR__ . '/../includes/tipo_cambio_helper.php';

requierePermiso('tours');



// Solo mostrar errores en pantalla cuando se pide explícitamente (debug)
if (!empty($_GET['debug'])) {

    ini_set('display_errors', 1);

    ini_set('display_startup_errors', 1);

    error_reporting(E_ALL);

}



require_once '../config/database.php';

$database = new Database();

$db = $database->getConnection();

$tipo_cambio_actual = obtenerTipoCambio($db);



if (!$db) {

    die("Error de conexión a la base de datos.");

}



// ================================================================

// LÓGICA 1: ELIMINAR ARCHIVO PDF FÍSICO

// ================================================================

if (isset($_GET['eliminar_archivo']) && isset($_GET['tour_id']) && isset($_GET['campo'])) {
    $nombre_archivo = urldecode($_GET['eliminar_archivo']);
    $tour_id = (int)$_GET['tour_id'];
    $campo = in_array($_GET['campo'], ['imagen_principal', 'folleto_pdf', 'mapa_imagen']) ? $_GET['campo'] : 'imagen_principal';

    $directorio = ($campo === 'folleto_pdf') ? "../assets/pdf/" : "../assets/img/tours/";
    if ($campo === 'mapa_imagen') {
        $directorio = "../assets/img/mapas/";
    }
    $ruta_completa = $directorio . basename($nombre_archivo);

    if (file_exists($ruta_completa)) {
        if (unlink($ruta_completa)) {
            $stmt = $db->prepare("UPDATE tours SET `$campo` = '' WHERE id = ?");
            $stmt->execute([$tour_id]);
            $stmt->closeCursor();
            header("Location: tours.php?res=success&msg=Archivo+eliminado");
            exit;
        } else {
            header("Location: tours.php?res=error&msg=Error+al+borrar");
        }
    } else {
        header("Location: tours.php?res=error&msg=No+encontrado");
    }
    exit;
}


// ================================================================



function archivo_usado_por_otro_tour($db, $campo, $valor, $tour_id_actual) {
    if (empty($valor)) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM tours WHERE `$campo` = ? AND id != ?");
    $stmt->execute([$valor, $tour_id_actual]);
    return $stmt->fetchColumn() > 0;
}

function galeria_usada_por_otro_tour($db, $foto, $tour_id_actual) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM tours WHERE id != ? AND (galeria_itinerario LIKE ?)");
    $stmt->execute([$tour_id_actual, '%' . $foto . '%']);
    return $stmt->fetchColumn() > 0;
}

// LÓGICA 1b: VERIFICAR USO DE ARCHIVO POR OTROS TOURS (AJAX JSON)

// ================================================================

if (isset($_GET['usado_por_tours']) && isset($_GET['archivo']) && isset($_GET['campo'])) {

    header('Content-Type: application/json');

    $nombre_archivo = urldecode($_GET['archivo']);

    $campo = in_array($_GET['campo'], ['imagen_principal', 'folleto_pdf', 'mapa_imagen']) ? $_GET['campo'] : 'imagen_principal';

    $stmt = $db->prepare("SELECT id, titulo FROM tours WHERE `$campo` = ? AND id != ?");

    $stmt->execute([$nombre_archivo, 0]);

    $tours_usan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt->closeCursor();

    echo json_encode([

        'usado' => count($tours_usan) > 0,

        'tours' => $tours_usan

    ]);

    exit;

}


// ================================================================

// LÓGICA 1c: DESVINCULAR ARCHIVO (clear DB field, KEEP physical file)

// ================================================================

if (isset($_GET['desvincular_archivo']) && isset($_GET['tour_id']) && isset($_GET['campo'])) {

    $tour_id = (int)$_GET['tour_id'];

    $campo = in_array($_GET['campo'], ['imagen_principal', 'folleto_pdf', 'mapa_imagen']) ? $_GET['campo'] : 'imagen_principal';

    $stmt = $db->prepare("UPDATE tours SET `$campo` = '' WHERE id = ?");

    $stmt->execute([$tour_id]);

    $stmt->closeCursor();

    header("Location: tours.php?res=success&msg=Archivo+desvinculado");

    exit;

}


// ================================================================

// LÓGICA 1d: ELIMINAR PDF FÍSICO DEL SERVIDOR (no vinculado a ningún tour)

// ================================================================

if (isset($_GET['eliminar_pdf_servidor'])) {

    $nombre_archivo = urldecode($_GET['eliminar_pdf_servidor']);

    $ruta_completa = "../assets/pdf/" . basename($nombre_archivo);

    if (file_exists($ruta_completa)) {

        @unlink($ruta_completa);

    }

    $tour_id = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;

    $redir = "tours.php?res=success&msg=PDF+eliminado+del+servidor";

    if ($tour_id > 0) {

        $redir .= "&editar=" . $tour_id;

    }

    header("Location: " . $redir);

    exit;

}


// ================================================================

// LÓGICA 2: ELIMINAR TOUR COMPLETO

// ================================================================

if (isset($_GET['eliminar'])) {

    $id = $_GET['eliminar'];

    $stmt = $db->prepare("SELECT imagen_principal, folleto_pdf, mapa_imagen, img_galeria1, img_galeria2, img_galeria3, img_galeria4, galeria_itinerario FROM tours WHERE id = ?");

    $stmt->execute([$id]);

    $archivos = $stmt->fetch(PDO::FETCH_ASSOC);



    if ($archivos) {

        $ruta_tours = "../assets/img/tours/";

        if (!empty($archivos['imagen_principal'])) {

            if (!archivo_usado_por_otro_tour($db, 'imagen_principal', $archivos['imagen_principal'], $id)) {

                @unlink($ruta_tours . $archivos['imagen_principal']);

            }

        }

        if (!empty($archivos['folleto_pdf'])) {

            if (!archivo_usado_por_otro_tour($db, 'folleto_pdf', $archivos['folleto_pdf'], $id)) {

                @unlink("../assets/pdf/" . $archivos['folleto_pdf']);

            }

        }

        if (!empty($archivos['mapa_imagen'])) {

            if (!archivo_usado_por_otro_tour($db, 'mapa_imagen', $archivos['mapa_imagen'], $id)) {

                @unlink("../assets/img/mapas/" . $archivos['mapa_imagen']);

            }

        }

        for ($i = 1; $i <= 4; $i++) {

            if (!empty($archivos['img_galeria' . $i])) {

                $campo = 'img_galeria' . $i;

                if (!archivo_usado_por_otro_tour($db, $campo, $archivos[$campo], $id)) {

                    @unlink($ruta_tours . $archivos[$campo]);

                }

            }

        }

        if (!empty($archivos['galeria_itinerario'])) {

            $fotos_premium = explode(",", $archivos['galeria_itinerario']);

            foreach ($fotos_premium as $f_pre) {

                $foto = trim($f_pre);

                if (!empty($foto) && !galeria_usada_por_otro_tour($db, $foto, $id)) {

                    @unlink($ruta_tours . $foto);

                }

            }

        }

    }

    $stmt = $db->prepare("DELETE FROM tours WHERE id = ?");

    $stmt->execute([$id]);

    header("Location: tours.php?res=success&msg=Tour+eliminado");

    exit;

}



// ================================================================

// LÓGICA 3: GUARDAR O EDITAR TOUR (POST)

// ================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_tour'])) {



    // Captura de datos básicos

    $id_tour            = $_POST['id_tour'] ?? '';

    $id_categoria       = (int)($_POST['id_categoria'] ?? 0);

    $parent_id          = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;



    $titulo             = $_POST['titulo'] ?? '';

    $titulo_en          = $_POST['titulo_en'] ?? '';

    $titulo_corto       = $_POST['titulo_corto'] ?? '';

    $titulo_corto_en    = $_POST['titulo_corto_en'] ?? '';

    $precio             = (!empty($_POST['precio'])) ? $_POST['precio'] : 0;

    $precio_soles       = (!empty($_POST['precio_soles'])) ? $_POST['precio_soles'] : null;

    $auto_soles         = isset($_POST['auto_soles']);

    $moneda             = $_POST['moneda'] ?? 'USD';

    $tipo_precio        = $_POST['tipo_precio'] ?? 'persona';

    $duracion           = $_POST['duracion'] ?? '';

    $duracion_en        = $_POST['duracion_en'] ?? '';

    $descripcion_corta  = $_POST['descripcion_corta'] ?? '';

    $descripcion_corta_en = $_POST['descripcion_corta_en'] ?? '';

    $itinerario         = $_POST['itinerario'] ?? '';

    $itinerario_en      = $_POST['itinerario_en'] ?? '';

    $incluye            = $_POST['incluye'] ?? '';

    $incluye_en         = $_POST['incluye_en'] ?? '';

    $no_incluye         = $_POST['no_incluye'] ?? '';

    $no_incluye_en      = $_POST['no_incluye_en'] ?? '';

    $info_detallada     = $_POST['info_importante_detallada'] ?? '';

    $info_detallada_en  = $_POST['info_importante_detallada_en'] ?? '';



    $video_url          = $_POST['video_url'] ?? '';

    $altitud_max        = $_POST['altitud_max'] ?? '';

    $dificultad         = $_POST['dificultad'] ?? 'Moderado';

    $ubicacion_texto    = $_POST['ubicacion_texto'] ?? '';

    $distancia_caminata = $_POST['distancia_caminata'] ?? '';

    $comidas_info       = $_POST['comidas_info'] ?? '';

    $comidas_info_en    = $_POST['comidas_info_en'] ?? '';

    

    // IMPORTANTE: No sobreescribir la variable de texto si no hay archivo nuevo

    $antes_de_viajar    = $_POST['antes_de_viajar'] ?? '';

    $antes_de_viajar_en = $_POST['antes_de_viajar_en'] ?? '';

    

    $destacados         = $_POST['destacados'] ?? '';

    $destacados_en      = $_POST['destacados_en'] ?? '';

    $itinerario_resumen = $_POST['itinerario_resumen'] ?? '';

    $itinerario_resumen_en = $_POST['itinerario_resumen_en'] ?? '';

    $lista_equipaje     = $_POST['lista_equipaje'] ?? '';

    $lista_equipaje_en  = $_POST['lista_equipaje_en'] ?? '';

    $precio_grupal      = (!empty($_POST['precio_grupal'])) ? $_POST['precio_grupal'] : 0;

    $porcentaje_adelanto = (!empty($_POST['porcentaje_adelanto'])) ? (int)$_POST['porcentaje_adelanto'] : 30;

    $precio_nino        = (!empty($_POST['precio_nino'])) ? (float)$_POST['precio_nino'] : null;

    $max_personas       = (!empty($_POST['max_personas'])) ? (int)$_POST['max_personas'] : 0;

    $pago_adelantado    = (!empty($_POST['pago_adelantado'])) ? $_POST['pago_adelantado'] : 0;

    $saldo_cusco        = (!empty($_POST['saldo_cusco'])) ? $_POST['saldo_cusco'] : 0;

    $extras_texto       = $_POST['extras_texto'] ?? '';

    $extras_texto_en    = $_POST['extras_texto_en'] ?? '';

    $aclimatacion_texto = $_POST['aclimatacion_texto'] ?? '';

    $aclimatacion_texto_en = $_POST['aclimatacion_texto_en'] ?? '';



    $mostrar_precio   = isset($_POST['mostrar_precio']) ? 1 : 0;

    $es_recomendado   = isset($_POST['es_recomendado']) ? 1 : 0;

    $en_oferta        = isset($_POST['en_oferta']) ? 1 : 0;

    $en_menu          = isset($_POST['en_menu']) ? 1 : 0;

    $estado           = 'activo';



    // Manejo de Galería de Itinerario (Fotos Premium)

    $cadena_fotos = "";

    if (!empty($id_tour)) {

        $stmt_gal = $db->prepare("SELECT galeria_itinerario FROM tours WHERE id = ?");

        $stmt_gal->execute([$id_tour]);

        $cadena_fotos = $stmt_gal->fetchColumn() ?: "";



        // Eliminar fotos marcadas

        if (!empty($_POST['fotos_para_eliminar'])) {

            $array_eliminar = explode(",", $_POST['fotos_para_eliminar']);

            foreach ($array_eliminar as $foto_nom) {

                $foto_nom = trim($foto_nom);

                if (!empty($foto_nom) && file_exists("../assets/img/tours/" . $foto_nom)) {

                    if (empty($id_tour) || !galeria_usada_por_otro_tour($db, $foto_nom, (int)$id_tour)) {

                        @unlink("../assets/img/tours/" . $foto_nom);

                    }

                }

            }

            $fotos_actuales = explode(",", $cadena_fotos);

            $fotos_restantes = array_filter($fotos_actuales, function ($f) use ($array_eliminar) {

                return !in_array(trim($f), $array_eliminar) && trim($f) !== "";

            });

            $cadena_fotos = implode(",", $fotos_restantes);

        }

    }



    // Subir nuevas fotos a la galería

    if (isset($_FILES['fotos_itinerario']) && !empty($_FILES['fotos_itinerario']['name'][0])) {

        foreach ($_FILES['fotos_itinerario']['tmp_name'] as $key => $tmp_name) {

            if ($_FILES['fotos_itinerario']['error'][$key] == 0) {

                $nombre_p = procesar_imagen_upload(
                    [
                        'tmp_name' => $_FILES['fotos_itinerario']['tmp_name'][$key],
                        'name'     => $_FILES['fotos_itinerario']['name'][$key],
                        'error'    => $_FILES['fotos_itinerario']['error'][$key]
                    ],
                    "../assets/img/tours/",
                    time() . "_" . uniqid()
                );

                if ($nombre_p) {

                    $cadena_fotos .= ($cadena_fotos != "" ? "," : "") . $nombre_p;

                }

            }

        }

    }



    // --- PROCESAR ARCHIVOS INDIVIDUALES ---

    $img_sql = "";

    $nombre_img = "";

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {

        $nombre_img = procesar_imagen_upload(
            $_FILES['imagen'],
            "../assets/img/tours/",
            time() . "_principal"
        );

        $img_sql = ", imagen_principal = '$nombre_img'";

    }



    
    $pdf_sql = "";
    $nombre_pdf = "";
    
    // Handle borrar_folleto_pdf
    if (!empty($_POST['borrar_folleto_pdf']) && $_POST['borrar_folleto_pdf'] == "1" && empty($_FILES['nuevo_pdf']['name'])) {
        // Get current PDF filename to delete physical file
        if (!empty($id_tour)) {
            $stmt_pdf = $db->prepare("SELECT folleto_pdf FROM tours WHERE id = ?");
            $stmt_pdf->execute([$id_tour]);
            $old_pdf = $stmt_pdf->fetchColumn();
            if (!empty($old_pdf) && file_exists("../assets/pdf/" . $old_pdf)) {
                @unlink("../assets/pdf/" . $old_pdf);
            }
            $pdf_sql = ", folleto_pdf = ''";
        }
    } elseif (isset($_FILES['nuevo_pdf']) && $_FILES['nuevo_pdf']['error'] == 0) {
        $nombre_pdf = time() . "_folleto.pdf";
        move_uploaded_file($_FILES['nuevo_pdf']['tmp_name'], "../assets/pdf/" . $nombre_pdf);
        $pdf_sql = ", folleto_pdf = '$nombre_pdf'";
    } elseif (!empty($_POST['folleto_pdf'])) {
        // Manual text input (from dropdown selection)
        $pdf_val = trim($_POST['folleto_pdf']);
        if ($pdf_val !== ($datos['folleto_pdf'] ?? '')) {
            $pdf_sql = ", folleto_pdf = '" . addslashes($pdf_val) . "'";
        }
    }

    $mapa_sql = "";
    $nombre_mapa = "";
    
    // Handle borrar_mapa_imagen
    if (!empty($_POST['borrar_mapa_imagen']) && $_POST['borrar_mapa_imagen'] == "1" && empty($_FILES['mapa_imagen']['name'])) {
        if (!empty($id_tour)) {
            $stmt_mapa = $db->prepare("SELECT mapa_imagen FROM tours WHERE id = ?");
            $stmt_mapa->execute([$id_tour]);
            $old_mapa = $stmt_mapa->fetchColumn();
            if (!empty($old_mapa) && file_exists("../assets/img/mapas/" . $old_mapa)) {
                @unlink("../assets/img/mapas/" . $old_mapa);
            }
            $mapa_sql = ", mapa_imagen = ''";
        }
    } elseif (isset($_FILES['mapa_imagen']) && $_FILES['mapa_imagen']['error'] == 0) {
        $nombre_mapa = procesar_imagen_upload(
            $_FILES['mapa_imagen'],
            "../assets/img/mapas/",
            time() . "_mapa"
        );
        $mapa_sql = ", mapa_imagen = '$nombre_mapa'";
    }

// Galería fija de 4 imágenes

    $galeria_fija_sql = "";

    $g = [1 => '', 2 => '', 3 => '', 4 => ''];

    for ($i = 1; $i <= 4; $i++) {

        $campo = "img_galeria" . $i;

        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {

            $g[$i] = procesar_imagen_upload(
                $_FILES[$campo],
                "../assets/img/tours/",
                time() . "_g" . $i
            );

            $galeria_fija_sql .= ", $campo = '" . $g[$i] . "'";

        } elseif (isset($_POST['borrar_' . $campo]) && $_POST['borrar_' . $campo] == "1") {

            $galeria_fija_sql .= ", $campo = ''";

        }

    }



    // Subida opcional de PDF en "Antes de viajar"

    if (isset($_FILES['pdf_antes_viajar']) && $_FILES['pdf_antes_viajar']['error'] == 0) {

        $nombre_limpio_pdf = time() . "_" . str_replace(' ', '_', strtolower($_FILES['pdf_antes_viajar']['name']));

        move_uploaded_file($_FILES['pdf_antes_viajar']['tmp_name'], "../assets/img/tours/" . $nombre_limpio_pdf);

        $antes_de_viajar = $nombre_limpio_pdf;

    }



    // ================================================================

    // EJECUCIÓN FINAL DEL SQL

    // ================================================================

    if (!empty($id_tour)) {

        // UPDATE (Añadimos todas las columnas _en)

        $sql = "UPDATE tours SET 

                titulo=?, titulo_en=?, id_categoria=?, parent_id=?, precio=?, precio_soles=?, duracion=?, duracion_en=?, 

                titulo_corto=?, titulo_corto_en=?, descripcion_corta=?, descripcion_corta_en=?, 

                itinerario=?, itinerario_en=?, moneda=?, tipo_precio=?, incluye=?, incluye_en=?, 

                no_incluye=?, no_incluye_en=?, mostrar_precio=?, es_recomendado=?, en_oferta=?, 

                en_menu=?, video_url=?, altitud_max=?, dificultad=?, ubicacion_texto=?, 

                distancia_caminata=?, comidas_info=?, comidas_info_en=?, destacados=?, destacados_en=?, 

                itinerario_resumen=?, itinerario_resumen_en=?, lista_equipaje=?, lista_equipaje_en=?, 

                precio_grupal=?, porcentaje_adelanto=?, precio_nino=?, max_personas=?, pago_adelantado=?, saldo_cusco=?, extras_texto=?, extras_texto_en=?, 

                info_importante_detallada=?, info_importante_detallada_en=?, antes_de_viajar=?, 

                antes_de_viajar_en=?, aclimatacion_texto=?, aclimatacion_texto_en=?, 

                galeria_itinerario=? " . $img_sql . $pdf_sql . $mapa_sql . $galeria_fija_sql . " WHERE id=?";



        $params = [

            $titulo,

            $titulo_en,

            $id_categoria,

            $parent_id,

            (float)$precio,

            $auto_soles ? (($moneda === 'PEN') ? (float)$precio : round((float)$precio * (float)$tipo_cambio_actual, 2)) : (($precio_soles !== null) ? (float)$precio_soles : (($moneda === 'PEN') ? (float)$precio : round((float)$precio * (float)$tipo_cambio_actual, 2))),

            $duracion,

            $duracion_en,

            $titulo_corto,

            $titulo_corto_en,

            $descripcion_corta,

            $descripcion_corta_en,

            $itinerario,

            $itinerario_en,

            $moneda,

            $tipo_precio,

            $incluye,

            $incluye_en,

            $no_incluye,

            $no_incluye_en,

            (int)$mostrar_precio,

            (int)$es_recomendado,

            (int)$en_oferta,

            (int)$en_menu,

            $video_url,

            $altitud_max,

            $dificultad,

            $ubicacion_texto,

            $distancia_caminata,

            $comidas_info,

            $comidas_info_en,

            $destacados,

            $destacados_en,

            $itinerario_resumen,

            $itinerario_resumen_en,

            $lista_equipaje,

            $lista_equipaje_en,

            (float)$precio_grupal,

            (int)$porcentaje_adelanto,

            $precio_nino,

            (int)$max_personas,

            (float)$pago_adelantado,

            (float)$saldo_cusco,

            $extras_texto,

            $extras_texto_en,

            $info_detallada,

            $info_detallada_en,

            $antes_de_viajar,

            $antes_de_viajar_en,

            $aclimatacion_texto,

            $aclimatacion_texto_en,

            $cadena_fotos,

            $id_tour

        ];

    } else {

        // INSERT (Para tours nuevos)

        $sql = "INSERT INTO tours (

                titulo, titulo_en, id_categoria, parent_id, precio, precio_soles, duracion, duracion_en, 

                titulo_corto, titulo_corto_en, descripcion_corta, descripcion_corta_en, 

                itinerario, itinerario_en, moneda, tipo_precio, incluye, incluye_en, 

                no_incluye, no_incluye_en, mostrar_precio, es_recomendado, en_oferta, 

                en_menu, video_url, altitud_max, dificultad, ubicacion_texto, distancia_caminata, 

                comidas_info, comidas_info_en, destacados, destacados_en, itinerario_resumen, 

                itinerario_resumen_en, lista_equipaje, lista_equipaje_en, precio_grupal, 

                porcentaje_adelanto, precio_nino, max_personas, pago_adelantado, saldo_cusco, extras_texto, extras_texto_en, info_importante_detallada, 

                info_importante_detallada_en, antes_de_viajar, antes_de_viajar_en, aclimatacion_texto, 

                aclimatacion_texto_en, galeria_itinerario, imagen_principal, folleto_pdf, mapa_imagen, 

                img_galeria1, img_galeria2, img_galeria3, img_galeria4, estado

                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";



        $params = [

            $titulo,

            $titulo_en,

            $id_categoria,

            $parent_id,

            (float)$precio,

            $auto_soles ? (($moneda === 'PEN') ? (float)$precio : round((float)$precio * (float)$tipo_cambio_actual, 2)) : (($precio_soles !== null) ? (float)$precio_soles : (($moneda === 'PEN') ? (float)$precio : round((float)$precio * (float)$tipo_cambio_actual, 2))),

            $duracion,

            $duracion_en,

            $titulo_corto,

            $titulo_corto_en,

            $descripcion_corta,

            $descripcion_corta_en,

            $itinerario,

            $itinerario_en,

            $moneda,

            $tipo_precio,

            $incluye,

            $incluye_en,

            $no_incluye,

            $no_incluye_en,

            (int)$mostrar_precio,

            (int)$es_recomendado,

            (int)$en_oferta,

            (int)$en_menu,

            $video_url,

            $altitud_max,

            $dificultad,

            $ubicacion_texto,

            $distancia_caminata,

            $comidas_info,

            $comidas_info_en,

            $destacados,

            $destacados_en,

            $itinerario_resumen,

            $itinerario_resumen_en,

            $lista_equipaje,

            $lista_equipaje_en,

            (float)$precio_grupal,

            (int)$porcentaje_adelanto,

            $precio_nino,

            (int)$max_personas,

            (float)$pago_adelantado,

            (float)$saldo_cusco,

            $extras_texto,

            $extras_texto_en,

            $info_detallada,

            $info_detallada_en,

            $antes_de_viajar,

            $antes_de_viajar_en,

            $aclimatacion_texto,

            $aclimatacion_texto_en,

            $cadena_fotos,

            $nombre_img,

            $nombre_pdf,

            $nombre_mapa,

            $g[1],

            $g[2],

            $g[3],

            $g[4],

            $estado

        ];

    }



    $stmt = $db->prepare($sql);

    $res = $stmt->execute($params);



    if ($res) {

        header("Location: tours.php?res=success&msg=" . urlencode(!empty($id_tour) ? "Actualizado correctamente" : "Creado exitosamente"));

    } else {

        echo "<h1>Error de SQL:</h1><pre>";

        print_r($stmt->errorInfo());

        echo "</pre><br>SQL: " . $sql;

        exit;

    }

}

