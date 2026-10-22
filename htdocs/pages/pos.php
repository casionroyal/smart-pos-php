<?php
$message = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

// Récupération de CHAQUE unité physique disponible avec son IMEI et son Numéro de Série (SN)
try {
    $query = $pdo->query("
        SELECT 
            pi.imei_code,
            pi.serial_number,
            v.id AS variant_id,
            v.network, v.color, v.ram, v.storage,
            m.brand, m.model_name
        FROM phone_imei pi
        JOIN phone_variants v ON pi.variant_id = v.id
        JOIN phone_models m ON v.model_id = m.id
        WHERE pi.status = 'available' OR pi.status = 'en stock' OR pi.status IS NULL
        ORDER BY m.brand ASC, m.model_name ASC
    ");
    $items_stock = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback si la table phone_imei n'a pas la colonne serial_number
    $query = $pdo->query("
        SELECT 
            v.id AS variant_id,
            v.stock_qty,
            v.network, v.color, v.ram, v.storage,
            m.brand, m.model_name,
            '' AS imei_code,
            '' AS serial_number
        FROM phone_variants v
        JOIN phone_models m ON v.model_id = m.id
        WHERE v.stock_qty > 0
        ORDER BY m.brand ASC, m.model_name ASC
    ");
    $items_stock = $query->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid px-4">
    <div class="mt-3 mb-3">
        <h3 class="fw-bold"><i class="bi bi-calculator"></i> Nouvelle Vente / Caisse</h3>
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

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="process/process_sales.php" method="POST">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Sélectionner le Smartphone (par Modèle, IMEI & SN)</label>
                    <select name="variant_id" id="variant_select" class="form-select form-select-lg" required>
                        <option value="">-- Choisir un téléphone en stock --</option>
                        <?php foreach ($items_stock as $item): ?>
                            <option value="<?= $item['variant_id'] ?>" 
                                    data-imei="<?= htmlspecialchars($item['imei_code'] ?? '') ?>"
                                    data-sn="<?= htmlspecialchars($item['serial_number'] ?? '') ?>">
                                <?= htmlspecialchars($item['brand'] . ' ' . $item['model_name'] . ' (' . ($item['color'] ?? '') . ' - ' . ($item['storage'] ?? '') . ') — IMEI: ' . (!empty($item['imei_code']) ? $item['imei_code'] : 'N/A') . (!empty($item['serial_number']) ? ' | SN: ' . $item['serial_number'] : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">N° de Série (SN)</label>
                        <input type="text" name="serial_number" id="sn_input" class="form-control" placeholder="Numéro de série">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Numéro IMEI (Unique)</label>
                        <input type="text" name="imei" id="imei_input" class="form-control" placeholder="15 chiffres">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Prix de Vente Unitaire (DA)</label>
                        <!-- PRIX SAISI LIBREMENT PAR LE VENDEUR EN CAISSE -->
                        <input type="number" step="0.01" name="unit_price" id="unit_price_input" class="form-control fw-bold text-success" required placeholder="0.00">
                    </div>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="form-label fw-bold">Type de Vente</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sale_type" id="type_direct" value="direct" checked onclick="toggleDeliveryFields(false)">
                        <label class="form-check-label" for="type_direct">Vente Directe (Comptoir)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sale_type" id="type_delivery" value="delivery" onclick="toggleDeliveryFields(true)">
                        <label class="form-check-label" for="type_delivery">Vente par Livraison</label>
                    </div>
                </div>

                <div id="delivery_fields" class="p-3 bg-light border rounded mb-3" style="display: none;">
                    <h5 class="fw-bold fs-6 mb-3"><i class="bi bi-person-lines-fill"></i> Informations Client & Livraison</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nom & Prénom Client</label>
                            <input type="text" name="client_name" class="form-control" placeholder="Nom du client">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="client_phone" class="form-control" placeholder="05XX XX XX XX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Adresse de Livraison</label>
                            <input type="text" name="client_address" class="form-control" placeholder="Wilaya, Commune, Rue">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                    <i class="bi bi-check-circle"></i> Valider & Enregistrer la Vente
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDeliveryFields(show) {
    var fields = document.getElementById('delivery_fields');
    if (fields) {
        fields.style.display = show ? 'block' : 'none';
    }
}

// Remplissage automatique du SN et de l'IMEI lors de la sélection du smartphone
document.getElementById('variant_select').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    
    var imeiValue = selectedOption.getAttribute('data-imei') || '';
    var snValue = selectedOption.getAttribute('data-sn') || '';
    
    document.getElementById('imei_input').value = imeiValue;
    document.getElementById('sn_input').value = snValue;
});
</script>

<!-- DEBUT BLOC IMPRESSION AUTOMATIQUE -->
<?php if (isset($_GET['print_id'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const printId = "<?= intval($_GET['print_id']) ?>";
    const saleType = "<?= htmlspecialchars($_GET['type'] ?? 'direct') ?>";
    
    let targetUrl = '';
    
    if (saleType === 'delivery') {
        targetUrl = 'print/delivery_note.php?id=' + printId;
    } else {
        targetUrl = 'print/invoice_a4.php?id=' + printId + '&type=invoice';
    }
    
    if (targetUrl !== '') {
        window.open(targetUrl, '_blank');
    }
});
</script>
<?php endif; ?>