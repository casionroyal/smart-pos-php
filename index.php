<?php
session_start();

// Empêcher la mise en cache par le navigateur pour éviter que le modal ne réapparaisse au retour en arrière
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/auth_check.php';
require_once 'config/db.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$page = $_GET['page'] ?? 'dashboard';
?>

<main class="main-wrapper">
<?php
switch ($page) {
    case 'dashboard':
        require_once 'pages/dashboard.php';
        break;

    case 'pos':
        require_once 'pages/pos.php';
        break;

    case 'sales_history':
        include 'pages/sales_history.php';
        break;

    case 'deliveries':
        require_once 'pages/deliveries.php';
        break;

    case 'stock':
        require_once 'pages/stock.php';
        break;

    case 'users':
        if ($_SESSION['role'] !== 'superadmin') {
            echo "<div class='container alert alert-danger mt-4'>Accès refusé. Vous n'avez pas les droits nécessaires.</div>";
        } else {
            require_once 'pages/users.php';
        }
        break;
        
    case 'support':
        include 'support.php';
        break;

    case 'profile':
        require_once 'pages/profile.php';
        break;

   case 'suppliers':
    include 'suppliers.php';
    break;
                
    default:
        require_once 'pages/dashboard.php';
        break;
}
?>
</main>

<!-- MODAL DE RAPPEL MENSUEL / SHOW_PROMO -->
<?php if (isset($_SESSION['show_promo']) && $_SESSION['show_promo'] == 1): ?>
<div class="modal fade" id="promoReminderModal" tabindex="-1" aria-labelledby="promoReminderModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="promoReminderModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Rappel Mensuel & Dûs / Promo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-danger fs-5">Attention : Échéance / Rappel important !</p>
                <p class="text-secondary">C'est le moment de vérifier et de régler les dûs de ce mois-ci ou de mettre à jour vos abonnements et promotions.</p>
            </div>
            <div class="modal-footer">
                <a href="disable_promo.php" class="btn btn-dark fw-bold w-100 text-center text-decoration-none">
                    J'ai compris, voir les informations
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var promoModal = new bootstrap.Modal(document.getElementById('promoReminderModal'));
    promoModal.show();
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>