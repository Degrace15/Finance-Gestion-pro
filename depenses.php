<?php
session_start();
require_once 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. RÉCUPÉRATION UTILISATEUR ET MODE INTERFACE
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Mise à jour de la session pour l'interface dynamique
$_SESSION['interface_expert'] = $user['interface_expert'] ?? 0;
$is_expert = ($_SESSION['interface_expert'] == 1);

require_once 'check_trial.php'; 

// --- LOGIQUE DYNAMIQUE DE LIMITE ---
$plan = $user['plan'] ?? 'gratuit';
$plan_compare = strtolower(trim($plan)); 

$countDepenses = $pdo->prepare("SELECT COUNT(*) FROM depenses WHERE user_id = ?");
$countDepenses->execute([$user_id]);
$totalDepenses = $countDepenses->fetchColumn();

$plans_illimites = ['pme', 'startup', 'ge', 'premium'];

if (in_array($plan_compare, $plans_illimites)) {
    $limite_texte = '<i class="fas fa-infinity"></i>';
    $limite_nombre = 999999; 
} else {
    $limite_texte = '50 000';
    $limite_nombre = 50000;
}

$peut_ajouter = ($totalDepenses < $limite_nombre);
$message = "";

// 2. TRAITEMENT DE L'AJOUT
if (isset($_POST['ajouter_depense'])) {
    if (!$peut_ajouter) {
        $message = "<div class='alert error'>⚠️ Limite atteinte ($totalDepenses / $limite_texte). Passez au pack supérieur.</div>";
    } else {
        $titre = trim($_POST['titre_depense']); 
        $categorie = $_POST['categorie'];
        $montant = floatval($_POST['montant']);
        $date_depense = $_POST['date_depense'];
        
        if (!empty($titre) && $montant > 0) {
            $sql = "INSERT INTO depenses (user_id, titre_depense, categorie, montant, date_depense) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$user_id, $titre, $categorie, $montant, $date_depense]);
            $message = "<div class='alert success'>✅ Dépense enregistrée avec succès !</div>";
            $totalDepenses++; 
            $peut_ajouter = ($totalDepenses < $limite_nombre);
        }
    }
}

// Récupération pour l'affichage
$stmt_list = $pdo->prepare("SELECT * FROM depenses WHERE user_id = ? ORDER BY date_depense DESC");
$stmt_list->execute([$user_id]);
$depenses = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// 3. STATISTIQUES REELLES PAR CATEGORIE POUR LE GRAPHIQUE EXPERT
$stats_categories = [
    'Appro' => 0,
    'Loyer' => 0,
    'Transport' => 0,
    'Facture' => 0,
    'Salaire' => 0,
    'Autre' => 0
];

