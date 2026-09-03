<?php
// src/Services/EmailService.php

// Importamos las clases necesarias de PHPMailer (gracias al autoloader de Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;

    public function __construct() {
        // Instanciamos PHPMailer (el 'true' habilita las excepciones para ver errores)
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }

    /**
     * Configuración del servidor de salida de correos
     */
    private function configurarSMTP() {
        // Configuración para Gmail (Si usas correo de hosting web como cPanel, esto cambia)
        $this->mail->isSMTP();                                      
        $this->mail->Host       = 'smtp.gmail.com';                 // Servidor SMTP
        $this->mail->SMTPAuth   = true;                             // Habilitar autenticación
        
        // ¡IMPORTANTE! Cambia esto por el correo y contraseña reales de IntiPath Tours
        $this->mail->Username   = 'tu_correo_real_de_la_agencia@gmail.com'; 
        $this->mail->Password   = 'las16letrasqueacabasdecopiar'; // Pégala toda junta, sin espacios   // Contraseña (o App Password de Gmail)
        
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Encriptación segura
        $this->mail->Port       = 587;                              // Puerto TLS
        
        // Configuración para que las tildes y caracteres en español se vean bien
        $this->mail->CharSet = 'UTF-8';
    }

    /**
     * Método para enviar un correo de confirmación al cliente
     */
    public function enviarConfirmacionCliente($nombreCliente, $correoCliente) {
        try {
            // Remitente (La agencia)
            $this->mail->setFrom('correo_agencia@gmail.com', 'IntiPath Tours');
            
            // Destinatario (El cliente)
            $this->mail->addAddress($correoCliente, $nombreCliente);

            // Contenido del correo
            $this->mail->isHTML(true);                                  
            $this->mail->Subject = '¡Hemos recibido tu solicitud! - IntiPath Tours';
            
            // Construimos el cuerpo del mensaje de forma clara y directa
            $cuerpo = "<h3>Estimado $nombreCliente,</h3>";
            $cuerpo .= "<p>Gracias por contactarte con IntiPath Tours. Hemos recibido tu mensaje y estamos procesando tu solicitud de reserva o información.</p>";
            $cuerpo .= "<p>Un asesor de nuestro equipo se comunicará contigo a la brevedad para afinar los detalles.</p>";
            $cuerpo .= "<br><p>Saludos cordiales,</p>";
            $cuerpo .= "<p><strong>El equipo de IntiPath Tours</strong></p>";

            $this->mail->Body = $cuerpo;
            
            // Texto plano alternativo (por si el correo del cliente no carga HTML)
            $this->mail->AltBody = "Estimado $nombreCliente,\n\nGracias por contactarte con IntiPath Tours. Hemos recibido tu mensaje...\n\nSaludos cordiales,\nEl equipo de IntiPath Tours";

            // Enviar el correo
            $this->mail->send();
            return true; 

        } catch (Exception $e) {
            // Si hay un error, lo devolvemos para saber qué falló
            return "Error al enviar el correo: {$this->mail->ErrorInfo}";
        }
    }
}
?>