<?php
// pages/deliveries.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$filter_period = trim($_GET['period'] ?? 'all');
$filter_date = trim($_GET['date_value'] ?? '');

// Récupération des livraisons avec filtres de recherche et de période
$sql = "
    SELECT s.*, 
           u.username AS vendor_name
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.sale_type = 'delivery'
";

$params = [];

// Recherche par IMEI, Nom client, Téléphone ou ID exact de la vente
if (!empty($search)) {
    $sql .= " AND (
        s.id IN (SELECT sale_id FROM sale_items WHERE imei LIKE :search_imei) 
        OR s.client_name LIKE :search_client 
        OR s.client_phone LIKE :search_phone 
        OR s.id = :exact_id
    )";
    $params[':search_imei'] = "%{$search}%";
    $params[':search_client'] = "%{$search}%";
    $params[':search_phone'] = "%{$search}%";
    $params[':exact_id'] = intval($search);
}

// Filtres par période (Jour, Mois, Année)
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

$sql .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="bi bi-truck text-warning me-2"></i> Suivi des Livraisons & Retours</h3>
        <a href="index.php?page=pos" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i> Nouvelle Vente</a>
    </div>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_GET['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_GET['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- BARRE DE RECHERCHE & FILTRAGE PAR TEMPS -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="deliveries">
                
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Rechercher IMEI, Nom, Téléphone, N° Vente..." value="<?= htmlspecialchars($search) ?>">
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

                <div class="col-md-3" id="dateInputContainer" style="display: none;">
                    <input type="text" name="date_value" id="dateValueInput" class="form-control" placeholder="Sélectionnez..." value="<?= htmlspecialchars($filter_date) ?>">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100"><i class="bi bi-filter"></i> Filtrer</button>
                    <a href="index.php?page=deliveries" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DES LIVRAISONS -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Vente</th>
                            <th>Date & Client</th>
                            <th>Appareils & IMEI (Détails de la commande)</th>
                            <th class="text-end">Montant Total</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions & Gestion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deliveries)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Aucune livraison trouvée pour ces critères.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deliveries as $deliv): 
                                // Récupérer les articles spécifiques à cette vente
                                $stmt_items = $pdo->prepare("
                                    SELECT si.*, m.brand, m.model_name, v.color, v.ram, v.storage 
                                    FROM sale_items si
                                    JOIN phone_variants v ON si.variant_id = v.id
                                    JOIN phone_models m ON v.model_id = m.id
                                    WHERE si.sale_id = ?
                                ");
                                $stmt_items->execute([$deliv['id']]);
                                $sale_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                                $status = $deliv['delivery_status'] ?? 'pending';
                            ?>
                                <tr>
                                    <td class="fw-bold">#<?= sprintf('%05d', $deliv['id']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($deliv['client_name'] ?? 'Client') ?></strong><br>
                                        <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($deliv['client_phone'] ?? '-') ?></small><br>
                                        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($deliv['client_address'] ?? 'Non spécifiée') ?></small>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0 small">
                                            <?php if (empty($sale_items)): ?>
                                                <span class="text-muted italic">Articles retournés</span>
                                            <?php else: ?>
                                                <?php foreach ($sale_items as $item): ?>
                                                    <li class="border-bottom py-1">
                                                        <strong><?= htmlspecialchars($item['brand'] . ' ' . $item['model_name']) ?></strong> 
                                                        (<?= htmlspecialchars($item['color'] . ' - ' . $item['ram'] . '/' . $item['storage']) ?>)<br>
                                                        <code class="text-danger">IMEI: <?= htmlspecialchars($item['imei'] ?? 'N/A') ?></code>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td class="text-end fw-bold text-dark"><?= number_format($deliv['total_amount'], 2) ?> DA</td>
                                    
                                    <!-- STATUT -->
                                    <td class="text-center">
                                        <?php if ($status === 'delivered'): ?>
                                            <span class="badge bg-success">Livré (Validé)</span>
                                        <?php elseif ($status === 'returned' || empty($sale_items)): ?>
                                            <span class="badge bg-danger">Retourné</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- ACTIONS -->
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                            <!-- Imprimer BL -->
                                            <a href="print/delivery_note.php?id=<?= $deliv['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Imprimer Bon de Livraison">
                                                <i class="bi bi-truck"></i> BL
                                            </a>

                                            <!-- Facture A4 (affichée si livré/validé) -->
                                            <?php if ($status === 'delivered'): ?>
                                                <a href="print/invoice_a4.php?id=<?= $deliv['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Imprimer Facture A4">
                                                    <i class="bi bi-file-earmark-text"></i> Facture A4
                                                </a>
                                            <?php endif; ?>

                                            <!-- Valider toute la livraison d'un coup (si en attente) -->
                                            <?php if ($status === 'pending'): ?>
                                                <a href="process/process_delivery.php?action=deliver_all&id=<?= $deliv['id'] ?>" class="btn btn-sm btn-success" title="Valider toute la livraison">
                                                    <i class="bi bi-check-lg"></i> Tout Valider
                                                </a>
                                            <?php endif; ?>

                                            <!-- Bouton Gérer Retours -->
                                            <?php if (!empty($sale_items) && $status !== 'returned'): ?>
                                                <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="openDynamicModal(<?= $deliv['id'] ?>, '<?= htmlspecialchars(sprintf('%05d', $deliv['id']), ENT_QUOTES) ?>', <?= htmlspecialchars(json_encode($sale_items), ENT_QUOTES, 'UTF-8') ?>)" title="Gérer les retours">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Gérer Retours
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
        <div class="bg-white rounded shadow-lg p-4" style="width: 700px; max-width: 95%; max-height: 90vh; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-arrow-counterclockwise text-warning"></i> Gestion des Retours - Vente #${saleNum}</h5>
                <button type="button" class="btn-close" onclick="closeDynamicModal()"></button>
            </div>
            <form action="process/process_return.php" method="POST">
                <p class="text-muted small">Cochez les articles que le client souhaite <strong>retourner</strong> (remise en stock).</p>
                
                <input type="hidden" name="action" value="process_partial_return">
                <input type="hidden" name="sale_id" value="${saleId}">
                <input type="hidden" name="redirect" value="deliveries">

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
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

                <div class="d-flex justify-content-end gap-2">
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