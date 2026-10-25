<?php
// pages/sales_history.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($pdo) && isset($db)) { $pdo = $db; }

$search = trim($_GET['search'] ?? '');
$filter_period = trim($_GET['period'] ?? 'all');
$filter_date = trim($_GET['date_value'] ?? '');

// Construction de la requête SQL principale pour le tableau
$sql = "
    SELECT s.*, 
           u.username AS vendor_name,
           COALESCE(SUM(si.quantity * IFNULL(v.purchase_price, 0)), 0) AS total_purchase_cost
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN phone_variants v ON si.variant_id = v.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (s.id IN (SELECT sale_id FROM sale_items WHERE imei LIKE :search_imei) OR s.client_name LIKE :search_client OR s.id = :exact_id)";
    $params[':search_imei'] = "%{$search}%";
    $params[':search_client'] = "%{$search}%";
    $params[':exact_id'] = intval($search);
}

if ($filter_period === 'day' && !empty($filter_date)) {
    $sql .= " AND DATE(s.created_at) = :filter_date";
    $params[':filter_date'] = $filter_date;
} elseif ($filter_period === 'month' && !empty($filter_date)) {
    $sql .= " AND DATE_FORMAT(s.created_at, '%Y-%m') = :filter_month";
    $params[':filter_month'] = $filter_date;
} elseif ($filter_period === 'year' && !empty($filter_date)) {
    $sql .= " AND YEAR(s.created_at) = :filter_year";
    $params[':filter_year'] = $filter_date;
}

