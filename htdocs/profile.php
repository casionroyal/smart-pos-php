<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } else {
        // Vérification de l'ancien mot de passe
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === md5($current_password)) {
            $update = $pdo->prepare("UPDATE users SET password = MD5(?) WHERE id = ?");
            $update->execute([$new_password, $_SESSION['user_id']]);
            $message = "Mot de passe mis à jour avec succès !";
        } else {
            $error = "Le mot de passe actuel est incorrect.";
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4" style="max-width: 500px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-shield-lock"></i> Mon Profil & Sécurité</h4>
            
            <?php if ($message): ?><div class="alert alert-success py-2"><?= $message ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Mot de passe actuel</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Enregistrer les modifications</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>