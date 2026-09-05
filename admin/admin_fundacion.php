<?php
// admin/admin_fundacion.php — Fundación: config general + CRUD proyectos
ob_start();
require_once __DIR__ . '/../includes/auth_helper.php';

// Detectar AJAX ANTES de verificar sesión — si la sesión expiró, devolver JSON en vez de redirect
$is_ajax = !empty($_POST['ajax_guardar_proyecto'])
    || !empty($_POST['reemplazar_secimg']) || !empty($_POST['reorder_secimg'])
    || !empty($_POST['reorder_secciones']) || !empty($_POST['reorder_proyectos'])
    || !empty($_GET['eliminar_seccion'])
    || !empty($_GET['eliminar_secimg']) || !empty($_GET['toggle_proyecto'])
    || !empty($_GET['eliminar_proyecto']);

if ($is_ajax) {
    while (ob_get_level()) ob_end_clean();
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.']);
        exit;
    }
}

// Envolver TODO en try-catch para que errores de setup devuelvan JSON en vez de 500
try {
    requierePermiso('fundacion');
    require_once '../config/database.php';
    require_once __DIR__ . '/../includes/image_helper.php';

    $db = (new Database())->getConnection();
    $img_dir = '../assets/img/fundacion/';
    if (!is_dir($img_dir)) {
        @mkdir($img_dir, 0777, true);
    }
} catch (Throwable $e) {
    if ($is_ajax) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Error de configuración: ' . $e->getMessage()]);
        exit;
    }
    throw $e;
}

$mensaje = null;

// =============================================
// POST: Guardar configuración general
// =============================================
if (isset($_POST['guardar_config'])) {
    $campos = ['titulo','titulo_en','subtitulo','subtitulo_en','descripcion','descripcion_en',
               'mision','mision_en','vision','vision_en','valores','valores_en',
               'cita','cita_en','diferente','diferente_en'];
    $vals = [];
    foreach ($campos as $c) {
        $vals[$c] = $_POST[$c] ?? '';
    }
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Upload hero
    if (!empty($_FILES['hero_imagen']['tmp_name']) && $_FILES['hero_imagen']['error'] == 0) {
        error_log("FUNDACION HERO UPLOAD: file=" . $_FILES['hero_imagen']['name'] . " size=" . $_FILES['hero_imagen']['size']);
        $hero = procesar_imagen_upload($_FILES['hero_imagen'], $img_dir, 'fundacion_hero_' . time());
        error_log("FUNDACION HERO RESULT: " . ($hero ?: 'EMPTY'));
        if ($hero) {
            $old = $db->query("SELECT hero_imagen FROM fundacion WHERE id=1")->fetchColumn();
            if ($old && file_exists($img_dir . $old)) @unlink($img_dir . $old);
            $vals['hero_imagen'] = $hero;
        }
    }

    // Upload logo
    if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] == 0) {
        error_log("FUNDACION LOGO UPLOAD: file=" . $_FILES['logo']['name'] . " size=" . $_FILES['logo']['size']);
        $logo = procesar_imagen_upload($_FILES['logo'], $img_dir, 'fundacion_logo_' . time());
        error_log("FUNDACION LOGO RESULT: " . ($logo ?: 'EMPTY'));
        if ($logo) {
            $old = $db->query("SELECT logo FROM fundacion WHERE id=1")->fetchColumn();
            if ($old && file_exists($img_dir . $old)) @unlink($img_dir . $old);
            $vals['logo'] = $logo;
        }
    }

    $sets = [];
    $params = [];
    foreach ($vals as $k => $v) {
        $sets[] = "$k=?";
        $params[] = $v;
    }
    $sets[] = "activo=?";
    $params[] = $activo;

    $sql = "UPDATE fundacion SET " . implode(', ', $sets) . " WHERE id=1";
    $db->prepare($sql)->execute($params);
    $mensaje = 'config_ok';
}

