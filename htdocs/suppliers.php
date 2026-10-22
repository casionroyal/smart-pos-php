<?php
// Protection d'accès
if (!defined('ABSPATH') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

// Récupération des fournisseurs avec le nombre de variantes/téléphones associés
$suppliers_stmt = $pdo->query("
    SELECT s.*, COUNT(v.id) as total_models 
    FROM suppliers s 
    LEFT JOIN phone_variants v ON s.id = v.supplier_id 
    GROUP BY s.id 
    ORDER BY s.company_name ASC
");
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-truck text-primary"></i> Gestion des Fournisseurs</h3>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORMULAIRE D'AJOUT -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle-fill text-success"></i> Nouveau Fournisseur</h5>
                    <form action="process/process_suppliers.php" method="POST">
                        <input type="hidden" name="action" value="add_supplier">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom / Nom de Société *</label>
                            <input type="text" name="company_name" class="form-control" placeholder="ex: Ets Ahmed Import" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom du Contact</label>
                            <input type="text" name="contact_name" class="form-control" placeholder="ex: Mohamed">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="phone" class="form-control" placeholder="ex: 0550 00 00 00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Adresse / Ville</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="ex: Belfort, Alger"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="bi bi-save"></i> Enregistrer Fournisseur
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTE DES FOURNISSEURS -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-list-stars text-primary"></i> Répertoire Fournisseurs</h5>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fournisseur</th>
                                    <th>Contact</th>
                                    <th>Téléphone</th>
                                    <th>Adresse</th>
                                    <th class="text-center">Modèles Achetés</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Aucun fournisseur enregistré.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($suppliers as $sup): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($sup['company_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($sup['contact_name'] ?: '-') ?></td>
                                        <td><i class="bi bi-telephone text-muted"></i> <?= htmlspecialchars($sup['phone'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($sup['address'] ?: '-') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-dark"><?= $sup['total_models'] ?> lot(s)</span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $sup['id'] ?>" title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="process/process_suppliers.php?action=delete_supplier&id=<?= $sup['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Voulez-vous supprimer ce fournisseur ?');" 
                                               title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALES DE MODIFICATION -->
<?php foreach ($suppliers as $sup): ?>
    <div class="modal fade" id="editSupplierModal<?= $sup['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Modifier Fournisseur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/process_suppliers.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_supplier">
                        <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom / Raison Sociale *</label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($sup['company_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contact</label>
                            <input type="text" name="contact_name" class="form-control" value="<?= htmlspecialchars($sup['contact_name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($sup['phone']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Adresse</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($sup['address']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary fw-bold">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>