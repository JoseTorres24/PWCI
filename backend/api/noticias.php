<?php
header('Content-Type: application/json');

// Usar RSS2JSON gratuito sin API key
function getNoticiasFutbol() {
    $url = 'https://api.rss2json.com/v1/api.json?rss_url=' . urlencode('https://www.espn.com/espn/rss/football/news');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['items'])) {
        
            return array_slice($data['items'], 0, 5);
        }
    }
    
    return [
        ['title' => 'Mundial 2026: Todo listo para la inauguración', 'link' => '#'],
        ['title' => 'México se prepara para el partido inaugural', 'link' => '#'],
        ['title' => 'Las selecciones ya están en sus sedes', 'link' => '#'],
    ];
}

$noticias = getNoticiasFutbol();
echo json_encode(['success' => true, 'noticias' => $noticias]);
?>