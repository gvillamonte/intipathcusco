<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('usuarios');

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();
$mensaje_alerta = "";

// --- 1. LÓGICA DE PROCESAMIENTO (GUARDAR / ACTUALIZAR) ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_usuario'] ?? null;
    $user = trim($_POST['usuario']);
    $nombres = strtoupper(trim($_POST['nombres'])); // Guardar en mayúsculas
    $apellidos = strtoupper(trim($_POST['apellidos'])); // Guardar en mayúsculas
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = strtoupper(trim($_POST['direccion'])); // Guardar en mayúsculas
    $estado = $_POST['estado'] ?? 1;

    // Convertimos el array de permisos en una cadena separada por comas
    $permisos = isset($_POST['accesos']) ? implode(',', $_POST['accesos']) : '';

    if ($id) {
        // MODO: ACTUALIZAR
        $sql = "UPDATE usuarios SET usuario=?, nombres=?, apellidos=?, email=?, telefono=?, direccion=?, estado=?, permisos=? ";
        $params = [$user, $nombres, $apellidos, $email, $telefono, $direccion, $estado, $permisos];

        if (!empty($_POST['password'])) {
            $sql .= ", password=? ";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            $mensaje_alerta = "Swal.fire('¡Actualizado!', 'Los datos se guardaron correctamente.', 'success').then(() => { window.location='usuarios_crear.php'; });";
        }
    } else {
        // MODO: CREAR NUEVO
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (usuario, nombres, apellidos, email, telefono, direccion, password, permisos, estado) VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$user, $nombres, $apellidos, $email, $telefono, $direccion, $pass, $permisos, 1])) {
            $mensaje_alerta = "Swal.fire('¡Creado!', 'Nuevo colaborador registrado.', 'success').then(() => { window.location='usuarios_crear.php'; });";
        }
    }
}

