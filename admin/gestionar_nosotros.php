<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('nosotros');

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($db)) {
    die("Error crítico: No se pudo establecer la conexión con la base de datos.");
}

// --- A. LÓGICA AJAX: ELIMINAR ENLACE ---
if (isset($_POST['eliminar_enlace'])) {
    $id = intval($_POST['id']);
    $stmt = $db->prepare("DELETE FROM menu_nosotros WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo "OK";
    }
    exit; // Importante detener la ejecución aquí
}

// --- B. LÓGICA AJAX: ACTUALIZAR NOMBRES Y ARCHIVO ---
if (isset($_POST['update_names'])) {
    $id = $_POST['id'];
    $nom_es = trim($_POST['nombre_es']);
    $nom_en = trim($_POST['nombre_en']);
    $archivo = trim($_POST['archivo']);

    if (!empty($archivo) && strpos($archivo, '.php') === false) {
        $archivo .= ".php";
    }

    $stmt = $db->prepare("UPDATE menu_nosotros SET nombre_enlace = ?, nombre_enlace_en = ?, url_enlace = ? WHERE id = ?");
    if ($stmt->execute([$nom_es, $nom_en, $archivo, $id])) {
        echo "OK";
    }
    exit;
}

// --- C. LÓGICA: CREAR NUEVA PÁGINA (CON PROTECCIÓN F5) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_enlace'])) {
    $nom_es   = trim($_POST['nombre_es']);
    $nom_en   = trim($_POST['nombre_en']);
    $columna  = intval($_POST['columna_nro']);
    $archivo_input = trim($_POST['archivo_manual']);

    if (!empty($nom_es)) {
        if (empty($archivo_input)) {
            $archivo = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom_es))) . ".php";
        } else {
            $archivo = (strpos($archivo_input, '.php') !== false) ? $archivo_input : $archivo_input . ".php";
        }

        $stmt = $db->prepare("INSERT INTO menu_nosotros (titulo_columna, nombre_enlace, nombre_enlace_en, url_enlace, es_footer_link, columna_nro) VALUES (?, ?, ?, ?, 1, ?)");

        $titulos_default = [
            1 => 'NUESTRA EMPRESA',
            2 => '¿POR QUÉ VIAJAR CON NOSOTROS?',
            3 => 'INFORMACIÓN SOBRE VIAJES',
            4 => 'INFORMACIÓN SOBRE RESERVAS'
        ];
        $titulo_col = $titulos_default[$columna] ?? 'NUESTRA EMPRESA';

        if ($stmt->execute([$titulo_col, $nom_es, $nom_en, $archivo, $columna])) {
            $ruta_archivo = __DIR__ . "/../" . $archivo;
            if (!file_exists($ruta_archivo)) {
                // ESTA ES LA NUEVA PLANTILLA DINÁMICA
                $plantilla = "<?php 
// Archivo generado automáticamente - " . date('Y-m-d H:i:s') . "
require_once 'config/database.php';
require_once 'config/lang.php';
include('includes/header.php'); 

// 1. Conexión y obtención de datos
\$db = (new Database())->getConnection();
\$current_file = basename(__FILE__);
\$stmt = \$db->prepare('SELECT * FROM menu_nosotros WHERE url_enlace = ?');
\$stmt->execute([\$current_file]);
\$pagina = \$stmt->fetch(PDO::FETCH_ASSOC);

// 2. Definición de textos según idioma
\$titulo_render = (\$idioma == 'en') ? (\$pagina['nombre_enlace_en'] ?? '') : (\$pagina['nombre_enlace'] ?? '');
\$contenido_render = (\$idioma == 'en') ? (\$pagina['contenido_en'] ?? 'Content under construction.') : (\$pagina['contenido_es'] ?? 'Contenido en construcción.');
?>

<main class='ip-page-dynamic'>
    <!-- Banner de Título -->
    <section class='ip-banner-clean' style='background: #15305D; padding: 60px 0; color: white;'>
        <div class='container' style='max-width: 1200px; margin: 0 auto; padding: 0 20px;'>
            <h1 style='margin: 0; font-size: 2.5rem;'><?php echo htmlspecialchars(\$titulo_render); ?></h1>
        </div>
    </section>

    <!-- Cuerpo de la Página -->
    <section class='ip-content-body' style='padding: 50px 0;'>
        <div class='container' style='max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; gap: 40px;'>
            
            <!-- Contenido Principal -->
            <div class='ip-main-text' style='flex: 3;'>
                <?php echo \$contenido_render; // Renderiza el HTML del editor ?>
            </div>

            <!-- Sidebar Lateral Derecha (Opcional) -->
            <aside class='ip-sidebar-info' style='flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; height: fit-content;'>
                <h4 style='color: #15305D; border-bottom: 2px solid #E8AC18; padding-bottom: 10px;'>IntiPath Tours</h4>
                <p style='font-size: 0.9rem;'>Descubre Cusco con los expertos. Tours personalizados y caminatas inolvidables.</p>
                <a href='contacto.php' style='display: block; background: #E8AC18; color: white; text-align: center; padding: 10px; text-decoration: none; border-radius: 5px; font-weight: bold;'>CONTÁCTANOS</a>
            </aside>

        </div>
    </section>
</main>

<?php include('includes/footer.php'); ?>";

                file_put_contents($ruta_archivo, $plantilla);
            }

            header("Location: gestionar_nosotros.php?success=1");
            exit;
        }
    }
}

