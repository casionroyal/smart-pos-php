<?php
// Restriction SuperAdmin
if ($_SESSION['role'] !== 'superadmin') {
    echo "<div class='container alert alert-danger mt-4'>Accès réservé au SuperAdmin.</div>";
    return;
}

$message = '';
$error = '';

// 1. Traitement de l'ajout d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $phone     = trim($_POST['phone']);
    $role      = $_POST['role'];

    if (!empty($full_name) && !empty($username) && !empty($password) && !empty($phone)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        
        if ($check->rowCount() > 0) {
            $error = "Ce nom d'utilisateur est déjà pris.";
        } else {
            // Hachage sécurisé compatible avec password_verify()
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $hashed_password, $phone, $role])) {
                $message = "Utilisateur créé avec succès !";
            } else {
                $error = "Erreur lors de la création de l'utilisateur.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// 2. Traitement de la MODIFICATION d'un utilisateur (Nom, Login, Tél, Rôle, Mot de passe)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id   = intval($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $phone     = trim($_POST['phone']);
    $role      = $_POST['role'];
    $new_pass  = $_POST['new_password'];

    if (!empty($full_name) && !empty($username) && !empty($phone)) {
        // Vérifier si le login n'est pas utilisé par un AUTRE utilisateur
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $user_id]);
        
        if ($check->rowCount() > 0) {
            $error = "Ce nom d'utilisateur est déjà utilisé par un autre compte.";
        } else {
            // Mettre à jour avec ou sans le mot de passe
            if (!empty($new_pass)) {
                // Hachage sécurisé du nouveau mot de passe
                $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                $updated = $stmt->execute([$full_name, $username, $phone, $role, $hashed_new_pass, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, phone = ?, role = ? WHERE id = ?");
                $updated = $stmt->execute([$full_name, $username, $phone, $role, $user_id]);
            }

            if ($updated) {
                $message = "Utilisateur mis à jour avec succès !";
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs requis.";
    }
}

// 3. Traitement de la suppression d'un utilisateur
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte !";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = "Utilisateur supprimé avec succès !";
        } else {
            $error = "Erreur lors de la suppression de l'utilisateur.";
        }
    }
}

// Récupération de tous les utilisateurs
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="container-fluid px-4">
    <?php if ($message): ?><div class="alert alert-success py-2"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

    <div class="row g-4">
        <!-- Formulaire de création -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person-plus-fill"></i> Créer un Utilisateur</h5>
                    <form method="POST">
                        <input type="hidden" name="add_user" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="full_name" class="form-control" placeholder="ex: Karim Benali" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur (Login user)</label>
                            <input type="text" name="username" class="form-control" placeholder="ex: user" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Numéro de Téléphone</label>
                            <input type="text" name="phone" class="form-control" placeholder="ex: 0550123456" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select">
                                <option value="employe">Employé (Vente uniquement)</option>
                                <option value="superadmin">SuperAdmin (Accès total)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="bi bi-person-plus"></i> Enregistrer l'utilisateur
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des utilisateurs avec Actions -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-people-fill"></i> Liste des Utilisateurs du Système</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom & Prénom</th>
                                    <th>Identifiant</th>
                                    <th>Téléphone</th>
                                    <th>Rôle</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>#<?= $u['id'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
                                        <td><span class="text-primary fw-bold"><?= htmlspecialchars($u['username']) ?></span></td>
                                        <td><i class="bi bi-telephone"></i> <?= htmlspecialchars($u['phone']) ?></td>
                                        <td>
                                            <span class="badge <?= $u['role'] === 'superadmin' ? 'bg-danger' : 'bg-info' ?>">
                                                <?= strtoupper($u['role']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <!-- Bouton Modifier (Édition globale) -->
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>" title="Modifier l'utilisateur">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Bouton Supprimer -->
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="index.php?page=users&action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');" title="Supprimer">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal de modification de l'utilisateur -->
                                    <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Modifier <?= htmlspecialchars($u['full_name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="edit_user" value="1">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                                                        <div class="mb-3">
                                                            <label class="form-label">Nom complet</label>
                                                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Nom d'utilisateur (Login)</label>
                                                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Téléphone</label>
                                                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone']) ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Rôle</label>
                                                            <select name="role" class="form-select">
                                                                <option value="employe" <?= $u['role'] === 'employe' ? 'selected' : '' ?>>Employé (Vente uniquement)</option>
                                                                <option value="superadmin" <?= $u['role'] === 'superadmin' ? 'selected' : '' ?>>SuperAdmin (Accès total)</option>
                                                            </select>
                                                        </div>

                                                        <hr>

                                                        <div class="mb-3">
                                                            <label class="form-label text-danger fw-bold">Nouveau mot de passe (optionnel)</label>
                                                            <input type="password" name="new_password" class="form-control" placeholder="Laissez vide pour conserver l'actuel">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>