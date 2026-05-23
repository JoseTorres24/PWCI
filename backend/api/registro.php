<?php
session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($nombre) || empty($correo) || empty($password)) {
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Correo electrónico inválido']);
    exit;
}

// Validación de contraseña mejorada
function validarPassword($password) {
    $errores = [];
    
    
    if (strlen($password) < 7) {
        $errores[] = 'La contraseña debe tener al menos 7 caracteres';
    }
    
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = 'La contraseña debe contener al menos una letra mayúscula';
    }
    
    
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = 'La contraseña debe contener al menos una letra minúscula';
    }
    
    
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = 'La contraseña debe contener al menos un número';
    }
    
    
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:,.?\/]/', $password)) {
        $errores[] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*() etc.)';
    }
    
    return $errores;
}

// Uso en el registro
$errores = validarPassword($password);
if (!empty($errores)) {
    echo json_encode(['error' => implode('. ', $errores)]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // ========== USAR SP_REGISTRO ==========
    $stmt = $conn->prepare("CALL SP_REGISTRO(?, ?, ?, @p_usuario_id, @p_success, @p_mensaje)");
    $stmt->execute([$nombre, $correo, $passwordHash]);
    
    // Obtener los valores de salida
    $stmt = $conn->query("SELECT @p_usuario_id as usuario_id, @p_success as success, @p_mensaje as mensaje");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->nextRowset(); // Limpiar resultados
    
    if ($result['success'] == 1) {
        $_SESSION['usuario'] = [
            'id' => $result['usuario_id'],
            'nombre' => $nombre,
            'correo' => $correo,
            'rol' => 'coleccionista'
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => $result['mensaje'],
            'rol' => 'coleccionista'
        ]);
    } else {
        echo json_encode(['error' => $result['mensaje']]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>