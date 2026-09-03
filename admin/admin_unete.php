<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('unete');

require_once '../config/database.php';
$db = (new Database())->getConnection();

// --- VERIFICAR Y AGREGAR COLUMNAS A LA TABLA SI NO EXISTEN ---
try { $db->query("SELECT archivo_pdf FROM vacantes LIMIT 1"); } catch (Exception $e) { $db->query("ALTER TABLE vacantes ADD COLUMN archivo_pdf VARCHAR(255) NULL AFTER estado"); }
try { $db->query("SELECT cta_titulo FROM vacantes LIMIT 1"); } catch (Exception $e) { 
    $db->query("ALTER TABLE vacantes ADD COLUMN cta_titulo VARCHAR(255) NULL, ADD COLUMN cta_titulo_en VARCHAR(255) NULL, ADD COLUMN cta_descripcion TEXT NULL, ADD COLUMN cta_descripcion_en TEXT NULL, ADD COLUMN cta_boton_txt VARCHAR(100) NULL, ADD COLUMN cta_boton_txt_en VARCHAR(100) NULL"); 
}

// --- VERIFICAR REGISTRO BASE (ID 1) ---
$check_banner = $db->query("SELECT id FROM vacantes WHERE id = 1")->fetch();
if (!$check_banner) {
    $db->query("INSERT INTO vacantes (id, titulo_es, banner_titulo, banner_subtitulo) VALUES (1, 'CONFIG_BASE', 'Únete a nuestro equipo', 'Construye el futuro del turismo con nosotros')");
}

// --- 1. LÓGICA PARA ACTUALIZAR CONTENIDO GLOBAL (ID 1) ---
if (isset($_POST['actualizar_contenido_global'])) {
    $stmt = $db->prepare("UPDATE vacantes SET 
        banner_titulo = ?, banner_titulo_en = ?, banner_subtitulo = ?, banner_subtitulo_en = ?,
        intro_descripcion = ?, intro_descripcion_en = ?,
        cta_titulo = ?, cta_titulo_en = ?, cta_descripcion = ?, cta_descripcion_en = ?, cta_boton_txt = ?, cta_boton_txt_en = ?
        WHERE id = 1");
    
    $stmt->execute([
        $_POST['b_titulo'], $_POST['b_titulo_en'], $_POST['b_subtitulo'], $_POST['b_subtitulo_en'],
        $_POST['intro_descripcion'], $_POST['intro_descripcion_en'],
        $_POST['cta_titulo'], $_POST['cta_titulo_en'], $_POST['cta_descripcion'], $_POST['cta_descripcion_en'], $_POST['cta_boton_txt'], $_POST['cta_boton_txt_en']
    ]);

    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] == 0) {
        move_uploaded_file($_FILES['banner_img']['tmp_name'], "../assets/img/banner_unete_header.jpg");
    }
    if (isset($_FILES['cta_bg_img']) && $_FILES['cta_bg_img']['error'] == 0) {
        move_uploaded_file($_FILES['cta_bg_img']['tmp_name'], "../assets/img/bg_cta_unete.jpg");
    }
    header("Location: admin_unete.php?res=content_ok"); exit;
}

