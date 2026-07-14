<?php
// models/Coin.php

class Coin {
    public $id;
    public $crypto_id;
    public $naam;
    public $prijs_usd;
    public $gekozen_valuta; // // Gekozen valuta

    // Maakt een nieuwe coin aan
    public function __construct($id = null, $crypto_id = null, $naam = null, $prijs_usd = null, $gekozen_valuta = 'EUR') {
        $this->id = $id;
        $this->crypto_id = $crypto_id;
        $this->naam = $naam;
        $this->prijs_usd = $prijs_usd;
        $this->gekozen_valuta = $gekozen_valuta;
    }


}
