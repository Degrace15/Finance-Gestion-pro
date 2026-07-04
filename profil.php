<?php
session_start();
require_once 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// 1. RÉCUPÉRATION DES INFOS ET MODE INTERFACE
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

if (!$u) {
    session_destroy();
    header('Location: auth.php');
    exit();
}

// Mise à jour de la session pour l'interface
$_SESSION['interface_expert'] = $u['interface_expert'] ?? 0;
$is_expert = ($_SESSION['interface_expert'] == 1);

// Vérification du paywall
require_once 'check_trial.php';

// 2. LOGIQUE DE MISE À JOUR (PROFIL & PHOTO AVATAR)
if (isset($_POST['update_profile'])) {
    $nom = htmlspecialchars($_POST['nom_complet']);
    $email = htmlspecialchars($_POST['email']);
    $tel = htmlspecialchars($_POST['telephone']);
    $entreprise = htmlspecialchars($_POST['nom_entreprise']);
    $photo_bdd = $u['avatar'] ?? 'default-avatar.png';

    // Traitement de l'upload de l'image si présente
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['avatar_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $newName = "avatar_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = "./uploads/avatars/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $upload_dir . $newName)) {
                $photo_bdd = $upload_dir . $newName;
            }
        }
    }

    try {
        $upd = $pdo->prepare("UPDATE utilisateurs SET nom_complet = ?, email = ?, telephone = ?, nom_entreprise = ?, avatar = ? WHERE id = ?");
        if ($upd->execute([$nom, $email, $tel, $entreprise, $photo_bdd, $user_id])) {
            $_SESSION['user_nom'] = $nom;
            $message = "<div class='alert-msg success'>✅ Profil et préférences mis à jour avec succès !</div>";
            
            // Rafraîchir les données locales
            $u['nom_complet'] = $nom;
            $u['email'] = $email;
            $u['telephone'] = $tel;
            $u['nom_entreprise'] = $entreprise;
            $u['avatar'] = $photo_bdd;
        }
    } catch (Exception $e) {
        $message = "<div class='alert-msg error'>❌ Erreur lors de la mise à jour des informations.</div>";
    }
}

