<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('colores');

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Guardar colores
if (isset($_POST['guardar_colores'])) {
    $stmt = $db->prepare("UPDATE colores SET valor_actual = ? WHERE variable = ?");
    foreach ($_POST['color'] as $var => $val) {
        $stmt->execute([$val, $var]);
    }
    header("Location: colores.php?res=ok");
    exit;
}

// Resetear a valores por defecto
if (isset($_GET['reset'])) {
    $db->exec("UPDATE colores SET valor_actual = valor_defecto");
    header("Location: colores.php?res=reset");
    exit;
}

$grupos = [];
$agrupados = [];
try {
    $grupos = $db->query("SELECT * FROM colores ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $grupos = [];
}

foreach ($grupos as $c) {
    $agrupados[$c['grupo']][] = $c;
}

$iconos_grupo = [
    'marca' => 'fa-palette',
    'textos' => 'fa-font',
    'fondos' => 'fa-fill-drip',
    'botones' => 'fa-square-check',
    'estados' => 'fa-circle-check',
    'bordes' => 'fa-border-all',
    'admin' => 'fa-user-shield',
];

$nombres_grupo = [
    'marca' => 'Colores de Marca',
    'textos' => 'Colores de Texto',
    'fondos' => 'Colores de Fondo',
    'botones' => 'Colores de Botones',
    'estados' => 'Colores de Estado',
    'bordes' => 'Colores de Bordes',
    'admin' => 'Colores del Panel Admin',
];

// Calcular variables para preview
$preview = [];
foreach ($grupos as $c) {
    $preview[$c['variable']] = $c['valor_actual'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Colores | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .admin-title { color: var(--admin-blue); font-weight: 800; border-bottom: 4px solid var(--admin-accent); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; text-transform: uppercase; font-size: 1.4rem; }
        .admin-title i { margin-right: 10px; }

        /* Buscador */
        .search-bar { margin-bottom: 25px; }
        .search-bar input { width: 100%; max-width: 450px; padding: 12px 18px 12px 45px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 0.95rem; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23888'%3E%3Cpath d='M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E") 12px center no-repeat; background-size: 22px; outline: none; transition: 0.3s; }
        .search-bar input:focus { border-color: var(--admin-blue); box-shadow: 0 0 0 3px rgba(21,48,93,0.1); }

        .grupo-card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #e0e0e0; overflow: hidden; }
        .grupo-header { background: var(--admin-blue); color: #fff; padding: 15px 20px; font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .grupo-body { padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .color-item { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px; padding: 15px; transition: 0.3s; position: relative; }
        .color-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .color-item label { display: block; font-weight: 700; color: var(--admin-blue); font-size: 0.85rem; margin-bottom: 3px; }
        .color-item small { display: block; color: #888; font-size: 0.75rem; margin-bottom: 10px; }
        .color-row { display: flex; align-items: center; gap: 12px; }
        .color-row input[type="color"] { width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; padding: 2px; background: none; }
        .color-row input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-family: monospace; font-size: 0.9rem; text-transform: uppercase; transition: 0.3s; }
        .color-row input[type="text"]:focus { border-color: var(--admin-blue); box-shadow: 0 0 0 3px rgba(21,48,93,0.1); outline: none; }
        .color-preview { width: 30px; height: 30px; border-radius: 6px; border: 2px solid #fff; box-shadow: 0 0 0 1px #ddd; flex-shrink: 0; }
        .color-item .btn-copy { background: none; border: 1px solid #ddd; border-radius: 6px; padding: 6px 10px; cursor: pointer; color: #888; font-size: 0.85rem; transition: 0.2s; }
        .color-item .btn-copy:hover { background: #e9ecef; color: var(--admin-blue); }

        /* Indicador de personalizado */
        .custom-badge { display: none; position: absolute; top: 10px; right: 10px; background: var(--admin-accent); color: #fff; font-size: 0.65rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .color-item.customized .custom-badge { display: block; }

        /* Animación de transición en preview */
        .preview-box { background: #fff; border: 2px dashed #ccc; border-radius: 12px; padding: 20px; margin-bottom: 30px; transition: 0.3s; }
        .preview-box h3 { margin: 0 0 10px 0; font-size: 0.9rem; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .preview-box h3 i { margin-right: 6px; }
        .preview-ejemplo { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .preview-ejemplo .btn-preview { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 700; cursor: default; }
        .preview-ejemplo .card-preview { padding: 15px; border-radius: 10px; border: 1px solid; min-width: 150px; }
        .preview-ejemplo .badge-preview { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }

        .acciones-top { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .btn-admin { background: var(--admin-blue); color: #fff; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.95rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-admin:hover { background: #0e2245; }
        .btn-reset { background: #e74c3c; color: #fff; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.95rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-reset:hover { background: #c0392b; }

        .grupo-card.grupo-hidden { display: none; }
        @media (max-width: 768px) {
            .grupo-body { grid-template-columns: 1fr; }
            .color-row { flex-wrap: wrap; }
            .color-row input[type="text"] { min-width: 120px; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding: 30px;">
        <h1 class="admin-title"><i class="fas fa-palette"></i> Colores del Sitio Web</h1>

        <!-- Buscador -->
        <div class="search-bar">
            <input type="text" id="buscarColor" placeholder="Buscar color por nombre o variable..." autocomplete="off">
        </div>

        <!-- Preview en vivo -->
        <div class="preview-box">
            <h3><i class="fas fa-eye"></i> Vista previa en vivo</h3>
            <div class="preview-ejemplo" id="livePreview">
                <button class="btn-preview" style="background:<?= $preview['--ip-btn-primary'] ?? '#0f9b9e' ?>; color:<?= $preview['--ip-text-light'] ?? '#fff' ?>">Botón Principal</button>
                <button class="btn-preview" style="background:<?= $preview['--ip-btn-accent'] ?? '#c6d544' ?>; color:#333">Botón Secundario</button>
                <button class="btn-preview" style="background:<?= $preview['--ip-btn-whatsapp'] ?? '#25d366' ?>; color:#fff"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                <span class="badge-preview" style="background:<?= $preview['--ip-success'] ?? '#27ae60' ?>; color:#fff"><i class="fas fa-check"></i> Aprobado</span>
                <span class="badge-preview" style="background:<?= $preview['--ip-danger'] ?? '#e74c3c' ?>; color:#fff"><i class="fas fa-times"></i> Rechazado</span>
                <span style="color:<?= $preview['--ip-star'] ?? '#ffbc00' ?>; font-size:1.2rem"><i class="fas fa-star"></i> 4.8</span>
                <div class="card-preview" style="background:<?= $preview['--ip-bg-section'] ?? '#f8f9fa' ?>; border-color:<?= $preview['--ip-border'] ?? '#eee' ?>; color:<?= $preview['--ip-text'] ?? '#333' ?>">Texto sobre fondo de sección</div>
                <div class="card-preview" style="background:<?= $preview['--ip-bg-dark'] ?? '#0d1a33' ?>; color:<?= $preview['--ip-text-light'] ?? '#ECF0F1' ?>"><i class="fas fa-moon"></i> Fondo oscuro</div>
            </div>
        </div>

        <form method="POST" id="formColores">
            <?php foreach ($agrupados as $grupo => $items): ?>
            <div class="grupo-card" data-grupo="<?= $grupo ?>">
                <div class="grupo-header">
                    <i class="fas <?= $iconos_grupo[$grupo] ?? 'fa-palette' ?>"></i>
                    <?= $nombres_grupo[$grupo] ?? ucfirst($grupo) ?>
                </div>
                <div class="grupo-body">
                    <?php foreach ($items as $c):
                        $customized = $c['valor_actual'] !== $c['valor_defecto'];
                    ?>
                    <div class="color-item<?= $customized ? ' customized' : '' ?>" data-variable="<?= $c['variable'] ?>" data-nombre="<?= htmlspecialchars(strtolower($c['nombre'])) ?>">
                        <span class="custom-badge"><i class="fas fa-pen"></i> Modificado</span>
                        <label><?= htmlspecialchars($c['nombre']) ?></label>
                        <small><?= htmlspecialchars($c['descripcion']) ?> · <code style="background:#e9ecef;padding:1px 5px;border-radius:3px;font-size:0.7rem"><?= htmlspecialchars($c['variable']) ?></code></small>
                        <div class="color-row">
                            <input type="color" name="color[<?= $c['variable'] ?>]" value="<?= htmlspecialchars($c['valor_actual']) ?>" class="color-picker">
                            <input type="text" class="color-hex" value="<?= htmlspecialchars($c['valor_actual']) ?>">
                            <div class="color-preview" style="background:<?= htmlspecialchars($c['valor_actual']) ?>"></div>
                            <button type="button" class="btn-copy" title="Copiar hex"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="acciones-top">
                <button type="submit" name="guardar_colores" class="btn-admin" onclick="return confirm('¿Guardar todos los colores?')">
                    <i class="fas fa-save"></i> Guardar Colores
                </button>
                <a href="?reset=1" class="btn-reset" onclick="return confirm('¿Restablecer TODOS los colores a sus valores por defecto? Esta acción no se puede deshacer.')">
                    <i class="fas fa-undo"></i> Restablecer valores por defecto
                </a>
            </div>
        </form>
    </main>
</div>

<script>
// --- 1. BÚSQUEDA EN VIVO ---
document.getElementById('buscarColor').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.color-item').forEach(function(item) {
        var variable = item.dataset.variable.toLowerCase();
        var nombre = item.dataset.nombre;
        var match = !q || variable.includes(q) || nombre.includes(q);
        item.style.display = match ? '' : 'none';
    });
    // Ocultar grupos vacíos
    document.querySelectorAll('.grupo-card').forEach(function(grupo) {
        var visibles = grupo.querySelectorAll('.color-item[style*="display: none"]');
        var total = grupo.querySelectorAll('.color-item').length;
        grupo.style.display = (visibles.length === total && total > 0 && q) ? 'none' : '';
    });
});

// --- 2. SINCRONIZACIÓN BIDIRECCIONAL COLOR PICKER ↔ HEX ---
document.querySelectorAll('.color-item').forEach(function(item) {
    var picker = item.querySelector('.color-picker');
    var hexInput = item.querySelector('.color-hex');
    var preview = item.querySelector('.color-preview');
    var badge = item.querySelector('.custom-badge');
    var defaultValue = picker.value;

    // Color picker → hex + preview
    picker.addEventListener('input', function() {
        var val = this.value.toUpperCase();
        hexInput.value = val;
        preview.style.background = val;
        updateLivePreview(val, item.dataset.variable);
        checkCustomized(item, val, defaultValue);
    });

    // Hex editable → color picker + preview
    hexInput.addEventListener('input', function() {
        var val = this.value.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(val) || /^#[0-9a-fA-F]{3}$/.test(val)) {
            picker.value = val;
            preview.style.background = val;
            updateLivePreview(val, item.dataset.variable);
            checkCustomized(item, val, defaultValue);
        }
    });

    // Copiar hex
    item.querySelector('.btn-copy').addEventListener('click', function() {
        var hex = hexInput.value;
        navigator.clipboard.writeText(hex).then(function() {
            var icon = item.querySelector('.btn-copy i');
            icon.className = 'fas fa-check';
            setTimeout(function() { icon.className = 'fas fa-copy'; }, 1500);
        });
    });
});

// --- 3. INDICADOR DE PERSONALIZADO ---
function checkCustomized(item, currentVal, defaultVal) {
    if (currentVal.toUpperCase() !== defaultVal.toUpperCase()) {
        item.classList.add('customized');
    } else {
        item.classList.remove('customized');
    }
}

// --- 4. ACTUALIZAR PREVIEW EN VIVO ---
function updateLivePreview(val, variable) {
    var previewBox = document.getElementById('livePreview');
    if (!previewBox) return;

    // Mapear cada variable a los elementos de preview
    var map = {
        '--ip-primary': { selector: null },   // no se usa directamente en preview
        '--ip-accent': { selector: null },
        '--ip-text': { selector: '.card-preview:nth-child(7)', prop: 'color' },
        '--ip-text-light': { selector: '.card-preview:nth-child(8), .btn-preview:nth-child(1)', prop: 'color' },
        '--ip-bg': { selector: null },
        '--ip-bg-section': { selector: '.card-preview:nth-child(7)', prop: 'backgroundColor' },
        '--ip-bg-dark': { selector: '.card-preview:nth-child(8)', prop: 'backgroundColor' },
        '--ip-btn-primary': { selector: '.btn-preview:nth-child(1)', prop: 'backgroundColor' },
        '--ip-btn-accent': { selector: '.btn-preview:nth-child(2)', prop: 'backgroundColor' },
        '--ip-btn-whatsapp': { selector: '.btn-preview:nth-child(3)', prop: 'backgroundColor' },
        '--ip-success': { selector: '.badge-preview:nth-child(4)', prop: 'backgroundColor' },
        '--ip-danger': { selector: '.badge-preview:nth-child(5)', prop: 'backgroundColor' },
        '--ip-star': { selector: 'span[style*="font-size:1.2rem"]', prop: 'color' },
        '--ip-border': { selector: '.card-preview:nth-child(7)', prop: 'borderColor' },
    };

    if (map[variable]) {
        var m = map[variable];
        if (m.selector) {
            var el = previewBox.querySelector(m.selector);
            if (el) {
                if (m.prop === 'backgroundColor') el.style.backgroundColor = val;
                else if (m.prop === 'color') el.style.color = val;
                else if (m.prop === 'borderColor') el.style.borderColor = val;
            }
        }
    }
    // También actualizar el color de texto de botones que usan --ip-text-light
    if (variable === '--ip-text-light') {
        previewBox.querySelectorAll('.btn-preview').forEach(function(btn) {
            if (btn.style.backgroundColor === '' || btn.style.backgroundColor === 'transparent') return;
            btn.style.color = val;
        });
    }
}

// --- 5. SWEETALERT2 CONFIRMACIONES ---
var res = new URLSearchParams(window.location.search).get('res');
if (res === 'ok') {
    Swal.fire({ icon: 'success', title: '¡Colores guardados!', text: 'Los cambios se ven reflejados en todo el sitio web.', confirmButtonColor: '#15305D' }).then(function() {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
} else if (res === 'reset') {
    Swal.fire({ icon: 'info', title: 'Valores restablecidos', text: 'Todos los colores volvieron a sus valores por defecto.', confirmButtonColor: '#15305D' }).then(function() {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
}
</script>
</body>
</html>
