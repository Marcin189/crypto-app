<?php
// config/Database.php

class Database {
    private $host = 'localhost';
    private $db   = 'cryptoapp';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4'; // Zorgt dat speciale tekens goed worden opgeslagen
    private $pdo;

    public function getConnection() {
        // Kijk of er al een databaseverbinding is
        if ($this->pdo === null) {

            // Gegevens die nodig zijn om verbinding te maken met de database
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";

            try {
                // Maak verbinding met de database
                $this->pdo = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

            } catch (PDOException $e) {
                // Laat een melding zien als de verbinding niet lukt
                die("<p style='color:red; font-weight:bold;'>Database connectie mislukt: " . $e->getMessage() . "</p>");
            }
        }

        // Geef de verbinding terug zodat de rest van de code hem kan gebruiken
        return $this->pdo;
    }
}

