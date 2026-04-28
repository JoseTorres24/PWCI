<?php
// backend/middleware/AuthMiddleware.php
class AuthMiddleware {
    
    public static function verificarAutenticacion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['usuario'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('Location: /acceso');
            exit;
        }
        
        return $_SESSION['usuario'];
    }
    
    public static function verificarAdmin() {
        $usuario = self::verificarAutenticacion();
        
        if ($usuario['rol'] !== 'admin') {
            http_response_code(403);
            die('Acceso denegado. Se requieren permisos de administrador.');
        }
        
        return $usuario;
    }

    public static function registrarAccion($conn, $usuario_id, $accion, $detalle = null) {
        $stmt = $conn->prepare("
            INSERT INTO historial (usuario_id, descripcion, fecha)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$usuario_id, $accion . ($detalle ? ": " . $detalle : '')]);
    }

    public static function limitarIntentosLogin($conn, $email) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as intentos 
            FROM historial 
            WHERE descripcion LIKE ? 
            AND fecha > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute(["Intento fallido de login: %{$email}%"]);
        $intentos = $stmt->fetch()['intentos'];
        
        if ($intentos >= 5) {
            header('HTTP/1.1 429 Too Many Requests');
            die('Demasiados intentos fallidos. Espera 15 minutos.');
        }
    }
}
?>