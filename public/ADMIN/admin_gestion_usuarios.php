<?php 
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

// ============================================
// PROCESAR ACCIONES
// ============================================

// Cambiar rol de usuario
if (isset($_GET['cambiar_rol'])) {
    $id = $_GET['cambiar_rol'];
    $nuevo_rol = $_GET['rol'] ?? 'coleccionista';
    
    if ($id != $_SESSION['usuario']['id']) {
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        $stmt->execute([$nuevo_rol, $id]);
        header('Location: /admin/gestion_usuarios?success=rol_actualizado');
        exit;
    } else {
        header('Location: /admin/gestion_usuarios?error=no_auto_cambio');
        exit;
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    if ($id != $_SESSION['usuario']['id']) {
        $stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        
        if ($usuario && $usuario['rol'] !== 'admin') {
            $stmt = $conn->prepare("DELETE FROM estampas_usuario WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $stmt = $conn->prepare("DELETE FROM ofertas WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: /admin/gestion_usuarios?success=usuario_eliminado');
            exit;
        } else {
            header('Location: /admin/gestion_usuarios?error=no_eliminar_admin');
            exit;
        }
    } else {
        header('Location: /admin/gestion_usuarios?error=no_auto_eliminar');
        exit;
    }
}

// ============================================
// OBTENER DATOS
// ============================================

// Lista de usuarios
$usuarios = $conn->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM estampas_usuario WHERE usuario_id = u.id) as total_estampas,
           (SELECT COUNT(*) FROM ofertas WHERE usuario_id = u.id AND estado = 'activa') as ofertas_activas
    FROM usuarios u 
    ORDER BY u.created_at DESC
")->fetchAll();

// Preparar datos para el modal (usando JSON)
$usuario_modal = null;
$coleccion_json = [];

if (isset($_GET['ver_coleccion'])) {
    $id = (int)$_GET['ver_coleccion'];
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario_modal = $stmt->fetch();
    
    if ($usuario_modal) {
        $stmt = $conn->prepare("
            SELECT j.*, e.nombre as equipo_nombre, eu.cantidad, eu.fecha_obtencion
            FROM estampas_usuario eu
            JOIN jugadores j ON eu.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            WHERE eu.usuario_id = ?
            ORDER BY e.nombre, j.numero
        ");
        $stmt->execute([$id]);
        $coleccion_usuario = $stmt->fetchAll();
        
        // Formatear colección para JSON
        foreach ($coleccion_usuario as $carta) {
            $badge_color = match($carta['rareza']) {
                'ssr' => '#f1c40f',
                'sr' => '#3498db',
                'r' => '#2ecc71',
                default => '#95a5a6'
            };
            $coleccion_json[] = [
                'id' => $carta['id'],
                'nombre' => $carta['nombre'],
                'numero' => $carta['numero'],
                'posicion' => $carta['posicion'],
                'equipo_nombre' => $carta['equipo_nombre'],
                'rareza' => $carta['rareza'],
                'rareza_color' => $badge_color,
                'cantidad' => $carta['cantidad'],
                'foto' => $carta['foto'] ?: '/Jugadores/silueta.png',
                'fecha_obtencion' => date('d/m/Y', strtotime($carta['fecha_obtencion']))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Usuarios - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --turquesa: #08b8b3;
        --turquesa-oscuro: #069792;
        --rojo: #f12b4d;
        --amarillo: #f7c600;
        --blanco: #ffffff;
        --texto: #163049;
        --texto-suave: #46637a;
        --panel: rgba(255, 255, 255, 0.95);
        --borde: rgba(255, 255, 255, 0.25);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        min-height: 100vh;
    }

    .admin-hero {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .hero-contenido {
        max-width: 1280px;
        margin: 0 auto;
        padding: 24px 32px;
    }

    .admin-etiqueta {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.75rem;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    h1 {
        color: white;
        font-size: 1.8rem;
        font-weight: 800;
    }

    .admin-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
    }

    .admin-nav a {
        padding: 10px 24px;
        border-radius: 40px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }

    .admin-nav a:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    .admin-nav a.activo {
        background: white;
        color: var(--turquesa);
    }

    .admin-nav a.salir {
        background: rgba(241, 43, 77, 0.9);
    }

    .admin-layout {
        max-width: 1280px;
        margin: 32px auto 48px;
        padding: 0 32px;
    }

    .card-panel {
        background: var(--panel);
        backdrop-filter: blur(12px);
        border-radius: 28px;
        padding: 28px;
        margin-bottom: 32px;
        border: 1px solid var(--borde);
    }

    .card-panel h2 {
        color: var(--turquesa);
        font-size: 1.4rem;
        margin-bottom: 20px;
        border-left: 4px solid var(--turquesa);
        padding-left: 16px;
    }

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
        padding: 10px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 30px;
        font-size: 0.9rem;
        outline: none;
    }

    .search-box input:focus {
        border-color: var(--turquesa);
    }

    .filter-select {
        padding: 10px 18px;
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

    .table-container {
        overflow-x: auto;
        border-radius: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 20px;
        overflow: hidden;
    }

    th {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
        padding: 14px 12px;
        font-weight: 700;
        font-size: 0.85rem;
        text-align: left;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        color: var(--texto);
        font-size: 0.85rem;
    }

    tr:hover td {
        background: #f8fafc;
    }

    .badge-admin {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: bold;
        background: linear-gradient(135deg, #f12b4d, #c0392b);
        color: white;
    }

    .badge-coleccionista {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: bold;
        background: linear-gradient(135deg, #08b8b3, #069792);
        color: white;
    }

    .btn-ver, .btn-rol, .btn-eliminar {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        margin: 0 3px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-ver {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
    }

    .btn-rol {
        background: linear-gradient(135deg, var(--amarillo), #e5b800);
        color: #3c3200;
    }

    .btn-eliminar {
        background: linear-gradient(135deg, var(--rojo), #c0392b);
        color: white;
    }

    .btn-ver:hover, .btn-rol:hover, .btn-eliminar:hover {
        transform: translateY(-1px);
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
    }

    .modal-content {
        background: linear-gradient(135deg, #f5f7fa, #ffffff);
        margin: 2% auto;
        padding: 25px;
        border-radius: 32px;
        width: 90%;
        max-width: 1100px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 15px;
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
    }

    .modal-header h2 {
        color: var(--turquesa);
        font-size: 1.6rem;
        margin: 0;
    }

    .close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
        background: #f1f5f9;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close:hover {
        background: var(--rojo);
        color: white;
        transform: scale(1.05);
    }

    .user-stats-modal {
        display: flex;
        gap: 25px;
        flex-wrap: wrap;
        margin-bottom: 25px;
        padding: 15px 20px;
        background: linear-gradient(135deg, rgba(8, 184, 179, 0.1), rgba(8, 184, 179, 0.05));
        border-radius: 20px;
    }

    .user-stats-modal .stat {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }

    .user-stats-modal .stat span:first-child {
        font-weight: 600;
        color: var(--texto-suave);
    }

    .user-stats-modal .stat span:last-child {
        font-weight: 800;
        color: var(--turquesa);
        font-size: 1rem;
    }

    .cartas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .carta-item {
        background: white;
        border-radius: 20px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        cursor: pointer;
    }

    .carta-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        border-color: var(--turquesa);
    }

    .carta-imagen {
        position: relative;
        width: 100%;
        aspect-ratio: 3/4;
        overflow: hidden;
        border-radius: 16px;
        margin-bottom: 12px;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    }

    .carta-imagen img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .carta-item:hover .carta-imagen img {
        transform: scale(1.05);
    }

    .cantidad-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: bold;
        backdrop-filter: blur(4px);
        z-index: 2;
    }

    .carta-info h4 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--texto);
        margin: 8px 0 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .carta-info .equipo {
        font-size: 0.7rem;
        color: var(--texto-suave);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin-bottom: 6px;
    }

    .carta-info .numero-posicion {
        font-size: 0.65rem;
        color: var(--texto-suave);
        margin-bottom: 8px;
    }

    .rareza-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: bold;
        margin-top: 5px;
    }

    .fecha-obtencion {
        font-size: 0.6rem;
        color: var(--texto-suave);
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px solid #e2e8f0;
    }

    .no-cartas {
        text-align: center;
        padding: 60px 20px;
        background: #f8fafc;
        border-radius: 24px;
        color: var(--texto-suave);
    }

    .btn-cerrar {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
        padding: 12px 32px;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        font-weight: 700;
        font-size: 1rem;
        margin-top: 20px;
        transition: all 0.3s;
    }

    .btn-cerrar:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(8, 184, 179, 0.3);
    }

    .modal-content::-webkit-scrollbar {
        width: 8px;
    }

    .modal-content::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 10px;
    }

    .modal-content::-webkit-scrollbar-thumb {
        background: var(--turquesa);
        border-radius: 10px;
    }

    .toast-message {
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .toast-message.success {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }

    .toast-message.error {
        background: linear-gradient(135deg, var(--rojo), #c0392b);
        color: white;
    }

    @media (max-width: 900px) {
        .admin-layout, .hero-contenido {
            padding-left: 24px;
            padding-right: 24px;
        }
        .filters-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .stats-badge {
            text-align: center;
        }
    }

    @media (max-width: 768px) {
        .card-panel {
            padding: 20px;
        }
        .admin-nav {
            flex-direction: column;
        }
        .admin-nav a {
            text-align: center;
        }
        th, td {
            padding: 10px 8px;
            font-size: 0.75rem;
        }
        .cartas-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        .modal-content {
            padding: 20px;
        }
    }

    @media (max-width: 640px) {
        .admin-layout {
            padding: 0 20px 30px;
        }
        .filter-group {
            flex-direction: column;
        }
        .filter-select {
            width: 100%;
        }
        .cartas-grid {
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        }
    }
</style>
</head>
<body>

<header class="admin-hero">
  <div class="hero-contenido">
    <p class="admin-etiqueta">FIFA World Cup 2026</p>
    <h1>Gestión de Usuarios</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard">Panel Principal</a>
      <a href="/admin/jugadores">Gestionar Jugadores</a>
      <a href="/admin/equipos">Gestionar Equipos</a>
      <a href="/admin/gestion_usuarios" class="activo">Gestionar Usuarios</a>
      <a href="/admin/logout" class="salir">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="admin-layout">
    <?php if (isset($_GET['success'])): ?>
        <div class="toast-message success">Operación exitosa</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="toast-message error">
            <?php 
                if ($_GET['error'] == 'no_auto_cambio') echo "No puedes cambiar tu propio rol";
                elseif ($_GET['error'] == 'no_auto_eliminar') echo "No puedes eliminarte a ti mismo";
                elseif ($_GET['error'] == 'no_eliminar_admin') echo "No puedes eliminar a otro administrador";
                else echo "Error en la operación";
            ?>
        </div>
    <?php endif; ?>

    <section class="card-panel">
        <h2>Usuarios Registrados</h2>
        
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" id="searchUsuario" placeholder="Buscar usuario...">
            </div>
            <div class="filter-group">
                <select id="filterRol" class="filter-select">
                    <option value="todos">Todos los roles</option>
                    <option value="admin">Administradores</option>
                    <option value="coleccionista">Coleccionistas</option>
                </select>
            </div>
            <div class="stats-badge">
                <span id="usuariosMostrados">0</span> de <span id="usuariosTotal">0</span> usuarios
            </div>
        </div>

        <div class="table-container">
            <table id="tablaUsuarios">
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Fecha Registro</th><th>Estampas</th><th>Ofertas</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $u): ?>
                    <tr data-id="<?php echo $u['id']; ?>" data-nombre="<?php echo strtolower(htmlspecialchars($u['nombre'])); ?>" data-rol="<?php echo $u['rol']; ?>">
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($u['correo']); ?></td>
                        <td><span class="<?php echo $u['rol'] === 'admin' ? 'badge-admin' : 'badge-coleccionista'; ?>"><?php echo $u['rol'] === 'admin' ? 'Administrador' : 'Coleccionista'; ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                        <td><?php echo $u['total_estampas']; ?></td>
                        <td><?php echo $u['ofertas_activas']; ?></td>
                        <td>
                            <a href="#" class="btn-ver ver-coleccion" data-id="<?php echo $u['id']; ?>" data-nombre="<?php echo htmlspecialchars($u['nombre']); ?>">Ver Colección</a>
                            <?php if ($u['id'] != $_SESSION['usuario']['id']): ?>
                                <a href="?cambiar_rol=<?php echo $u['id']; ?>&rol=<?php echo $u['rol'] === 'admin' ? 'coleccionista' : 'admin'; ?>" class="btn-rol" onclick="return confirm('¿Cambiar rol de este usuario?')">Cambiar Rol</a>
                                <a href="?eliminar=<?php echo $u['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar este usuario?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Modal vacío que se llena con JavaScript -->
<div id="modalColeccion" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Colección</h2>
            <span class="close">&times;</span>
        </div>
        <div id="modalBody">
            <div style="text-align: center; padding: 40px;">Cargando...</div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <button class="btn-cerrar" onclick="cerrarModal()">Cerrar</button>
        </div>
    </div>
</div>

<script>
    // Filtros de tabla
    const filas = Array.from(document.querySelectorAll('#tablaUsuarios tbody tr'));
    const searchInput = document.getElementById('searchUsuario');
    const filterRol = document.getElementById('filterRol');
    const usuariosMostradosSpan = document.getElementById('usuariosMostrados');
    const usuariosTotalSpan = document.getElementById('usuariosTotal');
    
    let busquedaActual = '';
    let filtroRolActual = 'todos';
    
    function actualizarFiltros() {
        let visibles = 0;
        filas.forEach(fila => {
            const nombre = fila.dataset.nombre || '';
            const rol = fila.dataset.rol || '';
            const coincideBusqueda = nombre.includes(busquedaActual.toLowerCase());
            const coincideRol = filtroRolActual === 'todos' || rol === filtroRolActual;
            fila.style.display = (coincideBusqueda && coincideRol) ? '' : 'none';
            if (coincideBusqueda && coincideRol) visibles++;
        });
        usuariosMostradosSpan.textContent = visibles;
        usuariosTotalSpan.textContent = filas.length;
    }
    
    searchInput.addEventListener('input', (e) => {
        busquedaActual = e.target.value;
        actualizarFiltros();
    });
    
    filterRol.addEventListener('change', (e) => {
        filtroRolActual = e.target.value;
        actualizarFiltros();
    });
    
    usuariosTotalSpan.textContent = filas.length;
    usuariosMostradosSpan.textContent = filas.length;
    
    // Modal con AJAX
    const modal = document.getElementById('modalColeccion');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalBody = document.getElementById('modalBody');
    const closeBtn = document.querySelector('.close');

    function cerrarModal() {
        modal.style.display = 'none';
        const url = new URL(window.location.href);
        url.searchParams.delete('ver_coleccion');
        window.history.pushState({}, '', url);
    }

    closeBtn.onclick = cerrarModal;
    window.onclick = function(event) {
        if (event.target == modal) cerrarModal();
    }

    // Cargar colección vía AJAX
    document.querySelectorAll('.ver-coleccion').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const userId = btn.dataset.id;
            const userName = btn.dataset.nombre;
            
            modalTitulo.innerHTML = `Colección de ${userName}`;
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;">Cargando colección...</div>';
            modal.style.display = 'block';
            
            try {
                const response = await fetch(`/backend/api/obtener_coleccion.php?usuario_id=${userId}`);
                const data = await response.json();
                
                console.log('Respuesta del servidor:', data); // Para depuración
                
                if (data.success && data.cartas && data.cartas.length > 0) {
                    let html = `
                        <div class="user-stats-modal">
                            <div class="stat"><span>Registrado:</span><span>${data.fecha_registro || 'Desconocida'}</span></div>
                            <div class="stat"><span>Total de estampas:</span><span>${data.total_estampas}</span></div>
                            <div class="stat"><span>Rol:</span><span>${data.usuario && data.usuario.rol === 'admin' ? 'Administrador' : 'Coleccionista'}</span></div>
                        </div>
                        <div class="cartas-grid">
                    `;
                    
                    data.cartas.forEach(carta => {
                        html += `
                            <div class="carta-item">
                                <div class="carta-imagen">
                                    <img src="${carta.foto}" alt="${carta.nombre}" onerror="this.src='/Jugadores/silueta.png'">
                                    <div class="cantidad-badge">x${carta.cantidad}</div>
                                </div>
                                <div class="carta-info">
                                    <h4 title="${carta.nombre}">${carta.nombre}</h4>
                                    <div class="equipo"> ${carta.equipo_nombre}</div>
                                    <div class="numero-posicion">#${carta.numero} - ${carta.posicion}</div>
                                    <span class="rareza-badge" style="background: ${carta.rareza_color}; color: white;">${carta.rareza.toUpperCase()}</span>
                                    <div class="fecha-obtencion"> ${carta.fecha_obtencion}</div>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = `
                        <div class="no-cartas">
                            <i></i>
                            <p>Este usuario aún no tiene estampas en su colección.</p>
                            <p style="font-size: 0.8rem; margin-top: 10px;">¡Anímale a abrir sobres en la tienda!</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                modalBody.innerHTML = '<div class="no-cartas"><i></i><p>Error al cargar la colección</p></div>';
            }
        });
    });
    // Si hay ver_coleccion en URL, abrir automáticamente
    <?php if ($usuario_modal && !empty($coleccion_json)): ?>
        const btnAuto = document.querySelector(`.ver-coleccion[data-id="${<?php echo $usuario_modal['id']; ?>}"]`);
        if (btnAuto) btnAuto.click();
    <?php endif; ?>
</script>

</body>
</html>