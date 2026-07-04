<?php
session_start();
require_once 'config.php';

// Sécurité : Rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// 1. RÉCUPÉRATION DES INFOS UTILISATEUR
$check_user = $pdo->prepare("SELECT date_inscription, statut_abonnement, expire_at FROM utilisateurs WHERE id = ?");
$check_user->execute([$user_id]);
$user = $check_user->fetch();

$now = new DateTime();
$date_insc = new DateTime($user['date_inscription']);

// Vérification de l'expiration (3 jours d'essai)
$essai_expire = ($user['statut_abonnement'] == 'gratuit' && $date_insc->diff($now)->days >= 3);
$premium_expire = false;

if ($user['statut_abonnement'] == 'premium' && !empty($user['expire_at'])) {
    $date_fin = new DateTime($user['expire_at']);
    if ($now > $date_fin) { $premium_expire = true; }
}

// 2. TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['choisir_plan'])) {
    $plan_nom = htmlspecialchars($_POST['plan_nom']);
    $montant = intval($_POST['montant']);

    // Insertion de la demande dans ta table
    $stmt = $pdo->prepare("INSERT INTO demandes_abonnement (user_id, plan, montant, statut, date_demande) VALUES (?, ?, ?, 'en_attente', NOW())");
    
    if ($stmt->execute([$user_id, $plan_nom, $montant])) {
        // --- LA MODIFICATION EST ICI ---
        // Au lieu d'afficher juste un message, on redirige vers la page de paiement 
        header("Location: paiement.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnements</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/abon.css">
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
    
    <?php if($essai_expire || $premium_expire): ?>
        <div class="status-banner">
            <i class="fas fa-lock" style="font-size: 2rem; color: var(--primary); margin-bottom: 10px;"></i>
            <h1 style="margin:0; color:var(--primary); font-size: 1.5rem;">ACCÈS BLOQUÉ</h1>
            <p style="font-size:0.9rem; color: #ccc;">
                <?php echo ($essai_expire) ? "Votre essai est fini. Choisissez un plan pour continuer." : "Votre abonnement a expiré."; ?>
            </p>
        </div>
    <?php else: ?>
        <h1 style="color:var(--gold)">PREMIUM</h1>
        <p style="color:#888;">Boostez la gestion de votre entreprise</p>
    <?php endif; ?>

    <div class="plans-list">
        <div class="plan-card">
            <div class="plan-name">PLAN PME</div>
            <div class="plan_price">5.000 <span>FCFA/mois</span></div>
            <form method="POST" onsubmit="return confirm('Confirmer le choix du plan PME ?');">
                <input type="hidden" name="plan_nom" value="PME">
                <input type="hidden" name="montant" value="5000">
                <button type="submit" name="choisir_plan" class="btn-pay">Choisir ce plan</button>
            </form>
        </div>

        <div class="plan-card active">
            <div class="recommend-tag">POPULAIRE</div>
            <div class="plan-name">START-UP</div>
            <div class="plan_price">10.000 <span>FCFA/mois</span></div>
            <form method="POST" onsubmit="return confirm('Confirmer le choix du plan Start-up ?');">
                <input type="hidden" name="plan_nom" value="Start-up">
                <input type="hidden" name="montant" value="10000">
                <button type="submit" name="choisir_plan" class="btn-pay" style="background: white;">Choisir ce plan</button>
            </form>
        </div>

        <div class="plan-card">
            <div class="plan-name">ENTREPRISE</div>
            <div class="plan-price">15.000 <span>FCFA/mois</span></div>
            <form method="POST" onsubmit="return confirm('Confirmer le choix du plan Entreprise ?');">
                <input type="hidden" name="plan_nom" value="Entreprise">
                <input type="hidden" name="montant" value="15000">
                <button type="submit" name="choisir_plan" class="btn-pay">Choisir ce plan</button>
            </form>
        </div>
    </div>
    
    <p style="margin-top: 30px; font-size: 0.8rem; color: #444;">&copy; 2026 Blue World Technology</p>
</div>

</body>
</html>