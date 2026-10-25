<?php
// pages/suppliers.php
if (!defined('ABSPATH') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
$search  = trim($_GET['search'] ?? '');

// Récupération des fournisseurs avec le nombre de variantes/téléphones associés
$sql = "
    SELECT s.*, COUNT(v.id) as total_models 
    FROM suppliers s 
    LEFT JOIN phone_variants v ON s.id = v.supplier_id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.company_name LIKE :search OR s.contact_name LIKE :search OR s.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " GROUP BY s.id ORDER BY s.company_name ASC";

$suppliers_stmt = $pdo->prepare($sql);
$suppliers_stmt->execute($params);
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-truck text-primary"></i> Gestion des Fournisseurs & Traçabilité</h3>
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

    <!-- BARRE DE RECHERCHE FOURNISSEUR -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="suppliers">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Rechercher par nom de société, contact, téléphone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-filter"></i> Filtrer</button>
                    <a href="index.php?page=suppliers" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

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
                                    <th class="text-center">Lots / Achats</th>
                                    <th class="text-center">Traçabilité & Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Aucun fournisseur trouvé.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($suppliers as $sup): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($sup['company_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($sup['address'] ?: '') ?></small></td>
                                        <td><?= htmlspecialchars($sup['contact_name'] ?: '-') ?></td>
                                        <td><i class="bi bi-telephone text-muted"></i> <?= htmlspecialchars($sup['phone'] ?: '-') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-dark"><?= $sup['total_models'] ?> lot(s)</span>
                                        </td>
                                        <td class="text-center">
                                            <!-- Bouton Historique Achats / Factures -->
                                            <button class="btn btn-sm btn-outline-dark me-1" data-bs-toggle="modal" data-bs-target="#supplierHistoryModal<?= $sup['id'] ?>" title="Historique des achats & Factures">
                                                <i class="bi bi-receipt"></i> Achats & Factures
                                            </button>

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

<!-- MODALES (MODIFICATION ET HISTORIQUE ACHATS) -->
<?php foreach ($suppliers as $sup): ?>
    <!-- MODAL MODIFICATION -->
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

    <!-- MODAL HISTORIQUE ACHATS & FACTURES DU FOURNISSEUR -->
    <?php
        // Récupérer les lots/variantes achetés chez ce fournisseur (triés par ID décroissant)
        $stmt_purchases = $pdo->prepare("
            SELECT v.*, m.brand, m.model_name 
            FROM phone_variants v 
            JOIN phone_models m ON v.model_id = m.id 
            WHERE v.supplier_id = ? 
            ORDER BY v.id DESC
        ");
        $stmt_purchases->execute([$sup['id']]);
        $purchased_lots = $stmt_purchases->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="modal fade" id="supplierHistoryModal<?= $sup['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-journal-text text-warning"></i> Traçabilité & Achats - <?= htmlspecialchars($sup['company_name']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <?php if (empty($purchased_lots)): ?>
                        <p class="text-center text-muted py-4">Aucun achat enregistré auprès de ce fournisseur pour le moment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Lot ID</th>
                                        <th>Appareil & Modèle</th>
                                        <th>Déclinaison</th>
                                        <th>Prix d'Achat</th>
                                        <th>IMEI / Numéros de série associés</th>
                                        <th class="text-center">Facture / Bon d'achat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchased_lots as $lot): ?>
                                        <?php 
                                            // Récupérer les IMEI de ce lot
                                            $stmt_imeis = $pdo->prepare("SELECT imei_code, serial_number, status FROM phone_imei WHERE variant_id = ?");
                                            $stmt_imeis->execute([$lot['id']]);
                                            $lot_imeis = $stmt_imeis->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <tr>
                                            <td>#<?= sprintf('%05d', $lot['id']) ?></td>
                                            <td><strong><?= htmlspecialchars($lot['brand'] . ' ' . $lot['model_name']) ?></strong></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($lot['network']) ?></span> <?= htmlspecialchars($lot['color'] . ' - ' . $lot['ram'] . '/' . $lot['storage']) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($lot['purchase_price'], 2) ?> DA</td>
                                            <td>
                                                <div style="max-height: 100px; overflow-y: auto;">
                                                    <?php foreach ($lot_imeis as $im): ?>
                                                        <div><code class="text-danger">IMEI: <?= htmlspecialchars($im['imei_code'] ?: 'N/A') ?></code> <small class="text-muted">(<?= $im['status'] ?>)</small></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($lot['invoice_image'])): ?>
                                                    <a href="uploads/invoices/<?= htmlspecialchars($lot['invoice_image']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Voir la facture">
                                                        <i class="bi bi-file-earmark-image"></i> Voir Facture
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">Aucune pièce jointe</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>