<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

// 2. RÉCUPÉRER LES DONNÉES DE VÉRIFICATION
$stmt_check = $pdo->prepare("SELECT date_inscription, statut_abonnement, expire_at FROM utilisateurs WHERE id = ?");
$stmt_check->execute([$_SESSION['user_id']]);
$check_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($check_data) {
    $date_inscription = new DateTime($check_data['date_inscription']);
    $maintenant = new DateTime();
    
    // Calcul de la fin d'essai (+3 jours)
    $date_fin_essai = clone $date_inscription;
    $date_fin_essai->modify('+3 days');

    $statut = $check_data['statut_abonnement'];
    $page_actuelle = basename($_SERVER['PHP_SELF']);

    // --- CAS 1 : ESSAI EXPIRE (Redirige vers abonnements.php) ---
    if ($statut == 'gratuit' || $statut == 'essaie') {
        if ($maintenant > $date_fin_essai) {
            // On évite la boucle infinie si on est déjà sur la page
            if ($page_actuelle != 'abonnements.php') {
                header('Location: abonnements.php?statut=essai_fini');
                exit();
            }
        }
    }

    // --- CAS 2 : PREMIUM EXPIRE (Redirige vers reabonnements.php) ---
    if ($statut == 'premium' && !empty($check_data['expire_at'])) {
        $date_expiration = new DateTime($check_data['expire_at']);
        
        if ($maintenant > $date_expiration) {
            // On évite la boucle infinie si on est déjà sur reabonnements.php
            if ($page_actuelle != 'reabonnements.php') {
                header('Location: reabonnements.php?expired=1');
                exit();
            }
        }
    }
}
?>