<?php
// public/index.php

// bestanden laden
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Coin.php';
require_once __DIR__ . '/../models/Alert.php';
require_once __DIR__ . '/../repositories/CoinRepository.php';
require_once __DIR__ . '/../repositories/ValutaRepository.php';
require_once __DIR__ . '/../repositories/AlertRepository.php';
require_once __DIR__ . '/../services/CoinService.php';
require_once __DIR__ . '/../services/AlertService.php';

// objecten aanmaken
$database         = new Database();
$dbConn           = $database->getConnection();
$coinRepository   = new CoinRepository($dbConn);
$valutaRepository = new ValutaRepository($dbConn);
$alertRepository  = new AlertRepository($dbConn);

// Repositories doorgeven aan de services
$coinService = new CoinService($coinRepository, $valutaRepository);
$alertService = new AlertService($alertRepository);

// Gegevens ophalen voor de pagina
$api_cryptos = $coinService->getApiDropdownCoins();
$wisselkoersen = $coinService->getLiveWisselkoersen();
$beschikbare_valutas = ['EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'USD'];

// Formulieren verwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actie_add_crypto'])) {
        $coinService->voegCryptoToe($_POST['crypto_id'], $_POST['naam'], $_POST['prijs']);
    }
    if (isset($_POST['actie_delete_crypto'])) {
        $coinService->verwijderCrypto($_POST['id']);
    }
    if (isset($_POST['actie_update_valuta'])) {
        $coinService->updateValuta($_POST['id'], $_POST['gekozen_valuta']);
    }
    if (isset($_POST['actie_add_alert'])) {
        $alertService->addAlert($_POST['crypto_id'], $_POST['alert_name'], $_POST['target_price']);
    }
    if (isset($_POST['actie_delete_alert'])) {
        $alertService->deleteAlert($_POST['id']);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Crypto's en alerts ophalen
$eigen_cryptos = $coinService->getGesynchroniseerdeCryptoData();
$alerts = $alertService->getAlertsWithStatus($eigen_cryptos);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Crypto Website</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; color: #333; }
        h1 { text-align: center; color: #222; }
        h2 { border-bottom: 2px solid #ddd; padding-bottom: 5px; color: #444; }
        .container { display: flex; gap: 30px; flex-wrap: wrap; justify-content: center; }
        .section { flex: 1; min-width: 45%; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .form-box { background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #e3e3e3; margin-top: 25px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 3px; }
        .btn { padding: 6px 12px; font-size: 12px; cursor: pointer; border: none; border-radius: 4px; font-weight: bold; }
        .btn-add { background-color: #28a745; color: white; width: 100%; padding: 8px; font-size: 14px; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-update { background-color: #007bff; color: white; }
        select, input[type="text"], input[type="number"] { width: 90%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        .badge-price { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .badge-currency { background-color: #6c757d; color: white; padding: 2px 5px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        tr:hover { background-color: #fdfdfd; }
    </style>
</head>
<body>

<h1>Crypto Website</h1>

<div class="container">

    <div class="section">
        <h2>1. Crypto Invoeren</h2>
        <p style="font-size: 13px; color: #666;">Voeg hier munten toe aan je overzicht. Ze verschijnen automatisch in Tabel 2.</p>

        <div class="form-box">
            <h3>+ Nieuwe Crypto Toevoegen</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Snel kiezen uit Live API:</label>
                    <select id="api_crypto_select" onchange="vulCryptoVeldenIn()">
                        <option value="" data-name="" data-price="">-- Selecteer uit Live Binance API --</option>
                        <?php foreach ($api_cryptos as $coin): ?>
                            <option value="<?= htmlspecialchars($coin['id']) ?>" data-name="<?= htmlspecialchars($coin['name']) ?>" data-price="<?= $coin['current_price'] ?>">
                                <?= htmlspecialchars($coin['name']) ?> ($<?= number_format($coin['current_price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Binance Pair (bijv. BTCUSDT):</label>
                    <input type="text" name="crypto_id" id="c_id" placeholder="bijv. BTCUSDT" required>
                </div>
                <div class="form-group">
                    <label>Naam:</label>
                    <input type="text" name="naam" id="c_name" placeholder="bijv. Bitcoin" required>
                </div>
                <div class="form-group">
                    <label>Prijs (USD):</label>
                    <input type="number" step="any" name="prijs" id="c_price" placeholder="bijv. 64000" required>
                </div>
                <button type="submit" name="actie_add_crypto" class="btn btn-add">Voeg Crypto Toe</button>
            </form>
        </div>
    </div>

    <div class="section">
        <h2>2. Mijn Live Valuta Overzicht</h2>
        <p style="font-size: 13px; color: #666;">Kies per munt de gewenste valuta. Wijzigingen worden live omgerekend via de 2de API.</p>

        <table>
            <thead>
            <tr>
                <th>Munt Naam</th>
                <th>Kies Valuta</th>
                <th>Omgerekende Prijs (2de API)</th>
                <th>Acties</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($eigen_cryptos)): ?>
                <tr><td colspan="4" style="text-align:center; color:#777;">Je overzicht is nog leeg. Voeg links een crypto toe!</td></tr>
            <?php else: ?>
                <?php foreach ($eigen_cryptos as $crypto): ?>
                    <tr>
                        <form method="POST">
                            <!-- Stuur het ID van de crypto mee voor de acties -->
                            <input type="hidden" name="id" value="<?= $crypto['id'] ?>">

                            <td>
                                <strong><?= htmlspecialchars($crypto['naam']) ?></strong>
                                <br><small style="color:#777;">Pair: <?= htmlspecialchars($crypto['crypto_id']) ?></small>
                            </td>
                            <td>
                                <select name="gekozen_valuta" style="width: 100px;">
                                    <?php foreach ($beschikbare_valutas as $v): ?>
                                        <option value="<?= $v ?>" <?= strtoupper($crypto['gekozen_valuta']) === $v ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php
                                $valutaUpper = strtoupper($crypto['gekozen_valuta']);
                                $live_fiat_koers = $wisselkoersen[$valutaUpper] ?? 1.00;
                                $prijs_omgerekend = $crypto['prijs_usd'] * $live_fiat_koers;

                                $teken = '$';
                                if ($valutaUpper === 'EUR') { $teken = '€'; }
                                elseif ($valutaUpper === 'GBP') { $teken = '£'; }
                                elseif ($valutaUpper === 'JPY') { $teken = '¥'; }
                                ?>
                                <span class="badge-price"><?= $teken ?> <?= number_format($prijs_omgerekend, 2) ?></span>
                                <br><small style="color:#666;">(Basis: $<?= number_format($crypto['prijs_usd'], 2) ?> USD)</small>
                            </td>
                            <td>
                                <button type="submit" name="actie_update_valuta" class="btn btn-update">Opslaan</button>
                                <button type="submit" name="actie_delete_crypto" class="btn btn-delete" onclick="return confirm('Weet je zeker dat je deze munt wilt verwijderen?')">X</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>3. Crypto Alerten</h2>
        <p style="font-size: 13px; color: #666;">Maak een alert aan voor een munt en volg een gewenste prijs.</p>

        <div class="form-box">
            <h3>+ Nieuwe Alert Toevoegen</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Crypto</label>
                    <select name="crypto_id" required>
                        <option value="">-- Kies een crypto --</option>
                        <?php foreach ($eigen_cryptos as $crypto): ?>
                            <option value="<?= (int)$crypto['id'] ?>"><?= htmlspecialchars($crypto['naam']) ?> (<?= htmlspecialchars($crypto['crypto_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Alert Naam</label>
                    <input type="text" name="alert_name" placeholder="bijv. Koop alert" required>
                </div>
                <div class="form-group">
                    <label>Doelprijs (USD)</label>
                    <input type="number" step="any" name="target_price" placeholder="bijv. 70000" required>
                </div>
                <button type="submit" name="actie_add_alert" class="btn btn-add">Alert Opslaan</button>
            </form>
        </div>

        <?php $triggeredAlerts = array_filter($alerts, function ($alert) { return !empty($alert['is_triggered']); }); ?>
        <?php if (!empty($triggeredAlerts)): ?>
            <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:10px;">
                <strong>Alert geactiveerd!</strong> Een of meer prijzen hebben je doel bereikt.
            </div>
        <?php endif; ?>

        <table>
            <thead>
            <tr>
                <th>Crypto</th>
                <th>Alert Naam</th>
                <th>Doelprijs</th>
                <th>Status</th>
                <th>Actie</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="4" style="text-align:center; color:#777;">Nog geen alerts aangemaakt.</td></tr>
            <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= (int)$alert['id'] ?>">
                            <td><?= htmlspecialchars($alert['naam']) ?> (<?= htmlspecialchars($alert['crypto_id']) ?>)</td>
                            <td><?= htmlspecialchars($alert['alert_name']) ?></td>
                            <td>$<?= number_format((float)$alert['target_price_usd'], 2) ?></td>
                            <td>
                                <?php if (!empty($alert['is_triggered'])): ?>
                                    <span style="color:#dc3545; font-weight:bold;">Triggered</span>
                                <?php else: ?>
                                    <span style="color:#6c757d;">Wachtend</span>
                                <?php endif; ?>
                            </td>
                            <td><button type="submit" name="actie_delete_alert" class="btn btn-delete">Verwijder</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
<script>
    function vulCryptoVeldenIn() {
        var select = document.getElementById("api_crypto_select");
        var selectedOption = select.options[select.selectedIndex];
        if(selectedOption.value !== "") {
            document.getElementById("c_id").value = selectedOption.value;
            document.getElementById("c_name").value = selectedOption.getAttribute("data-name");
            document.getElementById("c_price").value = selectedOption.getAttribute("data-price");
        }
    }
</script>

</body>
</html>





















