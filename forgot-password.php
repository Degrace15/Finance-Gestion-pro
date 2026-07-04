<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <section class="auth-card">
            <div class="logo-area">
                <img src="images/Blue World.jpg" alt="Logo">
                <h2>Réinitialisation</h2>
            </div>

            <p style="text-align:center; font-size:13px; color:rgba(255,255,255,0.7); margin-bottom:20px;">
                Entrez votre email et votre nouveau mot de passe pour mettre à jour votre compte.
            </p>

            <form method="POST" action="process-forgot.php">
                <div class="input-group" style="margin-bottom: 15px;">
                    <input type="email" name="email" placeholder="Votre email" required>
                </div>
                
                <div class="input-group" style="margin-bottom: 20px;">
                    <input type="password" name="nouveau_mdp" placeholder="Nouveau mot de passe" required>
                </div>
                
                <button type="submit" name="reset_request" class="btn-primary">Mettre à jour</button>
            </form>

            <div class="toggle-area">
                <p><a href="auth.php" class="toggle-btn" style="text-decoration: none;">← Retour</a></p>
            </div>
        </section>
    </div>
</body>
</html>