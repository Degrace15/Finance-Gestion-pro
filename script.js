document.addEventListener('DOMContentLoaded', () => {
    // 1. SÉLECTION DES ÉLÉMENTS
    const burgerIcon = document.getElementById('burger-icon');
    const closeBtn = document.getElementById('close-menu');
    const menuLinks = document.getElementById('menu-links');
    const links = document.querySelectorAll('#menu-links a');
    const sections = document.querySelectorAll('section');

    // 2. OUVERTURE / FERMETURE DU MENU
    burgerIcon.addEventListener('click', () => {
        menuLinks.classList.add('show');
    });

    closeBtn.addEventListener('click', () => {
        menuLinks.classList.remove('show');
    });

    // 3. LOGIQUE DE NAVIGATION (SYSTÈME D'ÉCRANS)
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href').substring(1);
            
            // On vérifie si la section existe avant de changer
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                e.preventDefault();
                menuLinks.classList.remove('show'); // Ferme le menu
                changeScreen(targetId);
            }
        });
    });

    // 4. FONCTION POUR CHANGER D'ÉCRAN
    function changeScreen(id) {
        sections.forEach(section => {
            section.classList.remove('active');
            if (section.id === id) {
                section.classList.add('active');
            }
        });
        // Remet le scroll en haut pour le nouvel écran
        const activeSection = document.getElementById(id);
        if (activeSection) activeSection.scrollTop = 0;
    }

    // --- ZONE PRÉPARÉE POUR MA FUTURE LOGIQUE D'INSCRIPTION ---
    // Ici, j'ajouterai plus tard : 
    // if (userIsLoggedIn) { showManagementLinks(); } 
});