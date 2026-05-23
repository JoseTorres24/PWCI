<?php
// NO poner session_start() aquí

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $envPath = __DIR__ . '/.env';
        
        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            $this->host = $env['DB_HOST'] ?? '127.0.0.1';
            $this->db_name = $env['DB_NAME'] ?? 'mundial2026';
            $this->username = $env['DB_USER'] ?? 'root';
            $this->password = $env['DB_PASS'] ?? '2405';
        } 
    }

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}
?>