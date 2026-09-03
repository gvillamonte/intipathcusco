<?php
// admin/admin_paginas.php — CMS: páginas libres (CRUD) con SEO y contenido bilingüe
// Formato de contenido (markdown-light): # título, ## subtítulo, - lista, **negrita**, _subrayado_, [img:archivo.jpg]
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('paginas');
require_once '../config/database.php';

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $titulo_en = trim($_POST['titulo_en'] ?? '');
    $contenido = $_POST['contenido'] ?? '';
    $contenido_en = $_POST['contenido_en'] ?? '';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_desc = trim($_POST['meta_description'] ?? '');
    $og_imagen = $_POST['og_imagen_actual'] ?? '';
    $activo = isset($_POST['activo']) ? 1 : 0;
    $orden = (int)($_POST['orden'] ?? 0);

    if ($slug === '') {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $titulo), '-'));
    }
    $slug = preg_replace('/-+/', '-', $slug);

    if (isset($_FILES['nueva_og_imagen']) && $_FILES['nueva_og_imagen']['error'] == 0) {
        $dir = "../assets/img/paginas/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['nueva_og_imagen']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'avif'])) {
            $nombre_img = "pag_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['nueva_og_imagen']['tmp_name'], $dir . $nombre_img)) {
                if ($og_imagen && file_exists($dir . $og_imagen)) @unlink($dir . $og_imagen);
                $og_imagen = $nombre_img;
            }
        }
    }

    try {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE paginas SET slug=?, titulo=?, titulo_en=?, contenido=?, contenido_en=?, meta_title=?, meta_description=?, og_imagen=?, activo=?, orden=? WHERE id=?");
            $stmt->execute([$slug, $titulo, $titulo_en, $contenido, $contenido_en, $meta_title, $meta_desc, $og_imagen, $activo, $orden, $id]);
            $mensaje = 'ok_update';
        } else {
            $stmt = $db->prepare("INSERT INTO paginas (slug, titulo, titulo_en, contenido, contenido_en, meta_title, meta_description, og_imagen, activo, orden) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$slug, $titulo, $titulo_en, $contenido, $contenido_en, $meta_title, $meta_desc, $og_imagen, $activo, $orden]);
            $id = (int)$db->lastInsertId();
            $mensaje = 'ok_create';
        }
    } catch (PDOException $e) {
        $mensaje = 'error';
    }
}

if (isset($_GET['eliminar'])) {
    $st = $db->prepare("SELECT og_imagen FROM paginas WHERE id=?");
    $st->execute([(int)$_GET['eliminar']]);
    $imagen = $st->fetch(PDO::FETCH_ASSOC);
    $db->prepare("DELETE FROM paginas WHERE id=?")->execute([(int)$_GET['eliminar']]);
    if ($imagen && !empty($imagen['og_imagen']) && file_exists("../assets/img/paginas/" . $imagen['og_imagen'])) {
        @unlink("../assets/img/paginas/" . $imagen['og_imagen']);
    }
    header("Location: admin_paginas.php?borrada=1");
    exit;
}

