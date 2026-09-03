<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('contenido_index');

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

require_once __DIR__ . '/../includes/resenas_helper.php';
asegurar_infraestructura_resenas($db);

// --- GUARDAR ENCABEZADO DE RESEÑAS (título, subtítulo, descripción, activo) ---
if (isset($_POST['guardar_encabezado_resenas'])) {
    $stmt = $db->prepare("UPDATE secciones_index SET activo=?, titulo_es=?, titulo_en=?, subtitulo_es=?, subtitulo_en=?, texto_es=?, texto_en=? WHERE seccion='reviews' LIMIT 1");
    $stmt->execute([
        isset($_POST['seccion_activa']) ? 1 : 0,
        $_POST['titulo_es'] ?? '',
        $_POST['titulo_en'] ?? '',
        $_POST['subtitulo_es'] ?? '',
        $_POST['subtitulo_en'] ?? '',
        $_POST['texto_es'] ?? '',
        $_POST['texto_en'] ?? '',
    ]);
    header("Location: admin_index.php?res=ok");
    exit;
}

// --- GUARDAR CONFIGURACIÓN DE RESEÑAS (plataformas, widget, display) ---
if (isset($_POST['guardar_resenas'])) {
    $plataformas = [];
    foreach (['tripadvisor', 'google', 'trustpilot'] as $p) {
        $plataformas[$p] = [
            'activo'    => isset($_POST['plat_activo'][$p]) ? 1 : 0,
            'puntaje'   => trim($_POST['plat_puntaje'][$p] ?? ''),
            'opiniones' => trim($_POST['plat_opiniones'][$p] ?? ''),
            'url'       => trim($_POST['plat_url'][$p] ?? ''),
        ];
    }
    $extra = [
        'etiqueta'     => trim($_POST['etiqueta'] ?? ''),
        'plataformas'  => $plataformas,
        'widget_activo'=> isset($_POST['widget_activo']) ? 1 : 0,
        'widget_code'  => $_POST['widget_code'] ?? '',
        'max_por_plataforma'   => max(1, min(6, (int)($_POST['max_por_plataforma'] ?? 3))),
        'lineas_texto'         => max(1, min(8, (int)($_POST['lineas_texto'] ?? 3))),
        'sync_intervalo_horas' => max(1, min(72, (int)($_POST['sync_intervalo_horas'] ?? 6))),
    ];
    $paleta_raw = trim($_POST['paleta_colores'] ?? '');
    if ($paleta_raw !== '') {
        $paleta = array_filter(array_map('trim', explode(',', $paleta_raw)), function($c) {
            return preg_match('/^#[0-9a-fA-F]{3,6}$/', $c);
        });
        $extra['paleta_colores'] = array_values($paleta);
    }
    $stmt = $db->prepare("UPDATE secciones_index SET extra_json=? WHERE seccion='reviews' LIMIT 1");
    $stmt->execute([json_encode($extra, JSON_UNESCAPED_UNICODE)]);
    header("Location: admin_index.php?res=ok");
    exit;
}

// --- SINCRONIZAR DESDE TRUSTINDEX ---
if (isset($_POST['sync_trustindex'])) {
    $resultado = sincronizar_resenas_trustindex($db);
    if ($resultado['ok']) {
        $msg = "Sincronización completada: {$resultado['importadas']} nuevas, {$resultado['actualizadas']} actualizadas.";
        header("Location: admin_index.php?res=sync&msg=" . urlencode($msg));
    } else {
        header("Location: admin_index.php?res=syncerr&msg=" . urlencode($resultado['error']));
    }
    exit;
}

