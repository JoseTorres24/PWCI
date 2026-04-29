<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

$db = new Database();
$conn = $db->connect();

$correo = 'admin@mundial2026.com';
$password_prueba = 'admin123';

$stmt = $conn->prepare("SELECT password FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch();

echo "Hash en BD: " . $user['password'] . "<br>";
echo "Contraseña probada: " . $password_prueba . "<br>";
echo "Verificación: " . (password_verify($password_prueba, $user['password']) ? 'CORRECTA' : 'INCORRECTA') . "<br>";
?>
 
<?php
// Para generar un hash al administrador en caso de que se requiera al administrador generarle una contra
//echo "Nuevo hash para '---': " . password_hash('---', PASSWORD_DEFAULT);
?>