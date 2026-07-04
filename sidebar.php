<?php
// On récupère les infos de l'utilisateur pour le menu
$u_id = $_SESSION['user_id'];
$stmt_side = $pdo->prepare("SELECT interface_expert, plan FROM utilisateurs WHERE id = ?");
$stmt_side->execute([$u_id]);
$user_side = $stmt_side->fetch();

$is_expert = ($user_side['interface_expert'] == 1);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="sidebar <?php echo $is_expert ? 'sidebar-premium' : ''; ?>">
    <div class="sidebar-logo">
        <h2>F&G <span>Pro</span> <?php if($is_expert) echo '<i class="fas fa-crown" style="color: #d4af37; font-size: 0.7em;"></i>'; ?></h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
            </li>
            <li class="<?php echo ($current_page == 'inventaire.php') ? 'active' : ''; ?>">
                <a href="inventaire.php"><i class="fas fa-boxes"></i> <span>Inventaire</span></a>
            </li>
            <li class="<?php echo ($current_page == 'ventes.php') ? 'active' : ''; ?>">
                <a href="ventes.php"><i class="fas fa-shopping-cart"></i> <span>Ventes</span></a>
            </li>
            <li class="<?php echo ($current_page == 'depenses.php') ? 'active' : ''; ?>">
                <a href="depenses.php"><i class="fas fa-wallet"></i> <span>Dépenses</span></a>
            </li>
            <li class="<?php echo
            ($current_page == 'fournisseur.php') ? 'active' : ''; ?>">
              <a href="fournisseur.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            </li>
          
            <?php if($is_expert): ?>
            <li class="<?php echo ($current_page == 'analyses.php') ? 'active' : ''; ?>">
                <a href="analyses.php" style="color: #d4af37;">
                    <i class="fas fa-chart-pie"></i> <span>Analyses Pro</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="<?php echo ($current_page == 'profil.php') ? 'active' : ''; ?>">
                <a href="profil.php"><i class="fas fa-user-cog"></i> <span>Paramètres</span></a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <?php if(!$is_expert): ?>
            <a href="premium.php" class="upgrade-btn" style="background: rgba(212, 175, 55, 0.1); border: 1px solid #d4af37; color: #d4af37; margin-bottom: 10px; display: flex; align-items: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                <i class="fas fa-crown"></i> <span style="margin-left: 10px;">MODE EXPERT</span>
            </a>
        <?php endif; ?>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
        </a>
    </div>
</aside>

<style>
/* Style pour la sidebar quand elle est en mode premium */
.sidebar-premium {
    border-right: 1px solid rgba(212, 175, 55, 0.2);
}

.sidebar-menu li.active a {
    border-left: 4px solid <?php echo $is_expert ? '#d4af37' : '#007bff'; ?>;
    background: <?php echo $is_expert ? 'rgba(212, 175, 55, 0.05)' : 'rgba(0, 123, 255, 0.1)'; ?>;
}

.upgrade-btn:hover {
    background: #d4af37 !important;
    color: #000 !important;
    transition: 0.3s;
}
</style>