// --- GUARDAR UNA RESEÑA (agregar/editar) ---
if (isset($_POST['guardar_resena'])) {
    $id = $_POST['id'] ?? '';
    $campos = [
        $_POST['plataforma'] ?? 'google',
        $_POST['autor'] ?? '',
        $_POST['fecha'] ?? '',
        $_POST['titulo'] ?? '',
        $_POST['texto'] ?? '',
        $_POST['link'] ?? '',
        $_POST['color_avatar'] ?? '#0f9b9e',
        (int)($_POST['orden'] ?? 0),
        isset($_POST['activo']) ? 1 : 0,
    ];
    if (!empty($id)) {
        $stmt = $db->prepare("UPDATE resenas SET plataforma=?, autor=?, fecha=?, titulo=?, texto=?, link=?, color_avatar=?, orden=?, activo=? WHERE id=?");
        $stmt->execute(array_merge($campos, [$id]));
    } else {
        $stmt = $db->prepare("INSERT INTO resenas (plataforma, autor, fecha, titulo, texto, link, color_avatar, orden, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($campos);
    }
    header("Location: admin_index.php?res=ok");
    exit;
}

// --- ELIMINAR RESEÑA ---
if (isset($_GET['del_resena'])) {
    $db->prepare("DELETE FROM resenas WHERE id=?")->execute([$_GET['del_resena']]);
    header("Location: admin_index.php?res=deleted");
    exit;
}

// --- OBTENER DATOS ---
$d = $db->query("SELECT * FROM secciones_index WHERE seccion='reviews' LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

$resenas_list = $db->query("SELECT * FROM resenas ORDER BY plataforma ASC, orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$edit_resena = null;
if (isset($_GET['edit_resena'])) {
    foreach ($resenas_list as $r) {
        if ($r['id'] == $_GET['edit_resena']) {
            $edit_resena = $r;
            break;
        }
    }
}
$edit_resena = $edit_resena ?: ['id'=>'', 'plataforma'=>'google', 'autor'=>'', 'fecha'=>'', 'titulo'=>'', 'texto'=>'', 'link'=>'', 'color_avatar'=>'#0f9b9e', 'orden'=>1, 'activo'=>1];

$jd = json_decode($d['extra_json'] ?? '', true);
if (!is_array($jd)) {
    $jd = resenas_valores_default();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reseñas | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #ddd; }
        .card h3 { border-left: 5px solid var(--admin-blue); padding-left: 15px; color: var(--admin-blue); margin-bottom: 20px; text-transform: uppercase; font-size: 1rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; margin-bottom: 10px; }
        .btn-admin { background: var(--admin-blue); color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-admin:hover { background: #0e2245; }
        .btn-edit { background: var(--admin-accent); color: #000; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; display: inline-block; }
        .btn-del { background: #e74c3c; color: #fff; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-secondary { background: #95a5a6; color: #fff; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: var(--admin-blue); color: #fff; font-size: 0.85rem; text-transform: uppercase; }
        tr:hover { background: #f5f5f5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-full { grid-column: 1 / -1; }
        .info-box { background: #eef2ff; border-left: 4px solid var(--admin-blue); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; color: #333; }
        textarea.form-control { min-height: 120px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #27ae60; color: #fff; }
        .estilos-card { margin-top:20px; padding:18px; background:#f8f9fa; border:2px dashed #d0d5dd; border-radius:12px; }
        .estilos-card h4 { margin:0 0 15px 0; font-size:0.95rem; color:var(--admin-blue); }
        .estilos-card label { font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:4px; }
        .toggle-wrap { position:relative;display:inline-block;width:56px;height:28px;cursor:pointer; }
        .toggle-wrap input { opacity:0;width:0;height:0;position:absolute; }
        .toggle-track { position:absolute;inset:0;background:#ccc;border-radius:28px;transition:.3s; }
        .toggle-knob { position:absolute;left:2px;top:2px;width:24px;height:24px;background:#fff;border-radius:50%;transition:.3s; }
        .toggle-wrap input:checked ~ .toggle-track { background:#059669; }
        .toggle-wrap input:checked ~ .toggle-knob { left:30px; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding: 30px;">
        <h1 class="admin-title"><i class="fas fa-star"></i> Reseñas</h1>
        <div class="info-box">
            <i class="fas fa-info-circle" style="color:var(--admin-blue);"></i>
            Gestiona la sección "Lo que dicen nuestros clientes" de la página principal: encabezado, sincronización automática y reseñas individuales.
        </div>

        <?php if (isset($_GET['res']) && $_GET['res'] === 'sync'): ?>
            <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:12px 16px;margin-bottom:14px;font-size:0.9rem;">
                <i class="fas fa-check-circle" style="color:#059669;"></i>
                <?= htmlspecialchars($_GET['msg'] ?? 'Sincronización completada.') ?>
            </div>
        <?php elseif (isset($_GET['res']) && $_GET['res'] === 'syncerr'): ?>
            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;margin-bottom:14px;font-size:0.9rem;">
                <i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>
                <strong>Error:</strong> <?= htmlspecialchars($_GET['msg'] ?? '') ?>
            </div>
        <?php endif; ?>

        <!-- ENCABEZADO: Título, subtítulo, descripción, activar/desactivar -->
        <div class="card" style="border-left:4px solid var(--admin-accent);">
            <h3><i class="fas fa-heading"></i> Encabezado de la sección</h3>
            <form method="POST">
                <input type="hidden" name="guardar_encabezado_resenas" value="1">
                <div style="background:#f8f9fa;border:10px solid #e9ecef;border-radius:12px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                    <div>
                        <strong style="font-size:0.95rem;color:var(--admin-blue);">Mostrar esta sección en la página principal</strong>
                        <p style="margin:2px 0 0;font-size:0.8rem;color:#888;">Al desactivar, la sección no aparecerá para los visitantes del sitio.</p>
                    </div>
                    <label class="toggle-wrap">
                        <input type="checkbox" name="seccion_activa" <?= (int)($d['activo'] ?? 1) === 1 ? 'checked' : '' ?> onchange="document.getElementById('labelActivo').textContent=this.checked?'Sí — Sección visible':'No — Sección oculta';document.getElementById('labelActivo').style.color=this.checked?'#059669':'#999';">
                        <span class="toggle-track"></span>
                        <span class="toggle-knob"></span>
                    </label>
                    <span id="labelActivo" style="font-weight:700;font-size:0.85rem;color:<?= (int)($d['activo'] ?? 1) === 1 ? '#059669' : '#999' ?>;">
                        <?= (int)($d['activo'] ?? 1) === 1 ? 'Sí — Sección visible' : 'No — Sección oculta' ?>
                    </span>
                </div>
                <div class="grid-2">
                    <div>
                        <label>Título (Español)</label>
                        <input type="text" name="titulo_es" value="<?= htmlspecialchars($d['titulo_es'] ?? '') ?>" class="form-control">
                    </div>
                    <div>
                        <label>Título (Inglés)</label>
                        <input type="text" name="titulo_en" value="<?= htmlspecialchars($d['titulo_en'] ?? '') ?>" class="form-control">
                    </div>
                    <div>
                        <label>Subtítulo (Español)</label>
                        <input type="text" name="subtitulo_es" value="<?= htmlspecialchars($d['subtitulo_es'] ?? '') ?>" class="form-control">
                    </div>
                    <div>
                        <label>Subtítulo (Inglés)</label>
                        <input type="text" name="subtitulo_en" value="<?= htmlspecialchars($d['subtitulo_en'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="grid-full">
                        <label>Descripción (Español)</label>
                        <textarea name="texto_es" class="form-control"><?= htmlspecialchars($d['texto_es'] ?? '') ?></textarea>
                    </div>
                    <div class="grid-full">
                        <label>Descripción (Inglés)</label>
                        <textarea name="texto_en" class="form-control"><?= htmlspecialchars($d['texto_en'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-admin" style="margin-top:15px;"><i class="fas fa-save"></i> Guardar Encabezado</button>
            </form>
        </div>

        <!-- SINCRONIZAR -->
        <div class="card" style="border-left:4px solid #0f9b9e;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <h3 style="margin:0;"><i class="fas fa-sync-alt" style="color:#0f9b9e;"></i> Sincronización desde Trustindex</h3>
                    <p style="margin:4px 0 0;font-size:0.85rem;color:#666;">
                        Actualiza las reseñas automáticamente desde el widget. La sección se actualiza solo cada <?= (int)($jd['sync_intervalo_horas'] ?? 6) ?> horas cuando alguien visita el sitio.
                        <?php $ultima = obtener_ultima_sync($db); if ($ultima): ?>
                            <br>Última sincronización: <strong><?= htmlspecialchars($ultima) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="sync_trustindex" value="1">
                    <button type="submit" class="btn-admin" onclick="this.disabled=true;this.innerHTML='<i class=fa-spinner fa-spin></i> Sincronizando...'">
                        <i class="fas fa-sync-alt"></i> Sincronizar ahora
                    </button>
                </form>
            </div>
        </div>

        <!-- CONFIGURACIÓN: Etiqueta, plataformas, widget, display -->
        <div class="card">
            <h3><i class="fas fa-sliders-h"></i> Configuración</h3>
            <form method="POST">
                <input type="hidden" name="guardar_resenas" value="1">
                <div class="grid-2">
                    <div class="grid-full">
                        <label>Etiqueta de las tarjetas resumen (ej. "Inti Path Tours")</label>
                        <input type="text" name="etiqueta" value="<?= htmlspecialchars($jd['etiqueta'] ?? '') ?>" class="form-control">
                    </div>
                </div>
                <hr>
                <h3 style="margin-bottom:10px; font-size:0.95rem; color:#15305D;"><i class="fas fa-link"></i> Resumen de cada plataforma</h3>
                <div class="grid-full">
                    <?php
                    foreach (['tripadvisor', 'google', 'trustpilot'] as $p):
                        $pf = $jd['plataformas'][$p] ?? ['activo'=>1, 'puntaje'=>'', 'opiniones'=>'', 'url'=>''];
                    ?>
                    <div class="estilos-card">
                        <h4>
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="plat_activo[<?= $p ?>]" <?= ($pf['activo'] ?? 1) ? 'checked' : '' ?>>
                                <?= ucfirst($p) ?> — mostrar en el index
                            </label>
                        </h4>
                        <div class="grid-2">
                            <div>
                                <label>Puntaje (ej. 5.0)</label>
                                <input type="text" name="plat_puntaje[<?= $p ?>]" value="<?= htmlspecialchars($pf['puntaje'] ?? '') ?>" class="form-control">
                            </div>
                            <div>
                                <label>Nº de opiniones (ej. 12910)</label>
                                <input type="text" name="plat_opiniones[<?= $p ?>]" value="<?= htmlspecialchars($pf['opiniones'] ?? '') ?>" class="form-control">
                            </div>
                            <div class="grid-full">
                                <label>URL de la plataforma</label>
                                <input type="text" name="plat_url[<?= $p ?>]" value="<?= htmlspecialchars($pf['url'] ?? '') ?>" class="form-control" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <h3 style="margin-bottom:10px; font-size:0.95rem; color:#15305D;"><i class="fas fa-code"></i> Widget externo (Trustindex)</h3>
                <div class="grid-2">
                    <div class="grid-full">
                        <label>Código del widget (script + tag). Si se acaba la suscripción, desactívalo aquí.</label>
                        <textarea name="widget_code" class="form-control" style="min-height:80px;font-family:monospace;font-size:0.8rem;"><?= htmlspecialchars($jd['widget_code'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="widget_activo" <?= ($jd['widget_activo'] ?? 0) ? 'checked' : '' ?>>
                            <strong>Widget activo</strong>
                        </label>
                    </div>
                </div>
                <hr>
                <h3 style="margin-bottom:10px; font-size:0.95rem; color:#15305D;"><i class="fas fa-sliders-h"></i> Visualización y sincronización</h3>
                <div class="grid-2">
                    <div>
                        <label>Máximo de reseñas por plataforma</label>
                        <input type="number" name="max_por_plataforma" min="1" max="6" value="<?= (int)($jd['max_por_plataforma'] ?? 3) ?>" class="form-control">
                    </div>
                    <div>
                        <label>Líneas de texto visibles por tarjeta</label>
                        <input type="number" name="lineas_texto" min="1" max="8" value="<?= (int)($jd['lineas_texto'] ?? 3) ?>" class="form-control">
                    </div>
                    <div>
                        <label>Sincronización automática cada (horas)</label>
                        <input type="number" name="sync_intervalo_horas" min="1" max="72" value="<?= (int)($jd['sync_intervalo_horas'] ?? 6) ?>" class="form-control">
                    </div>
                    <div class="grid-full">
                        <label>Paleta de colores de avatar (hex separados por coma)</label>
                        <input type="text" name="paleta_colores" value="<?= htmlspecialchars(implode(', ', $jd['paleta_colores'] ?? ['#0f9b9e','#15305D','#E8AC18','#27ae60','#0ea5e9','#7c3aed','#16a34a','#dc2626','#ea580c'])) ?>" class="form-control" placeholder="#0f9b9e, #15305D, ...">
                        <small style="color:#888;">Los colores se asignan automáticamente por nombre del autor. Si dejas vacío, usa la paleta por defecto.</small>
                    </div>
                </div>
                <button type="submit" class="btn-admin" style="margin-top:15px;"><i class="fas fa-save"></i> Guardar Configuración</button>
            </form>
        </div>

        <!-- AGREGAR / EDITAR RESEÑA -->
        <div class="card">
            <h3><i class="fas fa-star-half-alt"></i> <?= $edit_resena['id'] ? 'Editar Reseña' : 'Agregar Nueva Reseña' ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit_resena['id'] ?>">
                <input type="hidden" name="guardar_resena" value="1">
                <div class="grid-2">
                    <div>
                        <label>Plataforma</label>
                        <select name="plataforma" class="form-control">
                            <?php foreach (['tripadvisor' => 'TripAdvisor', 'google' => 'Google', 'trustpilot' => 'Trustpilot'] as $pv => $pl): ?>
                                <option value="<?= $pv ?>" <?= $edit_resena['plataforma'] == $pv ? 'selected' : '' ?>><?= $pl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Orden</label>
                        <input type="number" name="orden" value="<?= $edit_resena['orden'] ?>" class="form-control">
                    </div>
                    <div>
                        <label>Autor</label>
                        <input type="text" name="autor" value="<?= htmlspecialchars($edit_resena['autor']) ?>" class="form-control" placeholder="Nombre del cliente">
                    </div>
                    <div>
                        <label>Fecha (texto libre)</label>
                        <input type="text" name="fecha" value="<?= htmlspecialchars($edit_resena['fecha']) ?>" class="form-control" placeholder="15 de enero de 2026">
                    </div>
                    <div class="grid-full">
                        <label>Título de la reseña</label>
                        <input type="text" name="titulo" value="<?= htmlspecialchars($edit_resena['titulo']) ?>" class="form-control">
                    </div>
                    <div class="grid-full">
                        <label>Texto de la opinión</label>
                        <textarea name="texto" class="form-control"><?= htmlspecialchars($edit_resena['texto']) ?></textarea>
                    </div>
                    <div class="grid-full">
                        <label>Enlace "Ver la opinión completa"</label>
                        <input type="text" name="link" value="<?= htmlspecialchars($edit_resena['link']) ?>" class="form-control" placeholder="https://...">
                    </div>
                    <div>
                        <label>Color del avatar</label>
                        <input type="color" name="color_avatar" value="<?= htmlspecialchars($edit_resena['color_avatar']) ?>" class="form-control" style="height:42px;padding:4px;">
                    </div>
                    <div>
                        <label style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                            <input type="checkbox" name="activo" <?= (int)$edit_resena['activo'] === 1 ? 'checked' : '' ?>>
                            <strong>Visible en el index</strong>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-admin"><i class="fas fa-save"></i> <?= $edit_resena['id'] ? 'Actualizar' : 'Guardar' ?> Reseña</button>
                <?php if ($edit_resena['id']): ?>
                    <a href="admin_index.php" class="btn-secondary" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- LISTA DE RESEÑAS -->
        <div class="card">
            <h3>Reseñas actuales (<?= count($resenas_list) ?>)</h3>
            <table>
                <thead><tr><th>Plataforma</th><th>Autor</th><th>Fecha</th><th>Situación</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($resenas_list)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#999;padding:20px;">Sin reseñas aún.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($resenas_list as $r): ?>
                    <tr>
                        <td style="text-transform:capitalize;"><?= $r['plataforma'] == 'tripadvisor' ? 'TripAdvisor' : ($r['plataforma'] == 'google' ? 'Google' : 'Trustpilot') ?></td>
                        <td><?= htmlspecialchars($r['autor']) ?></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                        <td>
                            <?php if ((int)$r['activo'] === 1): ?>
                                <span class="badge">Visible</span>
                            <?php else: ?>
                                <span class="badge" style="background:#95a5a6;">Oculta</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?edit_resena=<?= $r['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                            <button onclick="confDelResena(<?= $r['id'] ?>)" class="btn-del" style="margin-left:5px;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function confDelResena(id) {
    Swal.fire({
        title: '¿Eliminar esta reseña?',
        text: 'Desaparecerá de la sección de reseñas del index.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        confirmButtonText: 'Sí, eliminar'
    }).then((r) => {
        if (r.isConfirmed) window.location.href = '?del_resena=' + id;
    });
}

const res = new URLSearchParams(window.location.search).get('res');
if (res === 'ok' || res === 'deleted') {
    Swal.fire({ icon: 'success', title: res==='ok' ? '¡Guardado!' : '¡Eliminado!', confirmButtonColor: '#15305D' }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
}
</script>
</body>
</html>
