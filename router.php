<?php
session_start();

// Obtener la URI limpia
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/'); // Eliminar slash inicial

// ========== RUTAS DEL BACKEND (API) - Deben ir PRIMERO ==========
if (strpos($uri, 'backend/') === 0) {
    // Construir la ruta al archivo
    $backendFile = __DIR__ . '/' . $uri;
    
    // Si la URI no tiene extensión, agregar .php
    if (!pathinfo($backendFile, PATHINFO_EXTENSION)) {
        $backendFile .= '.php';
    }
    
    // Verificar si el archivo existe
    if (file_exists($backendFile)) {
        require $backendFile;
        exit;
    } else {
        // Depuración: mostrar qué archivo se está buscando
        header('HTTP/1.0 404 Not Found');
        echo "404 - Archivo no encontrado: " . htmlspecialchars($backendFile);
        exit;
    }
}

// ========== RUTAS DE ARCHIVOS ESTÁTICOS ==========
$staticFile = 'public/' . $uri;
if (file_exists($staticFile) && is_file($staticFile)) {
    $extension = pathinfo($staticFile, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon'
    ];
    
    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
        readfile($staticFile);
        exit;
    }
}

// ========== RUTAS DEL FRONTEND ==========
if (empty($uri)) {
    require 'public/COLECCIONISTA/MAIN/index.php';
    exit;
}

// Mapeo de rutas frontend
$frontendRoutes = [
    'acceso' => 'public/COLECCIONISTA/MAIN/acceso.php',
    'dashboard' => 'public/COLECCIONISTA/MAIN/dashboard.php',
    'album' => 'public/COLECCIONISTA/MAIN/album.php',
    'tienda' => 'public/COLECCIONISTA/MAIN/tienda.php',
    'mercado' => 'public/COLECCIONISTA/MAIN/mercado.php',
    'perfil' => 'public/COLECCIONISTA/MAIN/perfil.php',
    'equipo' => 'public/COLECCIONISTA/MAIN/equipo.php',
    'logout' => 'public/COLECCIONISTA/MAIN/logout.php',
];

if (isset($frontendRoutes[$uri])) {
    require $frontendRoutes[$uri];
    exit;
}

// Si la URI empieza con COLECCIONISTA o MAIN
if (strpos($uri, 'COLECCIONISTA/') === 0 || strpos($uri, 'MAIN/') === 0) {
    $phpFile = 'public/' . $uri . '.php';
    if (file_exists($phpFile)) {
        require $phpFile;
        exit;
    }
}

// Si no encuentra nada
http_response_code(404);
echo "<h1>404 - Página no encontrada</h1>";
echo "<p>La página que buscas no existe: /$uri</p>";
echo "<p>Archivo buscado: " . htmlspecialchars($backendFile ?? 'N/A') . "</p>";