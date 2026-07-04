<?php
session_start();
require_once 'config.php';

// 1. Vérification de base
if (!isset($_GET['id']) || !isset($_GET['token'])) {
    die("Accès refusé : Paramètres manquants.");
}

$vente_id = $_GET['id'];
$token_recu = $_GET['token'];

// 2. Récupération des données de la vente
$query = "SELECT v.*, p.nom_produit, u.nom_complet, u.telephone 
          FROM ventes v 
          JOIN produits p ON v.produit_id = p.id 
          JOIN utilisateurs u ON v.user_id = u.id
          WHERE v.id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$vente_id]);
$vente = $stmt->fetch();

if (!$vente) { 
    die("Facture introuvable."); 
}

// 3. SÉCURISATION : Vérification du Token
$cle_secrete = "BLUE_WORLD_2024_SECRET_KEY"; 
$token_attendu = md5($vente_id . $cle_secrete);

if ($token_recu !== $token_attendu) {
    die("Accès refusé : Signature invalide.");
}

// =========================================================
// CONFIGURATION DE TON NOM DE DOMAINE (MODIFIE ICI)
// =========================================================
$mon_domaine = "finance-gestion.infinityfreeapp.com"; 
// =========================================================

$url_facture = "https://" . $mon_domaine . "/facture.php?id=" . $vente_id . "&token=" . $token_attendu;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #<?= $vente['id'] ?></title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/factu.css">
      <script>
        if(!navigator.onLine) {
            localStorage.setItem('page_avant', window.location.href);
            window.location.replace('offline.html');
        }

        window.addEventListener('offline', () => {
            localStorage.setItem('page_avant', window.location.href);
            window.location.replace('offline.html');
        });

        setInterval(() => {
            if(!navigator.onLine) {
                localStorage.setItem('page_avant', window.location.href);
                window.location.replace('offline.html');
            }
        }, 2000);
    </script>
</head>
<body>

<div id="main-invoice">
    <div class="header">
        <h1><?= htmlspecialchars($vente['nom_complet']) ?></h1>
        <p>REÇU DE VENTE</p>
    </div>

    <div class="meta-info">
        <div><strong>N° :</strong> #<?= $vente['id'] ?></div>
        <div><strong>DATE :</strong> <?= date('d/m/Y', strtotime($vente['date_vente'])) ?></div>
    </div>

    <table>
        <tr>
            <td><strong><?= htmlspecialchars($vente['nom_produit']) ?></strong><br>Qté: <?= $vente['quantite_vendue'] ?></td>
            <td style="text-align: right; font-weight: bold;"><?= number_format($vente['prix_total'], 0, '.', ' ') ?> F</td>
        </tr>
    </table>

    <div style="text-align: right; margin-top: 10px;">
        <span style="font-size: 0.8rem;">TOTAL</span>
        <span class="total-amount"><?= number_format($vente['prix_total'], 0, '.', ' ') ?> FCFA</span>
    </div>

    <a class="qr-trigger" onclick="showFullQR()">
        <i class="fas fa-qrcode"></i> CLIQUER POUR LE CODE QR
    </a>

    <div class="actions">
        <button onclick="window.print();" class="btn btn-print">IMPRIMER</button>
        <a href="ventes.php" class="btn btn-back">RETOUR</a>
    </div>
</div>

<div id="qr-fullscreen">
    <h2 style="color: var(--dark); margin-bottom: 20px;">SCANNEZ LE REÇU</h2>
    <div id="qrcode"></div>
    <p style="margin-top: 15px; color: #666; font-size: 0.9rem;">ID Facture: #<?= $vente['id'] ?></p>
    <button class="btn-close-qr" onclick="hideFullQR()">RETOUR À LA FACTURE</button>
</div>

<script>
    // Initialiser le QR Code
    var qrcode = new QRCode(document.getElementById("qrcode"), {
        text: "<?= $url_facture ?>",
        width: 200,
        height: 200,
        colorDark : "#1a1a1a",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    function showFullQR() {
        document.getElementById("main-invoice").style.display = "none";
        document.getElementById("qr-fullscreen").style.display = "flex";
    }

    function hideFullQR() {
        document.getElementById("qr-fullscreen").style.display = "none";
        document.getElementById("main-invoice").style.display = "block";
    }
</script>

</body>
</html>