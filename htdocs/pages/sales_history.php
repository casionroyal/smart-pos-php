<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($pdo) && isset($db)) { $pdo = $db; }

$search = trim($_GET['search'] ?? '');

// Requête SQL mise à jour avec calcul du prix d'achat et des montants réglés
$sql = "
    SELECT s.*, 
           u.username AS vendor_name,
           GROUP_CONCAT(CONCAT(m.brand, ' ', m.model_name, ' (IMEI: ', IFNULL(si.imei, 'N/A'), ')') SEPARATOR '<br>') AS items_list,
           GROUP_CONCAT(si.imei SEPARATOR ' ') AS imei_list,
           -- Coût total d'achat des articles vendus
           COALESCE(SUM(si.quantity * IFNULL(v.purchase_price, 0)), 0) AS total_purchase_cost
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN phone_variants v ON si.variant_id = v.id
    LEFT JOIN phone_models m ON v.model_id = m.id
";

if (!empty($search)) {
    $sql .= " WHERE si.imei LIKE :search OR s.client_name LIKE :search OR s.id = :exact_id";
}

$sql .= " GROUP BY s.id ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->execute([
        ':search'   => "%{$search}%",
        ':exact_id' => intval($search)
    ]);
} else {
    $stmt->execute();
}
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques globales pour les cartes de synthèse
$total_revenue = 0;
$total_paid = 0;
$total_debt = 0;
$total_profit = 0;

foreach ($sales as $sale) {
    $sale_total = floatval($sale['total_amount'] ?? 0);
    $sale_paid  = floatval($sale['paid_amount'] ?? $sale_total);
    $sale_cost  = floatval($sale['total_purchase_cost'] ?? 0);

    $total_revenue += $sale_total;
    $total_paid    += $sale_paid;
    $total_debt    += max(0, $sale_total - $sale_paid);
    $total_profit  += ($sale_total - $sale_cost);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="bi bi-clock-history text-primary me-2"></i> Historique des Ventes & Bénéfices</h3>
        <a href="index.php?page=pos" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i> Nouvelle Vente</a>
    </div>

    <!-- CARTES DE SYNTHÈSE FINANCIÈRE -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white shadow-sm p-3">
                <small class="text-white-50 fw-bold text-uppercase">Chiffre d'Affaires Total</small>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($total_revenue, 2) ?> DA</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white shadow-sm p-3">
                <small class="text-white-50 fw-bold text-uppercase">Total Encaissé</small>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($total_paid, 2) ?> DA</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white shadow-sm p-3">
                <small class="text-white-50 fw-bold text-uppercase">Créances (Reste à Payer)</small>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($total_debt, 2) ?> DA</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-dark text-warning shadow-sm p-3">
                <small class="text-warning-50 fw-bold text-uppercase">Bénéfice Net Marge</small>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($total_profit, 2) ?> DA</h3>
            </div>
        </div>
    </div>

    <!-- BARRE DE RECHERCHE PAR IMEI / CODE-BARRES -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="sales_history">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-barcode"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Scanner un Code-barres / IMEI, N° de Facture ou Nom du Client..." value="<?= htmlspecialchars($search) ?>" autofocus>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Chercher</button>
                    <?php if (!empty($search)): ?>
                        <a href="index.php?page=sales_history" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
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
                            <th class="text-center">Statut / Créance</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Actions / Réimpression</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Aucune vente trouvée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): 
                                $sale_total = floatval($sale['total_amount'] ?? 0);
                                $sale_paid  = floatval($sale['paid_amount'] ?? $sale_total);
                                $sale_cost  = floatval($sale['total_purchase_cost'] ?? 0);
                                $profit     = $sale_total - $sale_cost;
                                $remaining  = $sale_total - $sale_paid;
                            ?>
                                <tr>
                                    <td class="fw-bold">#<?= sprintf('%05d', $sale['id']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($sale['client_name'] ?? 'Client Passager') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($sale['client_phone'] ?? '-') ?></small>
                                    </td>
                                    <td><small><?= $sale['items_list'] ?? 'Non spécifié' ?></small></td>
                                    <td class="text-end fw-bold text-dark"><?= number_format($sale_total, 2) ?> DA</td>
                                    
                                    <!-- BÉNÉFICE -->
                                    <td class="text-end fw-bold <?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                        +<?= number_format($profit, 2) ?> DA
                                    </td>

                                    <!-- STATUT ET CRÉANCES -->
                                    <td class="text-center">
                                        <?php if ($remaining <= 0): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Payé</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Créance</span>
                                            <div class="small text-danger fw-bold">Reste: <?= number_format($remaining, 2) ?> DA</div>
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

                                    <!-- BOUTONS DE RÉIMPRESSION (Facture A4 / Bon de livraison uniquement) -->
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <?php if (($sale['sale_type'] ?? '') === 'delivery'): ?>
                                                <a href="print/delivery_note.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-warning" title="Imprimer Bon de Livraison">
                                                    <i class="bi bi-truck"></i> Bon Livraison
                                                </a>
                                            <?php else: ?>
                                                <a href="print/invoice_a4.php?id=<?= $sale['id'] ?>&type=invoice" target="_blank" class="btn btn-sm btn-outline-primary" title="Imprimer Facture A4">
                                                    <i class="bi bi-file-earmark-pdf"></i> Facture A4
                                                </a>
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