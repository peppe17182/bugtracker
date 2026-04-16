<?php
/**
 * API Utenti - BugTracker Scuola
 * Gestisce le operazioni CRUD per la tabella 'utenti'.
 */

// Recuperiamo il metodo HTTP (GET, POST, DELETE)
$metodo = $_SERVER['REQUEST_METHOD'];

// Otteniamo la connessione al database
$conn = getConnection();

// L'URL è stato diviso in index.php e salvato in $urlParts
// $urlParts[0] è 'utenti'
// $urlParts[1] potrebbe essere l'ID dell'utente (es. /utenti/1)
$id_utente = isset($urlParts[1]) && is_numeric($urlParts[1]) ? intval($urlParts[1]) : null;

switch ($metodo) {
    case 'GET':
        if ($id_utente) {
            // GET /utenti/{id} - Recupera un singolo utente
            $query = "SELECT id, nome, cognome, email, ruolo, creato_il FROM utenti WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id_utente, PDO::PARAM_INT);
            $stmt->execute();
            
            $utente = $stmt->fetch();
            
            if ($utente) {
                inviaRispostaJson(200, 'successo', 'Utente recuperato con successo.', $utente);
            } else {
                inviaRispostaJson(404, 'errore', 'Utente non trovato.');
            }
        } else {
            // GET /utenti - Recupera tutti gli utenti
            $query = "SELECT id, nome, cognome, email, ruolo, creato_il FROM utenti ORDER BY id DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $utenti = $stmt->fetchAll();
            inviaRispostaJson(200, 'successo', 'Elenco utenti recuperato.', $utenti);
        }
        break;

    case 'POST':
        // POST /utenti - Crea un nuovo utente
        $dati = leggiDatiJson();
        
        if (!$dati) {
            inviaRispostaJson(400, 'errore', 'Dati JSON non validi o mancanti.');
        }
        
        // Validazione campi obbligatori
        if (empty($dati['nome']) || empty($dati['cognome']) || empty($dati['email'])) {
            inviaRispostaJson(400, 'errore', 'I campi nome, cognome ed email sono obbligatori.');
        }
        
        // Validazione email
        if (!filter_var($dati['email'], FILTER_VALIDATE_EMAIL)) {
            inviaRispostaJson(400, 'errore', 'Formato email non valido.');
        }
        
        // Validazione ruolo (se fornito)
        $ruoli_consentiti = ['studente', 'docente', 'tecnico', 'ata'];
        $ruolo = isset($dati['ruolo']) && in_array($dati['ruolo'], $ruoli_consentiti) ? $dati['ruolo'] : 'studente';
        
        try {
            $query = "INSERT INTO utenti (nome, cognome, email, ruolo) VALUES (:nome, :cognome, :email, :ruolo)";
            $stmt = $conn->prepare($query);
            
            // Usiamo strip_tags per una pulizia base dell'input
            $nome = strip_tags($dati['nome']);
            $cognome = strip_tags($dati['cognome']);
            $email = strip_tags($dati['email']);
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cognome', $cognome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':ruolo', $ruolo);
            
            if ($stmt->execute()) {
                $id_inserito = $conn->lastInsertId();
                inviaRispostaJson(201, 'successo', 'Utente creato con successo.', ["id" => $id_inserito]);
            } else {
                inviaRispostaJson(500, 'errore', 'Impossibile creare l\'utente.');
            }
        } catch (PDOException $e) {
            // Gestione errore per email duplicata (codice errore MySQL 1062 o SQLSTATE 23000)
            if ($e->getCode() == 23000) {
                inviaRispostaJson(400, 'errore', 'L\'email fornita è già in uso.');
            }
            inviaRispostaJson(500, 'errore', 'Errore del database: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        // DELETE /utenti/{id} - Elimina un utente
        if (!$id_utente) {
            inviaRispostaJson(400, 'errore', 'ID utente mancante per l\'eliminazione.');
        }
        
        try {
            // La Foreign Key in 'segnalazioni' ha ON DELETE CASCADE, quindi le segnalazioni
            // dell'utente verranno eliminate automaticamente dal database.
            $query = "DELETE FROM utenti WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id_utente, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                inviaRispostaJson(200, 'successo', 'Utente eliminato con successo.');
            } else {
                inviaRispostaJson(404, 'errore', 'Utente non trovato o già eliminato.');
            }
        } catch (PDOException $e) {
            inviaRispostaJson(500, 'errore', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
        break;

    default:
        inviaRispostaJson(405, 'errore', 'Metodo non consentito. Usa GET, POST o DELETE.');
        break;
}
