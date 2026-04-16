<?php
/**
 * API Segnalazioni - BugTracker Scuola
 * Gestisce le operazioni CRUD per la tabella 'segnalazioni'.
 */

$metodo = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();

// $urlParts[0] è 'segnalazioni'
// $urlParts[1] potrebbe essere l'ID della segnalazione (es. /segnalazioni/1)
$id_segnalazione = isset($urlParts[1]) && is_numeric($urlParts[1]) ? intval($urlParts[1]) : null;

switch ($metodo) {
    case 'GET':
        if ($id_segnalazione) {
            // GET /segnalazioni/{id} - Recupera una singola segnalazione con JOIN
            $query = "
                SELECT 
                    s.id, s.titolo, s.descrizione, s.stato, s.data_creazione, s.data_risoluzione,
                    u.id AS id_utente, u.nome AS nome_utente, u.cognome AS cognome_utente, u.ruolo,
                    a.id AS id_ambiente, a.nome AS nome_ambiente, a.piano
                FROM segnalazioni s
                JOIN utenti u ON s.id_utente = u.id
                JOIN ambienti a ON s.id_ambiente = a.id
                WHERE s.id = :id
            ";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id_segnalazione, PDO::PARAM_INT);
            $stmt->execute();
            
            $segnalazione = $stmt->fetch();
            
            if ($segnalazione) {
                // Formattiamo la risposta per renderla più pulita e strutturata
                $risposta = [
                    "id" => $segnalazione['id'],
                    "titolo" => $segnalazione['titolo'],
                    "descrizione" => $segnalazione['descrizione'],
                    "stato" => $segnalazione['stato'],
                    "data_creazione" => $segnalazione['data_creazione'],
                    "data_risoluzione" => $segnalazione['data_risoluzione'],
                    "utente" => [
                        "id" => $segnalazione['id_utente'],
                        "nome" => $segnalazione['nome_utente'],
                        "cognome" => $segnalazione['cognome_utente'],
                        "ruolo" => $segnalazione['ruolo']
                    ],
                    "ambiente" => [
                        "id" => $segnalazione['id_ambiente'],
                        "nome" => $segnalazione['nome_ambiente'],
                        "piano" => $segnalazione['piano']
                    ]
                ];
                inviaRispostaJson(200, 'successo', 'Segnalazione recuperata.', $risposta);
            } else {
                inviaRispostaJson(404, 'errore', 'Segnalazione non trovata.');
            }
        } else {
            // GET /segnalazioni - Recupera tutte le segnalazioni con JOIN
            $query = "
                SELECT 
                    s.id, s.titolo, s.stato, s.data_creazione,
                    u.nome AS nome_utente, u.cognome AS cognome_utente,
                    a.nome AS nome_ambiente
                FROM segnalazioni s
                JOIN utenti u ON s.id_utente = u.id
                JOIN ambienti a ON s.id_ambiente = a.id
                ORDER BY s.data_creazione DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $segnalazioni = $stmt->fetchAll();
            inviaRispostaJson(200, 'successo', 'Elenco segnalazioni recuperato.', $segnalazioni);
        }
        break;

    case 'POST':
        // POST /segnalazioni - Crea una nuova segnalazione
        $dati = leggiDatiJson();
        
        if (!$dati) {
            inviaRispostaJson(400, 'errore', 'Dati JSON non validi o mancanti.');
        }
        
        // Validazione campi obbligatori
        if (empty($dati['id_utente']) || empty($dati['id_ambiente']) || empty($dati['titolo']) || empty($dati['descrizione'])) {
            inviaRispostaJson(400, 'errore', 'I campi id_utente, id_ambiente, titolo e descrizione sono obbligatori.');
        }
        
        try {
            // Verifichiamo che l'utente esista
            $stmtUser = $conn->prepare("SELECT id FROM utenti WHERE id = :id");
            $stmtUser->execute([':id' => $dati['id_utente']]);
            if ($stmtUser->rowCount() === 0) {
                inviaRispostaJson(400, 'errore', 'L\'utente specificato non esiste.');
            }

            // Verifichiamo che l'ambiente esista
            $stmtAmbiente = $conn->prepare("SELECT id FROM ambienti WHERE id = :id");
            $stmtAmbiente->execute([':id' => $dati['id_ambiente']]);
            if ($stmtAmbiente->rowCount() === 0) {
                inviaRispostaJson(400, 'errore', 'L\'ambiente specificato non esiste.');
            }

            $query = "INSERT INTO segnalazioni (id_utente, id_ambiente, titolo, descrizione) VALUES (:id_utente, :id_ambiente, :titolo, :descrizione)";
            $stmt = $conn->prepare($query);
            
            $titolo = strip_tags($dati['titolo']);
            $descrizione = strip_tags($dati['descrizione']);
            
            $stmt->bindParam(':id_utente', $dati['id_utente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_ambiente', $dati['id_ambiente'], PDO::PARAM_INT);
            $stmt->bindParam(':titolo', $titolo);
            $stmt->bindParam(':descrizione', $descrizione);
            
            if ($stmt->execute()) {
                $id_inserito = $conn->lastInsertId();
                inviaRispostaJson(201, 'successo', 'Segnalazione creata con successo.', ["id" => $id_inserito]);
            } else {
                inviaRispostaJson(500, 'errore', 'Impossibile creare la segnalazione.');
            }
        } catch (PDOException $e) {
            inviaRispostaJson(500, 'errore', 'Errore del database: ' . $e->getMessage());
        }
        break;

    case 'PUT':
        // PUT /segnalazioni/{id} - Aggiornamento totale dell'istanza (Requisito Base)
        if (!$id_segnalazione) {
            inviaRispostaJson(400, 'errore', 'ID segnalazione mancante per l\'aggiornamento.');
        }
        
        $dati = leggiDatiJson();
        if (!$dati || empty($dati['id_utente']) || empty($dati['id_ambiente']) || empty($dati['titolo']) || empty($dati['descrizione']) || empty($dati['stato'])) {
            inviaRispostaJson(400, 'errore', 'Per il metodo PUT tutti i campi sono obbligatori (id_utente, id_ambiente, titolo, descrizione, stato).');
        }
        
        $stati_consentiti = ['aperta', 'in_lavorazione', 'risolta', 'rifiutata'];
        if (!in_array($dati['stato'], $stati_consentiti)) {
            inviaRispostaJson(400, 'errore', 'Stato non valido.');
        }
        
        try {
            $query = "UPDATE segnalazioni 
                      SET id_utente = :id_utente, id_ambiente = :id_ambiente, titolo = :titolo, descrizione = :descrizione, stato = :stato, 
                      data_risoluzione = " . ($dati['stato'] === 'risolta' ? "CURRENT_TIMESTAMP" : "NULL") . " 
                      WHERE id = :id";
            $stmt = $conn->prepare($query);
            
            $titolo = strip_tags($dati['titolo']);
            $descrizione = strip_tags($dati['descrizione']);
            
            $stmt->bindParam(':id_utente', $dati['id_utente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_ambiente', $dati['id_ambiente'], PDO::PARAM_INT);
            $stmt->bindParam(':titolo', $titolo);
            $stmt->bindParam(':descrizione', $descrizione);
            $stmt->bindParam(':stato', $dati['stato']);
            $stmt->bindParam(':id', $id_segnalazione, PDO::PARAM_INT);
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                inviaRispostaJson(200, 'successo', 'Segnalazione aggiornata completamente con successo.');
            } else {
                $checkStmt = $conn->prepare("SELECT id FROM segnalazioni WHERE id = :id");
                $checkStmt->execute([':id' => $id_segnalazione]);
                if ($checkStmt->rowCount() > 0) {
                    inviaRispostaJson(200, 'successo', 'Nessuna modifica effettuata (i dati erano già identici).');
                } else {
                    inviaRispostaJson(404, 'errore', 'Segnalazione non trovata.');
                }
            }
        } catch (PDOException $e) {
            inviaRispostaJson(500, 'errore', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
        break;

    case 'PATCH':
        // PATCH /segnalazioni/{id} - Aggiornamento parziale (INNOVAZIONE)
        if (!$id_segnalazione) {
            inviaRispostaJson(400, 'errore', 'ID segnalazione mancante per l\'aggiornamento.');
        }
        
        $dati = leggiDatiJson();
        if (!$dati || empty($dati['stato'])) {
            inviaRispostaJson(400, 'errore', 'Il campo "stato" è obbligatorio per l\'aggiornamento.');
        }
        
        $stati_consentiti = ['aperta', 'in_lavorazione', 'risolta', 'rifiutata'];
        if (!in_array($dati['stato'], $stati_consentiti)) {
            inviaRispostaJson(400, 'errore', 'Stato non valido. Valori consentiti: ' . implode(', ', $stati_consentiti));
        }
        
        try {
            // Se lo stato è 'risolta', impostiamo la data_risoluzione a NOW(), altrimenti a NULL
            $query = "UPDATE segnalazioni SET stato = :stato, data_risoluzione = " . 
                     ($dati['stato'] === 'risolta' ? "CURRENT_TIMESTAMP" : "NULL") . 
                     " WHERE id = :id";
                     
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':stato', $dati['stato']);
            $stmt->bindParam(':id', $id_segnalazione, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                inviaRispostaJson(200, 'successo', 'Stato della segnalazione aggiornato con successo.');
            } else {
                // Se rowCount è 0, l'ID non esiste o lo stato era già quello richiesto
                // Verifichiamo se l'ID esiste
                $checkStmt = $conn->prepare("SELECT id FROM segnalazioni WHERE id = :id");
                $checkStmt->execute([':id' => $id_segnalazione]);
                if ($checkStmt->rowCount() > 0) {
                    inviaRispostaJson(200, 'successo', 'Nessuna modifica effettuata (lo stato era già impostato a ' . $dati['stato'] . ').');
                } else {
                    inviaRispostaJson(404, 'errore', 'Segnalazione non trovata.');
                }
            }
        } catch (PDOException $e) {
            inviaRispostaJson(500, 'errore', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        // DELETE /segnalazioni/{id} - Elimina una segnalazione
        if (!$id_segnalazione) {
            inviaRispostaJson(400, 'errore', 'ID segnalazione mancante per l\'eliminazione.');
        }
        
        try {
            $query = "DELETE FROM segnalazioni WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id_segnalazione, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                inviaRispostaJson(200, 'successo', 'Segnalazione eliminata con successo.');
            } else {
                inviaRispostaJson(404, 'errore', 'Segnalazione non trovata o già eliminata.');
            }
        } catch (PDOException $e) {
            inviaRispostaJson(500, 'errore', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
        break;

    default:
        inviaRispostaJson(405, 'errore', 'Metodo non consentito. Usa GET, POST, PATCH o DELETE.');
        break;
}
