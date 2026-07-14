<?php
// services/CoinService.php

class CoinService {
    private $coinRepository;
    private $valutaRepository;

    // Repositories opslaan
    public function __construct($coinRepository, $valutaRepository) {
        $this->coinRepository = $coinRepository;
        $this->valutaRepository = $valutaRepository;
    }

    // API-data ophalen
    private function haalApiData($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode === 200) ? json_decode($response, true) : null;
    }

    // Coins voor de dropdown ophalen
    public function getApiDropdownCoins() {
        $binance_live = $this->haalApiData("https://binance.vision") ?? [];

        $api_cryptos = [
            ['id' => 'BTCUSDT', 'name' => 'Bitcoin (BTC)', 'current_price' => 64230.10],
            ['id' => 'ETHUSDT', 'name' => 'Ethereum (ETH)', 'current_price' => 3450.50],
            ['id' => 'SOLUSDT', 'name' => 'Solana (SOL)', 'current_price' => 142.25]
        ];

        if (!empty($binance_live) && is_array($binance_live)) {
            foreach ($binance_live as $ticker) {
                if (is_array($ticker) && isset($ticker['symbol']) && isset($ticker['price'])) {
                    foreach ($api_cryptos as &$standaard_coin) {
                        if ($ticker['symbol'] === $standaard_coin['id']) {
                            $standaard_coin['current_price'] = (float)$ticker['price'];
                        }
                    }
                }
            }
        }
        return $api_cryptos;
    }

    // Wisselkoersen ophalen
    public function getLiveWisselkoersen() {
        $valuta_api = $this->haalApiData("https://er-api.com");
        return $valuta_api['rates'] ?? ['EUR' => 0.92, 'GBP' => 0.78, 'JPY' => 155.00, 'CAD' => 1.36];
    }

    // Crypto's bijwerken met live prijzen
    public function getGesynchroniseerdeCryptoData() {
        $binance_live = $this->haalApiData("https://binance.vision") ?? [];

        // Crypto's uit de database ophalen
        $gejoinde_data = $this->coinRepository->haalEigenCryptoMetValutaJoin();

        $eigen_cryptos = [];
        if (is_array($gejoinde_data)) {
            foreach ($gejoinde_data as $crypto) {
                $search_pair = strtoupper($crypto['crypto_id']);
                if (!empty($binance_live) && is_array($binance_live)) {
                    foreach ($binance_live as $live_ticker) {
                        if (is_array($live_ticker) && isset($live_ticker['symbol']) && $live_ticker['symbol'] === $search_pair) {
                            $crypto['prijs_usd'] = $live_ticker['price'];
                            break;
                        }
                    }
                }
                $eigen_cryptos[] = $crypto;
            }
        }
        return $eigen_cryptos;
    }

    // Nieuwe crypto toevoegen
    public function voegCryptoToe($crypto_id, $naam, $prijs) {
        // Crypto opslaan
        $nieuwCryptoId = $this->coinRepository->voegCryptoToe($crypto_id, $naam, $prijs);

        // Standaard valuta toevoegen
        if ($nieuwCryptoId) {
            $this->valutaRepository->maakStandaardValutaRij($nieuwCryptoId);
        }
    }

    // Valuta aanpassen
    public function updateValuta($id, $valuta) {
        $this->valutaRepository->updateValuta($id, $valuta);
    }

    // Crypto verwijderen
    public function verwijderCrypto($id) {
        $this->coinRepository->verwijderCrypto($id);
    }
}






