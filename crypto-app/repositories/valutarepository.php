<?php
// repositories/ValutaRepository.php

class ValutaRepository {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    // --- CREATE
    public function maakStandaardValutaRij($crypto_id) {
        $stmt = $this->db->prepare("INSERT IGNORE INTO eigen_valuta (crypto_koppeling_id, gekozen_valuta) VALUES (?, 'EUR')");
        $stmt->execute([$crypto_id]);
    }

    // --- READ WITH JOIN ---
    public function haalValutaMetCryptoJoin() {
        $sql = "SELECT v.id, v.crypto_koppeling_id, v.gekozen_valuta, c.naam, c.crypto_id, c.prijs_usd
                FROM eigen_valuta v
                INNER JOIN eigen_crypto c ON v.crypto_koppeling_id = c.id
                ORDER BY v.id DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // --- UPDATE
    public function updateValuta($crypto_id, $nieuwe_valuta) {
        $stmt = $this->db->prepare("UPDATE eigen_valuta SET gekozen_valuta = ? WHERE crypto_koppeling_id = ?");
        $stmt->execute([strtoupper(trim($nieuwe_valuta)), $crypto_id]);
    }
}