$sql .= " GROUP BY s.id ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="bi bi-clock-history text-primary me-2"></i> Historique Global des Ventes</h3>
        <a href="index.php?page=pos" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i> Nouvelle Vente</a>
    </div>

    <!-- BARRE DE RECHERCHE & FILTRAGE PAR TEMPS -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="sales_history">
                
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Rechercher IMEI, N° Facture, Client..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="period" id="periodSelect" class="form-select" onchange="toggleDateInputs()">
                        <option value="all" <?= $filter_period === 'all' ? 'selected' : '' ?>>Toutes les périodes</option>
                        <option value="day" <?= $filter_period === 'day' ? 'selected' : '' ?>>Par Jour</option>
                        <option value="month" <?= $filter_period === 'month' ? 'selected' : '' ?>>Par Mois</option>
                        <option value="year" <?= $filter_period === 'year' ? 'selected' : '' ?>>Par Année</option>
                    </select>
                </div>

                <div class="col-md-3" id="dateInputContainer">
                    <input type="text" name="date_value" id="dateValueInput" class="form-control" placeholder="Sélectionnez..." value="<?= htmlspecialchars($filter_date) ?>">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filtrer</button>
                    <a href="index.php?page=sales_history" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DES VENTES -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Vente</th>
                            <th>Date & Heure</th>
                            <th>Client</th>
                            <th>Articles & IMEI</th>
                            <th class="text-end">Montant Vente</th>
                            <th class="text-end">Bénéfice</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Actions / Retours & Factures</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Aucune vente trouvée pour ces critères.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): 
                                $stmt_i = $pdo->prepare("
                                    SELECT si.*, m.brand, m.model_name, v.color, v.ram, v.storage, (si.unit_price - v.purchase_price) AS item_profit 
                                    FROM sale_items si
                                    JOIN phone_variants v ON si.variant_id = v.id
                                    JOIN phone_models m ON v.model_id = m.id
                                    WHERE si.sale_id = ?
                                ");
                                $stmt_i->execute([$sale['id']]);
                                $sale_items = $stmt_i->fetchAll(PDO::FETCH_ASSOC);

                                $sale_total = floatval($sale['total_amount'] ?? 0);
                                $sale_paid  = floatval($sale['paid_amount'] ?? $sale_total);
                                $sale_profit = array_sum(array_column($sale_items, 'item_profit'));
                                $remaining  = $sale_total - $sale_paid;
                                $status     = $sale['delivery_status'] ?? 'delivered';
                            ?>
                                <tr>
                                    <td class="fw-bold">#<?= sprintf('%05d', $sale['id']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($sale['client_name'] ?? 'Client Passager') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($sale['client_phone'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0 small">
                                            <?php if (empty($sale_items)): ?>
                                                <span class="text-muted italic">Articles retournés</span>
                                            <?php else: ?>
                                                <?php foreach ($sale_items as $it): ?>
                                                    <li>• <strong><?= htmlspecialchars($it['brand'] . ' ' . $it['model_name']) ?></strong> (<?= htmlspecialchars($it['color']) ?>)<br>
                                                        <code class="text-danger">IMEI: <?= htmlspecialchars($it['imei'] ?? 'N/A') ?></code>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td class="text-end fw-bold text-dark"><?= number_format($sale_total, 2) ?> DA</td>
                                    
                                    <!-- BÉNÉFICE -->
                                    <td class="text-end fw-bold <?= $sale_profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                        +<?= number_format($sale_profit, 2) ?> DA
                                    </td>

                                    <!-- STATUT -->
                                    <td class="text-center">
                                        <?php if ($status === 'returned' || empty($sale_items)): ?>
                                            <span class="badge bg-danger">Totalement Retourné</span>
                                        <?php elseif ($remaining <= 0): ?>
                                            <span class="badge bg-success">Payé</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Créance (Reste: <?= number_format($remaining, 2) ?> DA)</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- TYPE DE VENTE -->
                                    <td class="text-center">
                                        <?php if (($sale['sale_type'] ?? '') === 'delivery'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-truck me-1"></i> Livraison</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="bi bi-shop me-1"></i> Comptoir</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- ACTIONS & BOUTONS -->
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                            <?php if (($sale['sale_type'] ?? '') === 'delivery'): ?>
                                                <a href="print/delivery_note.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Bon de Livraison">
                                                    <i class="bi bi-truck"></i> BL
                                                </a>
                                            <?php endif; ?>

                                            <a href="print/invoice_a4.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Facture A4">
                                                <i class="bi bi-file-earmark-text"></i> Facture A4
                                            </a>

                                            <?php if (!empty($sale_items) && $status !== 'returned'): ?>
                                                <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="openDynamicModal(<?= $sale['id'] ?>, '<?= htmlspecialchars(sprintf('%05d', $sale['id']), ENT_QUOTES) ?>', <?= htmlspecialchars(json_encode($sale_items), ENT_QUOTES, 'UTF-8') ?>)" title="Gérer les retours ou remboursements">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Retour
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT JS POUR LES FILTRES DE DATE ET LA MODALE DE RETOUR -->
<script>
function toggleDateInputs() {
    var period = document.getElementById('periodSelect').value;
    var container = document.getElementById('dateInputContainer');
    var input = document.getElementById('dateValueInput');

    if (period === 'day') {
        container.style.display = 'block';
        input.type = 'date';
    } else if (period === 'month') {
        container.style.display = 'block';
        input.type = 'month';
    } else if (period === 'year') {
        container.style.display = 'block';
        input.type = 'number';
        input.placeholder = 'Ex: 2026';
    } else {
        container.style.display = 'none';
        input.value = '';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    toggleDateInputs();
});

// Gestion de la modale dynamique de retour avec défilement vertical simple
function openDynamicModal(saleId, saleNum, items) {
    var existingModal = document.getElementById('dynamicModalContainer');
    if (existingModal) {
        existingModal.remove();
    }

    var rowsHtml = '';
    items.forEach(function(item) {
        rowsHtml += `
            <tr>
                <td class="text-center">
                    <input type="checkbox" name="return_items[]" value="${item.id}" class="form-check-input" style="width: 22px; height: 22px; cursor: pointer;">
                </td>
                <td><strong>${item.brand} ${item.model_name}</strong> (${item.color})</td>
                <td><code>${item.imei ? item.imei : 'N/A'}</code></td>
                <td class="text-end fw-bold">${parseFloat(item.unit_price).toLocaleString('fr-FR', {minimumFractionDigits: 2})} DA</td>
            </tr>
        `;
    });

    var modalDiv = document.createElement('div');
    modalDiv.id = 'dynamicModalContainer';
    modalDiv.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 99999; display: flex; justify-content: center; align-items: center;';
    
    modalDiv.innerHTML = `
        <div class="bg-white rounded shadow-lg p-4" style="width: 750px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-arrow-counterclockwise text-warning"></i> Gestion des Retours - Vente #${saleNum}</h5>
                <button type="button" class="btn-close" onclick="closeDynamicModal()"></button>
            </div>
            
            <form action="process/process_return.php" method="POST" style="display: flex; flex-direction: column; overflow: hidden;">
                <p class="text-muted small mb-3">Cochez les articles que le client souhaite <strong>retourner</strong> (remise en stock). Le montant et les statistiques s'ajusteront automatiquement.</p>
                
                <input type="hidden" name="action" value="process_partial_return">
                <input type="hidden" name="sale_id" value="${saleId}">
                <input type="hidden" name="redirect" value="sales_history">

                <!-- Tableau avec scroll vertical -->
                <div class="table-responsive mb-3" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="text-center" style="width: 15%;">Sélection</th>
                                <th>Appareil</th>
                                <th>IMEI</th>
                                <th class="text-end">Prix unitaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-auto pt-2 border-top">
                    <button type="button" class="btn btn-secondary" onclick="closeDynamicModal()">Fermer</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirmer le retour des articles cochés</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(modalDiv);
}

function closeDynamicModal() {
    var modal = document.getElementById('dynamicModalContainer');
    if (modal) {
        modal.remove();
    }
}
</script>