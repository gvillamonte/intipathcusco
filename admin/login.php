<?php
// admin/login.php
ini_set('session.cookie_lifetime', 0); // La sesión muere al cerrar el navegador
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

// Inicializamos la variable para evitar el Warning de PHP
$error = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();

    $user_input = trim($_POST['usuario']); 
    $pass_input = $_POST['password'];

    if ($db) {
        $query = "SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $user_input);
        $stmt->execute();
        $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario_db) {
            if (password_verify($pass_input, $usuario_db['password'])) {
                if ($usuario_db['estado'] == 0) {
                    $error = "Acceso denegado: Tu cuenta ha sido bloqueada.";
                } else {
                    $_SESSION['admin_logeado'] = true;
                    $_SESSION['admin_id'] = $usuario_db['id'];
                    $_SESSION['admin_nombre'] = $usuario_db['nombres']; 
                    
                    if (!empty($usuario_db['permisos'])) {
                        $_SESSION['permisos'] = explode(',', $usuario_db['permisos']);
                    } else {
                        $_SESSION['permisos'] = []; 
                    }

                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = "La contraseña ingresada es incorrecta.";
            }
        } else {
            $error = "El usuario '$user_input' no existe.";
        }
    } else {
        $error = "Error crítico: No hay conexión a la base de datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IntiPath Tours</title>
    <link rel="stylesheet" href="../assets/css/admin_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="body-login">

    <div class="login-wrapper">
        
        <div class="login-info-side">
            <div class="info-content">
                <h1>Descubre<br>Cusco con<br><span>IntiPath</span></h1>
                <p>Accede al panel de administración para gestionar las mejores experiencias andinas.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <div class="login-form-side">
            <div class="login-caja">
                <div class="login-logo-container">
                    <img src="../assets/img/logo_intipath_azul.png" alt="Logo IntiPath Tours" class="main-login-logo">
                </div>

                <h2>Iniciar Sesión</h2>
                
                <?php if ($error != ""): ?>
                    <div class='error-diag'><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="login-form-grupo">
                        <label>USUARIO / EMAIL</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="usuario" placeholder="Tu nombre de usuario" required autofocus>
                        </div>
                    </div>

                    <div class="login-form-grupo">
                        <label>CONTRASEÑA</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="login-extras">
                        <label><input type="checkbox"> Recordarme</label>
                        <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-login">Acceder Ahora</button>

                    <p class="terms">
                        Al hacer clic, aceptas nuestros <br>
                        <a href="#">Términos de Servicio</a> | <a href="#">Política de Privacidad</a>
                    </p>
                </form>
            </div>
        </div>

    </div>

</body>
</html>