<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('usuarios');

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

// CONSULTA PARA MOSTRAR LA TABLA DE COLABORADORES
$query = "SELECT * FROM usuarios ORDER BY id DESC";
$usuarios = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios | IntiPath Tours</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-usuarios-crear {
            --color-primario-azul: #15305D;
            --color-secundario-amarillo: #E8AC18;
        }
        .page-usuarios-crear .table-inti thead {
            background: #15305D;
            color: #fff;
        }
        .page-usuarios-crear .table-inti thead th {
            color: #fff;
        }
        .page-usuarios-crear .header-main h1 span {
            color: var(--color-secundario-amarillo);
        }
        .page-usuarios-crear .btn-primary-inti {
            background: var(--color-primario-azul);
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .page-usuarios-crear .btn-primary-inti:hover {
            background: #1e4a8a;
            transform: translateY(-2px);
        }
        .page-usuarios-crear .badge-status.active {
            background: var(--color-primario-azul);
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .page-usuarios-crear .badge-permiso {
            background: #e8f0fe;
            color: var(--color-primario-azul);
            border: 1px solid var(--color-primario-azul);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            display: inline-block;
            margin: 2px;
        }
        .page-usuarios-crear .btn-circle.edit {
            background: none;
            color: var(--color-secundario-amarillo);
        }
        .page-usuarios-crear .btn-circle.edit:hover {
            color: #d4a014;
        }
        .page-usuarios-crear .btn-circle.delete {
            background: none !important;
            color: #e74c3c;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .page-usuarios-crear .btn-circle.delete:hover {
            color: #c0392b;
        }
    </style>
</head>
<body class="admin-panel page-usuarios-crear">
    <div class="admin-container">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="header-main">
                <div class="header-text">
                    <h1>Gestión de <span>Personal</span></h1>
                    <p>Lista oficial de colaboradores y sus niveles de acceso.</p>
                </div>
                <a href="edit_usuarios.php" class="btn-primary-inti">
                    <i class="fas fa-user-plus"></i> Nuevo Registro
                </a>
            </header>

            <section class="table-container">
                <table class="table-inti">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Accesos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-details">
                                        <span class="u-name"><?php echo $u['nombres'] . " " . $u['apellidos']; ?></span>
                                        <span class="u-id">@<?php echo $u['usuario']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem;">
                                    <i class="fas fa-envelope" style="color: var(--color-secundario-amarillo);"></i> <?php echo $u['email']; ?><br>
                                    <i class="fas fa-phone" style="color: var(--color-secundario-amarillo);"></i> <?php echo $u['telefono']; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($u['estado'] == 1): ?>
                                    <span class="badge-status active">Activo</span>
                                <?php else: ?>
                                    <span class="badge-status blocked">Bloqueado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="badge-group">
                                    <?php 
                                    $perms = explode(',', $u['permisos']);
                                    foreach($perms as $p): if($p): ?>
                                        <span class="badge-permiso"><?php echo $p; ?></span>
                                    <?php endif; endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="btn-actions-group">
                                    <a href="edit_usuarios.php?edit=<?php echo $u['id']; ?>" class="btn-circle edit" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button onclick="confirmarEliminar(<?php echo $u['id']; ?>)" class="btn-circle delete" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
    function confirmarEliminar(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#15305D',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a una lógica de eliminación o al mismo edit_usuarios con parámetro delete
                window.location.href = "edit_usuarios.php?delete=" + id;
            }
        })
    }
    </script>
</body>
</html>