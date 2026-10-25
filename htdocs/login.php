<?php
session_start();
require_once 'config/db.php';

$error = '';
$info_message = '';

// Check si un paramètre d'erreur/notification est présent dans l'URL
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'expired') {
        $error = "Votre session ou votre compte a expiré. <strong>Veuillez nous contacter pour prolongation </strong>.";
    } elseif ($_GET['error'] === 'timeout') {
        $error = "Session expirée suite à une inactivité de 40 minutes.";
    } elseif ($_GET['error'] === 'logout') {
        $info_message = "Vous avez été déconnecté avec succès.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // 1. Recherche de l'utilisateur par son nom d'utilisateur uniquement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 2. Vérification du mot de passe haché avec password_verify
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            header('Location: index.php');
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Smart POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { 
            background: #0f172a; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-card { 
            width: 100%; 
            max-width: 400px; 
            padding: 30px; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); 
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center fw-bold mb-2 text-primary"><i class="bi bi-phone"></i> SMART POS</h3>
        <p class="text-center text-muted mb-4">Espace d'authentification</p>

		<!-- Message d'erreur (Rouge) -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-3 small text-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Message d'information (Bleu) -->
        <?php if (!empty($info_message)): ?>
            <div class="alert alert-info py-2 mb-3 small text-center" role="alert">
                <i class="bi bi-info-circle-fill me-1"></i> <?= htmlspecialchars($info_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" placeholder="ex: admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Se Connecter</button>
        </form>

        <div class="text-center mt-3">
            <a href="forgot_password.php" class="text-decoration-none small text-muted">Mot de passe oublié ?</a>
        </div>
    </div>
</body>
</html>