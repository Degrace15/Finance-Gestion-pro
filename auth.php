<?php
session_start();
require_once 'config.php';

// ==========================================================
// CONFIGURATION DES CLÉS GOOGLE RECAPTCHA
// ==========================================================
$siteKey   = "Ici";
$secretKey = "Ici";
// ==========================================================

$pays_fr = [
    '+242' => ['Congo', 9]
];

function verifyCaptcha($response, $secretKey) {
    $url    = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$response";
    $verify = file_get_contents($url);
    $decode = json_decode($verify);
    return $decode->success;
}

// --- LOGIQUE DE CONNEXION ---
if(isset($_POST['connexion'])) {
    if(isset($_POST['g-recaptcha-response']) && verifyCaptcha($_POST['g-recaptcha-response'], $secretKey)) {
        $identifiant = htmlspecialchars($_POST['identifiant'] ?? '');
        $password    = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? OR telephone = ? LIMIT 1");
        $stmt->execute([$identifiant, $identifiant]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($password, $user['mdp'])) {
            $_SESSION['user_id']           = $user['id'];
            $_SESSION['user_nom']          = $user['nom_complet'];
            $_SESSION['role']              = $user['role'];
            $_SESSION['nom_entreprise']    = $user['nom_entreprise'];
            $_SESSION['statut_abonnement'] = $user['statut_abonnement'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error_msg = "Identifiant ou mot de passe incorrect.";
        }
    } else {
        $error_msg = "Veuillez cocher la case Captcha.";
    }
}

// --- LOGIQUE D'INSCRIPTION ---
if(isset($_POST['inscription'])) {
    if(isset($_POST['g-recaptcha-response']) && verifyCaptcha($_POST['g-recaptcha-response'], $secretKey)) {
        if(!isset($_POST['accept_terms'])) {
            echo "<script>alert('Erreur: Vous devez accepter les conditions d\'utilisation.'); window.history.back();</script>";
            exit();
        }

        $nom        = htmlspecialchars($_POST['nom_complet']);
        $code       = $_POST['pays_code'];
        $num        = htmlspecialchars($_POST['num_tel']);
        $email      = htmlspecialchars($_POST['email']);
        $mdp        = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $entreprise = htmlspecialchars($_POST['nom_entreprise']);
        $type       = $_POST['type_entreprise'];
        $secteur    = $_POST['secteur'];
        $pays       = htmlspecialchars($_POST['pays']);
        $ville      = htmlspecialchars($_POST['ville']);
        $d_ins      = date('Y-m-d H:i:s');
        $d_exp      = date('Y-m-d H:i:s', strtotime('+3 days'));

        $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? OR (telephone = ? AND pays_code = ?)");
        $check->execute([$email, $num, $code]);

        if($check->rowCount() == 0) {
            $sql = "INSERT INTO utilisateurs (nom_complet, telephone, pays_code, email, mdp, nom_entreprise, type_entreprise, secteur, pays, ville, statut_abonnement, date_inscription, expire_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'gratuit', ?, ?)";
            $pdo->prepare($sql)->execute([$nom, $num, $code, $email, $mdp, $entreprise, $type, $secteur, $pays, $ville, $d_ins, $d_exp]);

            $_SESSION['user_id']  = $pdo->lastInsertId();
            $_SESSION['user_nom'] = $nom;
            header('Location: dashboard.php');
            exit();
        } else {
            $error_msg = "Cet email ou numéro est déjà utilisé.";
        }
    } else {
        $error_msg = "Veuillez prouver que vous n'êtes pas un robot.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification | Finance & Gestion Pro</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

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
<body>

    <?php if(isset($error_msg)): ?>
        <div class="toast-error" id="errorToast"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="auth-wrapper">

        <!-- FORMULAIRE CONNEXION -->
        <section id="login-container" class="auth-card">
            <div class="logo-area">
                <img src="images/Blue World.jpg" alt="Logo">
                <h2>Connexion</h2>
            </div>
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="identifiant" placeholder="Email ou Téléphone" required>
                </div><br>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Mot de passe" required>
                </div><br>
                <div style="text-align:center; margin-bottom:20px;">
                    <a href="forgot-password.php" style="font-size:13px; color:#007bff; text-decoration:none; font-weight:500;">
                        Mot de passe oublié ?
                    </a>
                </div>
                <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>" style="margin-bottom:20px;"></div>
                <button type="submit" name="connexion" class="btn-primary">Se connecter</button>
            </form>
            <div class="toggle-area">
                <p>Nouveau ici ? <span id="to-signup" class="toggle-btn">Créer un compte</span></p>
            </div>
        </section>

        <!-- FORMULAIRE INSCRIPTION -->
        <section id="signup-container" class="auth-card hidden">
            <div class="logo-area">
                <img src="images/Blue World.jpg" alt="Logo">
                <h2>Inscription Pro</h2>
            </div>
            <form method="POST">
                <div class="form-section">
                    <h3><span class="step">1</span> Personnel</h3>
                    <input type="text" name="nom_complet" placeholder="Nom du gérant" required>
                    <div class="phone-input">
                        <select name="pays_code" id="reg-code" required>
                            <option value="" disabled selected>Code</option>
                            <?php foreach($pays_fr as $c => $i) echo "<option value='$c' data-pays='{$i[0]}' data-len='{$i[1]}'>$c</option>"; ?>
                        </select>
                        <input type="tel" name="num_tel" id="reg-tel" placeholder="Numéro" required>
                    </div>
                    <div class="input-row">
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="password" name="password" placeholder="Pass" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3><span class="step">2</span> Entreprise</h3>
                    <input type="text" name="nom_entreprise" placeholder="Entreprise" required style="margin-bottom:15px;">
                    <div class="input-row">
                        <select name="type_entreprise" required>
                            <option value="" disabled selected>Type</option>
                            <option value="pme">PME</option>
                            <option value="start-up">Start-up</option>
                            <option value="grande entreprise">Grande entreprise</option>
                        </select>
                        <select name="secteur" required>
                            <option value="" disabled selected>Secteur d'activité</option>
                            <optgroup label="Commerce et Vente">
                                <option value="alimentation">Alimentation / Supérette</option>
                                <option value="habillement">Habillement / Mode</option>
                                <option value="quincaillerie">Quincaillerie / BTP</option>
                                <option value="grossiste">Grossiste / Dépôt</option>
                                <option value="pieces_auto">Pièces Détachées Auto</option>
                            </optgroup>
                            <optgroup label="Services et Beauté">
                                <option value="coiffure">Coiffure / Esthétique</option>
                                <option value="pressing">Pressing / Nettoyage</option>
                                <option value="sante">Santé / Pharmacie</option>
                                <option value="education">Éducation / École</option>
                                <option value="consulting">Conseil / Services</option>
                            </optgroup>
                            <optgroup label="Restauration et Loisirs">
                                <option value="restaurant">Restaurant / Bar / Snack</option>
                                <option value="hotel">Hôtellerie / Tourisme</option>
                                <option value="evenementiel">Événementiel / Studio</option>
                            </optgroup>
                            <optgroup label="Production et Technique">
                                <option value="agriculture">Agriculture / Élevage</option>
                                <option value="menuiserie">Menuiserie / Artisanat</option>
                                <option value="technologie">Informatique / IT</option>
                                <option value="garage">Garage / Mécanique</option>
                            </optgroup>
                            <option value="autre">Autre secteur...</option>
                        </select>
                    </div>
                    <div class="input-row">
                        <input type="text" name="pays" id="pays-dest" placeholder="Pays" readonly required>
                        <input type="text" name="ville" placeholder="Ville" required>
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <label>
                        <input type="checkbox" name="accept_terms" required>
                        J'accepte les <a href="conditions.html" style="color:#007bff;">conditions d'utilisation</a>
                    </label>
                </div>

                <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>" style="margin-bottom:20px;"></div>
                <button type="submit" name="inscription" class="btn-primary">S'inscrire</button>
            </form>
            <div class="toggle-area">
                <p>Déjà inscrit ? <span id="to-login" class="toggle-btn">Se connecter</span></p>
            </div>
        </section>

    </div>

    <script>
        const errorToast = document.getElementById('errorToast');
        if(errorToast) setTimeout(() => { errorToast.style.display = 'none'; }, 5000);

        document.getElementById('reg-code').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            document.getElementById('pays-dest').value = opt.getAttribute('data-pays');
            document.getElementById('reg-tel').maxLength = opt.getAttribute('data-len');
        });

        const l = document.getElementById('login-container');
        const s = document.getElementById('signup-container');

        document.getElementById('to-signup').onclick = () => {
            l.classList.add('hidden');
            s.classList.remove('hidden');
        };
        document.getElementById('to-login').onclick = () => {
            s.classList.add('hidden');
            l.classList.remove('hidden');
        };
    </script>

</body>
</html>
