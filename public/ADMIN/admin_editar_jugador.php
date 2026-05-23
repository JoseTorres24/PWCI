<?php
session_start();
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
    $actualizar_imagen = 0;
    $foto_blob = null;
    
    // Procesar imagen si se subió
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);
        
        if (in_array($mime_type, $allowed)) {
            $foto_blob = file_get_contents($_FILES['foto']['tmp_name']);
            $actualizar_imagen = 1;
        }
    }
    
    // ========== USAR SP_ACTUALIZAR_JUGADOR_CON_IMAGEN ==========
    $stmt = $conn->prepare("CALL SP_ACTUALIZAR_JUGADOR_CON_IMAGEN(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $nombre);
    $stmt->bindParam(3, $numero);
    $stmt->bindParam(4, $posicion);
    $stmt->bindParam(5, $equipo_id);
    $stmt->bindParam(6, $rareza);
    $stmt->bindParam(7, $foto_blob, $actualizar_imagen === 1 ? PDO::PARAM_LOB : PDO::PARAM_NULL);
    $stmt->bindParam(8, $actualizar_imagen);
    $stmt->execute();
    
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
<style>
    .current-image {
        margin: 15px 0;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        text-align: center;
    }
    .current-image img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    .current-image p {
        margin-top: 8px;
        font-size: 0.8rem;
        color: #46637a;
    }
</style>
</head>
<body>
<header class="admin-hero">
  <div class="hero-contenido">
    <h1>Editar Jugador</h1>
    <nav class="admin-nav">
      <a href="/admin/jugadores">← Volver a Jugadores</a>
    </nav>
  </div>
</header>
<main class="admin-layout">
  <section class="card-panel">
    <form method="POST" enctype="multipart/form-data">
      <div class="campo">
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?php echo htmlspecialchars($jugador['nombre']); ?>" required>
      </div>
      <div class="campo">
        <label>Número</label>
        <input type="number" name="numero" value="<?php echo $jugador['numero']; ?>" required>
      </div>
      <div class="campo">
        <label>Posición</label>
        <select name="posicion">
          <?php foreach(['POR','DEF','MED','DEL'] as $p): ?>
            <option <?php echo $jugador['posicion'] == $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label>Equipo</label>
        <select name="equipo_id">
          <?php foreach($equipos as $e): ?>
            <option value="<?php echo $e['id']; ?>" <?php echo $jugador['equipo_id'] == $e['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($e['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label>Rareza</label>
        <select name="rareza">
          <option value="comun" <?php echo $jugador['rareza'] == 'comun' ? 'selected' : ''; ?>>Común</option>
          <option value="r" <?php echo $jugador['rareza'] == 'r' ? 'selected' : ''; ?>>R</option>
          <option value="sr" <?php echo $jugador['rareza'] == 'sr' ? 'selected' : ''; ?>>SR</option>
          <option value="ssr" <?php echo $jugador['rareza'] == 'ssr' ? 'selected' : ''; ?>>SSR</option>
        </select>
      </div>
      <div class="campo-completo">
        <label>Fotografía del Jugador</label>
        <input type="file" name="foto" accept="image/*">
        <small>Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 2MB</small>
        
        <div class="current-image">
          <p>Foto actual:</p>
          <img src="/backend/api/obtener_imagen.php?tipo=jugador&id=<?php echo $jugador['id']; ?>" 
               alt="Foto actual"
               onerror="this.src='/Jugadores/silueta.png'">
          <p>Deja el campo vacío para mantener la imagen actual</p>
        </div>
      </div>
      <div class="form-acciones">
        <button type="submit">Actualizar Jugador</button>
      </div>
    </form>
  </section>
</main>
</body>
</html>