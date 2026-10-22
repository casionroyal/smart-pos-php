<?php
session_start();

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

<?php require_once 'includes/footer.php'; ?>