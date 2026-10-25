<?php 
// Initialisation obligatoire des variables pour éviter les notices
$total_revenue = 0;
$total_paid = 0;
$creances_clients = 0;
$total_marge = 0;
$total_stock = 0;
$pending_deliveries = 0;
$salesStats = ['total_sales_count' => 0];

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($pdo) && isset($db)) { $pdo = $db; }

// 1. Calculs des statistiques financières et globales (hors ventes annulées/retournées)
try {
    $stmtSales = $pdo->query("SELECT 
        COUNT(id) AS total_sales_count,
        COALESCE(SUM(total_amount), 0) AS total_revenue
    FROM sales WHERE delivery_status != 'returned' AND payment_status != 'cancelled'");
    $salesStats = $stmtSales->fetch(PDO::FETCH_ASSOC) ?: $salesStats;
    $total_revenue = floatval($salesStats['total_revenue'] ?? 0);
} catch (Exception $e) {}

try {
    $stmtPaid = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total_paid FROM sales WHERE payment_status = 'paid' AND delivery_status != 'returned'");
    $total_paid = floatval($stmtPaid->fetch(PDO::FETCH_ASSOC)['total_paid'] ?? 0);
} catch (Exception $e) {}

try {
    $stmtDebt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total_debt FROM sales WHERE payment_status = 'pending' AND delivery_status != 'returned'");
    $creances_clients = floatval($stmtDebt->fetch(PDO::FETCH_ASSOC)['total_debt'] ?? 0);
} catch (Exception $e) {}

try {
    $stmtMarge = $pdo->query("
        SELECT COALESCE(SUM(si.unit_price - v.purchase_price), 0) AS total_marge 
        FROM sale_items si 
        JOIN phone_variants v ON si.variant_id = v.id
        JOIN sales s ON si.sale_id = s.id
        WHERE s.delivery_status != 'returned' AND s.payment_status != 'cancelled'
    ");
    $total_marge = floatval($stmtMarge->fetch(PDO::FETCH_ASSOC)['total_marge'] ?? 0);
} catch (Exception $e) {}

// Total des unités en stock
try {
    $stmtStock = $pdo->query("SELECT COALESCE(SUM(stock_qty), 0) AS total_stock FROM phone_variants");
    $stockStats = $stmtStock->fetch(PDO::FETCH_ASSOC);
    $total_stock = intval($stockStats['total_stock'] ?? 0);
} catch (Exception $e) {
    try {
        $stmtStock = $pdo->query("SELECT COUNT(id) AS total_stock FROM phone_variants");
        $stockStats = $stmtStock->fetch(PDO::FETCH_ASSOC);
        $total_stock = intval($stockStats['total_stock'] ?? 0);
    } catch (Exception $ex) {
        $total_stock = 0;
    }
}

// Livraisons en attente (toutes les livraisons non encore validées comme 'delivered' et non retournées)
try {
    $stmtDeliveries = $pdo->query("SELECT COUNT(id) AS pending_deliveries FROM sales WHERE sale_type = 'delivery' AND (delivery_status != 'delivered' AND delivery_status != 'returned')");
    $deliveryStats = $stmtDeliveries->fetch(PDO::FETCH_ASSOC);
    $pending_deliveries = intval($deliveryStats['pending_deliveries'] ?? 0);
} catch (Exception $e) {
    $pending_deliveries = 0;
}

// 2. Dernières ventes
try {
    $stmtRecent = $pdo->query("
        SELECT s.*, u.username AS vendor_name
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    $recentSales = $stmtRecent->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $recentSales = [];
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="bi bi-speedometer2 text-primary me-2"></i> Tableau de bord</h3>
        <a href="index.php?page=pos" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i> Nouvelle Vente</a>
    </div>

    <!-- CHIFFRES ET CARTES DE SYNTHÈSE -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white shadow-sm p-3">
                <small class="text-white-50 fw-bold text-uppercase">Chiffre d'Affaires</small>
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
                <small class="text-white-50 fw-bold text-uppercase">Créances Clients</small>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($creances_clients, 2) ?> DA</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white shadow-sm p-3" style="background-color: #20c997;">
                <small class="text-white-50 fw-bold text-uppercase">Marge Nette (Bénéfice)</small>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($total_marge, 2) ?> DA</h3>
            </div>
        </div>
    </div>

    <!-- LES 3 BLOCS DE NAVIGATION -->
    <div class="row g-4 mb-4">
        <!-- Bloc 1 : Ventes Validées -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold m-0 text-primary">
                            <i class="bi bi-check-circle-fill me-2"></i>Ventes Validées
                        </h5>
                        <span class="badge bg-primary rounded-pill"><?= intval($salesStats['total_sales_count'] ?? 0) ?></span>
                    </div>
                    <p class="text-muted small">Consultez l'historique complet des transactions et réimprimez les factures.</p>
                    <a href="index.php?page=sales_history" class="btn btn-outline-primary w-100 mt-2">
                        Voir Historique <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Bloc 2 : Ventes par Livraison -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold m-0 text-warning">
                            <i class="bi bi-truck me-2"></i>Livraisons
                        </h5>
                        <span class="badge bg-warning text-dark rounded-pill"><?= intval($pending_deliveries) ?> en attente</span>
                    </div>
                    <p class="text-muted small">Gérez les bons de commande en cours d'expédition et le suivi des clients.</p>
                    <a href="index.php?page=deliveries" class="btn btn-outline-warning text-dark w-100 mt-2">
                        Gérer Livraisons <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Bloc 3 : Gestion du Stock -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold m-0 text-success">
                            <i class="bi bi-box-seam me-2"></i>Stock & Modèles
                        </h5>
                        <span class="badge bg-success rounded-pill"><?= intval($total_stock) ?> unités</span>
                    </div>
                    <p class="text-muted small">Contrôlez les quantités disponibles, ajoutez des IMEI ou mettez à jour les prix.</p>
                    <a href="index.php?page=stock" class="btn btn-outline-success w-100 mt-2">
                        Accéder au Stock <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DERNIÈRES TRANSACTIONS RÉCENTES -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0"><i class="bi bi-clock-history me-2"></i>Dernières ventes enregistrées</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Vente</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Aucune vente enregistrée pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td class="fw-bold">#<?= sprintf('%05d', $sale['id']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($sale['client_name'] ?? 'Client Passager') ?></td>
                                    <td class="text-end fw-bold"><?= number_format(floatval($sale['total_amount'] ?? 0), 2) ?> DA</td>
                                    <td class="text-center">
                                        <?php if (($sale['sale_type'] ?? '') === 'delivery'): ?>
                                            <span class="badge bg-warning text-dark">Livraison</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Comptoir</span>
                                        <?php endif; ?>
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