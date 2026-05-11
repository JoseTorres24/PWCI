<?php
session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol']
        ];
        
        // Devolver el rol para que el frontend sepa a dónde redirigir
        echo json_encode([
            'success' => true, 
            'message' => 'Login exitoso', 
            'rol' => $usuario['rol']
        ]);
    } else {
        echo json_encode(['error' => 'Correo o contraseña incorrectos']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>