if ($is_expert) {
    $stmt_stats = $pdo->prepare("SELECT categorie, SUM(montant) as total_montant FROM depenses WHERE user_id = ? GROUP BY categorie");
    $stmt_stats->execute([$user_id]);
    $resultats_stats = $stmt_stats->fetchAll();

    foreach ($resultats_stats as $row) {
        if (array_key_exists($row['categorie'], $stats_categories)) {
            $stats_categories[$row['categorie']] = (float)$row['total_montant'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dépenses | Blue World Pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/dep.css">

    <style>
        :root { --accent: #00d4ff; }
        .expert-badge { background: #d4af37; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; margin-left: 10px; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        
        /* Style Premium pour le tableau et graphique */
        <?php if($is_expert): ?>
        .high-expense { border-left: 4px solid #e74c3c !important; }
        <?php endif; ?>
        .chart-section { background: #1a1a1a; margin-top: 30px; padding: 20px; border-radius: 15px; border: 1px solid #d4af37; }
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
        <header>
            <h1>
                <i class="fas fa-wallet"></i> Mes Dépenses
                <?php if($is_expert) echo '<span class="expert-badge">EXPERT</span>'; ?>
            </h1>
            <p style="font-size: 0.9em; color: #888; margin-top: 5px;">
                Plan : <span style="color: var(--accent); font-weight: bold;"><?= strtoupper($plan) ?></span> 
                <span style="margin-left: 15px;">Utilisation : <strong><?= $totalDepenses ?> / <?= $limite_texte ?></strong></span>
            </p>
        </header>

        <?= $message ?>

        <div class="box-vente" <?php if($is_expert) echo 'style="border: 1px solid #d4af37; position: relative; overflow: hidden;"'; ?>>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> DATE</label>
                        <input type="date" name="date_depense" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label><i class="fas fa-pen"></i> MOTIF / TITRE</label>
                        <input type="text" name="titre_depense" placeholder="Raison de la dépense..." required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-list"></i> CATÉGORIE</label>
                        <select name="categorie" required>
                            <option value="" disabled selected>CHOISIR</option>
                            <option value="Appro">Approvisionnement</option>
                            <option value="Loyer">Loyer</option>
                            <option value="Transport">Transport</option>
                            <option value="Facture">Factures</option>
                            <option value="Salaire">Salaires</option>
                            <option value="Autre">Autres</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-coins"></i> MONTANT (FCFA)</label>
                        <input type="number" name="montant" placeholder="0" required>
                    </div>
                </div>

                <button type="submit" name="ajouter_depense" class="btn-vendre" <?= !$peut_ajouter ? 'disabled' : '' ?>>
                    <?php if($peut_ajouter): ?>
                        <i class="fas fa-check-circle"></i> ENREGISTRER LA DÉPENSE
                    <?php else: ?>
                        LIMITE ATTEINTE
                    <?php endif; ?>
                </button>
            </form>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>MOTIF</th>
                        <th>CATÉGORIE</th>
                        <th style="text-align:right;">MONTANT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($depenses) > 0): ?>
                        <?php foreach($depenses as $d): 
                            // En mode expert, on marque en rouge les dépenses > 50 000 FCFA
                            $is_high = ($is_expert && $d['montant'] >= 50000) ? 'high-expense' : '';
                        ?>
                        <tr class="<?= $is_high ?>">
                            <td style="color:#888;"><?= date('d/m/Y', strtotime($d['date_depense'])) ?></td>
                            <td><strong style="color: white;"><?= htmlspecialchars($d['titre_depense']) ?></strong></td>
                            <td><span class="cat-badge"><?= htmlspecialchars($d['categorie']) ?></span></td>
                            <td style="color:#ff4d4d; font-weight:bold; text-align:right;">
                                <?= number_format($d['montant'], 0, '.', ' ') ?> <small>FCFA</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px; opacity: 0.5;">Aucune dépense enregistrée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($is_expert): ?>
            <div class="chart-section">
                <h3 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-chart-bar"></i> Structure et Répartition des Coûts (FCFA)</h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="depensesChart"></canvas>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            
            <script>
                const ctxDep = document.getElementById('depensesChart').getContext('2d');
                new Chart(ctxDep, {
                    type: 'bar', // Barres verticales pour comparer les coûts distinctement
                    data: {
                        labels: ['Appro', 'Loyer', 'Transport', 'Factures', 'Salaires', 'Autres'],
                        datasets: [{
                            label: 'Total Décaissé (FCFA)',
                            // Injection dynamique des vraies valeurs calculées en PHP
                            data: [
                                <?= $stats_categories['Appro'] ?>, 
                                <?= $stats_categories['Loyer'] ?>, 
                                <?= $stats_categories['Transport'] ?>, 
                                <?= $stats_categories['Facture'] ?>, 
                                <?= $stats_categories['Salaire'] ?>, 
                                <?= $stats_categories['Autre'] ?>
                            ],
                            backgroundColor: '#d4af37', // Or Premium
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#ffffff', font: { family: 'Poppins', size: 12 } }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                ticks: { color: '#888', font: { family: 'Poppins' } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#ffffff', font: { family: 'Poppins', weight: 'bold' } }
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