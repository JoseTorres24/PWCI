<?php

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_equipo'])) {
    $nombre = trim($_POST['nombre']);
    $grupo = trim($_POST['grupo']);
    $escudo = trim($_POST['escudo']);
    
    if (!empty($nombre) && !empty($grupo)) {
        $stmt = $conn->prepare("INSERT INTO equipos (nombre, grupo, escudo) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $grupo, $escudo]);
        header('Location: /admin/equipos?success=creado');
        exit;
    }
}

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    // Verificar que no tenga jugadores
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jugadores WHERE equipo_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $conn->prepare("DELETE FROM equipos WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: /admin/equipos?success=eliminado');
        exit;
    } else {
        header('Location: /admin/equipos?error=tiene_jugadores');
        exit;
    }
}

// Obtener equipos
$equipos = $conn->query("SELECT * FROM equipos ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Equipos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/ADMIN/adminCSS.css">
</head>
<body>

<header class="admin-hero">
  <div class="hero-contenido">
    <p class="admin-etiqueta">FIFA World Cup 2026</p>
    <h1>Gestión de Equipos</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard">Panel Principal</a>
      <a href="/admin/jugadores">Gestionar Jugadores</a>
      <a href="/admin/equipos" class="activo">Gestionar Equipos</a>
      <a href="/admin/logout" class="salir">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="admin-layout">
  <?php if (isset($_GET['success'])): ?>
    <div style="background:#2ecc71;color:white;padding:12px;border-radius:12px;margin-bottom:20px;">Operación exitosa</div>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error'] == 'tiene_jugadores'): ?>
    <div style="background:#e83b57;color:white;padding:12px;border-radius:12px;margin-bottom:20px;">No se puede eliminar el equipo porque tiene jugadores asociados</div>
  <?php endif; ?>

  <section class="card-panel">
    <div class="encabezado-seccion">
      <h2>Registrar Equipo</h2>
    </div>

    <form method="POST">
      <div class="campo">
        <label>Nombre del País</label>
        <input type="text" name="nombre" required>
      </div>
      <div class="campo">
        <label>Grupo</label>
        <select name="grupo">
          <option>A</option><option>B</option><option>C</option><option>D</option>
          <option>E</option><option>F</option><option>G</option><option>H</option>
        </select>
      </div>
      <div class="campo-completo">
        <label>URL del Escudo</label>
        <input type="text" name="escudo" placeholder="/ASSETS/escudos/ejemplo.png">
      </div>
      <div class="form-acciones">
        <button type="submit" name="crear_equipo">Guardar Equipo</button>
      </div>
    </form>
  </section>

  <section class="card-panel">
    <h2>Equipos Registrados</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>ID</th><th>País</th><th>Grupo</th><th>Escudo</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach ($equipos as $equipo): ?>
          <tr>
            <td><?php echo $equipo['id']; ?></td>
            <td><?php echo htmlspecialchars($equipo['nombre']); ?></td>
            <td><?php echo $equipo['grupo']; ?></td>
            <td><?php echo htmlspecialchars($equipo['escudo']); ?></td>
            <td>
              <a href="/admin/editar_equipo?id=<?php echo $equipo['id']; ?>"><button type="button">Editar</button></a>
              <a href="?eliminar=<?php echo $equipo['id']; ?>" onclick="return confirm('¿Eliminar equipo?')"><button type="button">Eliminar</button></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

</body>
</html>