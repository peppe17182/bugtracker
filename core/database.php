<?php
/**
 * Database Connection - BugTracker Scuola
 * Gestisce la connessione al database MySQL utilizzando PDO.
 */

/**
 * Restituisce l'istanza della connessione PDO.
 * Legge i parametri dal file config.ini.
 *
 * @return PDO La connessione al database.
 */
function getConnection() {
    // Percorso del file di configurazione (nella root del progetto)
    $configFile = __DIR__ . '/../config.ini';

    // Verifica che il file esista
    if (!file_exists($configFile)) {
        if (function_exists('inviaRispostaJson')) {
            inviaRispostaJson(500, 'errore', 'File di configurazione del database non trovato.');
        } else {
            die(json_encode(["stato" => "errore", "messaggio" => "File di configurazione del database non trovato."]));
        }
    }

    // Legge il file INI
    $config = parse_ini_file($configFile, true);

    if (!$config || !isset($config['database'])) {
         if (function_exists('inviaRispostaJson')) {
            inviaRispostaJson(500, 'errore', 'Formato del file di configurazione non valido.');
        } else {
            die(json_encode(["stato" => "errore", "messaggio" => "Formato del file di configurazione non valido."]));
        }
    }

    $dbConfig = $config['database'];

    $host = $dbConfig['host'] ?? 'localhost';
    $dbname = $dbConfig['dbname'] ?? '';
    $username = $dbConfig['username'] ?? '';
    $password = $dbConfig['password'] ?? '';
    $charset = $dbConfig['charset'] ?? 'utf8mb4';

    try {
        // DSN (Data Source Name) per PDO
        $dsn = "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=" . $charset;
        
        // Opzioni PDO per sicurezza e gestione errori
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lancia eccezioni in caso di errore SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Restituisce array associativi di default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Disabilita l'emulazione delle prepared statements (più sicuro)
        ];

        // Creazione e restituzione dell'istanza PDO
        return new PDO($dsn, $username, $password, $options);
        
    } catch(PDOException $exception) {
        // In caso di errore di connessione, restituiamo un JSON di errore 500
        if (function_exists('inviaRispostaJson')) {
            inviaRispostaJson(500, 'errore', 'Errore di connessione al database: ' . $exception->getMessage());
        } else {
            http_response_code(500);
            echo json_encode([
                "stato" => "errore",
                "messaggio" => "Errore di connessione al database: " . $exception->getMessage()
            ]);
            exit();
        }
    }
}
