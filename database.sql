-- Creazione del database
CREATE DATABASE IF NOT EXISTS bugtracker_scuola
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE bugtracker_scuola;

-- =======================================================
-- 1. Tabella UTENTI (3NF)
-- Ogni attributo dipende interamente ed esclusivamente da id_utente
-- =======================================================
CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE, -- Unique garantisce che non ci siano duplicati
    ruolo ENUM('studente', 'docente', 'tecnico', 'ata') NOT NULL DEFAULT 'studente',
    creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =======================================================
-- 2. Tabella AMBIENTI (3NF)
-- Nessuna dipendenza transitiva. 'piano' e 'tipo' dipendono solo dall'id.
-- =======================================================
CREATE TABLE IF NOT EXISTS ambienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    piano VARCHAR(20) NOT NULL, -- Es: 'Terra', 'Primo', 'Interrato'
    tipo ENUM('aula', 'laboratorio', 'bagno', 'ufficio', 'spazio_comune') NOT NULL,
    creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =======================================================
-- 3. Tabella SEGNALAZIONI (3NF)
-- I dati della segnalazione dipendono dal suo ID. 
-- L'utente e l'ambiente sono referenziati tramite Foreign Key rigorose.
-- =======================================================
CREATE TABLE IF NOT EXISTS segnalazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    id_ambiente INT NOT NULL,
    titolo VARCHAR(100) NOT NULL,
    descrizione TEXT NOT NULL,
    stato ENUM('aperta', 'in_lavorazione', 'risolta', 'rifiutata') NOT NULL DEFAULT 'aperta',
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_risoluzione TIMESTAMP NULL DEFAULT NULL,
    
    -- Vincoli di integrità referenziale (Foreign Keys)
    CONSTRAINT fk_segnalazione_utente 
        FOREIGN KEY (id_utente) REFERENCES utenti(id) 
        ON DELETE CASCADE -- Se eliminiamo l'utente, eliminiamo le sue segnalazioni
        ON UPDATE CASCADE,
        
    CONSTRAINT fk_segnalazione_ambiente 
        FOREIGN KEY (id_ambiente) REFERENCES ambienti(id) 
        ON DELETE RESTRICT -- Non puoi eliminare un ambiente se ha segnalazioni attive
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =======================================================
-- INSERIMENTO DATI DI PROVA (DUMMY DATA)
-- =======================================================

-- Inserimento Utenti
INSERT INTO utenti (nome, cognome, email, ruolo) VALUES
('Mario', 'Rossi', 'mario.rossi@studenti.scuola.it', 'studente'),
('Giulia', 'Bianchi', 'giulia.bianchi@docenti.scuola.it', 'docente'),
('Luigi', 'Verdi', 'luigi.verdi@tecnici.scuola.it', 'tecnico');

-- Inserimento Ambienti
INSERT INTO ambienti (nome, piano, tipo) VALUES
('Aula 3A', 'Primo', 'aula'),
('Laboratorio di Informatica 1', 'Terra', 'laboratorio'),
('Bagno Maschi Ovest', 'Primo', 'bagno');

-- Inserimento Segnalazioni
INSERT INTO segnalazioni (id_utente, id_ambiente, titolo, descrizione, stato) VALUES
(1, 2, 'Computer PC-04 non si accende', 'Il computer nella quarta fila, postazione 4, non dà segni di vita anche se collegato alla presa.', 'aperta'),
(2, 1, 'Termosifone perde acqua', 'C\'è una piccola pozzanghera sotto il termosifone vicino alla cattedra.', 'in_lavorazione'),
(1, 3, 'Luce fulminata', 'La plafoniera centrale è bruciata.', 'risolta');
