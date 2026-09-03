<?php
// admin/recuperar.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

$script_alerta = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_usuario = trim($_POST['email']); 
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("UPDATE usuarios SET reset_token = ?, token_expira = ? WHERE email = ?");
    $stmt->execute([$token, $expira, $email_usuario]);

    if ($stmt->rowCount() > 0) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'intipathtourstrekkinperu@gmail.com'; 
            $mail->Password   = 'aifgiwxedwogpsgq';           
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('intipathtourstrekkinperu@gmail.com', 'IntiPath Tours');
            $mail->addAddress($email_usuario);

            $enlace = "http://localhost/Agenciaintipathrours/admin/reset_pass.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Recuperar Password - IntiPath Tours';
            $mail->Body = "
                <div style='background-color: #f9f9f9; padding: 30px; font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden;'>
                        <div style='background-color: #15305D; padding: 20px; text-align: center;'>
                            <h1 style='color: #E8AC18; margin: 0;'>IntiPath Tours</h1>
                        </div>
                        <div style='padding: 30px; text-align: center;'>
                            <h2 style='color: #15305D;'>Solicitud de Cambio de Clave</h2>
                            <p>Hemos recibido una solicitud para restablecer la contraseña asociada a este correo electrónico.</p>
                            <div style='margin: 30px 0;'>
                                <a href='$enlace' style='background-color: #E8AC18; color: #15305D; padding: 15px 25px; text-decoration: none; font-weight: bold; border-radius: 5px;'>RESTABLECER MI CONTRASEÑA</a>
                            </div>
                            <p style='color: #666; font-size: 12px;'>Este enlace es válido por 1 hora. Si no solicitaste este cambio, ignora este mensaje.</p>
                        </div>
                    </div>
                </div>";

            $mail->send();
            $script_alerta = "Swal.fire({title: '¡Correo Enviado!', text: 'Revisa tu bandeja de entrada: $email_usuario', icon: 'success', confirmButtonColor: '#15305D'});";

        } catch (Exception $e) {
            $script_alerta = "Swal.fire('Error de Envío', 'No se pudo despachar el correo: {$mail->ErrorInfo}', 'error');";
        }
    } else {
        $script_alerta = "Swal.fire('No encontrado', 'El correo $email_usuario no está registrado.', 'warning');";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso - IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin_recuperar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="body-recuperar">
    <div class="recuperar-wrapper">
        <div class="recuperar-caja">
            <div class="recuperar-logo-container">
                <img src="../assets/img/logo_intipath_azul.png" alt="Logo IntiPath Tours" class="main-recuperar-logo">
            </div>

            <h2>Recuperar <span>Acceso</span></h2>
            <p class="instrucciones">Ingresa tu correo electrónico para enviarte las instrucciones de restablecimiento.</p>

            <form method="POST" id="formRecuperar">
                <div class="form-grupo">
                    <label>E-mail corporativo</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="nombre@intipathtours.com" required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn-enviar" id="btnEnviar">
                    <i class="fas fa-paper-plane"></i> Enviar Enlace
                </button>
                
                <div class="separador-retorno">
                    <a href="login.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Regresar al inicio de sesión
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Ejecución de alerta PHP si existe
        <?php echo $script_alerta; ?>
    </script>
</body>
</html>