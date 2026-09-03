<?php
// config/database.php

/**
 * Clase Database
 * Maneja la conexión a la base de datos MySQL usando PDO.
 */
class Database {
    // Definición de las credenciales de acceso a la base de datos
    private $host = "localhost";
    private $db_name = "intipath"; // Nombre de tu base de datos
    private $username = "root";       // Tu usuario de MySQL
    private $password = "";           // Tu contraseña de MySQL
    public $conn ;

    /**
     * Método para obtener la conexión a la base de datos.
     * @return PDO|null Retorna el objeto de conexión o null si falla.
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Se establece el string de conexión (DSN)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Se instancia PDO con manejo de errores en modo Excepción
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            // Si hay un error, se captura y se muestra
            echo "Error de conexión a la base de datos: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>