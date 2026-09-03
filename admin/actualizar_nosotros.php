<?php
/**
 * ARCHIVO: actualizar_nosotros.php
 * DESCRIPCIÓN: Procesa todos los cambios de la sección Nosotros de IntiPath Tours.
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('nosotros');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ID único de la fila
    $id = $_POST['id'];

    // 1. CAPTURA DE TEXTOS
    $titulo       = (string)($_POST['titulo'] ?? '');
    $subtitulo    = (string)($_POST['subtitulo'] ?? '');
    $resumen      = (string)($_POST['resumen'] ?? '');
    $descripcion  = (string)($_POST['descripcion_larga'] ?? '');
    $mision       = (string)($_POST['mision'] ?? '');
    $vision       = (string)($_POST['vision'] ?? '');
    $valores      = (string)($_POST['valores'] ?? ''); // <--- CAPTURADO
    $titulo_ger   = (string)($_POST['titulo_gerencia'] ?? '');
    $cont_ger     = (string)($_POST['contenido_gerencia'] ?? '');
    $frase_ger    = (string)($_POST['frase_gerente'] ?? '');
    $equipo_json  = (string)($_POST['equipo_json'] ?? '');

    // 2. RECUPERAR IMÁGENES ACTUALES
    $img_principal = $_POST['img_actual_principal'] ?? '';
    $img_resumen   = $_POST['img_actual_resumen'] ?? '';
    $img_historia  = $_POST['img_actual_historia'] ?? '';
    $img_gerencia  = $_POST['img_actual_gerencia'] ?? '';

    try {
        $ruta_img = "../assets/img/";
        $ruta_equipo = "../assets/img/equipo/";

        // Función rápida para procesar subidas
        function procesarSubida($file_key, $prefijo, $ruta_img, $actual)
        {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $nuevo_nombre = $prefijo . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $ruta_img . $nuevo_nombre)) {
                    return $nuevo_nombre;
                }
            }
            return $actual;
        }

        // 3. PROCESAR CADA IMAGEN
        $img_principal = procesarSubida('imagen_principal', 'banner', $ruta_img, $img_principal);
        $img_resumen   = procesarSubida('imagen_resumen', 'resumen', $ruta_img, $img_resumen);
        $img_historia  = procesarSubida('imagen_historia', 'historia', $ruta_img, $img_historia);
        $img_gerencia  = procesarSubida('imagen_gerencia', 'gerente', $ruta_img, $img_gerencia);

        // 4. CARGA MÚLTIPLE DE EQUIPO
        if (isset($_FILES['fotos_equipo'])) {
            if (!is_dir($ruta_equipo)) mkdir($ruta_equipo, 0777, true);
            $total_fotos = count($_FILES['fotos_equipo']['name']);
            for ($i = 0; $i < $total_fotos; $i++) {
                if ($_FILES['fotos_equipo']['error'][$i] === 0) {
                    $nombre_limpio = preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['fotos_equipo']['name'][$i]);
                    move_uploaded_file($_FILES['fotos_equipo']['tmp_name'][$i], $ruta_equipo . $nombre_limpio);
                }
            }
        }

        // 5. SENTENCIA SQL FINAL (Incluyendo 'valores')
        $sql = "UPDATE pagina_nosotros SET 
                titulo = :tit, 
                subtitulo = :sub, 
                resumen = :res, 
                descripcion_larga = :desc, 
                mision = :mis, 
                vision = :vis, 
                valores = :val,  -- <--- AGREGADO A LA CONSULTA
                imagen_principal = :imgp, 
                imagen_resumen = :imgr,
                imagen_historia = :imgh,
                titulo_gerencia = :t_ger,
                contenido_gerencia = :c_ger,
                frase_gerente = :f_ger,
                imagen_gerencia = :i_ger,
                equipo_json = :equipo
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':tit'    => $titulo,
            ':sub'    => $subtitulo,
            ':res'    => $resumen,
            ':desc'   => $descripcion,
            ':mis'    => $mision,
            ':vis'    => $vision,
            ':val'    => $valores, // <--- VINCULADO AL SQL
            ':imgp'   => $img_principal,
            ':imgr'   => $img_resumen,
            ':imgh'   => $img_historia,
            ':t_ger'  => $titulo_ger,
            ':c_ger'  => $cont_ger,
            ':f_ger'  => $frase_ger,
            ':i_ger'  => $img_gerencia,
            ':equipo' => $equipo_json,
            ':id'     => $id
        ]);

        header("Location: admin_menu_nosotros.php?status=success");
    } catch (Exception $e) {
        header("Location: admin_menu_nosotros.php?status=error&msg=" . urlencode($e->getMessage()));
    }
}