// --- 2. GUARDAR O EDITAR VACANTE ---
if (isset($_POST['guardar_vacante'])) {
    $id = !empty($_POST['vacante_id']) ? intval($_POST['vacante_id']) : null;
    $titulo_es = $_POST['titulo_es']; $titulo_en = $_POST['titulo_en'];
    $ubi_es = $_POST['ubi_es']; $ubi_en = $_POST['ubi_en']; $estado = $_POST['estado'];
    $archivo_pdf = !empty($_POST['pdf_actual']) ? $_POST['pdf_actual'] : null;

    if (isset($_FILES['vacante_pdf']) && $_FILES['vacante_pdf']['error'] == 0) {
        if ($_FILES['vacante_pdf']['type'] == 'application/pdf') {
            $carpeta_pdf = "../assets/pdf/vacantes/";
            if (!is_dir($carpeta_pdf)) { mkdir($carpeta_pdf, 0777, true); }
            $nombre_limpio = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "", $_FILES['vacante_pdf']['name']);
            if (move_uploaded_file($_FILES['vacante_pdf']['tmp_name'], $carpeta_pdf . $nombre_limpio)) {
                if (!empty($_POST['pdf_actual']) && file_exists($carpeta_pdf . $_POST['pdf_actual'])) { @unlink($carpeta_pdf . $_POST['pdf_actual']); }
                $archivo_pdf = $nombre_limpio;
            }
        }
    }

    if ($id) {
        $stmt = $db->prepare("UPDATE vacantes SET titulo_es=?, titulo_en=?, ubicacion_es=?, ubicacion_en=?, estado=?, archivo_pdf=? WHERE id=?");
        $stmt->execute([$titulo_es, $titulo_en, $ubi_es, $ubi_en, $estado, $archivo_pdf, $id]);
        $res = "updated";
    } else {
        $stmt = $db->prepare("INSERT INTO vacantes (titulo_es, titulo_en, ubicacion_es, ubicacion_en, estado, archivo_pdf) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$titulo_es, $titulo_en, $ubi_es, $ubi_en, $estado, $archivo_pdf]);
        $res = "success";
    }
    header("Location: admin_unete.php?res=$res"); exit;
}

// --- 3. ELIMINAR VACANTE ---
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($id !== 1) {
        $v_info = $db->prepare("SELECT archivo_pdf FROM vacantes WHERE id = ?"); $v_info->execute([$id]); $reg = $v_info->fetch();
        if ($reg && !empty($reg['archivo_pdf'])) { @unlink("../assets/pdf/vacantes/" . $reg['archivo_pdf']); }
        $db->prepare("DELETE FROM vacantes WHERE id = ?")->execute([$id]);
        $res = "deleted";
    } else { $res = "error"; }
    header("Location: admin_unete.php?res=$res"); exit;
}

