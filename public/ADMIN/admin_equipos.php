<?php
session_start();
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
        transition: all 0.3s;
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
        outline: none;
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
    .table-wrap {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    th {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
        cursor: pointer;
        user-select: none;
    }
    th:hover {
        opacity: 0.9;
    }
    tr:hover td {
        background: #f8fafc;
    }
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
    
    <!-- Barra de filtros -->
    <div class="filters-bar">
      <div class="search-box">
        <input type="text" id="searchEquipo" placeholder="🔍 Buscar equipo...">
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
          <tr><th data-col="id">ID</th><th data-col="nombre">País</th><th data-col="grupo">Grupo</th><th>Escudo</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach ($equipos as $equipo): ?>
          <tr data-id="<?php echo $equipo['id']; ?>" data-nombre="<?php echo strtolower(htmlspecialchars($equipo['nombre'])); ?>" data-grupo="<?php echo $equipo['grupo']; ?>">
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
            
            if (coincideBusqueda && coincideGrupo) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });
        
        equiposMostradosSpan.textContent = visibles;
        equiposTotalSpan.textContent = filas.length;
    }
    
    searchInput.addEventListener('input', (e) => {
        busquedaActual = e.target.value;
        actualizarFiltros();
    });
    
    filterGrupo.addEventListener('change', (e) => {
        filtroGrupoActual = e.target.value;
        actualizarFiltros();
    });
    
    equiposTotalSpan.textContent = filas.length;
    equiposMostradosSpan.textContent = filas.length;
</script>

</body>
</html>