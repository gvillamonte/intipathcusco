<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('barra_movil');

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

// --- GUARDAR / ACTUALIZAR ---
if (isset($_POST['guardar'])) {
    $id = $_POST['id'] ?? '';
    $elemento = $_POST['elemento'];
    $nombre_es = $_POST['nombre_es'];
    $nombre_en = $_POST['nombre_en'];
    $icono = $_POST['icono'];
    $color_fondo = $_POST['color_fondo'];
    $color_texto = $_POST['color_texto'];
    $enlace = $_POST['enlace_final'] ?: $_POST['enlace'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $orden = (int)$_POST['orden'];

    if (!empty($id)) {
        $stmt = $db->prepare("UPDATE barra_responsive SET elemento=?, nombre_es=?, nombre_en=?, icono=?, color_fondo=?, color_texto=?, enlace=?, activo=?, orden=? WHERE id=?");
        $stmt->execute([$elemento, $nombre_es, $nombre_en, $icono, $color_fondo, $color_texto, $enlace, $activo, $orden, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO barra_responsive (elemento, nombre_es, nombre_en, icono, color_fondo, color_texto, enlace, activo, orden) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$elemento, $nombre_es, $nombre_en, $icono, $color_fondo, $color_texto, $enlace, $activo, $orden]);
    }
    header("Location: admin_barra_responsive.php?res=ok");
    exit;
}

// --- ELIMINAR ---
if (isset($_GET['del'])) {
    $db->prepare("DELETE FROM barra_responsive WHERE id = ?")->execute([$_GET['del']]);
    header("Location: admin_barra_responsive.php?res=deleted");
    exit;
}

// --- OBTENER DATOS ---
$elementos = $db->query("SELECT * FROM barra_responsive ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
$edit = ['id'=>'','elemento'=>'','nombre_es'=>'','nombre_en'=>'','icono'=>'','color_fondo'=>'#0f9b9e','color_texto'=>'#ffffff','enlace'=>'','activo'=>1,'orden'=>0];
if (isset($_GET['edit'])) {
    foreach ($elementos as $e) {
        if ($e['id'] == $_GET['edit']) $edit = $e;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Barra Móvil | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title-inti { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .card-admin { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #ddd; }
        .card-admin h3 { border-left: 5px solid var(--admin-blue); padding-left: 15px; color: var(--admin-blue); margin-bottom: 20px; text-transform: uppercase; font-size: 1rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; margin-bottom: 10px; }
        .form-control-color { width: 60px; height: 40px; padding: 2px; border: 1px solid #ccc; border-radius: 6px; cursor: pointer; }
        .btn-admin { background: var(--admin-blue); color: #fff; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-admin:hover { background: #0e2245; }
        .btn-editar { background: var(--admin-accent); color: #000; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; display: inline-block; }
        .btn-eliminar { background: #e74c3c; color: #fff; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: var(--admin-blue); color: #fff; font-size: 0.85rem; text-transform: uppercase; }
        tr:hover { background: #f5f5f5; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
        .badge-activo { background: #27ae60; color: #fff; }
        .badge-inactivo { background: #95a5a6; color: #fff; }
        .color-muestra { display: inline-block; width: 20px; height: 20px; border-radius: 4px; vertical-align: middle; border: 1px solid #ddd; }
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .icon-preview { font-size: 1.3rem; margin-left: 8px; vertical-align: middle; }
        .elemento-badge { background: #eef2ff; color: #4338ca; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .preview-bar { display:flex; align-items:center; gap:8px; background:#fff; padding:10px 12px; border-radius:10px; box-shadow:0 -2px 10px rgba(0,0,0,0.1); max-width:400px; margin:0 auto; }
        .info-box { background: #eef2ff; border-left: 4px solid var(--admin-blue); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; color: #333; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding: 30px;">
        <h1 class="admin-title-inti"><i class="fas fa-mobile-alt"></i> Barra Flotante Móvil</h1>

        <div class="info-box">
            <i class="fas fa-info-circle" style="color:var(--admin-blue);"></i>
            Esta barra aparece en la parte inferior de <strong>detalle_tour.php</strong> solo en dispositivos móviles (&le;768px). 
            Puedes agregar, quitar y personalizar cada botón (nombre, icono, colores, enlace).
        </div>

        <!-- FORMULARIO -->
        <div class="card-admin">
            <h3><?= isset($_GET['edit']) ? 'Editar Elemento' : 'Agregar Nuevo Elemento' ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                <div class="grid-form">
                    <div>
                        <label>Tipo de Elemento</label>
                        <select name="elemento" class="form-control" id="selectElemento">
                            <option value="precio" <?= ($edit['elemento']=='precio')?'selected':'' ?>>💲 Precio (muestra valor del tour)</option>
                            <option value="boton_reserva" <?= ($edit['elemento']=='boton_reserva')?'selected':'' ?>>📱 Botón Reserva (WhatsApp / Modal)</option>
                            <option value="boton_correo" <?= ($edit['elemento']=='boton_correo')?'selected':'' ?>>✉️ Botón Correo (ícono solamente)</option>
                            <option value="personalizado" <?= ($edit['elemento']=='personalizado')?'selected':'' ?>>➕ Personalizado</option>
                        </select>
                    </div>
                    <div>
                        <label>Orden</label>
                        <input type="number" name="orden" value="<?= $edit['orden'] ?: count($elementos)+1 ?>" class="form-control">
                    </div>
                    <div>
                        <label>Nombre (Español)</label>
                        <input type="text" name="nombre_es" value="<?= $edit['nombre_es'] ?>" class="form-control" placeholder="Ej: Reservar Ya">
                    </div>
                    <div>
                        <label>Nombre (Inglés)</label>
                        <input type="text" name="nombre_en" value="<?= $edit['nombre_en'] ?>" class="form-control" placeholder="Ej: Book Now">
                    </div>
                    <div>
                        <label>Icono (clase FontAwesome)</label>
                        <input type="text" name="icono" value="<?= $edit['icono'] ?>" class="form-control" id="inputIcono" placeholder="Ej: fa-bolt">
                        <small class="text-muted">Sin "fas ", solo el nombre. Vacío = sin icono.</small>
                        <div style="margin-top:4px;">
                            <span class="icon-preview" id="iconPreview"><i class="fas <?= $edit['icono'] ?: 'fa-smile' ?>"></i></span>
                        </div>
                    </div>
                    <div>
                        <label>Enlace / Acción</label>
                        <select name="enlace" class="form-control" id="selectEnlace">
                            <option value="whatsapp" <?= ($edit['enlace']=='whatsapp')?'selected':'' ?>>WhatsApp (número global del footer)</option>
                            <option value="mailto" <?= ($edit['enlace']=='mailto')?'selected':'' ?>>Correo electrónico (email global del footer)</option>
                            <option value="modal" <?= ($edit['enlace']=='modal')?'selected':'' ?>>Modal de Reserva</option>
                            <option value="#servicios" <?= ($edit['enlace']=='#servicios')?'selected':'' ?>>Ancla #servicios</option>
                            <option value="personalizado" <?= (!in_array($edit['enlace'],['whatsapp','mailto','modal','#servicios','']) && !empty($edit['enlace']))?'selected':'' ?>>URL Personalizada</option>
                        </select>
                        <input type="text" name="enlace_personalizado" id="enlacePersonalizado" class="form-control" style="margin-top:6px; display:<?= (!in_array($edit['enlace'],['whatsapp','mailto','modal','#servicios','']) && !empty($edit['enlace']))?'block':'none' ?>;" placeholder="https://..." value="<?= (!in_array($edit['enlace'],['whatsapp','mailto','modal','#servicios','']) && !empty($edit['enlace'])) ? $edit['enlace'] : '' ?>">
                        <input type="hidden" name="enlace_final" id="enlaceFinal" value="<?= $edit['enlace'] ?>">
                    </div>
                    <div>
                        <label>Activo</label>
                        <div style="padding-top:8px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="activo" value="1" <?= ($edit['activo'] || $edit['activo']===null)?'checked':'' ?> style="width:20px;height:20px;">
                                <span>Mostrar en la barra</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label>Color de Fondo</label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="color" name="color_fondo" value="<?= str_replace('transparent', '#ffffff', $edit['color_fondo']) ?>" class="form-control-color" id="inputColorFondo">
                            <code id="codigoFondo"><?= $edit['color_fondo'] ?></code>
                        </div>
                        <small class="text-muted">Usa <strong>transparent</strong> como valor especial (sin fondo).</small>
                    </div>
                    <div>
                        <label>Color de Texto</label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="color" name="color_texto" value="<?= $edit['color_texto'] ?>" class="form-control-color" id="inputColorTexto">
                            <code id="codigoTexto"><?= $edit['color_texto'] ?></code>
                        </div>
                    </div>
                </div>
                <button type="submit" name="guardar" class="btn-admin" style="margin-top:15px;">
                    <i class="fas fa-save"></i> Guardar Elemento
                </button>
                <?php if(isset($_GET['edit'])): ?>
                    <a href="admin_barra_responsive.php" class="btn-admin" style="background:#95a5a6; margin-left:10px; text-decoration:none; display:inline-block;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- LISTA DE ELEMENTOS -->
        <div class="card-admin">
            <h3>Elementos Configurados (<?= count($elementos) ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Ord</th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Icono</th>
                        <th>Colores</th>
                        <th>Enlace</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($elementos)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#999;padding:30px;">No hay elementos configurados aún.</td></tr>
                    <?php endif; ?>
                    <?php foreach($elementos as $e): ?>
                    <tr>
                        <td><strong><?= $e['orden'] ?></strong></td>
                        <td><span class="elemento-badge"><?= $e['elemento'] ?></span></td>
                        <td><?= htmlspecialchars($e['nombre_es']) ?><?= $e['nombre_en'] ? ' <small class="text-muted">/ '.htmlspecialchars($e['nombre_en']).'</small>' : '' ?></td>
                        <td style="text-align:center;">
                            <?php if ($e['icono']): ?>
                                <i class="fas <?= htmlspecialchars($e['icono']) ?>" style="font-size:1.3rem;"></i>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="color-muestra" style="background:<?= htmlspecialchars($e['color_fondo'] === 'transparent' ? '#fff' : $e['color_fondo']) ?>"></span>
                            <span class="color-muestra" style="background:<?= htmlspecialchars($e['color_texto']) ?>; border-color:#999;"></span>
                            <small class="text-muted"><?= $e['color_fondo'] ?> / <?= $e['color_texto'] ?></small>
                        </td>
                        <td><code style="font-size:0.75rem;"><?= htmlspecialchars($e['enlace']) ?></code></td>
                        <td>
                            <span class="badge <?= $e['activo']?'badge-activo':'badge-inactivo' ?>">
                                <?= $e['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <a href="?edit=<?= $e['id'] ?>" class="btn-editar"><i class="fas fa-edit"></i></a>
                            <button onclick="confDel(<?= $e['id'] ?>)" class="btn-eliminar" style="margin-left:5px;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- VISTA PREVIA -->
        <div class="card-admin">
            <h3>Vista Previa (simula móvil)</h3>
            <div style="background:#f0f0f0; border-radius:12px; padding:20px;">
                <div class="preview-bar">
                    <?php foreach ($elementos as $e):
                        if (!$e['activo']) continue;
                        $icon = $e['icono'] ? '<i class="fas '.htmlspecialchars($e['icono']).'"></i> ' : '';
                        $bg = htmlspecialchars($e['color_fondo']);
                        $tc = htmlspecialchars($e['color_texto']);
                        $nm = htmlspecialchars($e['nombre_es']);
                        $bg_style = $bg === 'transparent' ? 'background:transparent;border:1px dashed #ccc;' : "background:$bg;";
                        if ($e['elemento'] === 'precio'): ?>
                            <span style="<?=$bg_style?>color:<?=$tc?>;padding:8px 12px;border-radius:8px;font-size:0.75rem;font-weight:700;white-space:nowrap;margin-right:auto;">
                                <?=$icon?><?=$nm?> US$1,200
                            </span>
                        <?php elseif ($e['elemento'] === 'boton_reserva'): ?>
                            <span style="<?=$bg_style?>color:<?=$tc?>;padding:8px 16px;border-radius:8px;font-size:0.8rem;font-weight:700;flex:1;text-align:center;">
                                <?=$icon?><?=$nm?>
                            </span>
                        <?php elseif ($e['elemento'] === 'boton_correo'): ?>
                            <span style="<?=$bg_style?>color:<?=$tc?>;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:1.2rem;">
                                <i class="fas <?=htmlspecialchars($e['icono'])?: 'fa-envelope'?>"></i>
                            </span>
                        <?php else: ?>
                            <span style="<?=$bg_style?>color:<?=$tc?>;padding:8px 12px;border-radius:8px;font-size:0.8rem;font-weight:700;text-align:center;">
                                <?=$icon?><?=$nm?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function confDel(id) {
    Swal.fire({
        title: '¿Eliminar este elemento?',
        text: 'Desaparecerá de la barra responsive en móviles.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        confirmButtonText: 'Sí, eliminar'
    }).then((r) => {
        if (r.isConfirmed) window.location.href = '?del=' + id;
    });
}

document.getElementById('inputIcono')?.addEventListener('input', function() {
    document.getElementById('iconPreview').innerHTML = '<i class="fas ' + this.value + '"></i>';
});

document.getElementById('selectEnlace')?.addEventListener('change', function() {
    var pers = document.getElementById('enlacePersonalizado');
    var final = document.getElementById('enlaceFinal');
    if (this.value === 'personalizado') {
        pers.style.display = 'block';
        final.value = pers.value;
    } else {
        pers.style.display = 'none';
        final.value = this.value;
    }
});
document.getElementById('enlacePersonalizado')?.addEventListener('input', function() {
    document.getElementById('enlaceFinal').value = this.value;
});

document.getElementById('inputColorFondo')?.addEventListener('input', function() {
    document.getElementById('codigoFondo').textContent = this.value;
});
document.getElementById('inputColorTexto')?.addEventListener('input', function() {
    document.getElementById('codigoTexto').textContent = this.value;
});

const res = new URLSearchParams(window.location.search).get('res');
if (res === 'ok' || res === 'deleted') {
    Swal.fire({ icon: 'success', title: res==='ok' ? '¡Guardado correctamente!' : '¡Eliminado correctamente!', confirmButtonColor: '#15305D' }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
}
</script>
</body>
</html>
