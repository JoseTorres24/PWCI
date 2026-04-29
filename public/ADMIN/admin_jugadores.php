<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

// Crear jugador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_jugador'])) {
    $nombre = trim($_POST['nombre']);
    $numero = $_POST['numero'];
    $posicion = $_POST['posicion'];
    $equipo_id = $_POST['equipo_id'];
    $rareza = $_POST['rareza'];
    $foto = trim($_POST['foto']);
    
    $stmt = $conn->prepare("INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $numero, $posicion, $equipo_id, $rareza, $foto]);
    header('Location: /admin/jugadores?success=creado');
    exit;
}

// Eliminar jugador
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM jugadores WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: /admin/jugadores?success=eliminado');
    exit;
}

$equipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre")->fetchAll();
$jugadores = $conn->query("SELECT j.*, e.nombre as equipo_nombre FROM jugadores j JOIN equipos e ON j.equipo_id = e.id ORDER BY e.nombre, j.numero")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Jugadores</title>
<link rel="stylesheet" href="/ADMIN/adminCSS.css">
</head>
<body>
<header class="admin-hero">
  <div class="hero-contenido">
    <h1>Gestión de Jugadores</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard">Panel Principal</a>
      <a href="/admin/jugadores" class="activo">Gestionar Jugadores</a>
      <a href="/admin/equipos">Gestionar Equipos</a>
      <a href="/admin/logout" class="salir">Cerrar sesión</a>
    </nav>
  </div>
</header>
<main class="admin-layout">
  <?php if (isset($_GET['success'])): ?>
    <div style="background:#2ecc71;color:white;padding:12px;border-radius:12px;margin-bottom:20px;">✅ Operación exitosa</div>
  <?php endif; ?>

  <section class="card-panel">
    <h2>Registrar Jugador</h2>
    <form method="POST">
      <div class="campo"><label>Nombre</label><input type="text" name="nombre" required></div>
      <div class="campo"><label>Número</label><input type="number" name="numero" required></div>
      <div class="campo"><label>Posición</label><select name="posicion"><option>POR</option><option>DEF</option><option>MED</option><option>DEL</option></select></div>
      <div class="campo"><label>Equipo</label><select name="equipo_id"><?php foreach($equipos as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option><?php endforeach; ?></select></div>
      <div class="campo"><label>Rareza</label><select name="rareza"><option value="comun">Común</option><option value="r">R</option><option value="sr">SR</option><option value="ssr">SSR</option></select></div>
      <div class="campo-completo"><label>Foto URL</label><input type="text" name="foto" placeholder="/Jugadores/nombre.png"></div>
      <div class="form-acciones"><button type="submit" name="crear_jugador">Guardar Jugador</button></div>
    </form>
  </section>

  <section class="card-panel">
    <h2>Lista de Jugadores</h2>
    <div class="table-wrap">
      <table><thead><tr><th>ID</th><th>Nombre</th><th>Número</th><th>Pos</th><th>Equipo</th><th>Rareza</th><th>Acciones</th></tr></thead>
      <tbody><?php foreach($jugadores as $j): ?><tr><td><?php echo $j['id']; ?></td><td><?php echo htmlspecialchars($j['nombre']); ?></td><td><?php echo $j['numero']; ?></td><td><?php echo $j['posicion']; ?></td><td><?php echo htmlspecialchars($j['equipo_nombre']); ?></td><td><?php echo strtoupper($j['rareza']); ?></td><td><a href="/admin/editar_jugador?id=<?php echo $j['id']; ?>"><button type="button">Editar</button></a><a href="?eliminar=<?php echo $j['id']; ?>" onclick="return confirm('¿Eliminar?')"><button type="button">Eliminar</button></a></td></tr><?php endforeach; ?></tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>