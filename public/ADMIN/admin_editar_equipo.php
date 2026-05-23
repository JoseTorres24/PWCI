<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM equipos WHERE id = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch();

if (!$equipo) {
    header('Location: /admin/equipos');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $grupo = trim($_POST['grupo']);
    $actualizar_imagen = 0;
    $escudo_blob = null;
    
    // Procesar imagen si se subió
    if (isset($_FILES['escudo']) && $_FILES['escudo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['escudo']['tmp_name']);
        finfo_close($finfo);
        
        if (in_array($mime_type, $allowed)) {
            $escudo_blob = file_get_contents($_FILES['escudo']['tmp_name']);
            $actualizar_imagen = 1;
        }
    }
    
    // ========== USAR SP_ACTUALIZAR_EQUIPO_CON_IMAGEN ==========
    $stmt = $conn->prepare("CALL SP_ACTUALIZAR_EQUIPO_CON_IMAGEN(?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $nombre);
    $stmt->bindParam(3, $grupo);
    $stmt->bindParam(4, $escudo_blob, $actualizar_imagen === 1 ? PDO::PARAM_LOB : PDO::PARAM_NULL);
    $stmt->bindParam(5, $actualizar_imagen);
    $stmt->execute();
    
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
<style>
    .current-image {
        margin: 15px 0;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        text-align: center;
    }
    .current-image img {
        max-width: 150px;
        max-height: 100px;
        object-fit: cover;
        border-radius: 8px;
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
    <h1>✏️ Editar Equipo</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard">Panel Principal</a>
      <a href="/admin/equipos" class="activo">← Volver a Equipos</a>
    </nav>
  </div>
</header>
<main class="admin-layout">
  <section class="card-panel">
    <form method="POST" enctype="multipart/form-data">
      <div class="campo">
        <label>Nombre del País</label>
        <input type="text" name="nombre" value="<?php echo htmlspecialchars($equipo['nombre']); ?>" required>
      </div>
      <div class="campo">
        <label>Grupo</label>
        <select name="grupo">
          <?php foreach(['A','B','C','D','E','F','G','H'] as $g): ?>
            <option <?php echo $equipo['grupo'] == $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo-completo">
        <label>📸 Escudo del Equipo</label>
        <input type="file" name="escudo" accept="image/*">
        <small>Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 2MB</small>
        
        <div class="current-image">
          <p>Escudo actual:</p>
          <img src="/backend/api/obtener_imagen.php?tipo=equipo&id=<?php echo $equipo['id']; ?>" 
               alt="Escudo actual"
               onerror="this.src='/ASSETS/escudos/default.png'">
          <p>Deja el campo vacío para mantener la imagen actual</p>
        </div>
      </div>
      <div class="form-acciones">
        <button type="submit">Actualizar Equipo</button>
      </div>
    </form>
  </section>
</main>
</body>
</html>