// --- 2. CARGAR DATOS SI SE VA A EDITAR ---
$data = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $data ? 'Editar' : 'Nuevo'; ?> Usuario | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="admin-panel page-usuarios-edit">
    <div class="admin-container">

        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="header-main">
                <h1><?php echo $data ? 'Editar' : 'Registrar'; ?> <span>Colaborador</span></h1>
                <a href="usuarios_crear.php" class="btn-primary-inti">
                    <i class="fas fa-arrow-left"></i> Volver a la lista
                </a>
            </header>

            <section class="card-admin">
                <form method="POST">
                    <input type="hidden" name="id_usuario" value="<?php echo $data['id'] ?? ''; ?>">

                    <div class="row">
                        <div class="col"><label>Nombres</label><input type="text" name="nombres" value="<?php echo $data['nombres'] ?? ''; ?>" required></div>
                        <div class="col"><label>Apellidos</label><input type="text" name="apellidos" value="<?php echo $data['apellidos'] ?? ''; ?>" required></div>
                    </div>

                    <div class="row">
                        <div class="col"><label>Usuario (Login)</label><input type="text" name="usuario" value="<?php echo $data['usuario'] ?? ''; ?>" required></div>
                        <div class="col"><label>Contraseña <?php echo $data ? '(Vacío para mantener)' : ''; ?></label><input type="password" name="password" <?php echo $data ? '' : 'required'; ?>></div>
                    </div>

                    <div class="row">
                        <div class="col"><label>Correo Electrónico</label><input type="email" name="email" value="<?php echo $data['email'] ?? ''; ?>" required></div>
                        <div class="col"><label>Teléfono</label><input type="text" name="telefono" value="<?php echo $data['telefono'] ?? ''; ?>"></div>
                    </div>

                    <div class="row">
                        <div class="col"><label>Dirección</label><input type="text" name="direccion" value="<?php echo $data['direccion'] ?? ''; ?>"></div>
                        <div class="col">
                            <label>Estado del Usuario</label>
                            <select name="estado" class="select-standard">
                                <option value="1" <?php echo (isset($data['estado']) && $data['estado'] == 1) ? 'selected' : ''; ?>>✅ Activo</option>
                                <option value="0" <?php echo (isset($data['estado']) && $data['estado'] == 0) ? 'selected' : ''; ?>>🚫 Bloqueado</option>
                            </select>
                        </div>
                    </div>

                    <div class="permisos-box" style="margin-top: 20px; border: 1px solid #E8AC18; border-radius: 8px; padding: 20px;">
                        <label class="title-permisos" style="color: #15305D; font-weight: bold; margin-bottom: 20px; display: block;">
                            <i class="fas fa-shield-alt"></i> ASIGNAR PERMISOS DEL PANEL (POR GRUPOS):
                        </label>

                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: #15305D; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <input type="checkbox" id="seleccionar_todos" style="accent-color: #15305D; width: 17px; height: 17px;">
                            <i class="fas fa-check-double"></i> Seleccionar todos
                        </label>
                        <div class="permisos-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">

                            <?php
                            $mis_perms = explode(',', $data['permisos'] ?? '');

                            $grupos = [
                                'CONTENIDO' => [
                                    'icon' => 'fa-folder-open',
                                    'items' => [
                                        'tours' => 'Gestionar Tours',
'blog' => 'Gestionar Blog',
                                        'paginas' => 'Páginas del sitio',
                                        'fundacion' => 'Fundación',
                                        'seo' => 'SEO / Metadatos',
                                        'unete' => 'Gestionar Vacantes',
                                        'calendario' => 'Calendario de Salidas',
                                        'tipos_tours' => 'Tipos de Tours',
                                        'info_viaje' => 'Info de Viaje (Cards)',
                                        'sliders' => 'Gestionar Sliders',
                                    ]
                                ],
                                'INFO VIAJE' => [
                                    'icon' => 'fa-info-circle',
                                    'items' => [
                                        'info_previa' => 'Info Previa',
                                        'niveles_dificultad' => 'Niveles Dificultad',
                                        'clima' => 'Clima',
                                        'equipaje' => 'Equipaje',
                                        'seguridad_viaje' => 'Seguridad Viaje',
                                        'como_reservar' => 'Cómo Reservar',
                                        'alquiler' => 'Alquiler',
                                        'disponibilidad' => 'Disponibilidad',
                                        'reservas_info' => 'Reservas Info',
                                    ]
                                ],
                                'PERSONALIZACIÓN' => [
                                    'icon' => 'fa-brush',
                                    'items' => [
                                        'header_footer' => 'Identidad y Footer',
                                        'footer_links' => 'Enlaces Footer',
                                        'licencias' => 'Licencias y Permisos',
                                        'terminos' => 'Términos y Cond.',
                                        'privacidad' => 'Pol. Privacidad',
                                        'config' => 'Configuración',
                                        'barra_movil' => 'Barra Móvil',
                                        'contenido_index' => 'Contenido Index',
                                        'colores' => 'Colores',
                                    ]
                                ],
                                'NOSOTROS' => [
                                    'icon' => 'fa-users',
                                    'items' => [
                                        'nosotros' => 'Gestionar Nosotros',
                                        'equipo' => 'Nuestro Equipo',
                                        'grupos' => 'Nuestros Grupos',
                                        'seguridad' => 'Seguridad',
                                        'garantia' => 'Garantía',
                                        'faqs' => 'Preguntas Frecuentes',
                                    ]
                                ],
                                'ADMINISTRACIÓN' => [
                                    'icon' => 'fa-user-lock',
                                    'items' => [
                                        'mensajes' => 'Mensajes Recibidos',
                                        'reclamos' => 'Libro Reclamaciones',
                                        'usuarios' => 'Crear Usuarios',
                                        'reservas' => 'Reservas',
                                        'pagos' => 'Pagos',
                                        'plantilla_pdf' => 'Plantilla PDF',
                                        'config_pagos' => 'Config. Yape/Plin',
                                    ]
                                ]
                            ];

                            foreach ($grupos as $titulo => $info): ?>
                                <fieldset style="border: 1px solid #eee; border-radius: 6px; padding: 12px;">
                                    <legend style="font-size: 0.75rem; font-weight: bold; color: #E8AC18; padding: 0 8px; text-transform: uppercase;">
                                        <i class="fas <?php echo $info['icon']; ?>"></i> <?php echo $titulo; ?>
                                    </legend>

                                    <?php foreach ($info['items'] as $valor => $nombre): ?>
                                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer; font-size: 0.9rem;">
                                            <input type="checkbox" name="accesos[]" value="<?php echo $valor; ?>"
                                                style="accent-color: #15305D; width: 17px; height: 17px;"
                                                <?php echo in_array($valor, $mis_perms) ? 'checked' : ''; ?>>
                                            <?php echo $nombre; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> <?php echo $data ? 'Guardar Cambios' : 'Registrar Colaborador'; ?>
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
    <script>
        <?php echo $mensaje_alerta; ?>

        document.getElementById('seleccionar_todos').addEventListener('change', function() {
            const checks = document.querySelectorAll('input[name="accesos[]"]');
            checks.forEach(c => c.checked = this.checked);
        });

        document.querySelectorAll('input[name="accesos[]"]').forEach(c => {
            c.addEventListener('change', function() {
                const checks = document.querySelectorAll('input[name="accesos[]"]');
                const todos = document.getElementById('seleccionar_todos');
                todos.checked = Array.from(checks).every(x => x.checked);
            });
        });
    </script>
</body>

</html>