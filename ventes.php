<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cle_secrete = "BLUE_WORLD_2024_SECRET_KEY"; 

// 1. RÉCUPÉRATION DE L'UTILISATEUR (Pour le mode Expert)
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// On s'assure que la session est à jour pour l'interface
$_SESSION['interface_expert'] = $user['interface_expert'] ?? 0;
$is_expert = ($_SESSION['interface_expert'] == 1);

require_once 'check_trial.php'; 

// --- LOGIQUE DE LIMITE ---
$plan = $user['plan'] ?? 'gratuit';
$plan_compare = strtolower(trim($plan)); 
$statut_abo = strtolower(trim($user['statut_abonnement'] ?? 'premium'));

$plans_illimites = ['pme', 'startup', 'ge'];

$countVentes = $pdo->prepare("SELECT COUNT(*) FROM ventes WHERE user_id = ?");
$countVentes->execute([$user_id]);
$totalVentes = $countVentes->fetchColumn();

if (in_array($plan_compare, $plans_illimites) || $statut_abo === 'premium') {
    $limite_texte = '<i class="fas fa-infinity"></i>';
    $limite_nombre = 999999; 
} else {
    $limite_texte = '50 000';
    $limite_nombre = 50000;
}

$peut_ajouter = ($totalVentes < $limite_nombre);
$message = "";

// 2. LOGIQUE DE VENTE
if (isset($_POST['vendre'])) {
    if (!$peut_ajouter) {
        $message = "<div class='alert error'>⚠️ Limite de transactions atteinte.</div>";
    } else {
        $p_id = $_POST['produit_id']; 
        $type_vente = $_POST['type_vente']; 
        $quantite_saisie = floatval($_POST['quantite']);
        $prix_unitaire_saisi = floatval($_POST['prix_saisi']); 
        $poids_par_carton = 10; 

        $check = $pdo->prepare("SELECT stock_actuel FROM produits WHERE id = ? AND user_id = ?");
        $check->execute([$p_id, $user_id]);
        $prod = $check->fetch();

        if ($prod) {
            $total_a_payer = $prix_unitaire_saisi * $quantite_saisie;
            $reduction_stock = ($type_vente === 'gros') ? $quantite_saisie : ($quantite_saisie / $poids_par_carton);

            if ($prod['stock_actuel'] >= $reduction_stock) {
                try {
                    $pdo->beginTransaction();
                    $stmtVente = $pdo->prepare("INSERT INTO ventes (user_id, produit_id, quantite_vendue, prix_total, date_vente) VALUES (?, ?, ?, ?, NOW())");
                    $stmtVente->execute([$user_id, $p_id, $quantite_saisie, $total_a_payer]);
                    
                    $id_derniere_vente = $pdo->lastInsertId();
                    $pdo->prepare("UPDATE produits SET stock_actuel = stock_actuel - ? WHERE id = ?")->execute([$reduction_stock, $p_id]);
                    $pdo->commit();
                    
                    $token_securite = md5($id_derniere_vente . $cle_secrete);
                    $message = "<div class='alert success'>✅ Vente enregistrée ! <a href='facture.php?id=$id_derniere_vente&token=$token_securite' target='_blank' class='btn-facture'>Imprimer Facture</a></div>";
                    $totalVentes++; 
                    $peut_ajouter = ($totalVentes < $limite_nombre);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "<div class='alert error'>❌ Erreur SQL.</div>";
                }
            } else {
                $message = "<div class='alert error'>❌ Stock insuffisant pour cette vente.</div>";
            }
        }
    }
}

// Liste des produits en stock
$get_prods = $pdo->prepare("SELECT id, nom_produit, stock_actuel FROM produits WHERE user_id = ? AND stock_actuel > 0");
$get_prods->execute([$user_id]);
$produits = $get_prods->fetchAll();

