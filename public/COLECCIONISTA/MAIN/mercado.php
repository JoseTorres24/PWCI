<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: /acceso");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/middleware/AuthMiddleware.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/middleware/LoggingMiddleware.php';

$usuario = AuthMiddleware::verificarAutenticacion();

$db = new Database();
$conn = $db->connect();

$usuario_id = $_SESSION['usuario']['id'];
$logger = new LoggingMiddleware($conn, $usuario_id);

// ========== USAR SP_OBTENER_OFERTAS_ACTIVAS ==========
$stmt = $conn->prepare("CALL SP_OBTENER_OFERTAS_ACTIVAS(?)");
$stmt->execute([$usuario_id]);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset(); // Limpiar resultados

// ========== USAR SP_OBTENER_MIS_JUGADORES_DISPONIBLES ==========
$stmt = $conn->prepare("CALL SP_OBTENER_MIS_JUGADORES_DISPONIBLES(?)");
$stmt->execute([$usuario_id]);
$mis_jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset(); // Limpiar resultados

// ========== USAR SP_OBTENER_TODOS_JUGADORES ==========
$stmt = $conn->prepare("CALL SP_OBTENER_TODOS_JUGADORES()");
$stmt->execute();
$todos_jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset(); // Limpiar resultados
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mercado - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/mercadoCSS.css">
</head>
<body>
  <div class="page-shell">
    <header class="topbar">
      <div class="brand">FIFA World Cup 2026</div>
      <nav class="topnav">
        <a href="/dashboard">Inicio</a>
        <a href="/album">Mi Álbum</a>
        <a href="/tienda">Tienda</a>
        <a class="activo" href="/mercado">Mercado</a>
        <a href="/perfil">Perfil</a>
      </nav>
    </header>

    <main class="content-wrap">
      <section class="hero-card">
        <div>
          <p class="eyebrow">Intercambios entre usuarios</p>
          <h1>Mercado de Intercambios</h1>
          <p class="hero-text">Encuentra ofertas activas y propón un intercambio para completar tu álbum.</p>
        </div>
        <a class="home-button" href="/dashboard">Regresar al inicio</a>
      </section>

      <!-- Botón para crear oferta -->
      <div class="create-offer-container">
        <button id="btnCrearOferta" class="btn-primary">Crear nueva oferta</button>
      </div>

      <!-- Filtro de búsqueda -->
      <div class="filters-bar">
        <div class="search-box">
          <input type="text" id="searchOferta" placeholder="Buscar oferta por usuario o jugador...">
        </div>
        <div class="stats-badge" id="statsOfertas">
          <span id="ofertasMostradas">0</span> de <span id="ofertasTotal">0</span> ofertas
        </div>
      </div>

      <!-- Lista de ofertas activas -->
      <section class="market-list" id="ofertasContainer">
        <h2 class="section-title">Ofertas disponibles</h2>
        <?php if (empty($ofertas)): ?>
          <p class="no-offers">No hay ofertas activas. ¡Sé el primero en crear una!</p>
        <?php else: ?>
          <?php foreach ($ofertas as $oferta): ?>
          <article class="offer-card <?php echo $oferta['es_mia'] ? 'mi-oferta' : ''; ?>" 
                   data-oferta-id="<?php echo $oferta['id']; ?>" 
                   data-usuario="<?php echo strtolower($oferta['usuario_nombre']); ?>" 
                   data-jugador="<?php echo strtolower($oferta['jugador_ofrece_nombre']); ?>">
            <p class="offer-user">
              <strong><?php echo htmlspecialchars($oferta['usuario_nombre']); ?></strong>
              <?php if ($oferta['es_mia']): ?>
                <span class="badge-mia">(Tu oferta)</span>
              <?php else: ?>
                <span>ofrece intercambio</span>
              <?php endif; ?>
            </p>
            <div class="offer-grid">
              <div class="offer-box rareza-<?php echo $oferta['ofrece_rareza']; ?>">
                <span class="offer-label">Ofrece</span>
                <strong><?php echo htmlspecialchars($oferta['jugador_ofrece_nombre']); ?></strong>
                <small>x<?php echo $oferta['cantidad_ofrece']; ?></small>
              </div>
              <div class="offer-arrow">→</div>
              <div class="offer-box dark">
                <span class="offer-label">🔍 Busca</span>
                <strong><?php echo $oferta['jugador_pide_nombre'] ?? 'Cualquier estampa'; ?></strong>
                <?php if ($oferta['jugador_pide_nombre']): ?>
                  <small>x<?php echo $oferta['cantidad_pide']; ?></small>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!$oferta['es_mia']): ?>
              <button class="btn-accept" data-oferta-id="<?php echo $oferta['id']; ?>">Aceptar intercambio</button>
            <?php else: ?>
              <button class="btn-cancel" data-oferta-id="<?php echo $oferta['id']; ?>">Cancelar oferta</button>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <!-- Modal para crear oferta -->
  <div id="modalOferta" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Crear nueva oferta</h2>
      <form id="formOferta">
        <div class="form-group">
          <label>¿Qué ofreces?</label>
          <select id="jugador_ofrece" required>
            <option value="">Selecciona un jugador</option>
            <?php foreach ($mis_jugadores as $jugador): ?>
            <option value="<?php echo $jugador['id']; ?>" data-cantidad="<?php echo $jugador['cantidad']; ?>">
              <?php echo htmlspecialchars($jugador['nombre']); ?> (x<?php echo $jugador['cantidad']; ?>) - <?php echo strtoupper($jugador['rareza']); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <small class="form-hint">Solo puedes ofrecer jugadores que tengas en tu colección</small>
        </div>
        <div class="form-group">
          <label>Cantidad a ofrecer</label>
          <input type="number" id="cantidad_ofrece" min="1" value="1" required>
        </div>
        <div class="form-group">
          <label>🔍 ¿Qué buscas? (opcional)</label>
          <select id="jugador_pide">
            <option value="">Cualquier jugador</option>
            <?php foreach ($todos_jugadores as $jugador): ?>
            <option value="<?php echo $jugador['id']; ?>">
              <?php echo htmlspecialchars($jugador['nombre']); ?> (<?php echo strtoupper($jugador['rareza']); ?>)
            </option>
            <?php endforeach; ?>
          </select>
          <small class="form-hint">Deja vacío si aceptas cualquier estampa a cambio</small>
        </div>
        <div class="form-group">
          <label>Cantidad que buscas</label>
          <input type="number" id="cantidad_pide" min="1" value="1">
        </div>
        <button type="submit" class="btn-primary btn-submit">Publicar oferta</button>
      </form>
    </div>
  </div>

  <div id="toastMessage" class="toast-message"></div>

  <script>
    // Filtro de búsqueda en tiempo real
    const searchInput = document.getElementById('searchOferta');
    const ofertas = Array.from(document.querySelectorAll('.offer-card'));
    const ofertasMostradasSpan = document.getElementById('ofertasMostradas');
    const ofertasTotalSpan = document.getElementById('ofertasTotal');
    
    function filtrarOfertas() {
      const busqueda = searchInput.value.toLowerCase();
      let visibles = 0;
      
      ofertas.forEach(oferta => {
        const usuario = oferta.dataset.usuario || '';
        const jugador = oferta.dataset.jugador || '';
        
        if (usuario.includes(busqueda) || jugador.includes(busqueda)) {
          oferta.style.display = '';
          visibles++;
        } else {
          oferta.style.display = 'none';
        }
      });
      
      ofertasMostradasSpan.textContent = visibles;
      ofertasTotalSpan.textContent = ofertas.length;
    }
    
    searchInput.addEventListener('input', filtrarOfertas);
    ofertasTotalSpan.textContent = ofertas.length;
    ofertasMostradasSpan.textContent = ofertas.length;
    
    // Modal
    const modal = document.getElementById('modalOferta');
    const btnCrear = document.getElementById('btnCrearOferta');
    const spanClose = document.getElementsByClassName('close')[0];
    
    btnCrear.onclick = () => modal.style.display = 'block';
    spanClose.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };
    
    function showToast(message, type) {
      const toast = document.getElementById('toastMessage');
      toast.textContent = message;
      toast.className = `toast-message ${type}`;
      toast.style.display = 'block';
      setTimeout(() => {
        toast.style.display = 'none';
      }, 3000);
    }
    
    // Validar cantidad máxima al seleccionar jugador
    const selectOfrece = document.getElementById('jugador_ofrece');
    const cantidadOfrece = document.getElementById('cantidad_ofrece');
    
    selectOfrece.addEventListener('change', () => {
      const selected = selectOfrece.options[selectOfrece.selectedIndex];
      const maxCantidad = selected.dataset.cantidad || 1;
      cantidadOfrece.max = maxCantidad;
      if (parseInt(cantidadOfrece.value) > maxCantidad) {
        cantidadOfrece.value = maxCantidad;
      }
    });
    
    // Crear oferta
    document.getElementById('formOferta').onsubmit = async (e) => {
      e.preventDefault();
      
      const formData = new FormData();
      formData.append('jugador_ofrece', document.getElementById('jugador_ofrece').value);
      formData.append('cantidad_ofrece', document.getElementById('cantidad_ofrece').value);
      formData.append('jugador_pide', document.getElementById('jugador_pide').value || '');
      formData.append('cantidad_pide', document.getElementById('cantidad_pide').value);
      
      try {
        const response = await fetch('/backend/api/crear_oferta.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        
        if (data.success) {
          showToast(data.mensaje, 'success');
          modal.style.display = 'none';
          setTimeout(() => location.reload(), 1500);
        } else {
          showToast(data.error, 'error');
        }
      } catch (error) {
        showToast('Error al crear la oferta', 'error');
      }
    };
    
    // Aceptar oferta
    document.querySelectorAll('.btn-accept').forEach(btn => {
      btn.onclick = async () => {
        const ofertaId = btn.dataset.ofertaId;
        if (!confirm('¿Seguro que quieres aceptar este intercambio?')) return;
        
        const formData = new FormData();
        formData.append('oferta_id', ofertaId);
        
        try {
          const response = await fetch('/backend/api/aceptar_oferta.php', {
            method: 'POST',
            body: formData
          });
          const data = await response.json();
          
          if (data.success) {
            showToast(data.mensaje, 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showToast(data.error, 'error');
          }
        } catch (error) {
          showToast('Error al aceptar la oferta', 'error');
        }
      };
    });
    
    // Cancelar oferta propia
    document.querySelectorAll('.btn-cancel').forEach(btn => {
      btn.onclick = async () => {
        const ofertaId = btn.dataset.ofertaId;
        if (!confirm('¿Seguro que quieres cancelar esta oferta?')) return;
        
        const formData = new FormData();
        formData.append('oferta_id', ofertaId);
        
        try {
          const response = await fetch('/backend/api/cancelar_oferta.php', {
            method: 'POST',
            body: formData
          });
          const data = await response.json();
          
          if (data.success) {
            showToast(data.mensaje, 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showToast(data.error, 'error');
          }
        } catch (error) {
          showToast('Error al cancelar la oferta', 'error');
        }
      };
    });
  </script>
</body>
</html>