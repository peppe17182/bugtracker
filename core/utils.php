<?php
/**
 * Utility Functions - BugTracker Scuola
 * Funzioni di supporto per l'intera applicazione.
 */

/**
 * Invia una risposta JSON standardizzata e termina l'esecuzione.
 *
 * @param int $codiceHttp Il codice di stato HTTP (es. 200, 201, 400, 404, 500)
 * @param string $stato Lo stato dell'operazione ('successo' o 'errore')
 * @param string $messaggio Un messaggio descrittivo
 * @param mixed $dati Eventuali dati da restituire (array, oggetto, ecc.)
 */
function inviaRispostaJson($codiceHttp, $stato, $messaggio, $dati = null) {
    http_response_code($codiceHttp);
    
    $risposta = [
        "stato" => $stato,
        "messaggio" => $messaggio
    ];
    
    if ($dati !== null) {
        $risposta["dati"] = $dati;
    }
    
    echo json_encode($risposta);
    exit();
}

/**
 * Legge e decodifica il corpo della richiesta in formato JSON.
 *
 * @return array|null Ritorna un array associativo con i dati, o null se il JSON non è valido.
 */
function leggiDatiJson() {
    $json = file_get_contents("php://input");
    $dati = json_decode($json, true);
    
    // Controlla se ci sono stati errori nella decodifica del JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $dati;
}
