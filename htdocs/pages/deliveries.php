<?php
// Message d'information/erreur
$message = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

// Récupération des livraisons
$query = $pdo->query("
    SELECT 
        s.id AS sale_id,
        s.sale_type,
        s.delivery_status,
        s.payment_status,
        s.client_name,
        s.client_phone,
        s.client_address,
        s.total_amount,
        s.created_at AS sale_date,
        v.network, v.color, v.ram, v.storage,
        m.brand, m.model_name,
        si.imei,
        i.serial_number
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN phone_variants v ON si.variant_id = v.id
    LEFT JOIN phone_models m ON v.model_id = m.id
    LEFT JOIN phone_imei i ON (si.imei = i.imei_code AND i.variant_id = v.id)
    WHERE s.sale_type = 'delivery'
    ORDER BY s.id DESC
");
$deliveries = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-truck"></i> Suivi des Livraisons & Bons de Livraison</h3>
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

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            
            <!-- BARRE DE RECHERCHE LIVRAISONS -->
            <div class="input-group mb-3">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchDeliveryInput" class="form-control border-start-0" placeholder="Rechercher par N° Vente, Nom client, Téléphone, Adresse, IMEI, Modèle..." onkeyup="filterDeliveryTable()">
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="deliveryTable">
                    <thead class="table-light">
                        <tr>
                            <th>N° Vente</th>
                            <th>Client & Adresse</th>
                            <th>Téléphone / Appareil</th>
                            <th>IMEI / N° Série</th>
                            <th>Montant Total</th>
                            <th>Statut Livraison</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deliveries)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Aucune livraison enregistrée pour le moment.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($deliveries as $row): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($row['sale_id']) ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($row['client_name'] ?: 'N/A') ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($row['client_address'] ?: 'Non spécifiée') ?></small><br>
                                    <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($row['client_phone'] ?: 'N/A') ?></small>
                                </td>
                                <td>
                                    <?php if ($row['brand']): ?>
                                        <strong><?= htmlspecialchars($row['brand'] . ' ' . $row['model_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['color']) ?> - <?= htmlspecialchars($row['ram']) ?>/<?= htmlspecialchars($row['storage']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><strong>IMEI:</strong> <code><?= htmlspecialchars($row['imei'] ?: 'N/A') ?></code></small><br>
                                    <small><strong>S/N:</strong> <code><?= htmlspecialchars($row['serial_number'] ?: 'N/A') ?></code></small>
                                </td>
                                <td class="fw-bold text-success"><?= number_format($row['total_amount'] ?? 0, 2) ?> DA</td>
                                <td>
                                    <?php
                                    $status = $row['delivery_status'] ?? 'pending';
                                    $badge_class = 'bg-warning text-dark';
                                    $status_label = 'Pending';

                                    if ($status === 'delivered') {
                                        $badge_class = 'bg-success';
                                        $status_label = 'Vendu / Livré';
                                    } elseif ($status === 'returned') {
                                        $badge_class = 'bg-danger';
                                        $status_label = 'Retourné';
                                    }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group gap-1">
                                        <!-- Bon de Livraison -->
                                        <a href="print/invoice_a4.php?id=<?= $row['sale_id'] ?>&type=delivery_note" target="_blank" class="btn btn-sm btn-outline-dark" title="Imprimer Bon de Livraison">
                                            <i class="bi bi-printer"></i> BL
                                        </a>

                                        <?php if ($status === 'pending'): ?>
                                            <!-- Actions si en attente -->
                                            <a href="actions/update_delivery.php?id=<?= $row['sale_id'] ?>&action=confirm" 
                                               class="btn btn-sm btn-success" 
                                               onclick="return confirm('Confirmer la livraison et valider la vente finale ?');" 
                                               title="Valider la vente">
                                                <i class="bi bi-check-circle"></i> Valider
                                            </a>
                                            <a href="actions/update_delivery.php?id=<?= $row['sale_id'] ?>&action=return" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Le client a refusé ? Le téléphone sera remis en stock.');" 
                                               title="Retour au stock">
                                                <i class="bi bi-x-circle"></i> Retour
                                            </a>

                                        <?php elseif ($status === 'delivered'): ?>
                                            <!-- Facture finale si livré -->
                                            <a href="print/invoice_a4.php?id=<?= $row['sale_id'] ?>&type=invoice" target="_blank" class="btn btn-sm btn-primary" title="Imprimer la Facture Finale">
                                                <i class="bi bi-file-earmark-text"></i> Facture
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT JS DE FILTRAGE INSTANTANÉ DES LIVRAISONS -->
<script>
function filterDeliveryTable() {
    let input = document.getElementById("searchDeliveryInput");
    let filter = input.value.toLowerCase();
    let table = document.getElementById("deliveryTable");
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