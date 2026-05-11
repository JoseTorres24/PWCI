<?php

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

$id = $_GET['id'] ?? 0;
$equipo = $conn->prepare("SELECT * FROM equipos WHERE id = ?");
$equipo->execute([$id]);
$equipo = $equipo->fetch();

if (!$equipo) {
    header('Location: /admin/equipos');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $grupo = trim($_POST['grupo']);
    $escudo = trim($_POST['escudo']);
    
    $stmt = $conn->prepare("UPDATE equipos SET nombre = ?, grupo = ?, escudo = ? WHERE id = ?");
    $stmt->execute([$nombre, $grupo, $escudo, $id]);
    header('Location: /admin/equipos?success=editado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Equipo</title>
<link rel="stylesheet" href="/ADMIN/adminCSS.css">
</head>
<body>
<header class="admin-hero">
  <div class="hero-contenido">
    <h1>Editar Equipo</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard">Panel Principal</a>
      <a href="/admin/equipos" class="activo">Volver a Equipos</a>
    </nav>
  </div>
</header>
<main class="admin-layout">
  <section class="card-panel">
    <form method="POST">
      <div class="campo"><label>Nombre</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($equipo['nombre']); ?>" required></div>
      <div class="campo"><label>Grupo</label><select name="grupo"><?php foreach(['A','B','C','D','E','F','G','H'] as $g): ?><option <?php echo $equipo['grupo']==$g?'selected':''; ?>><?php echo $g; ?></option><?php endforeach; ?></select></div>
      <div class="campo-completo"><label>URL Escudo</label><input type="text" name="escudo" value="<?php echo htmlspecialchars($equipo['escudo']); ?>"></div>
      <div class="form-acciones"><button type="submit">Actualizar</button></div>
    </form>
  </section>
</main>
</body>
</html>