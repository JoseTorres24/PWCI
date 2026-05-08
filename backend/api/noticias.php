<?php
header('Content-Type: application/json');

// ============================================
// API DE NOTICIAS - MULTIPLES FUENTES
// ============================================

function obtenerNoticiasDeRSS($rss_url) {
    $url = 'https://api.rss2json.com/v1/api.json?rss_url=' . urlencode($rss_url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['items']) && count($data['items']) > 0) {
            return $data['items'];
        }
    }
    return null;
}

function obtenerNoticiasDeGoogleNews() {
    // Google News RSS para fútbol (alternativa)
    $url = 'https://news.google.com/rss/search?q=fútbol+mundial+2026&hl=es&gl=ES&ceid=ES:es';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        // Parsear XML manualmente
        $xml = simplexml_load_string($response);
        if ($xml && isset($xml->channel->item)) {
            $noticias = [];
            $i = 0;
            foreach ($xml->channel->item as $item) {
                if ($i >= 5) 
                    break;
                $noticias[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'pubDate' => (string)$item->pubDate
                ];
                $i++;
            }
            return $noticias;
        }
    }
    return null;
}

// ============================================
// FUENTES DE NOTICIAS (orden de prioridad)
// ============================================

// Lista de RSS confiables
$fuentes_rss = [
    'https://espndeportes.espn.com/espn/rss/soccer/news',
    'https://www.marca.com/rss/futbol.html',
    'https://as.com/rss/tag/futbol.xml',
    'https://www.sport.es/es/rss/futbol/rss.xml'
];

$noticias = null;

// Intentar cada fuente RSS
foreach ($fuentes_rss as $rss_url) {
    $noticias = obtenerNoticiasDeRSS($rss_url);
    if ($noticias && count($noticias) > 0) {
        break;
    }
}

// Si ninguna fuente RSS funciona, intentar Google News
if (!$noticias || count($noticias) === 0) {
    $noticias = obtenerNoticiasDeGoogleNews();
}

// ============================================
// NOTICIAS DE RESPALDO (si todo falla)
// ============================================
if (!$noticias || count($noticias) === 0) {
    $noticias = [
        [
            'title' => ' ¡Prepárate para el Mundial 2026! Las selecciones ya se están preparando',
            'link' => '#',
            'pubDate' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'México: Santiago Giménez lidera la delantera en los últimos amistosos',
            'link' => '#',
            'pubDate' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Lionel Messi: "Quiero estar en el Mundial 2026"',
            'link' => '#',
            'pubDate' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Brasil y Francia se enfrentan en amistoso previo al Mundial',
            'link' => '#',
            'pubDate' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Ya están disponibles los paquetes de viaje para el Mundial 2026',
            'link' => '#',
            'pubDate' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Las 16 sedes confirmadas para el Mundial 2026',
            'link' => '#',
            'pubDate' => date('Y-m-d H:i:s')
        ]
    ];
}

// Limitar a máximo 6 noticias
$noticias = array_slice($noticias, 0, 6);

// Formatear respuesta
$response = [];
foreach ($noticias as $noticia) {
    $response[] = [
        'title' => htmlspecialchars_decode($noticia['title']),
        'link' => $noticia['link'] ?? '#',
        'date' => $noticia['pubDate'] ?? date('Y-m-d H:i:s')
    ];
}

echo json_encode([
    'success' => true,
    'noticias' => $response,
    'fuente' => count($response) > 0 ? 'actualizada' : 'respaldo'
]);
?>