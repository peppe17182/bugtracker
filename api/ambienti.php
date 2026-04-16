<?php
/**
 * API Ambienti - BugTracker Scuola
 * Gestisce le operazioni CRUD per la tabella 'ambienti'.
 * Come da specifiche, gestisce solo le richieste GET.
 */

// Recuperiamo il metodo HTTP
$metodo = $_SERVER['REQUEST_METHOD'];

// Otteniamo la connessione al database
$conn = getConnection();

// L'URL è stato diviso in index.php e salvato in $urlParts
// $urlParts[0] è 'ambienti'
// $urlParts[1] potrebbe essere l'ID dell'ambiente (es. /ambienti/1)
$id_ambiente = isset($urlParts[1]) && is_numeric($urlParts[1]) ? intval($urlParts[1]) : null;

if ($metodo === 'GET') {
    if ($id_ambiente) {
        // GET /ambienti/{id} - Recupera un singolo ambiente
        $query = "SELECT id, nome, piano, tipo, creato_il FROM ambienti WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id_ambiente, PDO::PARAM_INT);
        $stmt->execute();
        
        $ambiente = $stmt->fetch();
        
        if ($ambiente) {
            inviaRispostaJson(200, 'successo', 'Ambiente recuperato con successo.', $ambiente);
        } else {
            inviaRispostaJson(404, 'errore', 'Ambiente non trovato.');
        }
    } else {
        // GET /ambienti - Recupera tutti gli ambienti
        $query = "SELECT id, nome, piano, tipo, creato_il FROM ambienti ORDER BY piano, nome";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $ambienti = $stmt->fetchAll();
        inviaRispostaJson(200, 'successo', 'Elenco ambienti recuperato.', $ambienti);
    }
} else {
    // Se il metodo non è GET, restituiamo un errore 405 Method Not Allowed
    inviaRispostaJson(405, 'errore', 'Metodo non consentito. Questa risorsa supporta solo richieste GET.');
}
