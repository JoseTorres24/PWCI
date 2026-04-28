
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Álbum Mundial 2026</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/indexCSS.css">
</head>
<body>

<header>
    <video src="/ASSETS/FIFAcortinilla.mp4" class="vid-bg" autoplay loop muted></video>
    
    <div class="navigation">
        <div class="logo">Álbum Mundial 2026</div>
        
        <ul class="nav-items">
            <?php if (isset($_SESSION['usuario'])): ?>
                <li><a href="/dashboard">Ir al Dashboard</a></li>
            <?php else: ?>
                <li><a href="/acceso">Acceder</a></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="content">
        <h2>Colecciona las estampas digitales</h2>
        <p>Abre sobres, intercambia cartas y completa tu álbum del Mundial 2026.</p>
        
        <button class="btn" onclick="location.href='<?php echo isset($_SESSION['usuario']) ? "/dashboard" : "/acceso"; ?>'">
            Comenzar Colección
        </button>
    </div>
</header>

</body>
</html>