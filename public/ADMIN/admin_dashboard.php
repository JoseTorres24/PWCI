<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificar que sea admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

// Estadísticas
$stmt = $conn->query("SELECT COUNT(*) as total FROM equipos");
$total_equipos = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM jugadores");
$total_jugadores = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'coleccionista'");
$total_usuarios = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Administrador</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/ADMIN/adminCSS.css">
</head>
<body>

<header class="admin-hero">
  <div class="hero-contenido">
    <p class="admin-etiqueta">FIFA World Cup 2026</p>
    <h1>Panel de Administración</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard" class="activo">Panel Principal</a>
      <a href="/admin/jugadores">Gestionar Jugadores</a>
      <a href="/admin/equipos">Gestionar Equipos</a>
      <a href="/admin/gestion_usuarios">Gestion Usuarios</a>
      <a href="/admin/logout" class="salir">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="admin-layout">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;">
    <div style="background:white;border-radius:20px;padding:25px;text-align:center;box-shadow:0 4px 15px rgba(0,0,0,0.08);">
      <div style="font-size:2.5rem;font-weight:bold;color:#08b8b3;"><?php echo $total_equipos; ?></div>
      <div style="color:#46637a;">Equipos</div>
    </div>
    <div style="background:white;border-radius:20px;padding:25px;text-align:center;box-shadow:0 4px 15px rgba(0,0,0,0.08);">
      <div style="font-size:2.5rem;font-weight:bold;color:#08b8b3;"><?php echo $total_jugadores; ?></div>
      <div style="color:#46637a;">Jugadores</div>
    </div>
    <div style="background:white;border-radius:20px;padding:25px;text-align:center;box-shadow:0 4px 15px rgba(0,0,0,0.08);">
      <div style="font-size:2.5rem;font-weight:bold;color:#08b8b3;"><?php echo $total_usuarios; ?></div>
      <div style="color:#46637a;">Coleccionistas</div>
    </div>
  </div>

  <section class="card-panel">
    <h2>Administración del Sistema</h2>
    <p>Desde este panel puedes administrar jugadores y equipos del Mundial 2026.</p>
    <ul class="lista-destacada">
      <li>Registrar nuevos equipos</li>
      <li>Editar equipos existentes</li>
      <li>Eliminar equipos</li>
      <li>Registrar nuevos jugadores</li>
      <li>Editar jugadores existentes</li>
      <li>Eliminar jugadores</li>
    </ul>
  </section>
</main>

</body>
</html>