// Gestion de la photo de profil par défaut
$avatar_src = (!empty($u['avatar']) && file_exists($u['avatar'])) ? $u['avatar'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// --- CALCULS DES POURCENTAGES PREMIUM ---
$completion_score = 20; // 20% de base pour l'inscription
if (!empty($u['nom_complet'])) $completion_score += 20;
if (!empty($u['nom_entreprise'])) $completion_score += 20;
if (!empty($u['email'])) $completion_score += 20;
if (!empty($u['avatar']) && $u['avatar'] !== 'default-avatar.png') $completion_score += 20;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil | Blue World Pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/profi.css">

    <?php if ($is_expert): ?>
        <link rel="stylesheet" href="./CSS/premium.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>

    <style>
        :root { --accent: #00d4ff; --card-bg: #1a1a1a; }
        .expert-badge { background: #d4af37; color: #000; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: 10px; vertical-align: middle; }
        .alert-msg { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid #e74c3c; }
        .profile-card { background: var(--card-bg); border: 1px solid #333; border-radius: 15px; padding: 30px; margin-top: 20px; }
        
        <?php if($is_expert): ?>
        .profile-card { border-color: #d4af37; box-shadow: 0 0 15px rgba(212, 175, 55, 0.1); }
        <?php endif; ?>
        
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #222; align-items: center; }
        .info-row label { color: #888; font-size: 0.9em; }
        .info-row span { color: #fff; font-weight: 500; }
        .hidden { display: none; }

        /* Style interactif du bouton de profil et menu */
        .profile-trigger-container { position: relative; display: inline-block; }
        .btn-profile-trigger { background: none; border: none; color: inherit; font-size: inherit; cursor: pointer; padding: 0; display: inline-flex; align-items: center; outline: none; transition: transform 0.2s ease; }
        .btn-profile-trigger:hover { transform: scale(1.05); color: var(--accent); }
        
        .avatar-img-circle { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #333; margin-right: 10px; }
        <?php if($is_expert): ?>
        .avatar-img-circle { border-color: #d4af37; }
        <?php endif; ?>

        .profile-dropdown-menu { position: absolute; top: 110%; left: 0; background: #222; border: 1px solid #444; border-radius: 10px; width: 240px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); z-index: 1000; padding: 8px 0; display: none; animation: fadeIn 0.2s ease; }
        .profile-dropdown-menu.show { display: block; }
        .profile-dropdown-menu a { display: flex; align-items: center; width: 100%; padding: 10px 15px; color: #ccc; text-align: left; font-size: 0.9em; cursor: pointer; text-decoration: none; box-sizing: border-box; }
        .profile-dropdown-menu a:hover { background: #333; color: #fff; }
        .profile-dropdown-menu i { margin-right: 12px; width: 16px; text-align: center; color: #d4af37; }
        .dropdown-divider { border-top: 1px solid #333; margin: 6px 0; }

        /* Styles de la section des graphiques Premium */
        .premium-chart-box { background: #1a1a1a; border: 1px solid #d4af37; border-radius: 15px; padding: 25px; margin-top: 25px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .metric-card { background: #111; border: 1px solid #222; padding: 15px; border-radius: 10px; text-align: center; }
        .metric-card h4 { color: #888; font-size: 0.8em; margin-bottom: 5px; text-transform: uppercase; }
        .metric-card .value { color: #d4af37; font-size: 1.6em; font-weight: bold; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
<body class="dashboard-body">

    <div class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header-main">
            <div class="welcome-text">
                <h1>
                    <div class="profile-trigger-container">
                        <button class="btn-profile-trigger" id="profileDropdownBtn" title="Options de profil">
                            <?php if(!empty($u['avatar'])): ?>
                                <img src="<?= $avatar_src ?>" class="avatar-img-circle" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="margin-right: 10px;"></i> 
                            <?php endif; ?>
                            Mon <span>Profil</span>
                        </button>
                        
                        <div class="profile-dropdown-menu" id="profileDropdownMenu">
                            <a href="#" onclick="toggleEdit(); return false;"><i class="fas fa-camera"></i> Changer la photo</a>
                            <a href="#" onclick="toggleEdit(); return false;"><i class="fas fa-user-cog"></i> Modifier mes infos</a>
                            <a href="news.php"><i class="fas fa-bell"></i> Notifications</a>
                            <div class="dropdown-divider"></div>
                            <a href="support.php"><i class="fas fa-question-circle"></i> Obtenir de l'aide</a>
                        </div>
                    </div>

                    <?php if($is_expert) echo '<span class="expert-badge">EXPERT</span>'; ?>
                </h1>
                <p style="color: var(--text-muted)">Gérez les accès et les informations de votre entreprise</p>
            </div>
        </header>

        <div class="profile-card">
            <?= $message ?>

            <div id="view-profile">
                <div style="text-align: center; margin-bottom: 25px;">
                    <img src="<?= $avatar_src ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #d4af37; box-shadow: 0 4px 10px rgba(0,0,0,0.3);" alt="Photo de profil">
                </div>
                <div class="info-row">
                    <label><i class="fas fa-user"></i> Nom complet</label>
                    <span><?= htmlspecialchars($u['nom_complet']); ?></span>
                </div>
                <div class="info-row">
                    <label><i class="fas fa-building"></i> Entreprise</label>
                    <span><?= htmlspecialchars($u['nom_entreprise']); ?></span>
                </div>
                <div class="info-row">
                    <label><i class="fas fa-envelope"></i> Email de contact</label>
                    <span><?= htmlspecialchars($u['email']); ?></span>
                </div>
                <div class="info-row">
                    <label><i class="fas fa-phone"></i> Téléphone</label>
                    <span><?= htmlspecialchars($u['telephone']); ?></span>
                </div>
                <div class="info-row">
                    <label><i class="fas fa-crown"></i> Type de Plan</label>
                    <span style="color:#d4af37"><?= strtoupper($u['plan'] ?? 'Gratuit'); ?></span>
                </div>
                
                <br>
                <button class="btn-primary" onclick="toggleEdit()" style="width: 100%; justify-content: center;">
                    <i class="fas fa-edit"></i> Modifier mes informations
                </button>
            </div>

            <form id="edit-form" action="" method="POST" enctype="multipart/form-data" class="hidden">
                <div class="form-section" style="margin-bottom: 15px;">
                    <label style="color:#888; display:block; margin-bottom:5px;"><i class="fas fa-image"></i> Photo de profil (Logo / Avatar)</label>
                    <input type="file" name="avatar_file" accept="image/*" style="width:100%; padding:8px; border-radius:5px; background:#222; border:1px solid #444; color:#fff;">
                </div>
                <div class="form-section" style="margin-bottom: 15px;">
                    <label style="color:#888; display:block; margin-bottom:5px;">Nom Complet</label>
                    <input type="text" name="nom_complet" value="<?= htmlspecialchars($u['nom_complet']); ?>" required style="width:100%; padding:10px; border-radius:5px; background:#222; border:1px solid #444; color:#fff;">
                </div>
                <div class="form-section" style="margin-bottom: 15px;">
                    <label style="color:#888; display:block; margin-bottom:5px;">Nom de l'entreprise</label>
                    <input type="text" name="nom_entreprise" value="<?= htmlspecialchars($u['nom_entreprise']); ?>" required style="width:100%; padding:10px; border-radius:5px; background:#222; border:1px solid #444; color:#fff;">
                </div>
                <div class="form-section" style="margin-bottom: 15px;">
                    <label style="color:#888; display:block; margin-bottom:5px;">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($u['email']); ?>" required style="width:100%; padding:10px; border-radius:5px; background:#222; border:1px solid #444; color:#fff;">
                </div>
                <div class="form-section" style="margin-bottom: 20px;">
                    <label style="color:#888; display:block; margin-bottom:5px;">Téléphone</label>
                    <input type="tel" name="telephone" value="<?= htmlspecialchars($u['telephone']); ?>" required style="width:100%; padding:10px; border-radius:5px; background:#222; border:1px solid #444; color:#fff;">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update_profile" class="btn-primary" style="flex:1; justify-content: center;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="btn-cancel" onclick="toggleEdit()" style="flex:1; background:#444; color:#fff; border:none; padding:10px; border-radius:8px; cursor:pointer;">
                        Annuler
                    </button>
                </div>
            </form>
        </div>

        <?php if ($is_expert): ?>
            <div class="premium-chart-box">
                <h3 style="color:#d4af37; margin-bottom:20px;">
                    <i class="fas fa-chart-line"></i> Analyses & Statistiques Premium
                </h3>
                
                <div class="metrics-grid">
                    <div class="metric-card">
                        <h4>Complétion</h4>
                        <div class="value"><?= $completion_score ?>%</div>
                    </div>
                    <div class="metric-card">
                        <h4>Sécurité</h4>
                        <div class="value">95%</div>
                    </div>
                    <div class="metric-card">
                        <h4>Quota Data</h4>
                        <div class="value">100%</div>
                    </div>
                </div>

                <div style="height: 250px; position: relative;">
                    <canvas id="profilePremiumChart"></canvas>
                </div>
            </div>

            <script src="./js/premium-charts.js"></script>
            <script>
                const ctxProfile = document.getElementById('profilePremiumChart').getContext('2d');
                new Chart(ctxProfile, {
                    type: 'radar', // Graphique en radar idéal pour illustrer des métriques de profil/compte
                    data: {
                        labels: ['Complétion Profil', 'Sécurité Accès', 'Quota Stockage', 'Stabilité Plan', 'Intégration BDD'],
                        datasets: [{
                            label: 'Statut du Compte (%)',
                            data: [<?= $completion_score ?>, 95, 100, 90, 100],
                            backgroundColor: 'rgba(212, 175, 55, 0.2)',
                            borderColor: '#d4af37',
                            pointBackgroundColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { labels: { color: '#fff' } }
                        },
                        scales: {
                            r: {
                                grid: { color: '#333' },
                                angleLines: { color: '#333' },
                                pointLabels: { color: '#aaa', font: { size: 11 } },
                                ticks: { display: false, maxTicksLimit: 5 }
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

        function toggleEdit() {
            const view = document.getElementById('view-profile');
            const form = document.getElementById('edit-form');
            view.classList.toggle('hidden');
            form.classList.toggle('hidden');
        }

        // Gestion du clic pour ouvrir/fermer le menu de profil
        const dropdownBtn = document.getElementById('profileDropdownBtn');
        const dropdownMenu = document.getElementById('profileDropdownMenu');

        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            dropdownMenu.classList.remove('show');
        });
    </script>
</body>
</html>