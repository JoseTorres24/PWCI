<?php

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM jugadores WHERE id = ?");
$stmt->execute([$id]);
$jugador = $stmt->fetch();

if (!$jugador) {
    header('Location: /admin/jugadores');
    exit;
}

$equipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $numero = $_POST['numero'];
    $posicion = $_POST['posicion'];
    $equipo_id = $_POST['equipo_id'];
    $rareza = $_POST['rareza'];
    $foto = trim($_POST['foto']);
    
    $stmt = $conn->prepare("UPDATE jugadores SET nombre=?, numero=?, posicion=?, equipo_id=?, rareza=?, foto=? WHERE id=?");
    $stmt->execute([$nombre, $numero, $posicion, $equipo_id, $rareza, $foto, $id]);
    header('Location: /admin/jugadores?success=editado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Jugador</title>
<link rel="stylesheet" href="/ADMIN/adminCSS.css">
</head>
<body>
<header class="admin-hero"><div class="hero-contenido"><h1>Editar Jugador</h1><nav class="admin-nav"><a href="/admin/jugadores">Volver</a></nav></div></header>
<main class="admin-layout"><section class="card-panel"><form method="POST">
<div class="campo"><label>Nombre</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($jugador['nombre']); ?>" required></div>
<div class="campo"><label>Número</label><input type="number" name="numero" value="<?php echo $jugador['numero']; ?>" required></div>
<div class="campo"><label>Posición</label><select name="posicion"><?php foreach(['POR','DEF','MED','DEL'] as $p): ?><option <?php echo $jugador['posicion']==$p?'selected':''; ?>><?php echo $p; ?></option><?php endforeach; ?></select></div>
<div class="campo"><label>Equipo</label><select name="equipo_id"><?php foreach($equipos as $e): ?><option value="<?php echo $e['id']; ?>" <?php echo $jugador['equipo_id']==$e['id']?'selected':''; ?>><?php echo htmlspecialchars($e['nombre']); ?></option><?php endforeach; ?></select></div>
<div class="campo"><label>Rareza</label><select name="rareza"><?php foreach(['comun','r','sr','ssr'] as $r): ?><option <?php echo $jugador['rareza']==$r?'selected':''; ?>><?php echo strtoupper($r); ?></option><?php endforeach; ?></select></div>
<div class="campo-completo"><label>Foto URL</label><input type="text" name="foto" value="<?php echo htmlspecialchars($jugador['foto']); ?>"></div>
<div class="form-acciones"><button type="submit">Actualizar</button></div>
</form></section></main>
</body>
</html>