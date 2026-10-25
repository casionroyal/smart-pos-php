<?php
// Notification de succès/erreur si renvoyé par process_stock.php
$message =$_GET['success'] ?? '';
$error   =$_GET['error'] ?? '';

// Récupération de tous les fournisseurs pour la sélection
$suppliers_list =$pdo->query("SELECT * FROM suppliers ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les smartphones avec le calcul dynamique du stock et la jointure fournisseur
$query =$pdo->query("SELECT v.*, m.brand, m.model_name, s_sup.company_name AS supplier_name,
                             COUNT(CASE WHEN i.status IN ('available', 'in_stock') THEN 1 END) AS stock_qty
                      FROM phone_variants v 
                      JOIN phone_models m ON v.model_id = m.id 
                      LEFT JOIN suppliers s_sup ON v.supplier_id = s_sup.id
                      LEFT JOIN phone_imei i ON v.id = i.variant_id
                      GROUP BY v.id
                      ORDER BY m.brand ASC, m.model_name ASC");
$variants =$query->fetchAll();

// Récupération globale des IMEI avec jointure sur les ventes
$imei_query =$pdo->query("
    SELECT i.*, 
           si.sale_id, 
           s.sale_type 
    FROM phone_imei i
    LEFT JOIN sale_items si ON i.imei_code = si.imei
    LEFT JOIN sales s ON si.sale_id = s.id
    ORDER BY i.id DESC
");
$all_imeis =$imei_query->fetchAll(PDO::FETCH_ASSOC);

// Structuration des IMEI par id de variante
$imeis_by_variant = [];
foreach ($all_imeis as $imei) {$imeis_by_variant[$imei['variant_id']][] =$imei;
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-box-seam"></i> Inventaire & Stock des Téléphones</h3>
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
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle-fill text-success"></i> Ajouter un / des Smartphone(s)</h5>
                    <form action="process/process_stock.php" method="POST" id="addStockForm">
                        <input type="hidden" name="action" value="add_variant">
                        
                        <!-- SELECTION DU FOURNISSEUR -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold"><i class="bi bi-truck text-primary"></i> Fournisseur</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Aucun / Inconnu --</option>
                                <?php foreach ($suppliers_list as$sup): ?>
                                    <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold">Marque</label>
                            <input type="text" name="brand" class="form-control" placeholder="ex: Samsung" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold">Modèle</label>
                            <input type="text" name="model_name" class="form-control" placeholder="ex: Galaxy S25" required>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Réseau</label>
                                <select name="network" class="form-select">
                                    <option value="5G">5G</option>
                                    <option value="4G">4G</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Couleur</label>
                                <input type="text" name="color" class="form-control" placeholder="Noir" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold">RAM</label>
                                <input type="text" name="ram" class="form-control" placeholder="8GB" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Stockage</label>
                                <input type="text" name="storage" class="form-control" placeholder="128GB" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Prix Achat Fournisseur (DA)</label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Quantité d'unités à ajouter</label>
                            <input type="number" id="stock_qty" name="stock_qty" class="form-control" value="1" min="1" required>
                        </div>

                        <!-- ZONE DE SAISIE DYNAMIQUE IMEI ET NUMÉRO DE SÉRIE -->
                        <div class="p-3 bg-light rounded border mb-3">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-qr-code-scan"></i> Scan / Saisie IMEI & N° de Série</h6>
                            <p class="small text-muted mb-2">Scannez à la douchette ou saisissez manuellement pour chaque appareil :</p>
                            
                            <div id="imeiSerialContainer"></div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="bi bi-plus-circle"></i> Enregistrer dans le Stock
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTE DES STOCKS DE TÉLÉPHONES -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0"><i class="bi bi-phone-fill text-primary"></i> Inventaire des Téléphones</h5>
                    </div>

                    <!-- BARRE DE RECHERCHE -->
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchStockInput" class="form-control border-start-0" placeholder="Rechercher par marque, modèle, couleur, fournisseur..." onkeyup="filterStockTable()">
                    </div>
                    
                    <!-- CONTENEUR AVEC SCROLL VERTICAL -->
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="stockTable">
                            <thead class="table-light sticky-top" style="z-index: 1;">
                                <tr>
                                    <th>Appareil</th>
                                    <th>Fournisseur</th>
                                    <th>Déclinaison</th>
                                    <th>Prix Achat</th>
                                    <th>Stock</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($variants) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Aucun smartphone en stock pour le moment.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($variants as$row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['brand']) ?></strong> <?= htmlspecialchars($row['model_name']) ?></td>
                                        <td>
                                            <?php if (!empty($row['supplier_name'])): ?>
                                                <span class="badge bg-light text-dark border"><i class="bi bi-truck me-1"></i><?= htmlspecialchars($row['supplier_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($row['network']) ?></span> 
                                            <?= htmlspecialchars($row['color']) ?> - <?= htmlspecialchars($row['ram']) ?>/<?= htmlspecialchars($row['storage']) ?>
                                        </td>
                                        <td class="text-muted"><?= number_format($row['purchase_price'], 2) ?> DA</td>
                                        <td>
                                            <span class="badge <?= $row['stock_qty'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $row['stock_qty'] ?> en stock
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#listImeiModal<?= $row['id'] ?>" title="Voir IMEI / Serial">
                                                <i class="bi bi-list-ul"></i>
                                            </button>

                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <?php if (isset($_SESSION['role']) &&$_SESSION['role'] === 'superadmin'): ?>
                                                <a href="process/process_stock.php?action=delete_variant&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette déclinaison ?');" title="Supprimer">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function filterStockTable() {
            let input = document.getElementById("searchStockInput");
            let filter = input.value.toLowerCase();
            let table = document.getElementById("stockTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let rowText = tr[i].textContent || tr[i].innerText;
                if (rowText.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
        </script>
    </div>
</div>

<!-- SECTION DES MODALES -->
<?php foreach ($variants as$row): ?>
    <?php $variant_imeis = $imeis_by_variant[$row['id']] ?? []; ?>

    <!-- MODAL MODIFICATION VARIANTE -->
    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Modifier <?= htmlspecialchars($row['brand'] . ' ' .$row['model_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/process_stock.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_variant">
                        <input type="hidden" name="variant_id" value="<?= $row['id'] ?>">

                        <div class="mb-2">
                            <label class="form-label fw-semibold">Fournisseur</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Aucun / Inconnu --</option>
                                <?php foreach ($suppliers_list as$sup): ?>
                                    <option value="<?= $sup['id'] ?>" <?= ($row['supplier_id'] ==$sup['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sup['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Réseau</label>
                                <select name="network" class="form-select">
                                    <option value="5G" <?= $row['network'] === '5G' ? 'selected' : '' ?>>5G</option>
                                    <option value="4G" <?= $row['network'] === '4G' ? 'selected' : '' ?>>4G</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Couleur</label>
                                <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($row['color']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold">RAM</label>
                                <input type="text" name="ram" class="form-control" value="<?= htmlspecialchars($row['ram']) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Stockage</label>
                                <input type="text" name="storage" class="form-control" value="<?= htmlspecialchars($row['storage']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Prix Achat Fournisseur (DA)</label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= $row['purchase_price'] ?>" required>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold text-primary mb-2">
                            <i class="bi bi-qr-code-scan"></i> Appareils enregistrés (<?= count($variant_imeis) ?>)
                        </h6>

                        <?php if (!empty($variant_imeis)): ?>
                            <?php $index = 1; ?>
                            <?php foreach ($variant_imeis as $imei_item): ?>
                                <div class="p-2 border rounded bg-light mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-secondary">Appareil N° <?= $index ?></span>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge <?= ($imei_item['status'] === 'available' || $imei_item['status'] === 'in_stock') ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= ($imei_item['status'] === 'available' || $imei_item['status'] === 'in_stock') ? 'En Stock' : 'Vendu' ?>
                                            </span>
                                            <?php if ($imei_item['status'] === 'available' || $imei_item['status'] === 'in_stock'): ?>
                                                <a href="process/process_stock.php?action=delete_single_imei&imei_id=<?= $imei_item['id'] ?>&variant_id=<?= $row['id'] ?>" 
                                                class="btn btn-sm btn-outline-danger py-0 px-1" 
                                                onclick="return confirm('Voulez-vous vraiment supprimer cet IMEI/S/N du stock ?');" 
                                                title="Supprimer cet appareil">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="text" name="existing_imei[<?= $imei_item['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($imei_item['imei_code'] ?? '') ?>" placeholder="Code IMEI">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" name="existing_serial[<?= $imei_item['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($imei_item['serial_number'] ?? '') ?>" placeholder="N° de Série">
                                        </div>
                                    </div>
                                </div>
                                <?php $index++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="p-3 border rounded border-success bg-light mt-3">
                            <h6 class="fw-bold text-success mb-2"><i class="bi bi-plus-circle-fill"></i> Ajouter un appareil supplémentaire à ce stock</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" name="new_imei" class="form-control form-control-sm" placeholder="Scanner/Saisir nouvel IMEI">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="new_serial" class="form-control form-control-sm" placeholder="Scanner/Saisir nouveau N° de Série">
                                </div>
                            </div>
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

    <!-- MODAL CONSULTATION DES IMEI -->
    <div class="modal fade" id="listImeiModal<?= $row['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-list-ul"></i> Unités - <?= htmlspecialchars($row['brand'] . ' ' .$row['model_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($variant_imeis)): ?>
                        <p class="text-muted text-center py-2 m-0">Aucun IMEI / N° de Série enregistré.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($variant_imeis as$imei_item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                    <div>
                                        <div><strong>IMEI:</strong> <code class="text-danger fw-bold"><?= htmlspecialchars($imei_item['imei_code'] ?: 'N/A') ?></code></div>
                                        <div><strong>S/N:</strong> <code class="text-primary fw-bold"><?= htmlspecialchars($imei_item['serial_number'] ?: 'N/A') ?></code></div>
                                    </div>
                                    <span class="badge <?= ($imei_item['status'] === 'available') ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars($imei_item['status']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qtyInput = document.getElementById('stock_qty');
    const container = document.getElementById('imeiSerialContainer');

    function renderImeiFields() {
        if (!qtyInput || !container) return;
        const qty = parseInt(qtyInput.value) || 1;
        container.innerHTML = '';

        for (let i = 1; i <= qty; i++) {
            const row = document.createElement('div');
            row.className = 'border-bottom pb-2 mb-2';
            row.innerHTML = `
                <span class="badge bg-secondary mb-1">Appareil N° ${i}</span>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="text" name="imei_codes[]" class="form-control form-control-sm scan-input" placeholder="IMEI ${i}" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <input type="text" name="serial_numbers[]" class="form-control form-control-sm scan-input" placeholder="N° de Série ${i}" autocomplete="off">
                    </div>
                </div>
            `;
            container.appendChild(row);
        }
    }

    if (qtyInput) {
        qtyInput.addEventListener('input', renderImeiFields);
        renderImeiFields();
    }
});
</script>