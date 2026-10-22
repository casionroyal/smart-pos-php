<?php
session_start();
require_once 'config/db.php'; // Ajustez le chemin vers votre fichier de connexion PDO si nécessaire

$message = '';
$error = '';
$step = 1; // Étape par défaut : Demande du numéro de téléphone
$auto_token = ''; // Variable pour pré-remplir l'input

// ÉTAPE 1 : Vérification du numéro de téléphone et génération du Token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_token'])) {
    $phone = trim($_POST['phone']);

    if (!empty($phone)) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            // Génération d'un token à 6 chiffres
            $token = sprintf("%06d", mt_rand(100000, 999999));
            // Expiration dans 15 minutes
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Enregistrement dans la base de données
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$token, $expires, $user['id']]);

            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['temp_token'] = $token; // Sauvegarde temporaire du code généré
            
            $auto_token = $token; // Injecté dans l'input
            $step = 2; // Passer à l'étape de réinitialisation
        } else {
            $error = "Aucun compte associé à ce numéro de téléphone.";
        }
    } else {
        $error = "Veuillez entrer votre numéro de téléphone.";
    }
}

// ÉTAPE 2 : Vérification du Token et modification du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token_input = trim($_POST['token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['reset_user_id'] ?? 0;

    if (!empty($token_input) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
            $auto_token = $token_input;
            $step = 2;
        } else {
            // Vérifier si le token est valide et non expiré
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$user_id, $token_input]);
            $validUser = $stmt->fetch();

            if ($validUser) {
                // Hachage sécurisé du nouveau mot de passe au format $2y$12$...
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Mise à jour du mot de passe et nettoyage du token
                $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $update->execute([$hashed_password, $user_id]);

                unset($_SESSION['reset_user_id']);
                unset($_SESSION['temp_token']);

                $message = "Mot de passe réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
                $step = 3; // Étape succès
            } else {
                $error = "Code invalide ou expiré. Veuillez réessayer.";
                $auto_token = $token_input;
                $step = 2;
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
        $auto_token = $token_input;
        $step = 2;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMART POS - Mot de passe oublié</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-dark d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body p-4">
                    
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-info"><i class="bi bi-phone"></i> SMART POS</h3>
                        <p class="text-muted">Récupération de mot de passe</p>
                    </div>

                    <!-- Affichage des messages d'erreur -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <!-- Message de succès -->
                    <?php if ($message): ?>
                        <div class="alert alert-success py-2"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($message) ?></div>
                        <div class="d-grid mt-3">
                            <a href="login.php" class="btn btn-primary fw-bold">Se connecter</a>
                        </div>
                    <?php endif; ?>

                    <!-- ÉTAPE 1 : Formulaire Numéro de Téléphone -->
                    <?php if ($step === 1): ?>
                        <form method="POST">
                            <input type="hidden" name="request_token" value="1">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Numéro de Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" class="form-control" placeholder="ex: 0550123456" required autofocus>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                                <i class="bi bi-send"></i> Envoyer le code de vérification
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- ÉTAPE 2 : Formulaire Token (Automatique) + Nouveau mot de passe -->
                    <?php if ($step === 2): ?>
                        <div class="alert alert-success border-success text-center py-2 mb-3">
                            <i class="bi bi-check-circle-fill"></i> <strong>Code généré avec succès !</strong>
                            <small class="d-block text-muted">Le code de vérification a été inséré automatiquement ci-dessous.</small>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="reset_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Code de vérification (Généré)</label>
                                <input type="text" name="token" class="form-control text-center fs-5 fw-bold bg-light" maxlength="6" value="<?= htmlspecialchars($auto_token) ?>" required readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" placeholder="••••••••" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                                <i class="bi bi-shield-check"></i> Valider et changer le mot de passe
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($step !== 3): ?>
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> Retour à la connexion</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>