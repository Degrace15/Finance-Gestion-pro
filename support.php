<?php
session_start();
require_once 'config.php';

// 1. VÉRIFICATION DE LA CONNEXION
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id']; // ✅ Correction ici : Changement du nom pour éviter d'écraser la variable avec le fetch suivant

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. INITIALISATION DES VARIABLES
$reponse_aide = "";

// 3. MOTEUR DE RECHERCHE PRINCIPAL
if (isset($_POST['do_research'])) {
    $aide = strtolower(trim($_POST['aide'] ?? ''));
  
    if (!empty($aide)) {
        switch(true) {
            // Recherche des mots-clés liés au profil
            case (strpos($aide, 'profil') !== false || strpos($aide, 'avatar') !== false || strpos($aide, 'photo') !== false):
                $reponse_aide = "ℹ️ **Gestion du Profil :** Cliquez sur votre photo en haut de la page pour ouvrir le menu déroulant. Vous pourrez y modifier vos informations et uploader votre logo d'entreprise.";
                break;
                
            // Recherche des mots-clés liés au Premium / Graphiques
            case (strpos($aide, 'graphiques') !== false || strpos($aide, 'premium') !== false || strpos($aide, 'expert') !== false): // ✅ Correction ici : Ajout de la virgule manquante après $aide                         
                $reponse_aide = "⭐ **Analyses Premium :** Les graphiques avancés (comme le graphique en beignet pour l'inventaire) s'activent uniquement si votre compte est configuré en **Mode Expert/Premium**.";
                break;
                
            // Recherche des mots-clés liés aux finances
            case (strpos($aide, 'ventes') !== false || strpos($aide, 'depenses') !== false || strpos($aide, 'argent') !== false || strpos($aide, 'benefice') !== false):
                $reponse_aide = "💰 **Suivi Financier :** Allez dans les modules *Ventes* ou *Dépenses* pour enregistrer vos opérations. Le tableau de bord principal calculera automatiquement votre bénéfice net en FCFA.";
                break;
                
            // Recherche des mots-clés liés à l'inventaire
            case (strpos($aide, 'inventaires') !== false || strpos($aide, 'stock') !== false || strpos($aide, 'articles') !== false):
                $reponse_aide = "📦 **Inventaire :** Ce module vous permet de lister vos articles et de les classer par catégories pour éviter les ruptures de stock.";
                break;
                
            // Cas par défaut : L'utilisateur pose sa propre question
            default:
                $question_origine = htmlspecialchars($_POST['aide']);
                $reponse_aide = "🔍 Je n'ai pas trouvé de réponse exacte pour : *'" . $question_origine . "'*.<br><br>
                <div style='background: #1a1a1a; padding: 15px; border-radius: 8px; border: 1px solid #444; margin-top: 10px;'>
                    <p style='margin-bottom: 10px; color: #aaa; font-size: 0.9em;'>
                        <i class='fas fa-paper-plane'></i> Vous souhaitez poser cette question directement à notre équipe technique ?
                    </p>
                    <form action='' method='POST'>
                        <input type='hidden' name='question_soumise' value='" . $question_origine . "'>
                        <button type='submit' name='envoyer_au_support' style='background: #d4af37; color: #000; border: none; padding: 8px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 0.85em;'>
                            <i class='fas fa-check'></i> Poser ma question au support
                        </button>
                    </form>
                </div>";
                break;
        }
    }
}

// 4. TRAITEMENT DE L'ENVOI DE LA QUESTION PERSONNALISÉE AU SUPPORT
if (isset($_POST['envoyer_au_support'])) {
    $ma_question = htmlspecialchars($_POST['question_soumise']);
    
    try {
        // 
        $ins = $pdo->prepare("INSERT INTO tickets_support (user_id, question, statut, date_envoi) VALUES (?, ?, 'En attente', NOW())");
        $ins->execute([$user_id, $ma_question]);
        
        $reponse_aide = "<div style='color:#2ecc71; background: rgba(46, 204, 113, 0.1); padding: 15px; border-radius: 8px; border: 1px solid #2ecc71;'>✅ Votre question : *\"" . $ma_question . "\"* a bien été transmise à l'administration de **Blue World**. Une réponse vous sera apportée rapidement !</div>";
    } catch (Exception $e) {
        $reponse_aide = "<div style='color:#e74c3c;'>❌ Une erreur est survenue lors de l'envoi de votre question.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support | Finance & Gestion pro</title>
     <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
       <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/sup.css">
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
    
    <div class="search-help-box">
    
    <h3><i class="fas fa-question-circle"></i> Centre d'Aide & Recherche</h3>
    
    <form action="" method="POST">
        <input type="text" name="aide" class="search-input-help" placeholder="Ex: Comment voir mes graphiques ? Problème d'affichage..." required>
        <button type="submit" name="do_research" class="search-btn-help">
            <i class="fas fa-search"></i> Rechercher
        </button>
    </form>

    <?php if (!empty($reponse_aide)): ?>
        <div class="result-box">
            <?php echo $reponse_aide; ?>
        </div>
    <?php endif; ?>

</div>
<script>
  // 1. Soumission fluide 
  const supportForm = document.querySelector('.support-action-box form');
  if (supportForm) {
    supportForm.addEventListener('submit' function() {
      const btn = this.querySelector('.submít-support-btn');
      if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
      }
    });
  }
  
  // 
  const resultBox = document.querySelector('.result-box');
  if (resultBox) {
    resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest'});
  }
</script>
  </body>
</html>