$paginas = $db->query("SELECT * FROM paginas ORDER BY orden ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$editar = null;
if (isset($_GET['editar'])) {
    $st = $db->prepare("SELECT * FROM paginas WHERE id=?");
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch(PDO::FETCH_ASSOC);
}
$modo_editar = isset($_GET['nueva']) || $editar;
$f = $editar ?? ['id' => 0, 'slug' => '', 'titulo' => '', 'titulo_en' => '', 'contenido' => '', 'contenido_en' => '', 'meta_title' => '', 'meta_description' => '', 'og_imagen' => '', 'activo' => 1, 'orden' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Páginas | Admin IntiPath</title>
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

        .pag-tabs{display:flex;gap:8px;margin-bottom:14px}
        .pag-tab{padding:8px 18px;border-radius:var(--tds-radius) var(--tds-radius) 0 0;cursor:pointer;border:1px solid var(--tds-outline-variant);background:var(--tds-surface);font-family:var(--tds-font);font-weight:600;color:var(--tds-secondary);transition:background 0.2s,color 0.2s}
        .pag-tab.activa{background:var(--tds-primary);color:var(--tds-on-primary);border-color:var(--tds-primary)}
        .pag-tab:hover:not(.activa){background:var(--tds-surface-container)}
        .pag-panel{display:none}.pag-panel.activa{display:block}
        .pag-preview{border:1px solid var(--tds-outline-variant);border-radius:var(--tds-radius);padding:20px;background:#fff;min-height:180px;font-family:var(--tds-font);font-size:14px;line-height:1.7}
        .pag-preview h2{color:var(--tds-primary);border-bottom:2px solid var(--tds-primary-container);padding-bottom:8px;margin-bottom:12px}
        .pag-preview h3{color:var(--tds-primary-container);margin:16px 0 8px}
        .pag-preview li{list-style:disc;margin-left:20px;color:var(--tds-on-surface-variant)}
        .pag-preview img{max-width:100%;border-radius:var(--tds-radius);border:1px solid var(--tds-outline-variant)}
        .pag-preview p{margin-bottom:12px;color:var(--tds-on-surface)}
        .pag-tabla{width:48px;height:36px;object-fit:cover;border-radius:6px;display:block;flex-shrink:0}
        .badge-pub{background:var(--tds-primary);color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-family:var(--tds-font);font-weight:500}
        .badge-bor{background:var(--tds-outline);color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-family:var(--tds-font);font-weight:500}

        .pag-form label{font-family:var(--tds-font);font-weight:600;font-size:13px;line-height:18px;letter-spacing:0.01em;color:var(--tds-on-surface-variant);display:block;margin:12px 0 5px}
        .pag-form input[type=text],.pag-form input[type=number],.pag-form textarea{width:100%;padding:10px 14px;font-family:var(--tds-font);font-size:14px;color:var(--tds-on-surface);background:#fff;border:1px solid var(--tds-outline-variant);border-radius:var(--tds-radius);box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s}
        .pag-form input[type=text]:focus,.pag-form input[type=number]:focus,.pag-form textarea:focus{outline:none;border-color:var(--tds-primary);box-shadow:0 0 0 3px rgba(0,104,95,0.12)}
        .pag-form textarea{font-family:monospace;line-height:1.6;min-height:120px;resize:vertical}
        .pag-form input[type=file]{font-family:var(--tds-font);font-size:13px;color:var(--tds-on-surface-variant)}
        .pag-form input[type=checkbox]{width:18px;height:18px;accent-color:var(--tds-primary)}

        .pag-form .btn-save{display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:var(--tds-primary);color:var(--tds-on-primary);font-family:var(--tds-font);font-size:14px;font-weight:600;border:none;border-radius:var(--tds-radius);cursor:pointer;transition:background 0.2s}
        .pag-form .btn-save:hover{background:#005249}
        .pag-form .btn-cancel{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:transparent;color:var(--tds-secondary);font-family:var(--tds-font);font-size:14px;font-weight:600;border:1px solid var(--tds-outline-variant);border-radius:var(--tds-radius);cursor:pointer;text-decoration:none;transition:background 0.2s,border-color 0.2s}
        .pag-form .btn-cancel:hover{background:var(--tds-surface);border-color:var(--tds-outline)}
        .pag-form .btn-view{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--tds-primary-container);color:#fff;border-radius:var(--tds-radius);text-decoration:none;font-family:var(--tds-font);font-size:14px;font-weight:600;transition:background 0.2s}
        .pag-form .btn-view:hover{background:#006d63}

        .pag-actions{display:flex;gap:10px;align-items:center;}
        .pag-actions a{color:var(--tds-primary);text-decoration:none;font-size:16px;transition:color 0.2s;padding:4px;}
        .pag-actions a:hover{color:var(--tds-primary-container)}
        .pag-actions a[title="Eliminar"]:hover{color:var(--tds-error)}

        .pag-seo-box{margin-top:18px;background:var(--tds-surface);padding:16px;border-radius:var(--tds-radius);border:1px solid var(--tds-outline-variant)}
        .pag-seo-box strong{color:var(--tds-primary);font-family:var(--tds-font);font-size:14px;display:flex;align-items:center;gap:8px}
        .pag-seo-box img{max-width:220px;border-radius:var(--tds-radius);display:block;margin:8px 0;border:1px solid var(--tds-outline-variant)}
    </style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
<h1 class="admin-title"><i class="fas fa-file-alt"></i> <?= $modo_editar ? 'Editar página' : 'Páginas del sitio' ?></h1>

<?php if (!$modo_editar): ?>
<div class="admin-contenedor">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:10px;">
        <p style="color:var(--tds-outline);margin:0;font-family:var(--tds-font);font-size:14px;">Crea páginas de contenido sin programar. Se publican en <code style="background:var(--tds-surface);border:1px solid var(--tds-outline-variant);border-radius:4px;padding:2px 6px;font-size:12px;">pagina.php?slug=…</code></p>
        <a href="admin_paginas.php?nueva=1" style="background:var(--tds-primary);color:#fff;padding:10px 20px;border-radius:var(--tds-radius);text-decoration:none;font-weight:bold;font-family:var(--tds-font);display:inline-flex;align-items:center;gap:8px;transition:background 0.2s;">
            <i class="fas fa-plus"></i> Nueva página
        </a>
    </div>
    <table style="width:100%;border-collapse:collapse;background:#fff;border-radius:var(--tds-radius);overflow:hidden;font-family:var(--tds-font);">
        <thead>
            <tr style="background:var(--tds-primary);color:#fff;text-align:left;">
                <th style="padding:12px;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Página</th>
                <th style="padding:12px;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Slug</th>
                <th style="padding:12px;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Estado</th>
                <th style="padding:12px;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Orden</th>
                <th style="padding:12px;text-align:center;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($paginas)): ?>
            <tr><td colspan="5" style="padding:25px;text-align:center;color:var(--tds-outline);font-family:var(--tds-font);">Aún no hay páginas. Crea la primera.</td></tr>
        <?php endif; ?>
        <?php foreach ($paginas as $p): ?>
            <tr style="border-bottom:1px solid var(--tds-outline-variant);">
                <td style="padding:12px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img class="pag-tabla" src="<?= (!empty($p['og_imagen']) && file_exists('../assets/img/paginas/' . $p['og_imagen'])) ? '../assets/img/paginas/' . $p['og_imagen'] : '../assets/img/logo-inti-1e06.png' ?>" alt="">
                        <strong style="color:var(--tds-on-surface);font-family:var(--tds-font);"><?= htmlspecialchars($p['titulo']) ?></strong>
                    </div>
                </td>
                <td style="padding:12px;color:var(--tds-outline);font-family:var(--tds-font);font-size:13px;"><code style="background:var(--tds-surface);border:1px solid var(--tds-outline-variant);border-radius:4px;padding:2px 6px;">pagina.php?slug=<?= htmlspecialchars($p['slug']) ?></code></td>
                <td style="padding:12px;"><?= $p['activo'] ? '<span class="badge-pub">Publicada</span>' : '<span class="badge-bor">Borrador</span>' ?></td>
                <td style="padding:12px;color:var(--tds-outline);font-family:var(--tds-font);"><?= (int)$p['orden'] ?></td>
                <td style="padding:12px;text-align:center;white-space:nowrap;">
                    <div class="pag-actions">
                        <a href="admin_paginas.php?editar=<?= $p['id'] ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="pagina.php?slug=<?= htmlspecialchars($p['slug']) ?>" target="_blank" title="Ver página"><i class="fas fa-eye"></i></a>
                        <a href="javascript:void(0)" onclick="confirmarEliminar(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['titulo'])) ?>')" title="Eliminar"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<div class="admin-contenedor pag-form">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:280px;">
                <label>Título (Español) *</label>
                <input type="text" name="titulo" required value="<?= htmlspecialchars($f['titulo']) ?>" oninput="slugAuto(this.value)">
            </div>
            <div style="flex:1;min-width:280px;">
                <label>Título (English)</label>
                <input type="text" name="titulo_en" value="<?= htmlspecialchars($f['titulo_en']) ?>">
            </div>
        </div>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:280px;">
                <label>Slug (URL) <small style="color:#94a3b8;font-weight:400;">→ pagina.php?slug=…</small></label>
                <input type="text" name="slug" id="campo_slug" value="<?= htmlspecialchars($f['slug']) ?>" placeholder="se-genera-solo">
            </div>
            <div style="flex:1;min-width:280px;">
                <label>Orden (menor = primero)</label>
                <input type="number" name="orden" value="<?= (int)$f['orden'] ?>">
            </div>
        </div>

        <div style="margin-top:18px;">
            <div class="pag-tabs">
                <div class="pag-tab activa" onclick="cambiarTab('es',this)">Español</div>
                <div class="pag-tab" onclick="cambiarTab('en',this)">English</div>
            </div>
            <div id="panel-es" class="pag-panel activa">
                <textarea name="contenido" id="contenido_es" rows="18" placeholder="# Título&#10;## Subtítulo&#10;&#10;Párrafo de texto…&#10;&#10;- Elemento de lista&#10;&#10;**negrita** y _subrayado_&#10;&#10;[img:foto.jpg] para imágenes"><?= htmlspecialchars($f['contenido']) ?></textarea>
            </div>
            <div id="panel-en" class="pag-panel">
                <textarea name="contenido_en" rows="18" placeholder="English content…"><?= htmlspecialchars($f['contenido_en']) ?></textarea>
            </div>
            <div style="margin-top:10px;">
                <label>Vista previa</label>
                <div id="vista_previa" class="pag-preview"></div>
            </div>
        </div>

        <div class="pag-seo-box">
            <strong><i class="fas fa-search"></i> SEO (Google / WhatsApp)</strong>
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;">
                <div style="flex:1;min-width:280px;">
                    <label>Meta título <small>(máx. 60-65 caracteres)</small></label>
                    <input type="text" name="meta_title" maxlength="200" value="<?= htmlspecialchars($f['meta_title']) ?>" placeholder="<?= htmlspecialchars($f['titulo']) ?> | IntiPath Tours">
                </div>
                <div style="flex:1;min-width:280px;">
                    <label>Meta descripción <small>(máx. 160)</small></label>
                    <input type="text" name="meta_description" maxlength="300" value="<?= htmlspecialchars($f['meta_description']) ?>">
                </div>
            </div>
            <label>Imagen para compartir (OG)</label>
            <?php if (!empty($f['og_imagen'])): ?>
                <img src="../assets/img/paginas/<?= $f['og_imagen'] ?>" style="max-width:220px;border-radius:8px;display:block;margin:8px 0;">
            <?php endif; ?>
            <input type="hidden" name="og_imagen_actual" value="<?= htmlspecialchars($f['og_imagen']) ?>">
            <input type="file" name="nueva_og_imagen" accept="image/*" style="font-size:14px;">
        </div>

        <div style="margin:18px 0;">
            <label style="display:flex;align-items:center;gap:8px;font-family:var(--tds-font);font-weight:600;font-size:14px;color:var(--tds-on-surface-variant);">
                <input type="checkbox" name="activo" <?= $f['activo'] ? 'checked' : '' ?>> Publicada (visible en el sitio)
            </label>
        </div>

        <div class="pag-actions" style="padding-top:16px;border-top:1px solid var(--tds-outline-variant);margin-top:24px;">
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Guardar página</button>
            <a href="admin_paginas.php" class="btn-cancel">Cancelar</a>
            <?php if ($editar && $editar['activo']): ?>
                <a href="pagina.php?slug=<?= htmlspecialchars($editar['slug']) ?>" target="_blank" class="btn-view">
                    <i class="fas fa-eye"></i> Ver página
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>
</main>
</div>

<script>
function cambiarTab(idioma, el) {
    document.querySelectorAll('.pag-tab').forEach(t => t.classList.remove('activa'));
    el.classList.add('activa');
    document.getElementById('panel-es').classList.toggle('activa', idioma === 'es');
    document.getElementById('panel-en').classList.toggle('activa', idioma === 'en');
    actualizarPreview();
}
function slugAuto(valor) {
    const slug = document.getElementById('campo_slug');
    if (slug.dataset.manual) return;
    slug.value = valor.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
}
document.getElementById('campo_slug').addEventListener('input', function(){ this.dataset.manual = '1'; });

function renderMarkdown(txt) {
    txt = txt.replace(/^## (.*)$/gm, '<h3>$1</h3>');
    txt = txt.replace(/^# (.*)$/gm, '<h2>$1</h2>');
    txt = txt.replace(/^\s*-\s+(.*)$/gm, '<li>$1</li>');
    txt = txt.replace(/\[img:([^\]]+)\]/g, '<img src="assets/img/paginas/$1" alt="" loading="lazy">');
    txt = txt.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    txt = txt.replace(/_(.*?)_/g, '<u>$1</u>');
    const parrafos = txt.split(/\n{2,}/).map(bloque => {
        if (/^<h2|<h3|<li|^<img/.test(bloque.trim())) return bloque.trim();
        return '<p>' + bloque.replace(/\n/g, '<br>') + '</p>';
    }).join('\n');
    if (/\<li\>/.test(parrafos)) {
        const li = parrafos.match(/(?:<li>.*<\/li>\n?)+/g);
        if (li) li.forEach(b => { parrafos = parrafos.replace(b, '<ul>' + b + '</ul>'); });
    }
    return parrafos;
}
function actualizarPreview() {
    const es = document.getElementById('panel-es').classList.contains('activa');
    const txt = es ? document.getElementById('contenido_es').value : (document.querySelector('#panel-en textarea') || {}).value || '';
    document.getElementById('vista_previa').innerHTML = renderMarkdown(txt) || '<span style="color:#94a3b8;">Escribe contenido para ver la vista previa…</span>';
}
document.getElementById('contenido_es').addEventListener('input', actualizarPreview);
document.querySelector('#panel-en textarea').addEventListener('input', actualizarPreview);
actualizarPreview();

function confirmarEliminar(id, titulo) {
    Swal.fire({
        title: '¿Eliminar página?',
        text: '"' + titulo + '" se eliminará permanentemente.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonText: 'Cancelar', confirmButtonText: 'Sí, eliminar'
    }).then(r => { if (r.isConfirmed) window.location = 'admin_paginas.php?eliminar=' + id; });
}
<?php if ($mensaje === 'ok_create'): ?>Swal.fire('¡Listo!', 'Página creada correctamente.', 'success');
<?php elseif ($mensaje === 'ok_update'): ?>Swal.fire('¡Listo!', 'Página actualizada correctamente.', 'success');
<?php elseif ($mensaje === 'error'): ?>Swal.fire('Error', 'No se pudo guardar. Revisa que el slug no esté repetido.', 'error');
<?php elseif (isset($_GET['borrada'])): ?>Swal.fire('Eliminada', 'La página fue eliminada.', 'info');
<?php endif; ?>
</script>
</body>
</html>

