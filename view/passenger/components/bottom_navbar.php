<?php
/**
 * Bottom Navigation Bar Component
 */

$activePage = $activePage ?? 'dashboard';
$basePath = $basePath ?? '../../';
$dashboard_view = $dashboard_view ?? false;
?>

<!-- ═══════════════════════════════════════
     BOTTOM NAVIGATION BAR
═════════════════════════════════════════ -->
<nav class="bottom-navbar">
    <div class="bottom-nav-container">

        <a href="<?php echo $dashboard_view ? 'index.php' : '../../index.php'; ?>"
           class="bottom-nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-home"></i></span>
            <span class="bottom-nav-text">Dashboard</span>
        </a>

        <a href="<?php echo $dashboard_view ? 'index.php?page=transaction' : '../../view/passenger/transaction.php'; ?>"
           class="bottom-nav-link <?php echo $activePage === 'transaction' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-receipt"></i></span>
            <span class="bottom-nav-text">Transaction</span>
        </a>

        <a href="<?php echo $dashboard_view ? 'index.php?page=buypoints' : '../../view/passenger/buypoints.php'; ?>"
           class="bottom-nav-link <?php echo $activePage === 'buypoints' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-coins"></i></span>
            <span class="bottom-nav-text">Buy Points</span>
        </a>

        <a href="<?php echo $dashboard_view ? 'index.php?page=settings' : '../../view/passenger/settings.php'; ?>"
           class="bottom-nav-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>">
            <span class="bottom-nav-icon"><i class="fas fa-cog"></i></span>
            <span class="bottom-nav-text">Settings</span>
        </a>

        <!-- Logout Trigger -->
        <a href="#"
           class="bottom-nav-link"
           data-logout-trigger
           data-logout-url="<?php echo htmlspecialchars($basePath . 'controller/auth/LogoutController.php'); ?>">
            <span class="bottom-nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="bottom-nav-text">Logout</span>
        </a>

    </div>
</nav>


<!-- ═══════════════════════════════════════
     LOGOUT CONFIRMATION MODAL
═════════════════════════════════════════ -->
<div class="logout-modal-overlay"
     id="logoutModalOverlay"
     role="dialog"
     aria-modal="true"
     aria-labelledby="logoutModalTitle">

    <div class="logout-modal-box">

        <button class="logout-modal-close-x"
                id="logoutModalCloseX"
                aria-label="Close dialog">
            <i class="fas fa-times"></i>
        </button>

        <div class="logout-modal-icon">
            <img src="<?php echo htmlspecialchars($basePath); ?>assets/icons/logout.svg" alt="Logout">
        </div>

        <h3 id="logoutModalTitle">Logout Confirmation</h3>

        <p>
            Are you sure you want to logout of your<br>
            <strong>E-JEEP account</strong>?<br>
            <span style="font-size:.82rem;">
                You will need to login again to access your dashboard.
            </span>
        </p>

        <div class="logout-modal-actions">
            <button class="btn-logout-cancel" id="logoutCancelBtn" type="button">
                Cancel
            </button>
            <button class="btn-logout-confirm" id="logoutConfirmBtn" type="button">
                Yes, Logout
            </button>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════
     LOGOUT MODAL CSS
═════════════════════════════════════════ -->
<style>
.logout-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}
.logout-modal-overlay.active {
    display: flex;
}
.logout-modal-box {
    background: #ffffff;
    padding: 40px 36px 50px;
    width: 92%;
    max-width: 420px;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.2);
    text-align: center;
    position: relative;
    animation: pop 0.25s ease;
    color: #000000;
}

.logout-modal-box h3 {
    margin-bottom: 18px;   /* space below title */
    font-size: 1.4rem;
    font-weight: 700;
}

.logout-modal-box p {
    margin-bottom: 28px;   /* space below paragraph */
    line-height: 1.6;      /* better readability */
    font-size: 0.95rem;
}

.logout-modal-box p strong {
    display: inline-block;
    margin: 6px 0;         /* space around E-JEEP account */
}


@keyframes pop {
    from { opacity:0; transform:scale(.9) translateY(15px);}
    to   { opacity:1; transform:scale(1) translateY(0);}
}
.logout-modal-close-x {
    position:absolute;
    top:14px;
    right:16px;
    background:#f4f7f5;
    border:none;
    width:32px;
    height:32px;
    border-radius:8px;
    cursor:pointer;
}
.logout-modal-icon img{
    width: 100px;
    height: 100px;
}

.logout-modal-actions {
    display:flex;
    gap:12px;
}
.logout-modal-actions button {
    flex:1;
    padding:10px;
    font-weight:600;
    cursor:pointer;
    border:none;
}
.btn-logout-cancel {
    background:#e2e8f0;
}
.btn-logout-confirm {
    background:linear-gradient(135deg,#38a169,#276749);
    color:#fff;
}
</style>


<!-- ═══════════════════════════════════════
     LOGOUT MODAL JS
═════════════════════════════════════════ -->
<script>
(function () {

    var overlay = document.getElementById('logoutModalOverlay');
    var cancelBtn = document.getElementById('logoutCancelBtn');
    var confirmBtn = document.getElementById('logoutConfirmBtn');
    var closeX = document.getElementById('logoutModalCloseX');
    var logoutUrl = '';

    function openModal(url) {
        logoutUrl = url;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function confirmLogout() {
        window.location.href = logoutUrl;
    }

    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('[data-logout-trigger]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                openModal(el.getAttribute('data-logout-url'));
            });
        });

        cancelBtn.addEventListener('click', closeModal);
        closeX.addEventListener('click', closeModal);
        confirmBtn.addEventListener('click', confirmLogout);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

    });

})();
</script>
