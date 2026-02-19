<?php
/**
 * Bottom Navigation Bar Component
 * 
 * @param string $activePage - The active page: 'dashboard', 'transaction', 'buypoints', 'settings'
 * @param string $basePath - Base path for assets and links
 * @param bool $dashboard_view - Whether accessed through dashboard view (index.php routing)
 */

// Set default values if not provided
$activePage = $activePage ?? 'dashboard';
$basePath = $basePath ?? '../../';
$dashboard_view = $dashboard_view ?? false;
?>

<!-- Bottom Navigation Bar -->
<nav class="bottom-navbar">
    <div class="bottom-nav-container">
        <a href="<?php echo $dashboard_view ? 'index.php' : '../../index.php'; ?>" class="bottom-nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-home"></i></span>
            <span class="bottom-nav-text">Dashboard</span>
        </a>
        <a href="<?php echo $dashboard_view ? 'index.php?page=transaction' : '../../view/passenger/transaction.php'; ?>" class="bottom-nav-link <?php echo $activePage === 'transaction' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-receipt"></i></span>
            <span class="bottom-nav-text">Transaction</span>
        </a>
        <a href="<?php echo $dashboard_view ? 'index.php?page=buypoints' : '../../view/passenger/buypoints.php'; ?>" class="bottom-nav-link <?php echo $activePage === 'buypoints' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-coins"></i></span>
            <span class="bottom-nav-text">Buy Points</span>
        </a>
        <a href="<?php echo $dashboard_view ? 'index.php?page=settings' : '../../view/passenger/settings.php'; ?>" class="bottom-nav-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-cog"></i></span>
            <span class="bottom-nav-text">Settings</span>
        </a>

        <!-- Logout: href="#" prevents direct navigation; data-logout-trigger fires the confirmation modal -->
        <a href="#"
           class="bottom-nav-link"
           data-logout-trigger
           data-logout-url="<?php echo htmlspecialchars($basePath . 'controller/auth/LogoutController.php'); ?>"
           aria-label="Logout">
            <span class="bottom-nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="bottom-nav-text">Logout</span>
        </a>
    </div>
</nav>