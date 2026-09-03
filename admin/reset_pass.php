<?php
// admin/reset_pass.php
session_start();
require_once __DIR__ . '/../config/database.php';

$mensaje_swal = ""; 
$token_valido = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// 1. Validar token y expiración
$stmt = $db->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW() LIMIT 1");
$stmt->execute([$token]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    $token_valido = true;
} else {
    $mensaje_swal = "Swal.fire({title: 'Error', text: 'El enlace es inválido o ha expirado', icon: 'error', confirmButtonColor: '#15305D'}).then(() => { window.location='recuperar.php'; });";
}

// 2. Procesar el cambio
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valido) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if (strlen($pass1) < 8) {
        $mensaje_swal = "Swal.fire('Contraseña débil', 'Debe tener al menos 8 caracteres', 'warning');";
    } elseif ($pass1 !== $pass2) {
        $mensaje_swal = "Swal.fire('Error', 'Las contraseñas no coinciden', 'error');";
    } else {
        $nueva_pass_hash = password_hash($pass1, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, token_expira = NULL WHERE id = ?");
        
        if ($update->execute([$nueva_pass_hash, $usuario['id']])) {
            header("Location: login.php?reset=success");
            exit;
        } else {
            $mensaje_swal = "Swal.fire('Error', 'No se pudo actualizar la clave', 'error');";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin_reset.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="body-reset">
    <div class="reset-wrapper">
        <div class="reset-caja">
            <div class="reset-logo">
                <img src="../assets/img/logo_intipath_azul.png" alt="Logo IntiPath Tours">
            </div>

            <h2>Nueva <span>Contraseña</span></h2>
            <p>Define tu nueva clave de acceso para tu cuenta corporativa.</p>

            <form method="POST" id="resetForm" autocomplete="off">
                <div class="form-grupo">
                    <label>Nueva Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" name="pass1" id="pass1" placeholder="Mínimo 8 caracteres" required autofocus>
                        <i class="fas fa-eye toggle-pass" onclick="toggleVisibility('pass1', this)"></i>
                    </div>
                </div>

                <div class="form-grupo">
                    <label>Confirmar Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fas fa-check-double icon-left"></i>
                        <input type="password" name="pass2" id="pass2" placeholder="Repite la contraseña" required>
                        <i class="fas fa-eye toggle-pass" onclick="toggleVisibility('pass2', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-actualizar">
                    <i class="fas fa-save"></i> Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleVisibility(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        <?php echo $mensaje_swal; ?>
    </script>
</body>
</html>