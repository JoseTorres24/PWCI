<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

$db = new Database();
$conn = $db->connect();

// Verificar si es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /acceso");
    exit;
}

// Obtener datos
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Validaciones
if (empty($nombre) || empty($correo) || empty($password)) {
    header("Location: /acceso?error=campos");
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: /acceso?error=correo");
    exit;
}

if (strlen($password) < 6) {
    header("Location: /acceso?error=password");
    exit;
}

try {
    // Verificar si el correo ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    
    if ($stmt->fetch()) {
        header("Location: /acceso?error=existe");
        exit;
    }
    
    // Crear usuario
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, 'coleccionista')");
    $stmt->execute([$nombre, $correo, $passwordHash]);
    
    // Iniciar sesión automáticamente
    $_SESSION['usuario'] = [
        'id' => $conn->lastInsertId(),
        'nombre' => $nombre,
        'correo' => $correo,
        'rol' => 'coleccionista'
    ];
    
    header("Location: /dashboard?registro=exitoso");
    exit;
    
} catch (PDOException $e) {
    header("Location: /acceso?error=bd");
    exit;
}
?>