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

// ========== CREAR EQUIPO CON SP_CREAR_EQUIPO_CON_IMAGEN ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_equipo'])) {
    $nombre = trim($_POST['nombre']);
    $grupo = trim($_POST['grupo']);
    $escudo_blob = null;
    
    if (!empty($nombre) && !empty($grupo)) {
        // Procesar imagen si se subió
        if (isset($_FILES['escudo']) && $_FILES['escudo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['escudo']['tmp_name']);
            finfo_close($finfo);
            
            if (in_array($mime_type, $allowed)) {
                $escudo_blob = file_get_contents($_FILES['escudo']['tmp_name']);
            }
        }
        
        // Usar SP_CREAR_EQUIPO_CON_IMAGEN
        $stmt = $conn->prepare("CALL SP_CREAR_EQUIPO_CON_IMAGEN(?, ?, ?, @id, @success, @mensaje)");
        $stmt->bindParam(1, $nombre);
        $stmt->bindParam(2, $grupo);
        $stmt->bindParam(3, $escudo_blob, PDO::PARAM_LOB);
        $stmt->execute();
        
        $stmt = $conn->query("SELECT @id as id, @success as success, @mensaje as mensaje");
        $result = $stmt->fetch();
        $stmt->nextRowset();
        $stmt->nextRowset();
        
        if ($result['success'] == 1) {
            header('Location: /admin/equipos?success=creado');
        } else {
            header('Location: /admin/equipos?error=' . urlencode($result['mensaje']));
        }
        exit;
    }
}

// ========== ELIMINAR EQUIPO CON SP_ELIMINAR_EQUIPO_SEGURO ==========
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    $stmt = $conn->prepare("CALL SP_ELIMINAR_EQUIPO_SEGURO(?, @success, @mensaje)");
    $stmt->execute([$id]);
    
    $stmt = $conn->query("SELECT @success as success, @mensaje as mensaje");
    $result = $stmt->fetch();
    $stmt->nextRowset();
    $stmt->nextRowset();
    
    if ($result['success'] == 1) {
        header('Location: /admin/equipos?success=eliminado');
    } else {
        header('Location: /admin/equipos?error=' . urlencode($result['mensaje']));
    }
    exit;
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
<style>
    .filters-bar {
        background: white;
        border-radius: 20px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        justify-content: space-between;
    }
    .search-box {
        flex: 2;
        min-width: 200px;
    }
    .search-box input {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 30px;
        font-size: 0.9rem;
        outline: none;
    }
    .search-box input:focus {
        border-color: var(--turquesa);
    }
    .filter-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filter-select {
        padding: 10px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 30px;
        background: white;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .stats-badge {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
        padding: 8px 18px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 20px; overflow: hidden; }
    th { background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro)); color: white; padding: 12px; }
    td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
    tr:hover td { background: #f8fafc; }
    .team-escudo { width: 50px; height: 40px; object-fit: cover; border-radius: 8px; background: #f0f0f0; }
    .btn-escudo { padding: 4px 8px; font-size: 0.7rem; background: #3498db; color: white; border: none; border-radius: 12px; cursor: pointer; text-decoration: none; display: inline-block; }
</style>
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
      <a href="/admin/gestion_usuarios">Gestion Usuarios</a>
      <a href="/admin/logout" class="salir">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="admin-layout">
  <?php if (isset($_GET['success'])): ?>
    <div class="toast-message success" style="display:block; margin-bottom:20px;">Operación exitosa</div>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error'] == 'tiene_jugadores'): ?>
    <div class="toast-message error" style="display:block; margin-bottom:20px;">No se puede eliminar el equipo porque tiene jugadores asociados</div>
  <?php endif; ?>

  <section class="card-panel">
    <div class="encabezado-seccion">
      <h2>Registrar Equipo</h2>
    </div>

    <form method="POST" enctype="multipart/form-data">
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
        <label>📸 Escudo del Equipo</label>
        <input type="file" name="escudo" accept="image/*">
        <small>Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 2MB</small>
      </div>
      <div class="form-acciones">
        <button type="submit" name="crear_equipo">Guardar Equipo</button>
      </div>
    </form>
  </section>

  <section class="card-panel">
    <h2>Equipos Registrados</h2>
    
    <div class="filters-bar">
      <div class="search-box">
        <input type="text" id="searchEquipo" placeholder="Buscar equipo...">
      </div>
      <div class="filter-group">
        <select id="filterGrupo" class="filter-select">
          <option value="todos">Todos los grupos</option>
          <option value="A">Grupo A</option><option value="B">Grupo B</option>
          <option value="C">Grupo C</option><option value="D">Grupo D</option>
          <option value="E">Grupo E</option><option value="F">Grupo F</option>
          <option value="G">Grupo G</option><option value="H">Grupo H</option>
        </select>
      </div>
      <div class="stats-badge" id="statsEquipos">
        <span id="equiposMostrados">0</span> de <span id="equiposTotal">0</span>
      </div>
    </div>

    <div class="table-wrap">
      <table id="tablaEquipos">
        <thead>
          <tr><th>ID</th><th>País</th><th>Grupo</th><th>Escudo</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach ($equipos as $equipo): ?>
          <tr data-id="<?php echo $equipo['id']; ?>" data-nombre="<?php echo strtolower(htmlspecialchars($equipo['nombre'])); ?>" data-grupo="<?php echo $equipo['grupo']; ?>">
            <td><?php echo $equipo['id']; ?></td>
            <td><?php echo htmlspecialchars($equipo['nombre']); ?></td>
            <td><?php echo $equipo['grupo']; ?></td>
            <td>
              <img src="/backend/api/obtener_imagen.php?tipo=equipo&id=<?php echo $equipo['id']; ?>" class="team-escudo" 
                   onerror="this.src='/ASSETS/escudos/default.png'">
            </td>
            <td>
              <a href="/admin/editar_equipo?id=<?php echo $equipo['id']; ?>"><button type="button">✏️ Editar</button></a>
              <a href="?eliminar=<?php echo $equipo['id']; ?>" onclick="return confirm('¿Eliminar equipo?')"><button type="button">🗑️ Eliminar</button></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
    const filas = Array.from(document.querySelectorAll('#tablaEquipos tbody tr'));
    const searchInput = document.getElementById('searchEquipo');
    const filterGrupo = document.getElementById('filterGrupo');
    const equiposMostradosSpan = document.getElementById('equiposMostrados');
    const equiposTotalSpan = document.getElementById('equiposTotal');
    
    let busquedaActual = '';
    let filtroGrupoActual = 'todos';
    
    function actualizarFiltros() {
        let visibles = 0;
        filas.forEach(fila => {
            const nombre = fila.dataset.nombre || '';
            const grupo = fila.dataset.grupo || '';
            const coincideBusqueda = nombre.includes(busquedaActual.toLowerCase());
            const coincideGrupo = filtroGrupoActual === 'todos' || grupo === filtroGrupoActual;
            fila.style.display = (coincideBusqueda && coincideGrupo) ? '' : 'none';
            if (coincideBusqueda && coincideGrupo) visibles++;
        });
        equiposMostradosSpan.textContent = visibles;
        equiposTotalSpan.textContent = filas.length;
    }
    
    searchInput.addEventListener('input', (e) => { busquedaActual = e.target.value; actualizarFiltros(); });
    filterGrupo.addEventListener('change', (e) => { filtroGrupoActual = e.target.value; actualizarFiltros(); });
    equiposTotalSpan.textContent = filas.length;
    equiposMostradosSpan.textContent = filas.length;
</script>

</body>
</html>