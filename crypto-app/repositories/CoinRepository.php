<?php
// repositories/CoinRepository.php

class CoinRepository {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    // Nieuwe crypto toevoegen
    public function voegCryptoToe($crypto_id, $naam, $prijs) {
        $stmt = $this->db->prepare("INSERT INTO eigen_crypto (crypto_id, naam, prijs_usd) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE naam=VALUES(naam)");
        $stmt->execute([strtoupper(trim($crypto_id)), $naam, $prijs]);

        // Geeft het ID van de nieuwe crypto terug
        return $this->db->lastInsertId();
    }

    // Alle crypto's met de gekozen valuta ophalen
    public function haalEigenCryptoMetValutaJoin() {
        $sql = "SELECT c.id, c.naam, c.crypto_id, c.prijs_usd, v.gekozen_valuta 
                FROM eigen_crypto c 
                INNER JOIN eigen_valuta v ON c.id = v.crypto_koppeling_id 
                ORDER BY c.id DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // Crypto verwijderen
    public function verwijderCrypto($id) {
        $stmt = $this->db->prepare("DELETE FROM eigen_crypto WHERE id = ?");
        $stmt->execute([$id]);
    }
}



