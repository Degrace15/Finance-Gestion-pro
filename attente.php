<?php
session_start();
require_once 'config.php';

// Sécurité
if(!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer la demande en cours
$stmt = $pdo->prepare("SELECT * FROM demandes_abonnement WHERE user_id = ? AND statut = 'en_attente' ORDER BY date_demande DESC LIMIT 1");
$stmt->execute([$user_id]);
$demande = $stmt->fetch(PDO::FETCH_ASSOC);

// Si pas de demande → rediriger
if(!$demande) {
    header('Location: abonnements.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En attente | Finance & Gestion pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/attente.css">
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

<div class="container">

    <!-- ICÔNE ANIMATION -->
    <div class="icone-attente">
        <i class="fas fa-clock"></i>
    </div>

    <h1>Paiement en vérification</h1>
    <p class="sous-titre">Votre demande a été envoyée avec succès</p>

    <!-- RÉCAPITULATIF -->
    <div class="recap-card">
        <div class="recap-item">
            <span class="label">Plan choisi</span>
            <span class="valeur"><?php echo htmlspecialchars($demande['plan']); ?></span>
        </div>
        <div class="recap-item">
            <span class="label">Montant payé</span>
            <span class="valeur green"><?php echo number_format($demande['montant'], 0, ',', ' '); ?> FCFA</span>
        </div>
        <div class="recap-item">
            <span class="label">Numéro d'envoi</span>
            <span class="valeur"><?php echo htmlspecialchars($demande['numero_envoi']); ?></span>
        </div>
        <div class="recap-item">
            <span class="label">Référence</span>
            <span class="valeur"><?php echo htmlspecialchars($demande['reference']); ?></span>
        </div>
        <div class="recap-item">
            <span class="label">Statut</span>
            <span class="valeur badge-attente">⏳ En vérification</span>
        </div>
    </div>

    <!-- MESSAGE -->
    <div class="message-card">
        <p>⏱️ Délai de vérification : <strong>quelques heures</strong></p>
        <p>📲 Vous serez notifié dès que votre paiement sera confirmé</p>
        <p>❓ Un problème ? Contactez-nous :</p>
        <div class="contacts">
            <!-- REMPLACER PAR TES CONTACTS -->
            <a href="tel:+242061714780">
                <i class="fas fa-phone"></i> MTN : 061 714 780
            </a>
            <a href="tel:+242044774122">
                <i class="fas fa-phone"></i> Airtel : 044 774 122
            </a>
            <a href="https://wa.me/242061714780">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
        </div>
    </div>

    <a href="dashboard.php" class="btn-dashboard">
        🏠 Retour au Dashboard
    </a>

    <p class="copyright">&copy; 2026 Blue World Technology</p>

</div>

</body>
</html>