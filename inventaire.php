<?php
session_start();
require_once 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. RÉCUPÉRATION COMPLÈTE DE L'UTILISATEUR
$stmt_u = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt_u->execute([$user_id]);
$user = $stmt_u->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// On s'assure que la session est à jour pour le changement d'interface
$_SESSION['interface_expert'] = $user['interface_expert'];
$is_expert = ($user['interface_expert'] == 1);

require_once 'check_trial.php'; 

$plan = $user['plan'] ?? 'essai'; 
$plan_compare = strtolower(trim($plan));
$plans_limites = ['essai', 'gratuit', 'aucun', ''];

// 2. COMPTAGE DES PRODUITS
$countProduits = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE user_id = ?");
$countProduits->execute([$user_id]);
$totalProduits = $countProduits->fetchColumn();

if (!in_array($plan_compare, $plans_limites)) {
    $limite_texte = '<i class="fas fa-infinity"></i>';
    $limite_nombre = 999999; 
} else {
    $limite_texte = '50 000';
    $limite_nombre = 50000;
}

$peut_ajouter = ($totalProduits < $limite_nombre);
$message = "";

// 3. TRAITEMENT DE L'AJOUT
if (isset($_POST['ajouter_produit'])) {
    if (!$peut_ajouter) {
        $message = "<div class='alert error'>⚠️ Limite atteinte. Passez au mode Premium.</div>";
    } else {
        $nom = trim($_POST['nom_produit']);
        $stock = floatval($_POST['stock_actuel']);
        
        if (!empty($nom)) {
            $stmt = $pdo->prepare("INSERT INTO produits (user_id, nom_produit, prix_vente, stock_actuel) VALUES (?, ?, 0, ?)");
            $stmt->execute([$user_id, $nom, $stock]);
            $message = "<div class='alert success'>✅ Article ajouté au stock !</div>";
            $totalProduits++;
            $peut_ajouter = ($totalProduits < $limite_nombre);
        }
    }
}

// 4. RÉCUPÉRATION POUR AFFICHAGE
$stmt_list = $pdo->prepare("SELECT * FROM produits WHERE user_id = ? ORDER BY nom_produit ASC");
$stmt_list->execute([$user_id]);
$produits = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// 5. CALCUL DES DONNÉES DU GRAPHIQUE (Pour le mode Expert)
$articles_ok = 0;
$articles_faibles = 0;

foreach ($produits as $p) {
    if ($p['stock_actuel'] <= 3) {
        $articles_faibles++;
    } else {
        $articles_ok++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire | F&G Pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/inv.css">
    
    <?php if ($is_expert): ?>
        <link rel="stylesheet" href="./CSS/premium.css">
    <?php endif; ?>
    
    <style>
        :root { --accent: #00d4ff; }
        .expert-badge { background: var(--accent); color: #000; padding: 2px 8px; border-radius: 5px; font-size: 10px; font-weight: bold; margin-left: 10px; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .stock-tag { font-weight: bold; padding: 5px 10px; border-radius: 20px; background: #222; border: 1px solid #444; }
        .faible { border-color: #e74c3c; color: #e74c3c; }
        
        /* Style des Graphiques Experts */
        .chart-section { background: #1a1a1a; margin-top: 30px; padding: 20px; border-radius: 15px; border: 1px solid var(--accent); }
    </style>
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
    <div class="burger-menu" id="toggleBtn"><i class="fas fa-bars"></i></div>
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <h1>
            <i class="fas fa-boxes"></i> Inventaires
            <?php if($is_expert) echo '<span class="expert-badge">EXPERT</span>'; ?>
        </h1>
        
        <div style="background: #1a1a1a; padding: 15px; border-radius: 12px; border: 1px solid #333; margin-bottom: 20px;">
            Plan : <strong style="color:var(--accent)"><?= strtoupper($plan) ?></strong> | 
            Articles : <?= $totalProduits ?> / <?= $limite_texte ?>
        </div>

        <?= $message ?>

        <div class="box-vente" <?php if($is_expert) echo 'style="border: 1px solid var(--accent);"'; ?>>
            <form method="POST">
                <div class="form-group">
                    <label>NOM DE L'ARTICLE</label>
                    <input type="text" name="nom_produit" placeholder="Ex: Cuisse de Poulet" required <?= !$peut_ajouter ? 'disabled' : '' ?>>
                </div>
                <div class="form-group">
                    <label>STOCK INITIAL (Nombre de Cartons)</label>
                    <input type="number" name="stock_actuel" step="0.01" placeholder="Ex: 50.5" required <?= !$peut_ajouter ? 'disabled' : '' ?>>
                </div>
                <button type="submit" name="ajouter_produit" class="btn-vendre" <?= !$peut_ajouter ? 'disabled' : '' ?>>
                    <?= $peut_ajouter ? '<i class="fas fa-plus"></i> ENREGISTRER' : 'LIMITE ATTEINTE' ?>
                </button>
            </form>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>ARTICLE</th>
                        <th>STOCK DÉTAILLÉ</th>
                        <?php if($is_expert): ?>
                            <th>ALERTE</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($produits as $p): 
                        // --- LE CALCUL DÉTAILLÉ ---
                        $c_entiers = floor($p['stock_actuel']);
                        $k_restants = round(($p['stock_actuel'] - $c_entiers) * 10);
                        
                        $affichage = $c_entiers . " cartons";
                        if($k_restants > 0) {
                            $affichage .= " et " . $k_restants . " kg";
                        }
                        
                        // Alerte stock faible (Moins de 3 cartons)
                        $est_faible = ($p['stock_actuel'] <= 3);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['nom_produit']) ?></strong></td>
                        <td>
                            <span class="stock-tag <?= ($is_expert && $est_faible) ? 'faible' : '' ?>">
                                <i class="fas fa-box-open"></i> <?= $affichage ?>
                            </span>
                        </td>
                        <?php if($is_expert): ?>
                            <td>
                                <?php if($est_faible): ?>
                                    <span style="color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> BAS</span>
                                <?php else: ?>
                                    <span style="color:#2ecc71;"><i class="fas fa-check"></i> OK</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($is_expert): ?>
            <div class="chart-section">
                <h3 style="color:var(--accent); margin-bottom:15px;"><i class="fas fa-chart-pie"></i> Répartition Proportionnelle du Stock (%)</h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="inventairesChart"></canvas>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            
            <script>
                const ctxInv = document.getElementById('inventairesChart').getContext('2d');
                new Chart(ctxInv, {
                    type: 'doughnut',
                    data: {
                        labels: ['Stock Sécurisé (OK)', 'Stock Critique (BAS)'],
                        datasets: [{
                            // Injection directe des statistiques réelles calculées en PHP
                            data: [<?= $articles_ok ?>, <?= $articles_faibles ?>],
                            backgroundColor: [
                                '#2ecc71', // Vert pour les articles OK
                                '#e74c3c'  // Rouge pour les articles en alerte
                            ],
                            borderWidth: 2,
                            borderColor: '#1a1a1a' // Fond sombre assorti à Blue World
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#ffffff',
                                    font: { family: 'Poppins', size: 12 }
                                }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>

    </main>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });
    </script>
</body>
</html>