// =============================================
// AJAX: Guardar proyecto (retorna JSON)
// =============================================
if (isset($_POST['ajax_guardar_proyecto'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $pid = (int)($_POST['proyecto_id'] ?? 0);
        $titulo = trim($_POST['proyecto_titulo'] ?? '');
        $titulo_en = trim($_POST['proyecto_titulo_en'] ?? '');
        $subtitulo = trim($_POST['proyecto_subtitulo'] ?? '');
        $subtitulo_en = trim($_POST['proyecto_subtitulo_en'] ?? '');
        $descripcion_corta = trim($_POST['proyecto_descripcion_corta'] ?? '');
        $descripcion_corta_en = trim($_POST['proyecto_descripcion_corta_en'] ?? '');
        $descripcion = $_POST['proyecto_descripcion'] ?? '';
        $descripcion_en = $_POST['proyecto_descripcion_en'] ?? '';
        $slug_pagina = trim($_POST['proyecto_slug'] ?? '');
        $activo_p = isset($_POST['proyecto_activo']) ? 1 : 0;
        $imagen_actual = $_POST['proyecto_imagen_actual'] ?? 'default-proyecto.webp';

        if (empty($titulo)) {
            echo json_encode(['ok' => false, 'error' => 'El título es obligatorio']);
            exit;
        }

        if (!empty($_FILES['proyecto_imagen']['tmp_name']) && $_FILES['proyecto_imagen']['error'] == 0) {
            $nueva_img = procesar_imagen_upload($_FILES['proyecto_imagen'], $img_dir, 'proyecto_' . time());
            if ($nueva_img) {
                if ($imagen_actual && $imagen_actual !== 'default-proyecto.webp' && file_exists($img_dir . $imagen_actual)) {
                    @unlink($img_dir . $imagen_actual);
                }
                $imagen_actual = $nueva_img;
            }
        }

        if ($pid > 0) {
            $stmt = $db->prepare("UPDATE fundacion_proyectos SET imagen=?, titulo=?, titulo_en=?, subtitulo=?, subtitulo_en=?, descripcion_corta=?, descripcion_corta_en=?, descripcion=?, descripcion_en=?, slug_pagina=?, activo=? WHERE id=?");
            $stmt->execute([$imagen_actual, $titulo, $titulo_en, $subtitulo, $subtitulo_en, $descripcion_corta, $descripcion_corta_en, $descripcion, $descripcion_en, $slug_pagina, $activo_p, $pid]);
        } else {
            $max_orden = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_proyectos")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO fundacion_proyectos (imagen, titulo, titulo_en, subtitulo, subtitulo_en, descripcion_corta, descripcion_corta_en, descripcion, descripcion_en, slug_pagina, activo, orden) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$imagen_actual, $titulo, $titulo_en, $subtitulo, $subtitulo_en, $descripcion_corta, $descripcion_corta_en, $descripcion, $descripcion_en, $slug_pagina, $activo_p, $max_orden]);
            $pid = (int)$db->lastInsertId();
        }

        echo json_encode(['ok' => true, 'id' => $pid, 'imagen' => $imagen_actual]);
    } catch (Exception $e) {
        error_log("FUNDACION AJAX ERROR: " . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
    }
    exit;
}

// =============================================
// POST: Guardar proyecto (crear/editar) — fallback sin AJAX
// =============================================
if (isset($_POST['guardar_proyecto'])) {
    $pid = (int)($_POST['proyecto_id'] ?? 0);
    $titulo = trim($_POST['proyecto_titulo'] ?? '');
    $titulo_en = trim($_POST['proyecto_titulo_en'] ?? '');
    $subtitulo = trim($_POST['proyecto_subtitulo'] ?? '');
    $subtitulo_en = trim($_POST['proyecto_subtitulo_en'] ?? '');
    $descripcion_corta = trim($_POST['proyecto_descripcion_corta'] ?? '');
    $descripcion_corta_en = trim($_POST['proyecto_descripcion_corta_en'] ?? '');
    $descripcion = $_POST['proyecto_descripcion'] ?? '';
    $descripcion_en = $_POST['proyecto_descripcion_en'] ?? '';
    $slug_pagina = trim($_POST['proyecto_slug'] ?? '');
    $activo_p = isset($_POST['proyecto_activo']) ? 1 : 0;
    $imagen_actual = $_POST['proyecto_imagen_actual'] ?? 'default-proyecto.webp';

    // Upload imagen
    if (!empty($_FILES['proyecto_imagen']['tmp_name']) && $_FILES['proyecto_imagen']['error'] == 0) {
        error_log("FUNDACION UPLOAD: file=" . $_FILES['proyecto_imagen']['name'] . " size=" . $_FILES['proyecto_imagen']['size'] . " tmp=" . $_FILES['proyecto_imagen']['tmp_name']);
        $nueva_img = procesar_imagen_upload($_FILES['proyecto_imagen'], $img_dir, 'proyecto_' . time());
        error_log("FUNDACION UPLOAD RESULT: " . ($nueva_img ?: 'EMPTY'));
        if ($nueva_img) {
            if ($imagen_actual && $imagen_actual !== 'default-proyecto.webp' && file_exists($img_dir . $imagen_actual)) {
                @unlink($img_dir . $imagen_actual);
            }
            $imagen_actual = $nueva_img;
        }
    }

    if ($pid > 0) {
        $stmt = $db->prepare("UPDATE fundacion_proyectos SET imagen=?, titulo=?, titulo_en=?, subtitulo=?, subtitulo_en=?, descripcion_corta=?, descripcion_corta_en=?, descripcion=?, descripcion_en=?, slug_pagina=?, activo=? WHERE id=?");
        $stmt->execute([$imagen_actual, $titulo, $titulo_en, $subtitulo, $subtitulo_en, $descripcion_corta, $descripcion_corta_en, $descripcion, $descripcion_en, $slug_pagina, $activo_p, $pid]);
    } else {
        $max_orden = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_proyectos")->fetchColumn();
        $stmt = $db->prepare("INSERT INTO fundacion_proyectos (imagen, titulo, titulo_en, subtitulo, subtitulo_en, descripcion_corta, descripcion_corta_en, descripcion, descripcion_en, slug_pagina, activo, orden) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$imagen_actual, $titulo, $titulo_en, $subtitulo, $subtitulo_en, $descripcion_corta, $descripcion_corta_en, $descripcion, $descripcion_en, $slug_pagina, $activo_p, $max_orden]);
    }
    header("Location: admin_fundacion.php?res=proyecto_ok");
    exit;
}

// =============================================
// GET: Eliminar proyecto
// =============================================
if (isset($_GET['eliminar_proyecto'])) {
    $eid = (int)$_GET['eliminar_proyecto'];
    $st = $db->prepare("SELECT imagen FROM fundacion_proyectos WHERE id=?");
    $st->execute([$eid]);
    $img = $st->fetchColumn();
    $db->prepare("DELETE FROM fundacion_proyectos WHERE id=?")->execute([$eid]);
    if ($img && $img !== 'default-proyecto.webp' && file_exists($img_dir . $img)) @unlink($img_dir . $img);
    // Eliminar galería
    $gals = $db->prepare("SELECT imagen FROM fundacion_proyecto_imagenes WHERE proyecto_id=?");
    $gals->execute([$eid]);
    foreach ($gals->fetchAll(PDO::FETCH_COLUMN) as $gi) {
        if ($gi && file_exists($img_dir . $gi)) @unlink($img_dir . $gi);
    }
    $db->prepare("DELETE FROM fundacion_proyecto_imagenes WHERE proyecto_id=?")->execute([$eid]);
    header("Location: admin_fundacion.php?res=proyecto_eliminado");
    exit;
}

// =============================================
// POST: Crear sección nueva
// =============================================
if (isset($_POST['crear_seccion'])) {
    $cs_pid = (int)($_POST['seccion_proyecto_id'] ?? 0);
    $cs_titulo = trim($_POST['seccion_titulo'] ?? '');
    $cs_titulo_en = trim($_POST['seccion_titulo_en'] ?? '');
    $cs_descripcion = $_POST['seccion_descripcion'] ?? '';
    $cs_descripcion_en = $_POST['seccion_descripcion_en'] ?? '';
    if ($cs_pid > 0 && !empty($_FILES['seccion_imagen']['tmp_name']) && $_FILES['seccion_imagen']['error'] == 0) {
        $cs_img = procesar_imagen_upload($_FILES['seccion_imagen'], $img_dir, 'seccion_' . time());
        if ($cs_img) {
            $max_so = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_secciones WHERE proyecto_id=$cs_pid")->fetchColumn();
            $db->prepare("INSERT INTO fundacion_secciones (proyecto_id, titulo, titulo_en, descripcion, descripcion_en, imagen_principal, orden) VALUES (?,?,?,?,?,?,?)")->execute([$cs_pid, $cs_titulo, $cs_titulo_en, $cs_descripcion, $cs_descripcion_en, $cs_img, $max_so]);
        }
    }
    header("Location: admin_fundacion.php?editar_proyecto=$cs_pid&res=seccion_ok");
    exit;
}

// =============================================
// POST: Editar sección (título + imagen)
// =============================================
if (isset($_POST['editar_seccion'])) {
    $es_id = (int)($_POST['seccion_id'] ?? 0);
    $es_pid = (int)($_POST['seccion_proyecto_id'] ?? 0);
    $es_titulo = trim($_POST['seccion_titulo_edit'] ?? '');
    $es_titulo_en = trim($_POST['seccion_titulo_en_edit'] ?? '');
    $es_descripcion = $_POST['seccion_descripcion_edit'] ?? '';
    $es_descripcion_en = $_POST['seccion_descripcion_en_edit'] ?? '';
    if ($es_id > 0) {
        $es_img_actual = $db->query("SELECT imagen_principal FROM fundacion_secciones WHERE id=$es_id")->fetchColumn();
        $es_img = $es_img_actual;
        if (!empty($_FILES['seccion_imagen_edit']['tmp_name']) && $_FILES['seccion_imagen_edit']['error'] == 0) {
            $es_img = procesar_imagen_upload($_FILES['seccion_imagen_edit'], $img_dir, 'seccion_' . time());
            if ($es_img && $es_img_actual && file_exists($img_dir . $es_img_actual)) @unlink($img_dir . $es_img_actual);
        }
        $db->prepare("UPDATE fundacion_secciones SET titulo=?, titulo_en=?, descripcion=?, descripcion_en=?, imagen_principal=? WHERE id=?")->execute([$es_titulo, $es_titulo_en, $es_descripcion, $es_descripcion_en, $es_img, $es_id]);
    }
    header("Location: admin_fundacion.php?editar_proyecto=$es_pid&res=seccion_ok");
    exit;
}

// =============================================
// GET: Eliminar sección (AJAX)
// =============================================
if (isset($_GET['eliminar_seccion'])) {
    header('Content-Type: application/json');
    $sid = (int)$_GET['eliminar_seccion'];
    $st = $db->prepare("SELECT imagen_principal FROM fundacion_secciones WHERE id=?");
    $st->execute([$sid]);
    $simg = $st->fetchColumn();
    // Eliminar imágenes hijas
    $sis = $db->prepare("SELECT imagen FROM fundacion_seccion_imagenes WHERE seccion_id=?");
    $sis->execute([$sid]);
    foreach ($sis->fetchAll(PDO::FETCH_COLUMN) as $si) {
        if ($si && file_exists($img_dir . $si)) @unlink($img_dir . $si);
    }
    $db->prepare("DELETE FROM fundacion_seccion_imagenes WHERE seccion_id=?")->execute([$sid]);
    $db->prepare("DELETE FROM fundacion_secciones WHERE id=?")->execute([$sid]);
    if ($simg && file_exists($img_dir . $simg)) @unlink($img_dir . $simg);
    echo json_encode(['ok' => true]);
    exit;
}

// =============================================
// POST: Subir imágenes a sección
// =============================================
if (isset($_POST['guardar_seccion_imgs'])) {
    $si_pid = (int)($_POST['secimg_proyecto_id'] ?? 0);
    $si_sid = (int)($_POST['secimg_seccion_id'] ?? 0);
    if ($si_sid > 0 && !empty($_FILES['secimg_archivos']['tmp_name'][0])) {
        foreach ($_FILES['secimg_archivos']['tmp_name'] as $idx => $tmp) {
            if ($_FILES['secimg_archivos']['error'][$idx] == 0 && $tmp) {
                $file_data = [
                    'name' => $_FILES['secimg_archivos']['name'][$idx] ?? '',
                    'type' => $_FILES['secimg_archivos']['type'][$idx] ?? '',
                    'tmp_name' => $_FILES['secimg_archivos']['tmp_name'][$idx],
                    'error' => $_FILES['secimg_archivos']['error'][$idx],
                    'size' => $_FILES['secimg_archivos']['size'][$idx] ?? 0,
                ];
                $si_img = procesar_imagen_upload($file_data, $img_dir, 'secimg_' . time() . '_' . $idx);
                if ($si_img) {
                    $max_sio = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_seccion_imagenes WHERE seccion_id=$si_sid")->fetchColumn();
                    $db->prepare("INSERT INTO fundacion_seccion_imagenes (seccion_id, imagen, orden) VALUES (?,?,?)")->execute([$si_sid, $si_img, $max_sio + $idx]);
                }
            }
        }
    }
    header("Location: admin_fundacion.php?editar_proyecto=$si_pid&res=seccion_ok");
    exit;
}

// =============================================
// GET: Eliminar imagen de sección (AJAX)
// =============================================
if (isset($_GET['eliminar_secimg'])) {
    header('Content-Type: application/json');
    $ssi_id = (int)$_GET['eliminar_secimg'];
    $st = $db->prepare("SELECT imagen FROM fundacion_seccion_imagenes WHERE id=?");
    $st->execute([$ssi_id]);
    $ssi_img = $st->fetchColumn();
    if ($ssi_img) {
        $db->prepare("DELETE FROM fundacion_seccion_imagenes WHERE id=?")->execute([$ssi_id]);
        if (file_exists($img_dir . $ssi_img)) @unlink($img_dir . $ssi_img);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// =============================================
// POST: Reemplazar imagen de sección (AJAX)
// =============================================
if (isset($_POST['reemplazar_secimg'])) {
    header('Content-Type: application/json');
    $rsi_id = (int)($_POST['imagen_id'] ?? 0);
    if ($rsi_id > 0 && !empty($_FILES['nueva_imagen']['tmp_name']) && $_FILES['nueva_imagen']['error'] == 0) {
        $st = $db->prepare("SELECT imagen FROM fundacion_seccion_imagenes WHERE id=?");
        $st->execute([$rsi_id]);
        $old_img = $st->fetchColumn();
        $new_img = procesar_imagen_upload($_FILES['nueva_imagen'], $img_dir, 'secimg_' . time() . '_' . $rsi_id);
        if ($new_img) {
            $db->prepare("UPDATE fundacion_seccion_imagenes SET imagen=? WHERE id=?")->execute([$new_img, $rsi_id]);
            if ($old_img && file_exists($img_dir . $old_img)) @unlink($img_dir . $old_img);
            echo json_encode(['ok' => true, 'nueva' => $new_img]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al procesar imagen']);
        }
    } else {
        echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    }
    exit;
}

// =============================================
// POST: Reordenar imágenes de sección (AJAX)
// =============================================
if (isset($_POST['reorder_secimg'])) {
    header('Content-Type: application/json');
    $rsids = $_POST['ids'] ?? [];
    if (is_array($rsids)) {
        foreach ($rsids as $pos => $rsid) {
            $db->prepare("UPDATE fundacion_seccion_imagenes SET orden=? WHERE id=?")->execute([$pos, (int)$rsid]);
        }
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// =============================================
// POST: Reordenar secciones (AJAX)
// =============================================
if (isset($_POST['reorder_secciones'])) {
    header('Content-Type: application/json');
    $sids = $_POST['ids'] ?? [];
    if (is_array($sids)) {
        foreach ($sids as $pos => $sid) {
            $db->prepare("UPDATE fundacion_secciones SET orden=? WHERE id=?")->execute([$pos, (int)$sid]);
        }
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// =============================================
// GET: Obtener datos de sección (AJAX)
// =============================================
if (isset($_GET['obtener_seccion'])) {
    header('Content-Type: application/json');
    $os_id = (int)$_GET['obtener_seccion'];
    $os = $db->prepare("SELECT titulo, titulo_en, descripcion, descripcion_en FROM fundacion_secciones WHERE id=?");
    $os->execute([$os_id]);
    $os_row = $os->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'titulo' => $os_row['titulo'] ?? '', 'titulo_en' => $os_row['titulo_en'] ?? '', 'descripcion' => $os_row['descripcion'] ?? '', 'descripcion_en' => $os_row['descripcion_en'] ?? '']);
    exit;
}

// =============================================
// GET: Toggle activo proyecto (AJAX)
// =============================================
if (isset($_GET['toggle_proyecto'])) {
    $tid = (int)$_GET['toggle_proyecto'];
    $db->prepare("UPDATE fundacion_proyectos SET activo = NOT activo WHERE id=?")->execute([$tid]);
    $nuevo = $db->prepare("SELECT activo FROM fundacion_proyectos WHERE id=?");
    $nuevo->execute([$tid]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'activo' => (int)$nuevo->fetchColumn()]);
    exit;
}

// =============================================
// POST: Reordenar proyectos (AJAX drag & drop)
// =============================================
if (isset($_POST['reorder_proyectos'])) {
    header('Content-Type: application/json');
    $ids = $_POST['ids'] ?? [];
    if (is_array($ids)) {
        foreach ($ids as $position => $id) {
            $db->prepare("UPDATE fundacion_proyectos SET orden=? WHERE id=?")->execute([$position, (int)$id]);
        }
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Invalid data']);
    }
    exit;
}

// =============================================
// CARGAR DATOS
// =============================================
$fund = $db->query("SELECT * FROM fundacion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$proyectos = $db->query("SELECT * FROM fundacion_proyectos ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$editar_proyecto = null;
$secciones = [];
if (isset($_GET['editar_proyecto'])) {
    $st = $db->prepare("SELECT * FROM fundacion_proyectos WHERE id=?");
    $st->execute([(int)$_GET['editar_proyecto']]);
    $editar_proyecto = $st->fetch(PDO::FETCH_ASSOC);
    if ($editar_proyecto) {
        $sts = $db->prepare("SELECT * FROM fundacion_secciones WHERE proyecto_id=? ORDER BY orden ASC, id ASC");
        $sts->execute([$editar_proyecto['id']]);
        $secciones = $sts->fetchAll(PDO::FETCH_ASSOC);

        foreach ($secciones as &$sec) {
            $ssi = $db->prepare("SELECT * FROM fundacion_seccion_imagenes WHERE seccion_id=? ORDER BY orden ASC, id ASC");
            $ssi->execute([$sec['id']]);
            $sec['imagenes'] = $ssi->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($sec);
    }
}
$fp = $editar_proyecto ?? ['id'=>0,'imagen'=>'default-proyecto.webp','titulo'=>'','titulo_en'=>'','subtitulo'=>'','subtitulo_en'=>'','descripcion_corta'=>'','descripcion_corta_en'=>'','descripcion'=>'','descripcion_en'=>'','slug_pagina'=>'','activo'=>1,'orden'=>0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fundación | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap');

        :root {
            --tds-primary: #00685f;
            --tds-primary-container: #008378;
            --tds-on-primary: #ffffff;
            --tds-on-primary-container: #f4fffc;
            --tds-surface: #f8fafc;
            --tds-surface-container: #e5eeff;
            --tds-surface-container-high: #dce9ff;
            --tds-on-surface: #0b1c30;
            --tds-on-surface-variant: #3d4947;
            --tds-outline: #6d7a77;
            --tds-outline-variant: #bcc9c6;
            --tds-secondary: #565e74;
            --tds-error: #ba1a1a;
            --tds-success-bg: #f0fdf9;
            --tds-success-border: #6ee7b7;
            --tds-success-text: #065f46;
            --tds-font: 'Hanken Grotesk', sans-serif;
            --tds-radius: 8px;
            --tds-radius-lg: 16px;
        }

        .fund-section {
            background: #ffffff;
            border-radius: var(--tds-radius);
            border: 1px solid var(--tds-outline-variant);
            padding: 24px;
            margin-bottom: 24px;
        }
        .fund-section h2 {
            font-family: var(--tds-font);
            font-size: 20px;
            font-weight: 600;
            line-height: 28px;
            color: var(--tds-primary);
            margin: 0 0 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--tds-outline-variant);
        }
        .fund-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .fund-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .fund-field { margin-bottom: 0; }
        .fund-field label {
            display: block;
            font-family: var(--tds-font);
            font-size: 13px;
            font-weight: 600;
            line-height: 18px;
            letter-spacing: 0.01em;
            color: var(--tds-on-surface-variant);
            margin-bottom: 6px;
        }
        .fund-field input[type=text],
        .fund-field input[type=number],
        .fund-field textarea {
            width: 100%;
            padding: 10px 14px;
            font-family: var(--tds-font);
            font-size: 14px;
            font-weight: 400;
            line-height: 20px;
            color: var(--tds-on-surface);
            background: #fff;
            border: 1px solid var(--tds-outline-variant);
            border-radius: var(--tds-radius);
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .fund-field input[type=text]:focus,
        .fund-field input[type=number]:focus,
        .fund-field textarea:focus {
            outline: none;
            border-color: var(--tds-primary);
            box-shadow: 0 0 0 3px rgba(0,104,95,0.12);
        }
        .fund-field textarea { min-height: 90px; resize: vertical; }
        .fund-field input[type=file] {
            font-family: var(--tds-font);
            font-size: 13px;
            color: var(--tds-on-surface-variant);
        }
        .fund-img-preview { width: 120px; height: 70px; object-fit: cover; border-radius: var(--tds-radius); border: 1px solid var(--tds-outline-variant); margin-top: 8px; display: block; }
        .fund-logo-preview { width: 100px; height: 100px; object-fit: contain; border-radius: var(--tds-radius); border: 1px solid var(--tds-outline-variant); margin-top: 8px; background: #fff; padding: 5px; display: block; }

        .fund-toggle { position: relative; width: 44px; height: 24px; display: inline-block; vertical-align: middle; }
        .fund-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
        .fund-toggle .slider { position: absolute; inset: 0; background: var(--tds-outline-variant); border-radius: 24px; cursor: pointer; transition: background 0.3s; }
        .fund-toggle .slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: transform 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
        .fund-toggle input:checked + .slider { background: var(--tds-primary); }
        .fund-toggle input:checked + .slider::before { transform: translateX(20px); }
        .fund-toggle input:focus + .slider { box-shadow: 0 0 0 3px rgba(0,104,95,0.15); }

        .fund-tabla { width: 100%; border-collapse: collapse; border-radius: var(--tds-radius); overflow: hidden; }
        .fund-tabla thead th {
            background: var(--tds-primary);
            color: var(--tds-on-primary);
            font-family: var(--tds-font);
            font-size: 12px;
            font-weight: 600;
            line-height: 16px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 12px 14px;
            text-align: left;
        }
        .fund-tabla tbody td {
            padding: 10px 14px;
            font-family: var(--tds-font);
            font-size: 14px;
            color: var(--tds-on-surface);
            border-bottom: 1px solid var(--tds-outline-variant);
            vertical-align: middle;
        }
        .fund-tabla tbody tr:hover { background: var(--tds-surface); }
        .fund-tabla tbody tr:last-child td { border-bottom: none; }
        .fund-tabla td img { width: 55px; height: 40px; object-fit: cover; border-radius: 6px; display: block; }
        .fund-tabla td code {
            font-size: 12px;
            background: var(--tds-surface);
            border: 1px solid var(--tds-outline-variant);
            border-radius: 4px;
            padding: 2px 6px;
            color: var(--tds-on-surface-variant);
        }
        .fund-tabla .acciones { display: flex; gap: 6px; }
        .fund-tabla .acciones a,
        .fund-tabla .acciones button {
            padding: 6px 10px;
            border-radius: var(--tds-radius);
            border: none;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: opacity 0.2s;
        }
        .fund-tabla .acciones a:hover,
        .fund-tabla .acciones button:hover { opacity: 0.85; }

        .btn-edit { background: #eab308; color: #fff; }
        .btn-del { background: var(--tds-error); color: #fff; }
        .btn-view { display: inline-flex; align-items: center; gap: 5px; font-family: var(--tds-font); font-size: 12px; color: var(--tds-primary); text-decoration: none; border: 1px solid var(--tds-outline-variant); border-radius: 6px; padding: 4px 10px; transition: background 0.2s; }
        .btn-view:hover { background: var(--tds-surface); }

        .fund-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .fund-section-header h2 { margin: 0; border: none; padding: 0; }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--tds-primary);
            color: var(--tds-on-primary);
            font-family: var(--tds-font);
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: var(--tds-radius);
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-save:hover { background: #005249; }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            color: var(--tds-secondary);
            font-family: var(--tds-font);
            font-size: 14px;
            font-weight: 600;
            border: 1px solid var(--tds-outline-variant);
            border-radius: var(--tds-radius);
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        .btn-cancel:hover { background: var(--tds-surface); border-color: var(--tds-outline); }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: var(--tds-primary);
            color: var(--tds-on-primary);
            font-family: var(--tds-font);
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: var(--tds-radius);
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: #005249; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(11,28,48,0.4); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: var(--tds-radius-lg);
            padding: 28px;
            width: 90%;
            max-width: 650px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0px 10px 15px -3px rgba(0,0,0,0.1), 0px 4px 6px -4px rgba(0,0,0,0.1);
        }
        .modal-box h3 {
            font-family: var(--tds-font);
            font-size: 20px;
            font-weight: 600;
            line-height: 28px;
            color: var(--tds-primary);
            margin: 0 0 20px;
        }
        .modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--tds-outline);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s, color 0.2s;
        }
        .modal-close:hover { background: var(--tds-surface); color: var(--tds-on-surface); }

        .fund-msg {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--tds-success-bg);
            border: 1px solid var(--tds-success-border);
            color: var(--tds-success-text);
            padding: 12px 18px;
            border-radius: var(--tds-radius);
            margin-bottom: 20px;
            font-family: var(--tds-font);
            font-size: 14px;
            font-weight: 500;
        }

        .fund-page-title {
            font-family: var(--tds-font);
            font-size: 24px;
            font-weight: 700;
            line-height: 32px;
            color: var(--tds-on-surface);
            letter-spacing: -0.01em;
            margin: 0 0 24px;
        }

        .fund-empty {
            color: var(--tds-outline);
            text-align: center;
            padding: 40px 20px;
            font-family: var(--tds-font);
            font-size: 14px;
        }

        .fund-res-hint {
            font-family: var(--tds-font);
            font-size: 12px;
            color: var(--tds-outline);
            margin-top: 4px;
            line-height: 16px;
        }
        .fund-res-warn {
            font-family: var(--tds-font);
            font-size: 12px;
            color: #b45309;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: var(--tds-radius);
            padding: 6px 10px;
            margin-top: 6px;
            line-height: 16px;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }
        .fund-res-warn .material-symbols-outlined {
            font-size: 16px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .fund-footer-actions {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .fund-footer-actions .toggle-group { display: flex; align-items: center; gap: 10px; }
        .fund-footer-actions .toggle-label { font-family: var(--tds-font); font-size: 14px; font-weight: 600; color: var(--tds-on-surface-variant); }
        .fund-footer-actions .actions-right { margin-left: auto; display: flex; gap: 10px; }

        .fund-modal-footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--tds-outline-variant);
            display: flex;
            gap: 10px;
        }

        .drag-handle {
            cursor: grab;
            color: var(--tds-outline-variant);
            text-align: center;
            font-size: 14px;
            user-select: none;
            transition: color 0.2s;
        }
        .drag-handle:hover { color: var(--tds-primary); }
        .drag-handle:active { cursor: grabbing; }
        .fund-tabla tbody tr.dragging { opacity: 0.4; background: var(--tds-surface); }
        .fund-tabla tbody tr.drag-over { border-top: 2px solid var(--tds-primary); }

        .fd-galeria-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .fd-galeria-item {
            position: relative;
            border-radius: var(--tds-radius);
            overflow: hidden;
            border: 1px solid var(--tds-outline-variant);
            aspect-ratio: 4/3;
        }
        .fd-galeria-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .fd-galeria-drag {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            background: rgba(0,0,0,0.5);
            color: #fff;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 3;
        }
        .fd-galeria-item:hover .fd-galeria-drag { opacity: 1; }
        .fd-galeria-drag:active { cursor: grabbing; }
        .fd-galeria-actions {
            position: absolute;
            top: 4px;
            right: 4px;
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 3;
        }
        .fd-galeria-item:hover .fd-galeria-actions { opacity: 1; }
        .fd-galeria-replace, .fd-galeria-del {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .fd-galeria-replace { background: var(--tds-primary); }
        .fd-galeria-replace:hover { background: #005249; }
        .fd-galeria-del { background: var(--tds-error); }
        .fd-galeria-item.dragging { opacity: 0.4; }
        .fd-galeria-item.drag-over-g { border-top: 2px solid var(--tds-primary); }

        .fd-seccion-card {
            border: 1px solid var(--tds-outline-variant);
            border-radius: var(--tds-radius);
            margin-bottom: 12px;
            overflow: hidden;
            background: #fff;
        }
        .fd-seccion-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--tds-surface);
            border-bottom: 1px solid var(--tds-outline-variant);
        }
        .fd-seccion-header strong {
            flex: 1;
            font-family: var(--tds-font);
            font-size: 14px;
            color: var(--tds-on-surface);
        }
        .fd-seccion-actions { display: flex; gap: 4px; }
        .fd-seccion-actions button {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        .fd-seccion-drag {
            cursor: grab;
            color: var(--tds-outline-variant);
            font-size: 12px;
        }
        .fd-seccion-drag:active { cursor: grabbing; }
        .fd-seccion-preview {
            padding: 10px 14px;
        }
        .fd-seccion-preview img {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: var(--tds-radius);
            border: 1px solid var(--tds-outline-variant);
        }
        .fd-seccion-imgs-panel {
            padding: 10px 14px;
            border-top: 1px solid var(--tds-outline-variant);
        }
        .fd-seccion-imgs-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        @media (max-width: 768px) {
            .fund-grid-2, .fund-grid-3 { grid-template-columns: 1fr; }
            .fund-section { padding: 16px; }
            .fund-section-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .fund-footer-actions { flex-direction: column; align-items: flex-start; }
            .fund-footer-actions .actions-right { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
<h1 class="fund-page-title"><i class="fas fa-hands-helping" style="color:var(--tds-primary)"></i> Fundación INTI PATH TOURS</h1>

<?php if ($mensaje === 'config_ok'): ?>
<div class="fund-msg"><i class="fas fa-check-circle"></i> Configuración guardada correctamente.</div>
<?php endif; ?>

<!-- ========== SECCIÓN A: CONFIGURACIÓN GENERAL ========== -->
<form method="POST" enctype="multipart/form-data" class="fund-section">
    <h2><i class="fas fa-cog"></i> Configuración General</h2>

    <div class="fund-grid-2" style="margin-bottom:16px">
        <div class="fund-field">
            <label>Imagen Hero (fondo de pantalla)</label>
            <input type="file" name="hero_imagen" accept=".webp" onchange="previewImg(this, 'prev-hero', 'hero')">
            <small class="fund-res-hint">Resolución ideal: 1920×823 px (relación 21:9)</small>
            <div id="prev-hero" style="margin-top:8px">
                <?php if (!empty($fund['hero_imagen'])): ?>
                    <img src="<?= $img_dir . $fund['hero_imagen'] ?>" class="fund-img-preview" alt="Hero">
                <?php endif; ?>
            </div>
        </div>
        <div class="fund-field">
            <label>Logo Fundación</label>
            <input type="file" name="logo" accept=".webp" onchange="previewImg(this, 'prev-logo', 'logo')">
            <small class="fund-res-hint">Resolución ideal: 200×200 px (cuadrado)</small>
            <div id="prev-logo" style="margin-top:8px">
                <?php if (!empty($fund['logo'])): ?>
                    <img src="<?= $img_dir . $fund['logo'] ?>" class="fund-logo-preview" alt="Logo">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="fund-grid-2">
        <div class="fund-field"><label>Título (ES)</label><input type="text" name="titulo" value="<?= htmlspecialchars($fund['titulo'] ?? '') ?>"></div>
        <div class="fund-field"><label>Título (EN)</label><input type="text" name="titulo_en" value="<?= htmlspecialchars($fund['titulo_en'] ?? '') ?>"></div>
    </div>
    <div class="fund-grid-2">
        <div class="fund-field"><label>Subtítulo (ES)</label><input type="text" name="subtitulo" value="<?= htmlspecialchars($fund['subtitulo'] ?? '') ?>"></div>
        <div class="fund-field"><label>Subtítulo (EN)</label><input type="text" name="subtitulo_en" value="<?= htmlspecialchars($fund['subtitulo_en'] ?? '') ?>"></div>
    </div>

    <div class="fund-grid-2">
        <div class="fund-field"><label>Descripción (ES)</label><textarea name="descripcion"><?= htmlspecialchars($fund['descripcion'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>Descripción (EN)</label><textarea name="descripcion_en"><?= htmlspecialchars($fund['descripcion_en'] ?? '') ?></textarea></div>
    </div>

    <div class="fund-grid-3">
        <div class="fund-field"><label>Misión (ES)</label><textarea name="mision"><?= htmlspecialchars($fund['mision'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>Visión (ES)</label><textarea name="vision"><?= htmlspecialchars($fund['vision'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>Valores (ES)</label><textarea name="valores"><?= htmlspecialchars($fund['valores'] ?? '') ?></textarea></div>
    </div>
    <div class="fund-grid-3">
        <div class="fund-field"><label>Misión (EN)</label><textarea name="mision_en"><?= htmlspecialchars($fund['mision_en'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>Visión (EN)</label><textarea name="vision_en"><?= htmlspecialchars($fund['vision_en'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>Valores (EN)</label><textarea name="valores_en"><?= htmlspecialchars($fund['valores_en'] ?? '') ?></textarea></div>
    </div>

    <div class="fund-grid-2">
        <div class="fund-field"><label>Cita / Frase (ES)</label><textarea name="cita"><?= htmlspecialchars($fund['cita'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>Cita / Frase (EN)</label><textarea name="cita_en"><?= htmlspecialchars($fund['cita_en'] ?? '') ?></textarea></div>
    </div>
    <div class="fund-grid-2">
        <div class="fund-field"><label>"¿Qué hace diferente?" (ES)</label><textarea name="diferente"><?= htmlspecialchars($fund['diferente'] ?? '') ?></textarea></div>
        <div class="fund-field"><label>"¿Qué hace diferente?" (EN)</label><textarea name="diferente_en"><?= htmlspecialchars($fund['diferente_en'] ?? '') ?></textarea></div>
    </div>

    <div class="fund-footer-actions">
        <div class="toggle-group">
            <label class="fund-toggle"><input type="checkbox" name="activo" value="1" <?= $fund['activo'] ? 'checked' : '' ?>><span class="slider"></span></label>
            <span class="toggle-label">Página activa</span>
        </div>
        <div class="actions-right">
            <button type="submit" name="guardar_config" class="btn-save"><i class="fas fa-save"></i> Guardar Configuración</button>
        </div>
    </div>
</form>

<!-- ========== SECCIÓN B: PROYECTOS ========== -->
<div class="fund-section">
    <div class="fund-section-header">
        <h2 style="margin:0;border:none;padding:0"><i class="fas fa-th-large"></i> Proyectos</h2>
        <button onclick="abrirModalProyecto()" class="btn-primary"><i class="fas fa-plus"></i> Nuevo Proyecto</button>
    </div>

    <?php if (empty($proyectos)): ?>
        <p class="fund-empty">No hay proyectos aún. Crea el primero con el botón de arriba.</p>
    <?php else: ?>
    <table class="fund-tabla" id="tablaProyectos">
        <thead>
            <tr><th style="width:40px"></th><th>Imagen</th><th>Título ES</th><th>Título EN</th><th>Página</th><th>Activo</th><th>Acciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($proyectos as $p): ?>
            <tr data-id="<?= $p['id'] ?>">
                <td class="drag-handle" title="Arrastrar para reordenar" draggable="true"><i class="fas fa-grip-vertical"></i></td>
                <td><img src="<?= $img_dir . $p['imagen'] ?>" width="64" height="44" style="object-fit:cover;border-radius:6px;background:#f1f5f9" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23cbd5e1%27%3E%3Cpath d=%27M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-5z%27/%3E%3C/svg%3E'"></td>
                <td><?= htmlspecialchars($p['titulo'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['titulo_en'] ?? '') ?></td>
                <td>
                    <?php if (!empty($p['slug_pagina'])): ?>
                        <a href="../fundacion/<?= urlencode($p['slug_pagina']) ?>" target="_blank" class="btn-view" title="Ver en sitio"><i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($p['slug_pagina'] ?? '') ?></a>
                    <?php else: ?>
                        <span style="color:var(--tds-outline)">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <label class="fund-toggle">
                        <input type="checkbox" <?= $p['activo'] ? 'checked' : '' ?> onchange="toggleProyecto(<?= $p['id'] ?>, this)">
                        <span class="slider"></span>
                    </label>
                </td>
                <td>
                    <div class="acciones">
                        <a href="?editar_proyecto=<?= $p['id'] ?>" class="btn-edit"><i class="fas fa-pen"></i></a>
                        <button class="btn-del" onclick="eliminarProyecto(<?= $p['id'] ?>, '<?= addslashes($p['titulo']) ?>')"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</main>
</div>

<!-- ========== MODAL: CREAR/EDITAR PROYECTO ========== -->
<div class="modal-overlay" id="modalProyecto">
    <div class="modal-box">
        <button class="modal-close" onclick="cerrarModal()">&times;</button>
        <h3 id="modalProyectoTitle"><?= $editar_proyecto ? 'Editar Proyecto' : 'Nuevo Proyecto' ?></h3>
        <form method="POST" enctype="multipart/form-data" id="formProyecto">
            <input type="hidden" name="proyecto_id" value="<?= $fp['id'] ?>">
            <input type="hidden" name="proyecto_imagen_actual" value="<?= $fp['imagen'] ?>">

            <div class="fund-field">
                <label>Imagen del proyecto</label>
                <input type="file" name="proyecto_imagen" accept=".webp" onchange="previewImg(this, 'prev-proyecto', 'proyecto')">
                <small class="fund-res-hint">Resolución ideal: 800×600 px (relación 4:3)</small>
                <div id="prev-proyecto" style="margin-top:8px">
                    <?php if (!empty($fp['imagen'])): ?>
                        <img src="<?= $img_dir . $fp['imagen'] ?>" class="fund-img-preview" alt="" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23cbd5e1%27%3E%3Cpath d=%27M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-5z%27/%3E%3C/svg%3E'">
                    <?php endif; ?>
                </div>
            </div>

            <div class="fund-grid-2">
                <div class="fund-field"><label>Título (ES) *</label><input type="text" name="proyecto_titulo" value="<?= htmlspecialchars($fp['titulo'] ?? '') ?>" required></div>
                <div class="fund-field"><label>Título (EN)</label><input type="text" name="proyecto_titulo_en" value="<?= htmlspecialchars($fp['titulo_en'] ?? '') ?>"></div>
            </div>

            <div class="fund-grid-2">
                <div class="fund-field"><label>Subtítulo (ES)</label><input type="text" name="proyecto_subtitulo" value="<?= htmlspecialchars($fp['subtitulo'] ?? '') ?>" placeholder="Breve descripción del proyecto"></div>
                <div class="fund-field"><label>Subtítulo (EN)</label><input type="text" name="proyecto_subtitulo_en" value="<?= htmlspecialchars($fp['subtitulo_en'] ?? '') ?>" placeholder="Short project description"></div>
            </div>

            <div class="fund-grid-2">
                <div class="fund-field"><label>Descripción Corta (ES)</label><textarea name="proyecto_descripcion_corta" rows="3" maxlength="1000" placeholder="Texto breve que resume el proyecto (máx. 1000 caracteres)"><?= htmlspecialchars($fp['descripcion_corta'] ?? '') ?></textarea></div>
                <div class="fund-field"><label>Descripción Corta (EN)</label><textarea name="proyecto_descripcion_corta_en" rows="3" maxlength="1000" placeholder="Brief project summary (max 1000 chars)"><?= htmlspecialchars($fp['descripcion_corta_en'] ?? '') ?></textarea></div>
            </div>

            <div class="fund-grid-2">
                <div class="fund-field"><label>Descripción Larga (ES)</label><textarea name="proyecto_descripcion"><?= htmlspecialchars($fp['descripcion'] ?? '') ?></textarea></div>
                <div class="fund-field"><label>Descripción Larga (EN)</label><textarea name="proyecto_descripcion_en"><?= htmlspecialchars($fp['descripcion_en'] ?? '') ?></textarea></div>
            </div>

            <div class="fund-field">
                <label>Slug de página (para enlace LEER MÁS → pagina.php?slug=...)</label>
                <input type="text" name="proyecto_slug" value="<?= htmlspecialchars($fp['slug_pagina'] ?? '') ?>" placeholder="ej: campana-limpieza">
            </div>

            <div class="fund-field toggle-group" style="display:flex;align-items:center;gap:10px;padding-top:4px">
                <label class="fund-toggle"><input type="checkbox" name="proyecto_activo" value="1" <?= $fp['activo'] ? 'checked' : '' ?>><span class="slider"></span></label>
                <span class="toggle-label">Activo</span>
            </div>

            <div class="fund-modal-footer">
                <button type="button" class="btn-save" onclick="guardarProyectoAjax()"><i class="fas fa-save"></i> Guardar</button>
                <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>

            <!-- SECCIONES DEL PROYECTO -->
            <?php if ($fp['id'] > 0): ?>
            <div class="fund-section" style="margin-top:20px">
                <div class="fund-section-header">
                    <h2 style="font-size:16px;margin:0;border:none;padding:0"><i class="fas fa-layer-group" style="color:var(--tds-primary)"></i> Secciones del Proyecto</h2>
                </div>

                <div id="seccionesContainer">
                    <?php foreach ($secciones as $sec): ?>
                    <div class="fd-seccion-card" id="sec-<?= $sec['id'] ?>" data-id="<?= $sec['id'] ?>">
                        <div class="fd-seccion-header">
                            <div class="fd-seccion-drag" title="Arrastrar"><i class="fas fa-grip-vertical"></i></div>
                            <strong><?= htmlspecialchars($sec['titulo'] ?? '(Sin título)') ?></strong>
                            <?php if (!empty($sec['titulo_en'])): ?>
                            <span style="font-size:11px;color:var(--tds-outline);margin-left:4px"><i class="fas fa-language"></i> EN</span>
                            <?php endif; ?>
                            <?php if (!empty($sec['descripcion'])): ?>
                            <span style="font-size:11px;color:var(--tds-outline);margin-left:4px"><i class="fas fa-align-left"></i> Texto</span>
                            <?php endif; ?>
                            <div class="fd-seccion-actions">
                                <button type="button" class="btn-edit" onclick="editarSeccion(<?= $sec['id'] ?>, '<?= addslashes($sec['titulo'] ?? '') ?>')" title="Editar"><i class="fas fa-pen"></i></button>
                                <button type="button" class="btn-del" onclick="eliminarSeccion(<?= $sec['id'] ?>)" title="Eliminar"><i class="fas fa-trash"></i></button>
                                <button type="button" class="btn-view" onclick="toggleSeccionImgs(<?= $sec['id'] ?>)" title="Imágenes"><i class="fas fa-images"></i></button>
                            </div>
                        </div>
                        <div class="fd-seccion-preview">
                            <img src="<?= $img_dir . $sec['imagen_principal'] ?>" alt="" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23cbd5e1%27%3E%3Cpath d=%27M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-5z%27/%3E%3C/svg%3E'">
                        </div>
                        <div class="fd-seccion-imgs-panel" id="secImgs-<?= $sec['id'] ?>" style="display:none">
                            <div class="fd-seccion-imgs-grid" id="secImgsGrid-<?= $sec['id'] ?>">
                                <?php foreach ($sec['imagenes'] as $si): ?>
                                <div class="fd-galeria-item" id="si-<?= $si['id'] ?>" data-id="<?= $si['id'] ?>" draggable="true">
                                    <div class="fd-galeria-drag" title="Arrastrar"><i class="fas fa-grip-vertical"></i></div>
                                    <img src="<?= $img_dir . $si['imagen'] ?>" alt="">
                                    <div class="fd-galeria-actions">
                                        <button type="button" class="fd-galeria-replace" onclick="reemplazarSecImg(<?= $si['id'] ?>)" title="Reemplazar"><i class="fas fa-sync-alt"></i></button>
                                        <button type="button" class="fd-galeria-del" onclick="eliminarSecImg(<?= $si['id'] ?>)" title="Eliminar"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <form method="POST" enctype="multipart/form-data" style="margin-top:10px">
                                <input type="hidden" name="secimg_proyecto_id" value="<?= $fp['id'] ?>">
                                <input type="hidden" name="secimg_seccion_id" value="<?= $sec['id'] ?>">
                                <div class="fund-field">
                                    <label>Agregar imágenes a esta sección</label>
                                    <input type="file" name="secimg_archivos[]" accept=".webp" multiple onchange="previewGaleria(this)">
                                    <small class="fund-res-hint">Resolución ideal: 1920×823 px (21:9) o 1280×720 px (16:9)</small>
                                </div>
                                <div style="margin-top:8px">
                                    <button type="submit" name="guardar_seccion_imgs" class="btn-primary" style="font-size:12px;padding:6px 14px"><i class="fas fa-upload"></i> Subir</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Formulario nueva sección -->
                <form method="POST" enctype="multipart/form-data" style="margin-top:16px;padding:16px;border:1px dashed var(--tds-outline-variant);border-radius:var(--tds-radius)">
                    <h3 style="font-size:14px;color:var(--tds-primary);margin:0 0 12px"><i class="fas fa-plus"></i> Nueva Sección</h3>
                    <div class="fund-grid-2">
                        <div class="fund-field">
                            <label>Título de la sección (ES)</label>
                            <input type="text" name="seccion_titulo" placeholder="Ej: Campaña Navidad 2024">
                        </div>
                        <div class="fund-field">
                            <label>Título de la sección (EN)</label>
                            <input type="text" name="seccion_titulo_en" placeholder="Ej: Christmas Campaign 2024">
                        </div>
                    </div>
                    <div class="fund-grid-2" style="margin-top:12px">
                        <div class="fund-field">
                            <label>Imagen principal</label>
                            <input type="file" name="seccion_imagen" accept=".webp" required onchange="previewImg(this, 'prev-seccion', 'seccion')">
                            <small class="fund-res-hint">Resolución ideal: 1920×823 px (relación 21:9)</small>
                            <div id="prev-seccion" style="margin-top:8px"></div>
                        </div>
                    </div>
                    <div class="fund-field" style="margin-top:12px">
                        <label>Descripción (ES)</label>
                        <small style="color:var(--tds-outline);font-size:11px">Usa: - para viñetas, **texto** para negrita, ## para subtítulo</small>
                        <textarea name="seccion_descripcion" rows="4" placeholder="Campaña de reforestación en la comunidad de Huasao&#10;- Se realizaron jornadas de siembra de árboles nativos&#10;- **Más de 200 familias** beneficiadas con el programa&#10;## Resultados&#10;Se plantearon más de 500 árboles en la zona"></textarea>
                    </div>
                    <div class="fund-field" style="margin-top:12px">
                        <label>Descripción (EN)</label>
                        <small style="color:var(--tds-outline);font-size:11px">Use: - for bullets, **text** for bold, ## for subtitle</small>
                        <textarea name="seccion_descripcion_en" rows="4" placeholder="Reforestation campaign in the Huasao community&#10;- Tree planting sessions were held with local farmers&#10;- **Over 200 families** benefited from the program&#10;## Results&#10;More than 500 trees were planted in the area"></textarea>
                    </div>
                    <input type="hidden" name="seccion_proyecto_id" value="<?= $fp['id'] ?>">
                    <div style="margin-top:12px">
                        <button type="submit" name="crear_seccion" class="btn-save"><i class="fas fa-plus"></i> Crear Sección</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
    </div>
</div>

<!-- ========== MODAL: EDITAR SECCIÓN ========== -->
<div class="modal-overlay" id="modalSeccion">
    <div class="modal-box">
        <button class="modal-close" onclick="cerrarModalSeccion()">&times;</button>
        <h3>Editar Sección</h3>
        <form method="POST" enctype="multipart/form-data" id="formSeccion">
            <input type="hidden" name="editar_seccion" value="1">
            <input type="hidden" name="seccion_id" id="seccion_id_edit">
            <input type="hidden" name="seccion_proyecto_id" value="<?= $fp['id'] ?>">
            <div class="fund-grid-2">
                <div class="fund-field">
                    <label>Título (ES)</label>
                    <input type="text" name="seccion_titulo_edit" id="seccion_titulo_edit">
                </div>
                <div class="fund-field">
                    <label>Título (EN)</label>
                    <input type="text" name="seccion_titulo_en_edit" id="seccion_titulo_en_edit">
                </div>
            </div>
            <div class="fund-field" style="margin-top:12px">
                <label>Descripción (ES)</label>
                <small style="color:var(--tds-outline);font-size:11px">Usa: - para viñetas, **texto** para negrita, ## para subtítulo</small>
                <textarea id="seccion_descripcion_edit" name="seccion_descripcion_edit" rows="4" placeholder="Campaña de reforestación en la comunidad de Huasao&#10;- Se realizaron jornadas de siembra de árboles nativos&#10;- **Más de 200 familias** beneficiadas"></textarea>
            </div>
            <div class="fund-field" style="margin-top:12px">
                <label>Descripción (EN)</label>
                <small style="color:var(--tds-outline);font-size:11px">Use: - for bullets, **text** for bold, ## for subtitle</small>
                <textarea id="seccion_descripcion_en_edit" name="seccion_descripcion_en_edit" rows="4" placeholder="Reforestation campaign in the Huasao community&#10;- Tree planting sessions were held with local farmers&#10;- **Over 200 families** benefited"></textarea>
            </div>
            <div class="fund-field" style="margin-top:12px">
                <label>Imagen principal (dejar vacío para mantener la actual)</label>
                <input type="file" name="seccion_imagen_edit" accept=".webp">
            </div>
            <div class="fund-modal-footer">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Guardar</button>
                <button type="button" class="btn-cancel" onclick="cerrarModalSeccion()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Vista previa de imagen
function previewImg(input, containerId, tipo) {
    var container = document.getElementById(containerId);
    if (!container) { console.log('Container not found:', containerId); return; }
    if (input.files && input.files[0]) {
        var file = input.files[0];
        if (!file.name.toLowerCase().endsWith('.webp')) {
            Swal.fire('Formato no válido', 'Solo se aceptan archivos .webp', 'warning');
            input.value = '';
            return;
        }
        var ratioMap = {
            'hero': { ratio: 21/9, label: '21:9 (1920×823 px)' },
            'logo': { ratio: 1, label: '1:1 (200×200 px)' },
            'proyecto': { ratio: 4/3, label: '4:3 (800×600 px)' },
            'seccion': { ratio: 21/9, label: '21:9 (1920×823 px)' }
        };
        var reader = new FileReader();
        reader.onload = function(e) {
            var isLogo = containerId === 'prev-logo';
            var cls = isLogo ? 'fund-logo-preview' : 'fund-img-preview';
            var html = '<img src="' + e.target.result + '" class="' + cls + '" alt="Preview">';
            if (tipo && ratioMap[tipo]) {
                var tmpImg = new Image();
                tmpImg.onload = function() {
                    var w = tmpImg.naturalWidth;
                    var h = tmpImg.naturalHeight;
                    var realRatio = w / h;
                    var expected = ratioMap[tipo].ratio;
                    var tolerance = 0.15;
                    if (Math.abs(realRatio - expected) / expected > tolerance) {
                        html += '<div class="fund-res-warn"><span class="material-symbols-outlined">warning</span><span>La imagen tiene relación ' + w + '×' + h + ' (' + realRatio.toFixed(2) + ':1). Se recomienda ' + ratioMap[tipo].label + ' para que no se distorsione.</span></div>';
                    } else {
                        html += '<div style="font-family:var(--tds-font);font-size:11px;color:var(--tds-outline);margin-top:3px">' + w + '×' + h + ' px</div>';
                    }
                    container.innerHTML = html;
                };
                tmpImg.src = e.target.result;
            } else {
                container.innerHTML = html;
            }
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Imagen cargada', showConfirmButton:false, timer:1500});
        };
        reader.onerror = function(e) {
            Swal.fire('Error', 'No se pudo leer el archivo', 'error');
        };
        reader.readAsDataURL(file);
    }
}

// Vista previa de imágenes antes de subir (galería y secciones)
function previewGaleria(input) {
    var container = document.getElementById('galeriaPreview');
    if (!container) {
        var secPreview = input.closest('.fd-seccion-imgs-panel') || input.closest('form');
        if (secPreview) {
            var existing = secPreview.querySelector('.sec-preview-grid');
            if (!existing) {
                existing = document.createElement('div');
                existing.className = 'sec-preview-grid';
                existing.style.cssText = 'display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:10px';
                input.parentElement.appendChild(existing);
            }
            container = existing;
        }
    }
    if (!container) return;

    if (!input.files || input.files.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = '';
    for (var i = 0; i < input.files.length; i++) {
        var file = input.files[i];
        if (!file.name.toLowerCase().endsWith('.webp')) {
            Swal.fire('Formato no válido', 'Solo se aceptan archivos .webp. Archivo: ' + file.name, 'warning');
            input.value = '';
            container.innerHTML = '';
            return;
        }
        var item = document.createElement('div');
        item.style.cssText = 'position:relative;border-radius:6px;overflow:hidden;border:1px solid var(--tds-outline-variant);aspect-ratio:4/3';
        var img = document.createElement('img');
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
        var info = document.createElement('div');
        info.style.cssText = 'position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);color:#fff;font-size:10px;padding:3px 5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:var(--tds-font)';
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '&times;';
        removeBtn.style.cssText = 'position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;border:none;background:var(--tds-error);color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1';
        removeBtn.onclick = (function(inp, idx) {
            return function() {
                var dt = new DataTransfer();
                for (var j = 0; j < inp.files.length; j++) {
                    if (j !== idx) dt.items.add(inp.files[j]);
                }
                inp.files = dt.files;
                previewGaleria(inp);
            };
        })(input, i);
        var reader = new FileReader();
        reader.onload = (function(imgEl, infoEl, idx) {
            return function(e) {
                imgEl.src = e.target.result;
                var tmpImg = new Image();
                tmpImg.onload = function() {
                    var w = tmpImg.naturalWidth;
                    var h = tmpImg.naturalHeight;
                    var realRatio = w / h;
                    var ratio21_9 = 21/9;
                    var ratio16_9 = 16/9;
                    var tolerance = 0.15;
                    var matchOk = (Math.abs(realRatio - ratio21_9) / ratio21_9 <= tolerance) ||
                                  (Math.abs(realRatio - ratio16_9) / ratio16_9 <= tolerance);
                    var txt = file.name + ' (' + (file.size / 1024).toFixed(0) + 'KB) — ' + w + '×' + h;
                    if (!matchOk) {
                        txt += ' ⚠ Relación no recomendada';
                        infoEl.style.background = 'rgba(180,83,9,0.85)';
                    }
                    infoEl.textContent = txt;
                };
                tmpImg.src = e.target.result;
            };
        })(img, info, i);
        reader.readAsDataURL(file);
        item.appendChild(img);
        item.appendChild(info);
        item.appendChild(removeBtn);
        container.appendChild(item);
    }
}


// Abrir modal
function abrirModalProyecto() {
    document.getElementById('modalProyecto').classList.add('active');
}
function cerrarModal() {
    document.getElementById('modalProyecto').classList.remove('active');
}

// Guardar proyecto vía AJAX (sin recargar página)
function guardarProyectoAjax() {
    var form = document.getElementById('formProyecto');
    var fd = new FormData(form);
    fd.append('ajax_guardar_proyecto', '1');

    var btn = form.querySelector('.btn-save');
    var btnHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    fetch('admin_fundacion.php', { method: 'POST', body: fd })
        .then(r => {
            var ct = r.headers.get('content-type') || '';
            if (ct.indexOf('application/json') === -1) {
                Swal.fire({icon:'warning', title:'Sesión expirada', text:'Tu sesión ha expirado.', timer:2500, showConfirmButton:false});
                setTimeout(function(){ window.location.href = 'login.php?res=sesion_expirada'; }, 2500);
                throw new Error('Respuesta no-JSON');
            }
            return r.json();
        })
        .then(d => {
            btn.innerHTML = btnHtml;
            btn.disabled = false;
            if (d.ok) {
                Swal.fire({toast:true, position:'top-end', icon:'success', title:'Proyecto guardado', showConfirmButton:false, timer:1500});
                // Actualizar hidden fields
                form.querySelector('input[name="proyecto_id"]').value = d.id;
                form.querySelector('input[name="proyecto_imagen_actual"]').value = d.imagen;
                // Actualizar título del modal
                document.getElementById('modalProyectoTitle').textContent = 'Editar Proyecto';
                // Recargar página para mostrar secciones
            } else {
                Swal.fire('Error', d.error || 'No se pudo guardar', 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = btnHtml;
            btn.disabled = false;
            if (err.message === 'Respuesta no-JSON') return;
            Swal.fire('Error', err.message || 'Error de conexión. Intenta de nuevo.', 'error');
        });
}


// Si estamos editando, abrir modal automáticamente
<?php if ($editar_proyecto): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalProyecto').classList.add('active');
});
<?php endif; ?>

// Toggle activo proyecto
function toggleProyecto(id, el) {
    fetch('?toggle_proyecto=' + id).then(r => {
        var ct = r.headers.get('content-type') || '';
        if (ct.indexOf('application/json') === -1) { throw new Error('Sesión'); }
        return r.json();
    }).then(d => {
            if (d.ok) {
                Swal.fire({toast:true,position:'top-end',icon:'success',title:'Estado actualizado',showConfirmButton:false,timer:1500});
            }
        });
}

// Eliminar proyecto
function eliminarProyecto(id, titulo) {
    Swal.fire({
        title: '¿Eliminar proyecto?',
        text: "'" + titulo + "' se eliminará permanentemente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Sí, eliminar'
    }).then(r => { if (r.isConfirmed) window.location = '?eliminar_proyecto=' + id; });
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModal(); });
// Cerrar modal haciendo clic fuera
document.getElementById('modalProyecto').addEventListener('click', function(e) { if (e.target === this) cerrarModal(); });


// Notificación post-redirect
document.addEventListener('DOMContentLoaded', function() {
    const p = new URLSearchParams(window.location.search);
    const res = p.get('res');
    if (res === 'proyecto_ok') {
        Swal.fire('¡Listo!', 'Proyecto guardado correctamente.', 'success');
        window.history.replaceState({}, '', 'admin_fundacion.php');
    } else if (res === 'proyecto_eliminado') {
        Swal.fire('Eliminado', 'El proyecto fue eliminado.', 'info');
        window.history.replaceState({}, '', 'admin_fundacion.php');
    } else if (res === 'seccion_ok') {
        Swal.fire('¡Listo!', 'Sección guardada correctamente.', 'success');
        window.history.replaceState({}, '', 'admin_fundacion.php');
    }
});

// Secciones: toggle panel de imágenes
function toggleSeccionImgs(id) {
    var panel = document.getElementById('secImgs-' + id);
    if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// Secciones: editar
function editarSeccion(id, titulo) {
    document.getElementById('seccion_id_edit').value = id;
    document.getElementById('seccion_titulo_edit').value = titulo;
    document.getElementById('seccion_titulo_en_edit').value = '';
    document.getElementById('seccion_descripcion_edit').value = '';
    document.getElementById('seccion_descripcion_en_edit').value = '';
    document.getElementById('modalSeccion').classList.add('active');
    fetch('admin_fundacion.php?obtener_seccion=' + id)
        .then(r => r.json())
        .then(d => {
            document.getElementById('seccion_titulo_en_edit').value = d.titulo_en || '';
            document.getElementById('seccion_descripcion_edit').value = d.descripcion || '';
            document.getElementById('seccion_descripcion_en_edit').value = d.descripcion_en || '';
        });
}
function cerrarModalSeccion() {
    document.getElementById('modalSeccion').classList.remove('active');
}

// Secciones: eliminar
function eliminarSeccion(id) {
    Swal.fire({
        title: '¿Eliminar sección?',
        text: 'Se eliminarán todas sus imágenes.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Sí, eliminar'
    }).then(r => {
        if (r.isConfirmed) {
            fetch('?eliminar_seccion=' + id).then(res => {
                var ct = res.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) { throw new Error('Sesión'); }
                return res.json();
            }).then(d => {
                if (d.ok) {
                    var el = document.getElementById('sec-' + id);
                    if (el) el.remove();
                    Swal.fire({toast:true,position:'top-end',icon:'success',title:'Sección eliminada',showConfirmButton:false,timer:1500});
                }
            });
        }
    });
}

// Imágenes de sección: eliminar
function eliminarSecImg(id) {
    Swal.fire({
        title: '¿Eliminar imagen?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Sí, eliminar'
    }).then(r => {
        if (r.isConfirmed) {
            fetch('?eliminar_secimg=' + id).then(res => {
                var ct = res.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) { throw new Error('Sesión'); }
                return res.json();
            }).then(d => {
                if (d.ok) {
                    var el = document.getElementById('si-' + id);
                    if (el) el.remove();
                    Swal.fire({toast:true,position:'top-end',icon:'success',title:'Imagen eliminada',showConfirmButton:false,timer:1500});
                }
            });
        }
    });
}

// Imágenes de sección: reemplazar
function reemplazarSecImg(id) {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = '.webp';
    input.onchange = function() {
        if (!input.files[0]) return;
        if (!input.files[0].name.toLowerCase().endsWith('.webp')) {
            Swal.fire('Formato no válido', 'Solo se aceptan archivos .webp', 'warning');
            return;
        }
        var fd = new FormData();
        fd.append('reemplazar_secimg', '1');
        fd.append('imagen_id', id);
        fd.append('nueva_imagen', input.files[0]);
        Swal.fire({title:'Reemplazando...',text:'Por favor espera',allowOutsideClick:false,allowEscapeKey:false,allowEnterKey:false,didOpen:function(){Swal.showLoading()}});
        fetch('admin_fundacion.php', { method: 'POST', body: fd })
            .then(r => {
                var ct = r.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) { Swal.close(); Swal.fire({icon:'warning',title:'Sesión expirada',text:'Tu sesión ha expirado.',timer:2500,showConfirmButton:false}); setTimeout(function(){window.location.href='login.php?res=sesion_expirada';},2500); throw new Error('Sesión'); }
                return r.json();
            })
            .then(d => {
                Swal.close();
                if (d.ok) {
                    var item = document.getElementById('si-' + id);
                    if (item) {
                        var img = item.querySelector('img');
                        if (img) img.src = '../assets/img/fundacion/' + d.nueva + '?t=' + Date.now();
                    }
                    Swal.fire({toast:true,position:'top-end',icon:'success',title:'Imagen reemplazada',showConfirmButton:false,timer:1500});
                } else {
                    Swal.fire('Error', d.error || 'No se pudo reemplazar la imagen', 'error');
                }
            })
            .catch(function(err) {
                Swal.close();
                if (err.message !== 'Sesión') Swal.fire('Error', 'Error de conexión al reemplazar imagen', 'error');
            });
    };
    input.click();
}

// Drag & Drop reorder secciones
(function() {
    var container = document.getElementById('seccionesContainer');
    if (!container) return;
    var dragItem = null;
    container.addEventListener('dragstart', function(e) {
        var card = e.target.closest('.fd-seccion-card');
        if (!card) return;
        dragItem = card;
        card.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
        var card = e.target.closest('.fd-seccion-card');
        if (card && card !== dragItem) {
            container.querySelectorAll('.fd-seccion-card').forEach(function(c) { c.style.borderTop = ''; });
            card.style.borderTop = '2px solid var(--tds-primary)';
        }
    });
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        var target = e.target.closest('.fd-seccion-card');
        if (target && dragItem && target !== dragItem) {
            var items = Array.from(container.children);
            var fromIdx = items.indexOf(dragItem);
            var toIdx = items.indexOf(target);
            if (fromIdx < toIdx) container.insertBefore(dragItem, target.nextSibling);
            else container.insertBefore(dragItem, target);
            var ids = Array.from(container.querySelectorAll('.fd-seccion-card')).map(function(c) { return c.dataset.id; });
            var fd = new FormData();
            fd.append('reorder_secciones', '1');
            ids.forEach(function(id) { fd.append('ids[]', id); });
            fetch('admin_fundacion.php', { method: 'POST', body: fd });
        }
        container.querySelectorAll('.fd-seccion-card').forEach(function(c) { c.style.borderTop = ''; });
    });
    container.addEventListener('dragend', function() {
        if (dragItem) dragItem.style.opacity = '';
        dragItem = null;
        container.querySelectorAll('.fd-seccion-card').forEach(function(c) { c.style.borderTop = ''; });
    });
})();

// Drag & Drop reorder imágenes de sección
(function() {
    document.querySelectorAll('.fd-seccion-imgs-grid').forEach(function(grid) {
        var dragEl = null;
        grid.addEventListener('dragstart', function(e) {
            var item = e.target.closest('.fd-galeria-item');
            if (!item) return;
            dragEl = item;
            item.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        grid.addEventListener('dragover', function(e) {
            e.preventDefault();
            var item = e.target.closest('.fd-galeria-item');
            if (item && item !== dragEl) {
                grid.querySelectorAll('.fd-galeria-item').forEach(function(i) { i.classList.remove('drag-over-g'); });
                item.classList.add('drag-over-g');
            }
        });
        grid.addEventListener('drop', function(e) {
            e.preventDefault();
            var target = e.target.closest('.fd-galeria-item');
            if (target && dragEl && target !== dragEl) {
                var items = Array.from(grid.children);
                var fromIdx = items.indexOf(dragEl);
                var toIdx = items.indexOf(target);
                if (fromIdx < toIdx) grid.insertBefore(dragEl, target.nextSibling);
                else grid.insertBefore(dragEl, target);
                var ids = Array.from(grid.querySelectorAll('.fd-galeria-item')).map(function(i) { return i.dataset.id; });
                var fd = new FormData();
                fd.append('reorder_secimg', '1');
                ids.forEach(function(id) { fd.append('ids[]', id); });
                fetch('admin_fundacion.php', { method: 'POST', body: fd });
            }
            grid.querySelectorAll('.fd-galeria-item').forEach(function(i) { i.classList.remove('drag-over-g'); });
        });
        grid.addEventListener('dragend', function() {
            if (dragEl) dragEl.classList.remove('dragging');
            dragEl = null;
            grid.querySelectorAll('.fd-galeria-item').forEach(function(i) { i.classList.remove('drag-over-g'); });
        });
    });
})();

// Drag & Drop reorder
(function() {
    var tbody = document.querySelector('#tablaProyectos tbody');
    if (!tbody) return;
    var dragRow = null;

    tbody.addEventListener('dragstart', function(e) {
        var handle = e.target.closest('.drag-handle');
        if (!handle) { e.preventDefault(); return; }
        var row = handle.closest('tr');
        if (!row || !row.dataset.id) return;
        dragRow = row;
        row.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.id);
    });

    tbody.addEventListener('dragend', function(e) {
        if (dragRow) dragRow.classList.remove('dragging');
        tbody.querySelectorAll('tr').forEach(function(r) { r.classList.remove('drag-over'); });
        dragRow = null;
    });

    tbody.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('tr');
        if (!target || target === dragRow) return;
        tbody.querySelectorAll('tr').forEach(function(r) { r.classList.remove('drag-over'); });
        target.classList.add('drag-over');
    });

    tbody.addEventListener('drop', function(e) {
        e.preventDefault();
        var target = e.target.closest('tr');
        if (!target || target === dragRow) return;
        target.classList.remove('drag-over');

        if (dragRow) {
            var rows = Array.from(tbody.querySelectorAll('tr'));
            var dragIdx = rows.indexOf(dragRow);
            var targetIdx = rows.indexOf(target);
            if (dragIdx < targetIdx) {
                tbody.insertBefore(dragRow, target.nextSibling);
            } else {
                tbody.insertBefore(dragRow, target);
            }
            saveNewOrder();
        }
    });

    function saveNewOrder() {
        var rows = tbody.querySelectorAll('tr[data-id]');
        var ids = Array.from(rows).map(function(r) { return r.dataset.id; });
        fetch('admin_fundacion.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'reorder_proyectos=1&ids[]=' + ids.join('&ids[]=')
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.ok) {
                Swal.fire({toast:true,position:'top-end',icon:'success',title:'Orden actualizado',showConfirmButton:false,timer:1200});
            }
        });
    }
})();

// Cerrar modal de sección con Escape
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModalSeccion(); });
// Cerrar modal de sección haciendo clic fuera
document.getElementById('modalSeccion').addEventListener('click', function(e) { if (e.target === this) cerrarModalSeccion(); });
</script>
</body>
</html>
