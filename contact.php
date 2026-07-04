<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | Blue World Finance</title>
    <link rel="shortcut icon" href="world.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/cont.css">
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

<div class="contact-container">
    <div class="header">
        <i class="fas fa-paper-plane" style="font-size: 3rem; color:  #00d4ff;"></i>
        <h1>Contactez-nous</h1>
    </div>

    <div id="status-message"></div>

    <form id="contact-form" action="https://formspree.io/f/xnjlyaoq" method="POST">
        
        <div class="form-group">
            <label>Nom complet</label>
            <i class="fas fa-user"></i>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Adresse Email</label>
            <i class="fas fa-at"></i>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Votre message</label>
            <i class="fas fa-comment-dots"></i>
            <textarea name="message" required></textarea>
        </div>

        <button type="submit" id="submit-btn" class="btn-submit">Envoyer le message</button>
    </form>
</div>

<script>
    const form = document.getElementById("contact-form");
    const status = document.getElementById("status-message");
    const btn = document.getElementById("submit-btn");

    form.addEventListener("submit", async function(event) {
        event.preventDefault(); // Empêche le rechargement de la page
        
        btn.disabled = true;
        btn.innerText = "Envoi en cours...";
        
        const data = new FormData(event.target);
        
        try {
            const response = await fetch(event.target.action, {
                method: form.method,
                body: data,
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                status.innerHTML = "✅ Merci ! Votre message a été envoyé avec succès.";
                status.className = "success";
                form.reset(); // Vide le formulaire
            } else {
                status.innerHTML = "❌ Oups ! Il y a eu un problème lors de l'envoi.";
                status.className = "error";
            }
        } catch (error) {
            status.innerHTML = "❌ Erreur de connexion .";
            status.className = "error";
        } finally {
            btn.disabled = false;
            btn.innerText = "Envoyer le message";
        }
    });
</script>

</body>
</html>