// 3. STATISTIQUES POUR LE GRAPHIQUE EXPERT (Somme des ventes par article)
$graph_labels = [];
$graph_data = [];
if ($is_expert) {
    $stmt_graph = $pdo->prepare("
        SELECT p.nom_produit, SUM(v.prix_total) as total_vendu 
        FROM ventes v 
        JOIN produits p ON v.produit_id = p.id 
        WHERE v.user_id = ? 
        GROUP BY v.produit_id 
        ORDER BY total_vendu DESC 
        LIMIT 5
    ");
    $stmt_graph->execute([$user_id]);
    $donnees_ventes = $stmt_graph->fetchAll();
    
    foreach ($donnees_ventes as $dv) {
        $graph_labels[] = $dv['nom_produit'];
        $graph_data[] = (float)$dv['total_vendu'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendre | F&G Pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/dash.css">
    <link rel="stylesheet" href="./CSS/vente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <?php if ($is_expert): ?>
        <link rel="stylesheet" href="./CSS/premium.css">
    <?php endif; ?>

    <style>
        :root { --accent: #00d4ff; }
        .expert-badge { background: #d4af37; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; margin-left: 10px; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .btn-facture { color: #fff; text-decoration: underline; margin-left: 10px; font-size: 0.9em; }
        .stock-info-label { font-size: 0.85em; color: #888; display: block; margin-top: 5px; }
        
        /* Style des Graphiques Experts */
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

    <div class="burger-menu" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h1>
            <i class="fas fa-cart-arrow-down"></i> Nouvelle Vente
            <?php if($is_expert) echo '<span class="expert-badge">EXPERT</span>'; ?>
        </h1>

        <div class="stat-info" style="background: #1a1a1a; padding: 15px; border-radius: 12px; border: 1px solid #333; margin-bottom: 20px;">
            <p>Plan : <strong style="color:var(--accent)"><?php echo strtoupper($plan); ?></strong> | 
            Ventes : <strong><?php echo $totalVentes; ?> / <?php echo $limite_texte; ?></strong></p>
        </div>

        <?php echo $message; ?>

        <div class="box-vente" <?php if($is_expert) echo 'style="border: 1px solid #d4af37;"'; ?>>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Choisir l'article :</label>
                    <select name="produit_id" id="produit_select" required>
                        <option value="">-- Sélectionner l'article --</option>
                        <?php foreach($produits as $p): 
                            // CALCUL DÉTAILLÉ CARTONS ET KG
                            $stock_val = (float)$p['stock_actuel'];
                            $c = floor($stock_val);
                            $k = round(($stock_val - $c) * 10);
                            
                            $txt_stock = $c . " carton(s)";
                            if ($k > 0) { $txt_stock .= " et " . $k . " kg"; }
                            
                            // Alerte stock faible en rouge (Mode Expert)
                            $style_alerte = ($is_expert && $stock_val <= 3) ? 'style="color:#e74c3c; font-weight:bold;"' : '';
                        ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $style_alerte; ?>>
                                <?php echo htmlspecialchars($p['nom_produit']); ?> (En stock: <?php echo $txt_stock; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exchange-alt"></i> Type de vente :</label>
                    <select name="type_vente" id="type_vente" onchange="majInterface()" required>
                        <option value="gros">📦 Vente en Gros (Carton)</option>
                        <option value="detail">⚖️ Vente au Détail (Kg)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="label_prix"><i class="fas fa-money-bill"></i> Prix du Carton (FCFA) :</label>
                    <input type="number" name="prix_saisi" placeholder="Ex: 15000" required>
                </div>

                <div class="form-group">
                    <label id="label_qte"><i class="fas fa-sort-numeric-up"></i> Nombre de Cartons :</label>
                    <input type="number" name="quantite" step="0.01" min="0.01" value="1" required>
                </div>

                <button type="submit" name="vendre" class="btn-vendre" <?php echo !$peut_ajouter ? 'disabled style="background:#444;"' : ''; ?>>
                    <?php echo $peut_ajouter ? '<i class="fas fa-check-circle"></i> VALIDER LA VENTE' : 'LIMITE ATTEINTE'; ?>
                </button>
            </form>
        </div>

        <?php if ($is_expert): ?>
            <div class="chart-section">
                <h3 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-bolt"></i> Palmarès du Top 5 des Articles les plus Vendus</h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="ventesChart"></canvas>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            
            <script>
                const ctxVentes = document.getElementById('ventesChart').getContext('2d');
                new Chart(ctxVentes, {
                    type: 'bar',
                    data: {
                        // Récupération dynamique des labels PHP (noms des produits)
                        labels: <?php echo json_encode(!empty($graph_labels) ? $graph_labels : ['Aucune vente']); ?>,
                        datasets: [{
                            label: 'Volume de ventes (FCFA)',
                            // Récupération dynamique des montants réels
                            data: <?php echo json_encode(!empty($graph_data) ? $graph_data : [0]); ?>,
                            backgroundColor: '#d4af37', // Or Premium
                            borderRadius: 6,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y', // Barres horizontales modernes très élégantes pour mobile
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#ffffff',
                                    font: { family: 'Poppins', size: 12 }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                ticks: { color: '#888', font: { family: 'Poppins' } }
                            },
                            y: {
                                grid: { display: false }, // On masque la grille Y pour alléger le rendu
                                ticks: { color: '#ffffff', font: { family: 'Poppins', weight: 'bold' } }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>

    </main>

    <script>
        function toggleSidebar() { 
            document.querySelector('.sidebar').classList.toggle('active'); 
        }
        
        function majInterface() {
            const type = document.getElementById('type_vente').value;
            const lp = document.getElementById('label_prix');
            const lq = document.getElementById('label_qte');
            
            if (type === 'gros') {
                lp.innerHTML = '<i class="fas fa-money-bill"></i> Prix du Carton (FCFA) :';
                lq.innerHTML = '<i class="fas fa-sort-numeric-up"></i> Nombre de Cartons :';
            } else {
                lp.innerHTML = '<i class="fas fa-money-bill"></i> Prix au Kilo (FCFA) :';
                lq.innerHTML = '<i class="fas fa-balance-scale"></i> Nombre de Kilos (Kg) :';
            }
        }
    </script>
</body>
</html>