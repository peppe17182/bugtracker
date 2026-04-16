<?php
/**
 * Front Controller - BugTracker Scuola
 * Riceve tutte le richieste e le instrada al file API corretto.
 */

// Impostiamo gli header globali per JSON e CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestione della richiesta OPTIONS (Preflight CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Recuperiamo l'URL richiesto (passato tramite .htaccess)
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$urlParts = explode('/', $url);

// Il primo parametro dell'URL è la risorsa (es. utenti, ambienti, segnalazioni)
$resource = $urlParts[0] ?? '';

// Se la risorsa è vuota, restituiamo un messaggio di benvenuto
if (empty($resource)) {
    http_response_code(200);
    echo json_encode([
        "stato" => "successo",
        "messaggio" => "Benvenuto nell'API del BugTracker Scuola"
    ]);
    exit();
}

// Mappiamo le risorse consentite ai rispettivi file nella cartella /api/
$allowedResources = ['utenti', 'ambienti', 'segnalazioni'];

if (in_array($resource, $allowedResources)) {
    $apiFile = __DIR__ . '/api/' . $resource . '.php';
    
    // Includiamo le utility generali e il database prima di chiamare l'API
    @include_once __DIR__ . '/core/utils.php';
    @include_once __DIR__ . '/core/database.php';
    
    // INNOVAZIONE: Gestione delle autorizzazioni sulle richieste
    // Le richieste GET sono pubbliche, le altre (POST, PUT, PATCH, DELETE) richiedono un token
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';
        
        // Token di esempio hardcodato (in un progetto reale starebbe nel DB o in config.ini)
        $tokenValido = 'Bearer token_segreto_5CII';
        
        if ($authHeader !== $tokenValido) {
            http_response_code(401); // Unauthorized
            echo json_encode(["stato" => "errore", "messaggio" => "Non autorizzato. Token mancante o non valido."]);
            exit();
        }
    }
    
    if (file_exists($apiFile)) {
        // Passiamo il controllo al file specifico della risorsa
        require_once $apiFile;
    } else {
        http_response_code(501); // Not Implemented
        echo json_encode(["stato" => "errore", "messaggio" => "Endpoint per la risorsa '$resource' non ancora implementato."]);
    }
} else {
    // Risorsa non trovata
    http_response_code(404);
    echo json_encode(["stato" => "errore", "messaggio" => "Risorsa non trovata. Endpoint non valido."]);
}
