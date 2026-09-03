<?php
// src/Services/WhatsappService.php

// Importamos las clases de Guzzle para hacer peticiones HTTP (gracias a Composer)
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Clase WhatsappService
 * Se encarga de enviar mensajes de texto a través de la API oficial de Meta (WhatsApp Cloud API).
 */
class WhatsappService {
    private $client;
    private $apiUrl;
    private $token;
    private $phoneNumberId;

    public function __construct() {
        // Instanciamos el cliente Guzzle que hará el envío de datos
        $this->client = new Client();
        
        // --- CREDENCIALES DE META (WHATSAPP CLOUD API) ---
        // Para que esto funcione en producción, debes crear una app en developers.facebook.com
        
        // 1. El ID del número de teléfono que Meta te asigna
        $this->phoneNumberId = 'TU_PHONE_NUMBER_ID_AQUI'; 
        
        // 2. El Token de acceso temporal o permanente de tu App de Meta
        $this->token = 'TU_TOKEN_DE_ACCESO_AQUI';
        
        // 3. La URL oficial de la API de Meta (Actualmente en la versión v17.0 o superior)
        $this->apiUrl = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";
    }

    /**
     * Método para enviar un mensaje de WhatsApp
     * * @param string $numeroDestino El número del cliente con código de país (Ej: 51987654321 para Perú)
     * @param string $mensaje El texto que queremos enviar
     * @return bool|string Retorna true si tuvo éxito, o el mensaje de error si falló
     */
    public function enviarMensaje($numeroDestino, $mensaje) {
        try {
            // Configuramos la petición POST que enviaremos a Meta
            $respuesta = $this->client->request('POST', $this->apiUrl, [
                // Cabeceras de autorización y formato
                'headers' => [
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type'  => 'application/json',
                ],
                // Cuerpo del mensaje estructurado como Meta lo exige
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to'                => $numeroDestino,
                    'type'              => 'text',
                    'text'              => [
                        'body' => $mensaje // Aquí va el texto que armemos para el cliente
                    ]
                ]
            ]);

            // Si el código de respuesta es 200 (OK), el mensaje salió con éxito
            if ($respuesta->getStatusCode() == 200) {
                return true;
            }

            return false;

        } catch (RequestException $e) {
            // Si hay un error de conexión o credenciales inválidas, lo capturamos aquí
            if ($e->hasResponse()) {
                // Obtenemos el error exacto que nos devuelve Meta
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                return "Error de Meta: " . ($errorBody['error']['message'] ?? 'Error desconocido');
            }
            // Error de conexión a internet o servidor caído
            return "Error de conexión: " . $e->getMessage();
        }
    }
}
?>