<?php
// admin/admin_fundacion.php — Fundación: config general + CRUD proyectos
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('fundacion');
require_once '../config/database.php';
require_once __DIR__ . '/../includes/image_helper.php';

$db = (new Database())->getConnection();
$img_dir = '../assets/img/fundacion/';
if (!is_dir($img_dir)) mkdir($img_dir, 0777, true);

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
    header('Content-Type: application/json');
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
// POST: Reemplazar imagen de galería (AJAX)
// =============================================
if (isset($_POST['reemplazar_img_galeria'])) {
    header('Content-Type: application/json');
    $rid = (int)($_POST['imagen_id'] ?? 0);
    if ($rid > 0 && !empty($_FILES['nueva_imagen']['tmp_name']) && $_FILES['nueva_imagen']['error'] == 0) {
        $st = $db->prepare("SELECT imagen FROM fundacion_proyecto_imagenes WHERE id=?");
        $st->execute([$rid]);
        $old_img = $st->fetchColumn();
        $new_img = procesar_imagen_upload($_FILES['nueva_imagen'], $img_dir, 'galeria_' . time() . '_' . $rid);
        if ($new_img) {
            $db->prepare("UPDATE fundacion_proyecto_imagenes SET imagen=? WHERE id=?")->execute([$new_img, $rid]);
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
// POST: Reordenar galería (AJAX drag & drop)
// =============================================
if (isset($_POST['reorder_galeria'])) {
    header('Content-Type: application/json');
    $gids = $_POST['ids'] ?? [];
    if (is_array($gids)) {
        foreach ($gids as $pos => $gid) {
            $db->prepare("UPDATE fundacion_proyecto_imagenes SET orden=? WHERE id=?")->execute([$pos, (int)$gid]);
        }
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// =============================================
// GET: Eliminar imagen de galería (AJAX)
// =============================================
if (isset($_GET['eliminar_img_galeria'])) {
    header('Content-Type: application/json');
    $gid = (int)$_GET['eliminar_img_galeria'];
    $st = $db->prepare("SELECT imagen FROM fundacion_proyecto_imagenes WHERE id=?");
    $st->execute([$gid]);
    $gimg = $st->fetchColumn();
    if ($gimg) {
        $db->prepare("DELETE FROM fundacion_proyecto_imagenes WHERE id=?")->execute([$gid]);
        if (file_exists($img_dir . $gimg)) @unlink($img_dir . $gimg);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// =============================================
// AJAX: Subir imágenes a galería (retorna JSON)
// =============================================
if (isset($_POST['ajax_guardar_galeria'])) {
    header('Content-Type: application/json');
    $gid_pid = (int)($_POST['galeria_proyecto_id'] ?? 0);
    if ($gid_pid <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Proyecto inválido']);
        exit;
    }
    $subidas = 0;
    $imagenes_subidas = [];
    if (!empty($_FILES['galeria_imagenes']['tmp_name'][0])) {
        foreach ($_FILES['galeria_imagenes']['tmp_name'] as $idx => $tmp) {
            if ($_FILES['galeria_imagenes']['error'][$idx] == 0 && $tmp) {
                $gimg = procesar_imagen_upload($_FILES['galeria_imagenes'][$idx], $img_dir, 'galeria_' . time() . '_' . $idx);
                if ($gimg) {
                    $max_go = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_proyecto_imagenes WHERE proyecto_id=$gid_pid")->fetchColumn();
                    $db->prepare("INSERT INTO fundacion_proyecto_imagenes (proyecto_id, imagen, orden) VALUES (?,?,?)")->execute([$gid_pid, $gimg, $max_go + $idx]);
                    $new_id = (int)$db->lastInsertId();
                    $imagenes_subidas[] = ['id' => $new_id, 'imagen' => $gimg];
                    $subidas++;
                }
            }
        }
    }
    $total = $db->prepare("SELECT COUNT(*) FROM fundacion_proyecto_imagenes WHERE proyecto_id=?");
    $total->execute([$gid_pid]);
    $count = $total->fetchColumn();
    echo json_encode(['ok' => true, 'subidas' => $subidas, 'total' => $count, 'imagenes' => $imagenes_subidas]);
    exit;
}

// =============================================
// POST: Subir imágenes a galería (proyecto actual) — fallback sin AJAX
// =============================================
if (isset($_POST['guardar_galeria'])) {
    $gid_pid = (int)($_POST['galeria_proyecto_id'] ?? 0);
    if ($gid_pid > 0 && !empty($_FILES['galeria_imagenes']['tmp_name'][0])) {
        foreach ($_FILES['galeria_imagenes']['tmp_name'] as $idx => $tmp) {
            if ($_FILES['galeria_imagenes']['error'][$idx] == 0 && $tmp) {
                $gimg = procesar_imagen_upload($_FILES['galeria_imagenes'][$idx], $img_dir, 'galeria_' . time() . '_' . $idx);
                if ($gimg) {
                    $max_go = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_proyecto_imagenes WHERE proyecto_id=$gid_pid")->fetchColumn();
                    $db->prepare("INSERT INTO fundacion_proyecto_imagenes (proyecto_id, imagen, orden) VALUES (?,?,?)")->execute([$gid_pid, $gimg, $max_go + $idx]);
                }
            }
        }
    }
    header("Location: admin_fundacion.php?editar_proyecto=$gid_pid&res=galeria_ok");
    exit;
}

// =============================================
// POST: Crear sección nueva
// =============================================
if (isset($_POST['crear_seccion'])) {
    $cs_pid = (int)($_POST['seccion_proyecto_id'] ?? 0);
    $cs_titulo = trim($_POST['seccion_titulo'] ?? '');
    if ($cs_pid > 0 && !empty($_FILES['seccion_imagen']['tmp_name']) && $_FILES['seccion_imagen']['error'] == 0) {
        $cs_img = procesar_imagen_upload($_FILES['seccion_imagen'], $img_dir, 'seccion_' . time());
        if ($cs_img) {
            $max_so = $db->query("SELECT COALESCE(MAX(orden),0)+1 FROM fundacion_secciones WHERE proyecto_id=$cs_pid")->fetchColumn();
            $db->prepare("INSERT INTO fundacion_secciones (proyecto_id, titulo, imagen_principal, orden) VALUES (?,?,?,?)")->execute([$cs_pid, $cs_titulo, $cs_img, $max_so]);
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
    if ($es_id > 0) {
        $es_img_actual = $db->query("SELECT imagen_principal FROM fundacion_secciones WHERE id=$es_id")->fetchColumn();
        $es_img = $es_img_actual;
        if (!empty($_FILES['seccion_imagen_edit']['tmp_name']) && $_FILES['seccion_imagen_edit']['error'] == 0) {
            $es_img = procesar_imagen_upload($_FILES['seccion_imagen_edit'], $img_dir, 'seccion_' . time());
            if ($es_img && $es_img_actual && file_exists($img_dir . $es_img_actual)) @unlink($img_dir . $es_img_actual);
        }
        $db->prepare("UPDATE fundacion_secciones SET titulo=?, imagen_principal=? WHERE id=?")->execute([$es_titulo, $es_img, $es_id]);
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
                $si_img = procesar_imagen_upload($_FILES['secimg_archivos'][$idx], $img_dir, 'secimg_' . time() . '_' . $idx);
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
$galeria_imagenes = [];
$secciones = [];
if (isset($_GET['editar_proyecto'])) {
    $st = $db->prepare("SELECT * FROM fundacion_proyectos WHERE id=?");
    $st->execute([(int)$_GET['editar_proyecto']]);
    $editar_proyecto = $st->fetch(PDO::FETCH_ASSOC);
    if ($editar_proyecto) {
        $stg = $db->prepare("SELECT * FROM fundacion_proyecto_imagenes WHERE proyecto_id=? ORDER BY orden ASC, id ASC");
        $stg->execute([$editar_proyecto['id']]);
        $galeria_imagenes = $stg->fetchAll(PDO::FETCH_ASSOC);

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
            <input type="file" name="hero_imagen" accept=".webp" onchange="previewImg(this, 'prev-hero')">
            <div id="prev-hero" style="margin-top:8px">
                <?php if (!empty($fund['hero_imagen'])): ?>
                    <img src="<?= $img_dir . $fund['hero_imagen'] ?>" class="fund-img-preview" alt="Hero">
                <?php endif; ?>
            </div>
        </div>
        <div class="fund-field">
            <label>Logo Fundación</label>
            <input type="file" name="logo" accept=".webp" onchange="previewImg(this, 'prev-logo')">
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
                <input type="file" name="proyecto_imagen" accept=".webp" onchange="previewImg(this, 'prev-proyecto')">
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

            <!-- GALERÍA DE IMÁGENES (máx. 8) -->
            <?php if ($fp['id'] > 0): ?>
            <div class="fund-section" style="margin-top:20px">
                <h2 style="font-size:16px"><i class="fas fa-images" style="color:var(--tds-primary)"></i> Galería de Imágenes <span style="font-weight:400;font-size:13px;color:var(--tds-outline)">(<?= count($galeria_imagenes) ?>/8)</span></h2>
                <div class="fd-galeria-grid" id="galeriaGrid">
                    <?php foreach ($galeria_imagenes as $gi): ?>
                    <div class="fd-galeria-item" id="gi-<?= $gi['id'] ?>" data-id="<?= $gi['id'] ?>" draggable="true">
                        <div class="fd-galeria-drag" title="Arrastrar para reordenar"><i class="fas fa-grip-vertical"></i></div>
                        <img src="<?= $img_dir . $gi['imagen'] ?>" alt="" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23cbd5e1%27%3E%3Cpath d=%27M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-5z%27/%3E%3C/svg%3E'">
                        <div class="fd-galeria-actions">
                            <button type="button" class="fd-galeria-replace" onclick="reemplazarImgGaleria(<?= $gi['id'] ?>)" title="Reemplazar imagen"><i class="fas fa-sync-alt"></i></button>
                            <button type="button" class="fd-galeria-del" onclick="eliminarImgGaleria(<?= $gi['id'] ?>)" title="Eliminar"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($galeria_imagenes) < 8): ?>
                <form method="POST" enctype="multipart/form-data" style="margin-top:12px" id="formGaleria">
                    <input type="hidden" name="galeria_proyecto_id" value="<?= $fp['id'] ?>">
                    <div class="fund-field">
                        <label>Agregar imágenes (puedes seleccionar varias) — Máximo 8 en total</label>
                        <input type="file" name="galeria_imagenes[]" accept=".webp" multiple id="inputGaleria" onchange="validarGaleria(this)">
                    </div>
                    <div style="margin-top:10px">
                        <button type="submit" name="guardar_galeria" class="btn-primary" id="btnSubirGaleria"><i class="fas fa-upload"></i> Subir imágenes</button>
                    </div>
                </form>
                <?php else: ?>
                <p style="margin-top:12px;color:var(--tds-outline);font-size:13px;font-family:var(--tds-font)"><i class="fas fa-info-circle"></i> Límite de 8 imágenes alcanzado. Elimina alguna para agregar más.</p>
                <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>

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
                                    <input type="file" name="secimg_archivos[]" accept="image/*" multiple>
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
                            <label>Título de la sección</label>
                            <input type="text" name="seccion_titulo" placeholder="Ej: Campaña Navidad 2024">
                        </div>
                        <div class="fund-field">
                            <label>Imagen principal</label>
                            <input type="file" name="seccion_imagen" accept="image/*" required>
                        </div>
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

<script>
// Vista previa de imagen
function previewImg(input, containerId) {
    var container = document.getElementById(containerId);
    if (!container) { console.log('Container not found:', containerId); return; }
    if (input.files && input.files[0]) {
        var file = input.files[0];
        if (!file.name.toLowerCase().endsWith('.webp')) {
            Swal.fire('Formato no válido', 'Solo se aceptan archivos .webp', 'warning');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            var isLogo = containerId === 'prev-logo';
            var cls = isLogo ? 'fund-logo-preview' : 'fund-img-preview';
            container.innerHTML = '<img src="' + e.target.result + '" class="' + cls + '" alt="Preview">';
            Swal.fire({toast:true, position:'top-end', icon:'success', title:'Imagen cargada', showConfirmButton:false, timer:1500});
        };
        reader.onerror = function(e) {
            Swal.fire('Error', 'No se pudo leer el archivo', 'error');
        };
        reader.readAsDataURL(file);
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
        .then(r => r.json())
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
                // Mostrar sección galería si no existía
                mostrarSeccionGaleria(d.id);
            } else {
                Swal.fire('Error', d.error || 'No se pudo guardar', 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = btnHtml;
            btn.disabled = false;
            Swal.fire('Error', 'Error de conexión', 'error');
        });
}

// Inyectar sección de galería dentro del modal
function mostrarSeccionGaleria(pid) {
    var modal = document.querySelector('#modalProyecto .modal-box');
    var existing = document.getElementById('galeriaSection');
    if (existing) {
        var hiddenInput = existing.querySelector('input[name="galeria_proyecto_id"]');
        if (hiddenInput) hiddenInput.value = pid;
        return;
    }
    var html = '<div class="fund-section" style="margin-top:20px" id="galeriaSection">' +
        '<h2 style="font-size:16px"><i class="fas fa-images" style="color:var(--tds-primary)"></i> Galería de Imágenes <span style="font-weight:400;font-size:13px;color:var(--tds-outline)" id="galeriaCount">(0/8)</span></h2>' +
        '<div class="fd-galeria-grid" id="galeriaGrid"></div>' +
        '<form method="POST" enctype="multipart/form-data" style="margin-top:12px" id="formGaleria">' +
        '<input type="hidden" name="galeria_proyecto_id" value="' + pid + '">' +
        '<div class="fund-field">' +
        '<label>Agregar imágenes (puedes seleccionar varias) — Máximo 8 en total</label>' +
        '<input type="file" name="galeria_imagenes[]" accept=".webp" multiple id="inputGaleria" onchange="validarGaleria(this)">' +
        '</div>' +
        '<div style="margin-top:10px">' +
        '<button type="button" name="guardar_galeria" class="btn-primary" id="btnSubirGaleria" onclick="subirGaleriaAjax()"><i class="fas fa-upload"></i> Subir imágenes</button>' +
        '</div>' +
        '</form></div>';
    // Insertar DESPUÉS del form del proyecto (fuera del form)
    var formProyecto = document.getElementById('formProyecto');
    if (formProyecto) {
        formProyecto.insertAdjacentHTML('afterend', html);
    } else {
        modal.insertAdjacentHTML('beforeend', html);
    }
}

// Subir galería vía AJAX
function subirGaleriaAjax() {
    var form = document.getElementById('formGaleria');
    var input = document.getElementById('inputGaleria');
    if (!input.files || input.files.length === 0) {
        Swal.fire('Sin archivos', 'Selecciona al menos una imagen', 'warning');
        return;
    }
    if (!validarGaleria(input)) return;

    var fd = new FormData(form);
    fd.append('ajax_guardar_galeria', '1');

    var btn = document.getElementById('btnSubirGaleria');
    var btnHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    btn.disabled = true;

    fetch('admin_fundacion.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            btn.innerHTML = btnHtml;
            btn.disabled = false;
            input.value = '';
            if (d.ok) {
                Swal.fire({toast:true, position:'top-end', icon:'success', title: d.subidas + ' imagen(es) subida(s)', showConfirmButton:false, timer:1500});
                // Actualizar contador
                var counter = document.getElementById('galeriaCount');
                if (counter) counter.textContent = '(' + d.total + '/8)';
                // Agregar thumbnails al grid dinámicamente
                var grid = document.getElementById('galeriaGrid');
                if (grid && d.imagenes) {
                    d.imagenes.forEach(function(img) {
                        var item = document.createElement('div');
                        item.className = 'fd-galeria-item';
                        item.id = 'gi-' + img.id;
                        item.dataset.id = img.id;
                        item.draggable = true;
                        item.innerHTML = '<div class="fd-galeria-drag" title="Arrastrar para reordenar"><i class="fas fa-grip-vertical"></i></div>' +
                            '<img src="../assets/img/fundacion/' + img.imagen + '" alt="" onerror="this.onerror=null;this.src=\'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23cbd5e1%27%3E%3Cpath d=%27M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-5z%27/%3E%3C/svg%3E\'">' +
                            '<div class="fd-galeria-actions">' +
                            '<button type="button" class="fd-galeria-replace" onclick="reemplazarImgGaleria(' + img.id + ')" title="Reemplazar imagen"><i class="fas fa-sync-alt"></i></button>' +
                            '<button type="button" class="fd-galeria-del" onclick="eliminarImgGaleria(' + img.id + ')" title="Eliminar"><i class="fas fa-times"></i></button>' +
                            '</div>';
                        grid.appendChild(item);
                    });
                }
                // Si se alcanzó el límite, ocultar form de upload
                if (d.total >= 8 && form) {
                    form.style.display = 'none';
                }
            } else {
                Swal.fire('Error', d.error || 'No se pudo subir', 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = btnHtml;
            btn.disabled = false;
            Swal.fire('Error', 'Error al subir imágenes', 'error');
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
    fetch('?toggle_proyecto=' + id)
        .then(r => r.json())
        .then(d => {
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

// Eliminar imagen de galería
function eliminarImgGaleria(id) {
    Swal.fire({
        title: '¿Eliminar imagen?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Sí, eliminar'
    }).then(r => {
        if (r.isConfirmed) {
            fetch('?eliminar_img_galeria=' + id).then(res => res.json()).then(d => {
                if (d.ok) {
                    var el = document.getElementById('gi-' + id);
                    if (el) el.remove();
                    Swal.fire({toast:true,position:'top-end',icon:'success',title:'Imagen eliminada',showConfirmButton:false,timer:1500});
                }
            });
        }
    });
}

// Reemplazar imagen de galería
function reemplazarImgGaleria(id) {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function() {
        if (!input.files[0]) return;
        var fd = new FormData();
        fd.append('reemplazar_img_galeria', '1');
        fd.append('imagen_id', id);
        fd.append('nueva_imagen', input.files[0]);
        fetch('admin_fundacion.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    var item = document.getElementById('gi-' + id);
                    if (item) {
                        var img = item.querySelector('img');
                        if (img) img.src = '../assets/img/fundacion/' + d.nueva + '?t=' + Date.now();
                    }
                    Swal.fire({toast:true,position:'top-end',icon:'success',title:'Imagen reemplazada',showConfirmButton:false,timer:1500});
                } else {
                    Swal.fire('Error', d.error || 'No se pudo reemplazar', 'error');
                }
            });
    };
    input.click();
}

// Drag & Drop reorder galería
(function() {
    var grid = document.getElementById('galeriaGrid');
    if (!grid) return;
    var dragItem = null;

    grid.addEventListener('dragstart', function(e) {
        var item = e.target.closest('.fd-galeria-item');
        if (!item) return;
        dragItem = item;
        item.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var item = e.target.closest('.fd-galeria-item');
        if (item && item !== dragItem) {
            document.querySelectorAll('.fd-galeria-item.drag-over-g').forEach(function(el) { el.classList.remove('drag-over-g'); });
            item.classList.add('drag-over-g');
        }
    });

    grid.addEventListener('dragleave', function(e) {
        var item = e.target.closest('.fd-galeria-item');
        if (item) item.classList.remove('drag-over-g');
    });

    grid.addEventListener('drop', function(e) {
        e.preventDefault();
        var target = e.target.closest('.fd-galeria-item');
        if (target && dragItem && target !== dragItem) {
            var items = Array.from(grid.children);
            var fromIdx = items.indexOf(dragItem);
            var toIdx = items.indexOf(target);
            if (fromIdx < toIdx) {
                grid.insertBefore(dragItem, target.nextSibling);
            } else {
                grid.insertBefore(dragItem, target);
            }
            guardarOrdenGaleria();
        }
        document.querySelectorAll('.fd-galeria-item.drag-over-g').forEach(function(el) { el.classList.remove('drag-over-g'); });
    });

    grid.addEventListener('dragend', function(e) {
        if (dragItem) dragItem.classList.remove('dragging');
        dragItem = null;
        document.querySelectorAll('.fd-galeria-item.drag-over-g').forEach(function(el) { el.classList.remove('drag-over-g'); });
    });

    function guardarOrdenGaleria() {
        var ids = Array.from(grid.querySelectorAll('.fd-galeria-item')).map(function(el) { return el.dataset.id; });
        var fd = new FormData();
        fd.append('reorder_galeria', '1');
        ids.forEach(function(id) { fd.append('ids[]', id); });
        fetch('admin_fundacion.php', { method: 'POST', body: fd });
    }
})();

// Validar límite de 8 imágenes y formato .webp en galería
function validarGaleria(input) {
    for (var i = 0; i < input.files.length; i++) {
        if (!input.files[i].name.toLowerCase().endsWith('.webp')) {
            Swal.fire('Formato no válido', 'Solo se aceptan archivos .webp. Archivo: ' + input.files[i].name, 'warning');
            input.value = '';
            return false;
        }
    }
    var maxImg = 8;
    var itemsActuales = document.querySelectorAll('.fd-galeria-item').length;
    if (itemsActuales + input.files.length > maxImg) {
        Swal.fire('Límite alcanzado', 'Solo puedes tener un máximo de 8 imágenes en la galería. Tienes ' + itemsActuales + ' y seleccionaste ' + input.files.length + ' más.', 'warning');
        input.value = '';
        return false;
    }
    return true;
}

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
    Swal.fire({
        title: 'Editar sección',
        input: 'text',
        inputLabel: 'Título',
        inputValue: titulo,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        confirmButtonColor: '#00685f'
    }).then(r => {
        if (r.isConfirmed) {
            var fd = new FormData();
            fd.append('editar_seccion', '1');
            fd.append('seccion_id', id);
            fd.append('seccion_proyecto_id', '<?= $fp["id"] ?? 0 ?>');
            fd.append('seccion_titulo_edit', r.value);
            fetch('admin_fundacion.php', { method: 'POST', body: fd })
                .then(res => res.text())
                .then(() => location.reload());
        }
    });
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
            fetch('?eliminar_seccion=' + id).then(res => res.json()).then(d => {
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
            fetch('?eliminar_secimg=' + id).then(res => res.json()).then(d => {
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
    input.accept = 'image/*';
    input.onchange = function() {
        if (!input.files[0]) return;
        var fd = new FormData();
        fd.append('reemplazar_secimg', '1');
        fd.append('imagen_id', id);
        fd.append('nueva_imagen', input.files[0]);
        fetch('admin_fundacion.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    var item = document.getElementById('si-' + id);
                    if (item) {
                        var img = item.querySelector('img');
                        if (img) img.src = '../assets/img/fundacion/' + d.nueva + '?t=' + Date.now();
                    }
                    Swal.fire({toast:true,position:'top-end',icon:'success',title:'Imagen reemplazada',showConfirmButton:false,timer:1500});
                } else {
                    Swal.fire('Error', d.error || 'No se pudo reemplazar', 'error');
                }
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
</script>
</body>
</html>