// --- D. LÓGICA AJAX: ACTIVAR/DESACTIVAR FOOTER ---
if (isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE menu_nosotros SET es_footer_link = ? WHERE id = ?");
    $stmt->execute([$_POST['nuevo_estado'], $_POST['id']]);
    echo "OK";
    exit;
}

// --- E. LÓGICA AJAX: ACTIVAR/DESACTIVAR HEADER ---
if (isset($_POST['update_header_status'])) {
    $stmt = $db->prepare("UPDATE menu_nosotros SET es_header_link = ? WHERE id = ?");
    $stmt->execute([$_POST['nuevo_estado'], $_POST['id']]);
    echo "OK";
    exit;
}

// 4. OBTENER ENLACES ACTUALES
$links = $db->query("SELECT * FROM menu_nosotros ORDER BY columna_nro ASC, orden ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestionar Nosotros | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card-nosotros {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            border: 1px solid #e1e8ef;
        }

        .tabla-inti {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tabla-inti th {
            background: #f8f9fa;
            color: #15305D;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #eee;
            font-size: 0.85rem;
        }

        .tabla-inti td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .actions-flex {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn-name {
            background: #e3f2fd;
            color: #1a73e8;
        }

        .btn-content {
            background: #f0fdf4;
            color: #16a34a;
        }

        .btn-delete {
            background: #fff5f5;
            color: #e53e3e;
        }

        .btn-delete:hover {
            background: #e53e3e;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        /* Estilo Switch */
        .switch-inti {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }

        .switch-inti input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider-inti {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 20px;
        }

        .slider-inti:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider-inti {
            background-color: #27ae60;
        }

        input:checked+.slider-inti:before {
            transform: translateX(20px);
        }
    </style>
</head>

<body>

    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1 class="page-title"><i class="fas fa-users-cog"></i> Gestión de Menú "Nosotros"</h1>

            <div class="card-nosotros">
                <form method="POST" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="font-weight: bold; font-size: 0.8rem; color: #15305D;">NOMBRE ESPAÑOL</label>
                        <input type="text" name="nombre_es" placeholder="Ej: Nuestra Empresa" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label style="font-weight: bold; font-size: 0.8rem; color: #15305D;">NAME ENGLISH</label>
                        <input type="text" name="nombre_en" placeholder="Ej: Our Company" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                    </div>
                    <div style="flex: 1; min-width: 180px;">
                        <label style="font-weight: bold; font-size: 0.8rem; color: #15305D;">ARCHIVO (OPCIONAL)</label>
                        <input type="text" name="archivo_manual" placeholder="ej: mision-vision" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    </div>
                    <div style="width: 200px;">
                        <label style="font-weight: bold; font-size: 0.8rem; color: #15305D;">COLUMNA MEGA-MENÚ</label>
                        <select name="columna_nro" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; background: white;">
                            <option value="1">NUESTRA EMPRESA</option>
                            <option value="2">¿POR QUÉ VIAJAR CON NOSOTROS?</option>
                            <option value="3">INFORMACIÓN SOBRE VIAJES</option>
                            <option value="4">INFORMACIÓN SOBRE RESERVAS</option>
                        </select>
                    </div>
                    <button type="submit" name="crear_enlace" class="btn-save" style="background: #15305D; color: white; padding: 11px 25px; border-radius: 8px; border: none; cursor: pointer; font-weight: bold; height: 42px;">
                        <i class="fas fa-plus"></i> PUBLICAR
                    </button>
                </form>
            </div>

            <div class="card-nosotros">
                <table class="tabla-inti">
                    <thead>
                        <tr>
                            <th>Nombre (ES)</th>
                            <th>Nombre (EN)</th>
<th>Archivo</th>
                                <th>Header</th>
                                <th>Footer</th>
                                <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $l): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($l['nombre_enlace']); ?></strong></td>
                                <td><?php echo htmlspecialchars($l['nombre_enlace_en']); ?></td>
                                <td><code><?php echo $l['url_enlace']; ?></code></td>
                                <td>
                                    <label class="switch-inti">
                                        <input type="checkbox" class="header-toggle" data-id="<?php echo $l['id']; ?>" <?php echo ($l['es_header_link'] == 1) ? 'checked' : ''; ?>>
                                        <span class="slider-inti"></span>
                                    </label>
                                </td>
                                <td>
                                    <label class="switch-inti">
                                        <input type="checkbox" class="status-toggle" data-id="<?php echo $l['id']; ?>" <?php echo ($l['es_footer_link'] == 1) ? 'checked' : ''; ?>>
                                        <span class="slider-inti"></span>
                                    </label>
                                </td>
                                <td class="actions-flex">
                                    <!-- BOTÓN 1: EDITAR NOMBRE Y ARCHIVO (Modal) -->
                                    <button class="btn-action btn-name"
                                        onclick="openEditModal(<?php echo $l['id']; ?>, '<?php echo addslashes($l['nombre_enlace']); ?>', '<?php echo addslashes($l['nombre_enlace_en']); ?>', '<?php echo $l['url_enlace']; ?>')"
                                        title="Editar nombres y ruta">
                                        <i class="fas fa-tag"></i> Info
                                    </button>

                                    <!-- BOTÓN 2: EDITAR CONTENIDO (Dinámico) -->
                                    <a href="../<?php echo $l['url_enlace']; ?>" target="_blank" class="btn-action btn-content ">
                                        <i class="fas fa-external-link-alt"></i>
                                        <span>Ver Página</span>
                                    </a>

                                    <!-- BOTÓN 3: ELIMINAR -->
                                    <button class="btn-action btn-delete"
                                        onclick="eliminarEnlace(<?php echo $l['id']; ?>)"
                                        title="Eliminar este enlace">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal Editar -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h4 style="margin-top: 0; color: #15305D;">Editar Detalles</h4>
            <input type="hidden" id="edit-id">
            <label style="font-size: 0.8rem; font-weight: bold;">Nombre Español:</label>
            <input type="text" id="edit-es">
            <label style="font-size: 0.8rem; font-weight: bold;">Nombre Inglés:</label>
            <input type="text" id="edit-en">
            <label style="font-size: 0.8rem; font-weight: bold;">Archivo (.php):</label>
            <input type="text" id="edit-file">
            <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top:15px;">
                <button onclick="closeModal()" style="background:#eee; border:none; padding:10px; border-radius:6px; cursor:pointer;">Cancelar</button>
                <button onclick="saveNames()" style="background:#15305D; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer; font-weight:bold;">Guardar</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openEditModal(id, es, en, archivo) {
            $('#edit-id').val(id);
            $('#edit-es').val(es);
            $('#edit-en').val(en);
            $('#edit-file').val(archivo);
            $('#editModal').fadeIn();
        }

        function closeModal() {
            $('#editModal').fadeOut();
        }

        function saveNames() {
            const data = {
                update_names: 1,
                id: $('#edit-id').val(),
                nombre_es: $('#edit-es').val(),
                nombre_en: $('#edit-en').val(),
                archivo: $('#edit-file').val()
            };
            $.post('', data, function(res) {
                if (res === "OK") location.reload();
            });
        }

        function eliminarEnlace(id) {
            Swal.fire({
                title: '¿Confirmar eliminación?',
                text: "Se quitará el enlace del menú inmediatamente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', {
                        eliminar_enlace: 1,
                        id: id
                    }, function(response) {
                        if (response.trim() === "OK") {
                            // Al usar window.location.href forzamos la carga a la página limpia
                            window.location.href = "gestionar_nosotros.php";
                        }
                    });
                }
            });
        }

        $('.status-toggle').change(function() {
            const id = $(this).data('id');
            const estado = $(this).is(':checked') ? 1 : 0;
            $.post('', {
                update_status: 1,
                id: id,
                nuevo_estado: estado
            });
        });
        
        $('.header-toggle').change(function() {
            const id = $(this).data('id');
            const estado = $(this).is(':checked') ? 1 : 0;
            $.post('', {
                update_header_status: 1,
                id: id,
                nuevo_estado: estado
            });
        });

        // Reemplaza el bloque de la alerta de éxito por este:
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                title: '¡Publicado!',
                text: 'El enlace ha sido creado con éxito.',
                icon: 'success',
                confirmButtonColor: '#15305D'
            }).then(() => {
                // Esto limpia la URL de '?success=1' para que no vuelva a salir al recargar
                window.history.replaceState({}, document.title, "gestionar_nosotros.php");
            });
        <?php endif; ?>
    </script>
</body>

</html>