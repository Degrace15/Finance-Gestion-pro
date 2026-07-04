<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 1. RÉCUPÉRATION DE L'UTILISATEUR
    $stmt_u = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt_u->execute([$user_id]);
    $user = $stmt_u->fetch();

    // 2. MISE À JOUR DE LA SESSION
    $_SESSION['interface_expert'] = $user['interface_expert'] ?? 0;

    // 3. VÉRIFICATION DU PAYWALL
    require_once 'check_trial.php';

    $nom_affichage = $user['nom_complet'] ?? "Utilisateur";
    $plan          = $user['plan'] ?? 'essai';
    $is_expert     = ($_SESSION['interface_expert'] == 1);

    // 4. CALCULS FINANCIERS
    $stmt_v = $pdo->prepare("SELECT SUM(`prix_total`) as total_v FROM ventes WHERE user_id = ?");
    $stmt_v->execute([$user_id]);
    $total_ventes = $stmt_v->fetch()['total_v'] ?? 0;

    $stmt_d = $pdo->prepare("SELECT SUM(montant) as total_d FROM depenses WHERE user_id = ?");
    $stmt_d->execute([$user_id]);
    $total_depenses = $stmt_d->fetch()['total_d'] ?? 0;

    $benefice = $total_ventes - $total_depenses;

    // 5. CALCUL REVENUS SEMAINE (Mode Expert)
    $revenus_semaine = [0, 0, 0, 0, 0, 0, 0];
    if($is_expert) {
        $stmt_semaine = $pdo->prepare("
            SELECT 
                CASE WHEN DAYOFWEEK(date_vente) = 1 THEN 6 ELSE DAYOFWEEK(date_vente) - 2 END as jour_index,
                SUM(prix_total) as total_jour
            FROM ventes
            WHERE user_id = ? AND WEEK(date_vente, 1) = WEEK(NOW(), 1) AND YEAR(date_vente) = YEAR(NOW())
            GROUP BY jour_index
        ");
        $stmt_semaine->execute([$user_id]);
        $ventes_jours = $stmt_semaine->fetchAll();

        foreach($ventes_jours as $vj) {
            $idx = (int)$vj['jour_index'];
            if($idx >= 0 && $idx <= 6) {
                $revenus_semaine[$idx] = (float)$vj['total_jour'];
            }
        }
    }

} catch(Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Finance & Gestion Pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <?php if($is_expert): ?>
        <link rel="stylesheet" href="./CSS/premium.css">
    <?php endif; ?>

    <style>
        :root { --accent: #00d4ff; --bg: #111; --card: #1a1a1a; }
        .burger-menu { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: var(--accent); color: #111; padding: 10px 15px; border-radius: 8px; cursor: pointer; }
        @media (max-width: 768px) { .burger-menu { display: block; } .sidebar { left: -260px; transition: 0.3s ease; position: fixed; } .sidebar.active { left: 0; } .main-content { margin-left: 0 !important; padding-top: 70px; } .stats-container { grid-template-columns: 1fr; } }
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-card { background: var(--card); border: 1px solid #333; padding: 25px; border-radius: 15px; text-align: center; transition: 0.3s; }
        .stat-card i { font-size: 2rem; margin-bottom: 10px; }
        .stat-card h3 { font-size: 0.9rem; color: #888; text-transform: uppercase; margin-bottom: 10px; }
        .stat-card p { font-size: 1.5rem; font-weight: bold; color: #fff; margin: 0; }
        .chart-section { background: var(--card); margin-top: 30px; padding: 20px; border-radius: 15px; border: 1px solid var(--accent); }
    </style>

    <!-- Vérification internet AVANT tout affichage -->
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
<body class="dashboard-body">

    <div class="burger-menu" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <h1>Tableau de bord <?php echo $is_expert ? '<span style="font-size:12px; background:var(--accent); color:#000; padding:2px 8px; border-radius:10px;">EXPERT</span>' : ''; ?></h1>
            <p>Heureux de vous revoir, <span style="color:var(--accent)"><?php echo htmlspecialchars($nom_affichage); ?></span></p>
        </header>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-shopping-basket" style="color:var(--accent);"></i>
                <h3>Total Ventes</h3>
                <p><?php echo number_format($total_ventes, 0, ',', ' '); ?> <small>FCFA</small></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave" style="color:#e74c3c;"></i>
                <h3>Total Dépenses</h3>
                <p><?php echo number_format($total_depenses, 0, ',', ' '); ?> <small>FCFA</small></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line" style="color:<?php echo ($benefice >= 0) ? '#2ecc71' : '#e74c3c'; ?>;"></i>
                <h3>Bénéfice Net</h3>
                <p><?php echo number_format($benefice, 0, ',', ' '); ?> <small>FCFA</small></p>
            </div>
        </div>

        <?php if($is_expert): ?>
            <div class="chart-section">
                <h3 style="color:var(--accent); margin-bottom:15px;"><i class="fas fa-chart-bar"></i> Analyse visuelle des flux</h3>
                <div style="height:300px;">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>

            <div class="chart-section">
                <h3 style="color:var(--accent); margin-bottom:15px;"><i class="fas fa-wave-square"></i> Croissance Hebdomadaire des Revenus</h3>
                <div style="height:300px; position:relative;">
                    <canvas id="dashboardChart"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Graphique 1 — Barres
                new Chart(document.getElementById('financeChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Ventes', 'Dépenses', 'Bénéfice'],
                        datasets: [{
                            label: 'Situation Financière (FCFA)',
                            data: [<?php echo $total_ventes; ?>, <?php echo $total_depenses; ?>, <?php echo $benefice; ?>],
                            backgroundColor: ['#d4af37', '#e74c3c', '#2ecc71'],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#333' }, ticks: { color: '#888' } },
                            x: { ticks: { color: '#fff' } }
                        }
                    }
                });

                // Graphique 2 — Courbe hebdomadaire
                new Chart(document.getElementById('dashboardChart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                        datasets: [{
                            label: 'Chiffre d\'affaire hebdomadaire (FCFA)',
                            data: <?php echo json_encode($revenus_semaine); ?>,
                            borderColor: '#d4af37',
                            backgroundColor: 'rgba(212, 175, 55, 0.06)',
                            tension: 0.3,
                            fill: true,
                            borderWidth: 3,
                            pointBackgroundColor: '#d4af37',
                            pointRadius: 4
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
                            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888', font: { family: 'Poppins' } } },
                            x: { grid: { display: false }, ticks: { color: '#ffffff', font: { family: 'Poppins', weight: 'bold' } } }
                        }
                    }
                });
            </script>

        <?php else: ?>
            <div style="margin-top:30px; padding:30px; text-align:center; background:#1a1a1a; border-radius:15px; border:1px dashed #444;">
                <i class="fas fa-lock" style="font-size:2rem; color:var(--accent); margin-bottom:10px;"></i>
                <p style="color:#fff; font-size:1.1rem; margin-bottom:5px;">Analyses avancées verrouillées</p>
                <p style="color:#888; font-size:0.9rem;">Passez en mode <strong>Expert</strong> pour débloquer les graphiques et l'analyse de croissance en temps réel.</p>
            </div>
        <?php endif; ?>

    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>

</body>
</html>