$c = $db->query("SELECT * FROM vacantes WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$vacantes = $db->query("SELECT * FROM vacantes WHERE id != 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$guia_placeholder = "GUÍA DE FORMATO:\n# Título de Sección\n## Tarjeta de Valor\n[icon:nombre-icono]\n**Texto en Negrita**\n_Texto Subrayado_";
$banner_url = file_exists("../assets/img/banner_unete_header.jpg") ? "../assets/img/banner_unete_header.jpg?v=".time() : "https://via.placeholder.com/1200x400?text=Sin+Banner";
$cta_url = file_exists("../assets/img/bg_cta_unete.jpg") ? "../assets/img/bg_cta_unete.jpg?v=".time() : "https://via.placeholder.com/1200x400?text=Sin+Imagen+CTA";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Únete Pro | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-teal: #0f9b9e; --admin-lime: #c6d544; }
        .main-content { padding: 30px; }
        .panel-box { background: white; padding: 25px; border-radius: 15px; border: 1px solid #edf2f7; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .sub-seccion-tit { grid-column: 1/3; border-bottom: 2px solid var(--admin-lime); color: var(--admin-blue); padding-bottom: 5px; margin-top: 15px; font-weight: 800; }
        .status-badge { padding: 5px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .open { background: #d4edda; color: #155724; } .closed { background: #f8d7da; color: #721c24; }
        .btn-pro { background: var(--admin-blue); color: #fff; border: none; padding: 12px 22px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; font-weight: 700; transition: 0.3s; text-decoration:none; }
        .btn-pro:hover { background: #1b3d75; transform: translateY(-2px); }
        .btn-teal { background: var(--admin-teal); } .btn-teal:hover { background: #0c8285; }
        input, select, textarea { width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; box-sizing: border-box; }
        textarea { font-family: monospace; resize: vertical; background: #fafafa; }
        .lang-tag { font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 5px; }
        .preview-box { margin-top: 10px; border: 2px dashed #cbd5e1; padding: 10px; border-radius: 12px; background: #f8fafc; text-align: center; position: relative; }
        .preview-box img { max-width: 100%; max-height: 140px; border-radius: 8px; object-fit: cover; }
        .preview-badge { position: absolute; top: 15px; left: 15px; background: rgba(21, 48, 93, 0.85); color: white; padding: 4px 10px; font-size: 11px; font-weight: bold; border-radius: 4px; }
        .modal-unete { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-unete.active { display: flex; }
        .modal-box { background: white; width: 92%; max-width: 650px; border-radius: 20px; overflow: hidden; }
        .modal-header { background: var(--admin-blue); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .pdf-indicator { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #fff1f2; color: #e11d48; border-radius: 6px; font-size: 12px; font-weight: bold; margin-top: 5px; border: 1px solid #ffe4e6; }
        .pdf-indicator a { color: #e11d48; text-decoration: underline; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h1 style="color: var(--admin-blue); font-weight: 900; margin-bottom: 30px;"><i class="fas fa-briefcase"></i> Panel Únete Pro</h1>

        <div class="panel-box">
            <h3 style="color: var(--admin-blue); margin-top: 0; font-weight: 800;"><i class="fas fa-cog"></i> 1. Identidad y Cuerpo de la Página</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid-inputs">
                    <div class="sub-seccion-tit"><i class="fas fa-image"></i> Cabecera Principal (Banner)</div>
                    <div>
                        <span class="lang-tag" style="background:#e2f0fd; color:#0c63e4;">Español</span>
                        <input type="text" name="b_titulo" value="<?= htmlspecialchars($c['banner_titulo'] ?? '') ?>" placeholder="Título Banner">
                        <input type="text" name="b_subtitulo" value="<?= htmlspecialchars($c['banner_subtitulo'] ?? '') ?>" placeholder="Subtítulo Banner">
                    </div>
                    <div>
                        <span class="lang-tag" style="background:#fef3c7; color:#d97706;">English</span>
                        <input type="text" name="b_titulo_en" value="<?= htmlspecialchars($c['banner_titulo_en'] ?? '') ?>" placeholder="Banner Title">
                        <input type="text" name="b_subtitulo_en" value="<?= htmlspecialchars($c['banner_subtitulo_en'] ?? '') ?>" placeholder="Banner Subtitle">
                    </div>
                    <div style="grid-column: 1/3;">
                        <label style="font-weight:700;">Imagen Banner Principal:</label>
                        <input type="file" id="inp_banner" name="banner_img" accept="image/*">
                        <div class="preview-box"><span class="preview-badge">Vista Previa Banner</span><img id="img_banner" src="<?= $banner_url ?>"></div>
                    </div>

                    <div class="sub-seccion-tit"><i class="fas fa-file-alt"></i> Contenido Intermedio (Introducción, Valores, etc.)</div>
                    <div>
                        <span class="lang-tag" style="background:#e2f0fd; color:#0c63e4;">Español</span>
                        <textarea name="intro_descripcion" rows="10" placeholder="<?= $guia_placeholder ?>"><?= htmlspecialchars($c['intro_descripcion'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <span class="lang-tag" style="background:#fef3c7; color:#d97706;">English</span>
                        <textarea name="intro_descripcion_en" rows="10" placeholder="<?= $guia_placeholder ?>"><?= htmlspecialchars($c['intro_descripcion_en'] ?? '') ?></textarea>
                    </div>

                    <div class="sub-seccion-tit"><i class="fas fa-bullhorn"></i> 2. Sección de Cierre (Llamada a la Acción)</div>
                    <div>
                        <span class="lang-tag" style="background:#e2f0fd; color:#0c63e4;">Español</span>
                        <input type="text" name="cta_titulo" value="<?= htmlspecialchars($c['cta_titulo'] ?? '') ?>" placeholder="Título de la Sección">
                        <textarea name="cta_descripcion" rows="4" placeholder="Cuerpo del Mensaje..."><?= htmlspecialchars($c['cta_descripcion'] ?? '') ?></textarea>
                        <input type="text" name="cta_boton_txt" value="<?= htmlspecialchars($c['cta_boton_txt'] ?? '') ?>" placeholder="Texto del Botón">
                    </div>
                    <div>
                        <span class="lang-tag" style="background:#fef3c7; color:#d97706;">English</span>
                        <input type="text" name="cta_titulo_en" value="<?= htmlspecialchars($c['cta_titulo_en'] ?? '') ?>" placeholder="Section Title">
                        <textarea name="cta_descripcion_en" rows="4" placeholder="Message body..."><?= htmlspecialchars($c['cta_descripcion_en'] ?? '') ?></textarea>
                        <input type="text" name="cta_boton_txt_en" value="<?= htmlspecialchars($c['cta_boton_txt_en'] ?? '') ?>" placeholder="Button Text">
                    </div>
                    <div style="grid-column: 1/3;">
                        <label style="font-weight:700;">Imagen de Fondo Ajustada (Sección CTA):</label>
                        <input type="file" id="inp_cta" name="cta_bg_img" accept="image/*">
                        <div class="preview-box"><span class="preview-badge">Vista Previa Fondo CTA</span><img id="img_cta" src="<?= $cta_url ?>"></div>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 25px;">
                    <button type="submit" name="actualizar_contenido_global" class="btn-pro" style="width: 100%; height: 45px; justify-content: center;"><i class="fas fa-save"></i> GUARDAR TODO EL PANEL DE DISEÑO</button>
                </div>
            </form>
        </div>

        <div class="admin-contenedor">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin:0; color: var(--admin-blue); font-weight: 800;"><i class="fas fa-users"></i> 3. Control de Convocatorias Abiertas</h3>
                <button onclick="abrirModal()" class="btn-pro btn-teal"><i class="fas fa-plus-circle"></i> Nueva Vacante</button>
            </div>
            <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #eee;">
                        <th style="padding:15px;">Puesto Vacante</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Bases / Info (PDF)</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($vacantes): foreach($vacantes as $v): ?>
                    <tr style="border-bottom: 1px solid #edf2f7;">
                        <td style="padding: 15px;">
                            <span style="font-weight: 800; color: var(--admin-blue);"><?= htmlspecialchars($v['titulo_es']) ?></span><br>
                            <small style="color: #a0aec0;">EN: <?= htmlspecialchars($v['titulo_en']) ?></small>
                        </td>
                        <td><strong><?= htmlspecialchars($v['ubicacion_es']) ?></strong></td>
                        <td><span class="status-badge <?= (strtolower($v['estado']) == 'abierto') ? 'open' : 'closed' ?>"><?= $v['estado'] ?></span></td>
                        <td>
                            <?php if(!empty($v['archivo_pdf'])): ?>
                                <a href="../assets/pdf/vacantes/<?= $v['archivo_pdf'] ?>" target="_blank" style="color: #e11d48; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-file-pdf"></i> Ver PDF</a>
                            <?php else: ?>
                                <span style="color:#a0aec0; font-style: italic; font-size:12px;">Sin PDF</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <button onclick='editarVacante(<?= json_encode($v) ?>)' style="color: #f39c12; background:none; border:none; cursor:pointer; font-size:1.2rem; margin-right:15px;"><i class="fas fa-edit"></i></button>
                            <button onclick="confirmarEliminar(<?= $v['id'] ?>)" style="color: #e74c3c; background:none; border:none; cursor:pointer; font-size:1.2rem;"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:20px; color:#a0aec0;">No hay convocatorias laborales.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div id="modalVacante" class="modal-unete">
    <div class="modal-box">
        <div class="modal-header"><h3 id="modal_label" style="margin:0;">Convocatoria</h3><span onclick="cerrarModal()" style="cursor:pointer; font-size:20px;">&times;</span></div>
        <form method="POST" enctype="multipart/form-data" style="padding: 25px;">
            <input type="hidden" name="vacante_id" id="vacante_id">
            <input type="hidden" name="pdf_actual" id="pdf_actual">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <label style="font-weight: bold;">Puesto (ES):</label><input type="text" name="titulo_es" id="titulo_es" required>
                    <label style="font-weight: bold;">Ubicación (ES):</label><input type="text" name="ubi_es" id="ubi_es" value="Cusco, Perú">
                </div>
                <div>
                    <label style="font-weight: bold;">Puesto (EN):</label><input type="text" name="titulo_en" id="titulo_en" required>
                    <label style="font-weight: bold;">Ubicación (EN):</label><input type="text" name="ubi_en" id="ubi_en" value="Cusco, Peru">
                </div>
            </div>
            <label style="font-weight: bold;">Estado:</label>
            <select name="estado" id="estado"><option value="Abierto">Abierto</option><option value="Cerrado">Cerrado</option></select>
            <div style="margin-bottom: 15px; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                <label style="font-weight: bold; color: var(--admin-blue);"><i class="fas fa-file-pdf"></i> Cargar Requisitos en PDF:</label>
                <input type="file" name="vacante_pdf" accept="application/pdf" style="background:white; margin-top:5px;">
                <div id="pdf_status_container"></div>
            </div>
            <button type="submit" name="guardar_vacante" class="btn-pro btn-teal" style="width:100%; height:45px; justify-content:center;">Guardar Convocatoria</button>
        </form>
    </div>
</div>

<script>
    function setupPreview(inputField, imgElement) {
        document.getElementById(inputField).addEventListener('change', function(e) {
            const r = new FileReader();
            r.onload = function() { document.getElementById(imgElement).src = r.result; };
            if(e.target.files[0]) r.readAsDataURL(e.target.files[0]);
        });
    }
    setupPreview('inp_banner', 'img_banner');
    setupPreview('inp_cta', 'img_cta');

    function abrirModal() {
        document.getElementById('vacante_id').value = ''; document.getElementById('pdf_actual').value = '';
        document.getElementById('titulo_es').value = ''; document.getElementById('titulo_en').value = '';
        document.getElementById('ubi_es').value = 'Cusco, Perú'; document.getElementById('ubi_en').value = 'Cusco, Peru';
        document.getElementById('estado').value = 'Abierto'; document.getElementById('pdf_status_container').innerHTML = '';
        document.getElementById('modal_label').innerText = 'Nueva Convocatoria'; document.getElementById('modalVacante').classList.add('active');
    }
    function editarVacante(v) {
        document.getElementById('vacante_id').value = v.id; document.getElementById('pdf_actual').value = v.archivo_pdf || '';
        document.getElementById('titulo_es').value = v.titulo_es; document.getElementById('titulo_en').value = v.titulo_en;
        document.getElementById('ubi_es').value = v.ubicacion_es; document.getElementById('ubi_en').value = v.ubicacion_en;
        document.getElementById('estado').value = v.estado;
        const s = document.getElementById('pdf_status_container');
        s.innerHTML = v.archivo_pdf ? `<div class="pdf-indicator"><i class="fas fa-file-pdf"></i> <span>Actual: <a href="../assets/pdf/vacantes/${v.archivo_pdf}" target="_blank">${v.archivo_pdf}</a></span></div>` : '';
        document.getElementById('modal_label').innerText = 'Editar Convocatoria'; document.getElementById('modalVacante').classList.add('active');
    }
    function cerrarModal() { document.getElementById('modalVacante').classList.remove('active'); }
    function confirmarEliminar(id) {
        Swal.fire({ title: '¿Borrar vacante?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'Sí, borrar' }).then((r) => { if (r.isConfirmed) window.location.href = '?eliminar=' + id; });
    }
    document.addEventListener('DOMContentLoaded', () => {
        const p = new URLSearchParams(window.location.search);
        if (p.get('res')) { Swal.fire({ icon: 'success', title: '¡Hecho!', confirmButtonColor: '#15305D' }); window.history.replaceState({}, document.title, window.location.pathname); }
    });
</script>
</body>
</html>