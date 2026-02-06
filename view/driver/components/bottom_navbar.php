<?php
/**
 * Bottom Navigation Bar Component for Drivers
 * 
 * @param string $activePage - The active page: 'dashboard', 'settings'
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
        <a href="<?php echo $dashboard_view ? 'index.php?page=settings' : '../../view/driver/settings.php'; ?>" class="bottom-nav-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-cog"></i></span>
            <span class="bottom-nav-text">Settings</span>
        </a>
        <a href="<?php echo $basePath . 'controller/auth/LogoutController.php'; ?>" class="bottom-nav-link">
            <span class="bottom-nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="bottom-nav-text">Logout</span>
        </a>
    </div>
</nav>

