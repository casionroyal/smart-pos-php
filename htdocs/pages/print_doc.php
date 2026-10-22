<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('Accès non autorisé.');
}

$sale_id  = intval($_GET['id'] ?? 0);
$doc_type = $_GET['type'] ?? 'invoice'; // invoice ou delivery_note

// Récupération des données de la vente
$stmt = $pdo->prepare("SELECT s.*, i.imei_code, i.unit_price, v.network, v.color, v.ram, v.storage, m.brand, m.model_name,
                              d.client_phone, d.client_address, u.username as vendor_name
                       FROM sales s
                       JOIN sale_items i ON s.id = i.sale_id
                       JOIN phone_variants v ON i.variant_id = v.id
                       JOIN phone_models m ON v.model_id = m.id
                       LEFT JOIN deliveries d ON s.id = d.sale_id
                       LEFT JOIN users u ON s.user_id = u.id
                       WHERE s.id = ?");
$stmt->execute([$sale_id]);
$data = $stmt->fetch();

if (!$data) {
    exit('Document introuvable.');
}

$title = ($doc_type === 'delivery_note') ? 'BON DE LIVRAISON' : 'FACTURE DE VENTE';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> #<?= $data['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .page-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page-a4 {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center py-3 bg-dark text-white mb-3">
    <button onclick="window.print()" class="btn btn-success me-2"><i class="bi bi-printer-fill"></i> Imprimer Document (A4)</button>
    <a href="../index.php?page=pos" class="btn btn-light"><i class="bi bi-arrow-left"></i> Retour à la Caisse</a>
</div>

<div class="page-a4 d-flex flex-column justify-content-between">
    <div>
        <!-- ENTÊTE -->
        <div class="row border-bottom pb-3 mb-4">
            <div class="col-6">
                <h2 class="fw-bold text-primary mb-1"><i class="bi bi-phone"></i> SMART POS</h2>
                <p class="mb-0 text-muted">Vente & Service Smartphone</p>
                <small class="text-muted">Alger, Algérie | Tél: 0550 00 00 00</small>
            </div>
            <div class="col-6 text-end">
                <h3 class="fw-bold <?= $doc_type === 'delivery_note' ? 'text-warning' : 'text-success' ?>"><?= $title ?></h3>
                <p class="mb-0"><strong>N° :</strong> #<?= sprintf('%05d', $data['id']) ?></p>
                <p class="mb-0"><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($data['created_at'])) ?></p>
                <p class="mb-0"><strong>Vendeur :</strong> <?= htmlspecialchars($data['vendor_name'] ?? 'N/A') ?></p>
            </div>
        </div>

        <!-- INFOS CLIENT -->
        <div class="card bg-light border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-uppercase text-secondary mb-2">Informations Client</h6>
                <div class="row">
                    <div class="col-6">
                        <strong>Nom & Prénom :</strong> <?= htmlspecialchars($data['customer_name']) ?><br>
                        <strong>Téléphone :</strong> <?= htmlspecialchars($data['client_phone'] ?? 'Non renseigné') ?>
                    </div>
                    <?php if ($doc_type === 'delivery_note'): ?>
                        <div class="col-6">
                            <strong>Adresse de Livraison :</strong><br>
                            <?= htmlspecialchars($data['client_address'] ?? 'À enlever sur place') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TABLEAU DES ARTICLES -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-dark">
                <tr>
                    <th>Désignation de l'Article</th>
                    <th class="text-center">IMEI / N° Série</th>
                    <th class="text-center">Qté</th>
                    <th class="text-end">Prix Unitaire</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($data['brand'] . ' ' . $data['model_name']) ?></strong><br>
                        <small class="text-muted">
                            Réseau: <?= htmlspecialchars($data['network']) ?> | 
                            Couleur: <?= htmlspecialchars($data['color']) ?> | 
                            RAM: <?= htmlspecialchars($data['ram']) ?> | 
                            Stockage: <?= htmlspecialchars($data['storage']) ?>
                        </small>
                    </td>
                    <td class="text-center font-monospace"><?= htmlspecialchars($data['imei_code'] ?: 'N/A') ?></td>
                    <td class="text-center">1</td>
                    <td class="text-end"><?= number_format($data['unit_price'], 2) ?> DA</td>
                    <td class="text-end fw-bold"><?= number_format($data['unit_price'], 2) ?> DA</td>
                </tr>
            </tbody>
        </table>

        <!-- TOTAL & PAIEMENT -->
        <div class="row">
            <div class="col-6">
                <?php if ($doc_type === 'delivery_note'): ?>
                    <div class="p-3 border rounded border-warning bg-light">
                        <h6 class="fw-bold text-warning mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Montant à Encaisser à la Livraison :</h6>
                        <h4 class="fw-bold text-dark m-0"><?= number_format($data['total_amount'], 2) ?> DZD</h4>
                    </div>
                <?php else: ?>
                    <p class="text-muted small">
                        <strong>Statut de Paiement :</strong> Payé en Espèces<br>
                        <em>Merci pour votre confiance !</em>
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <table class="table table-borderless">
                    <tr>
                        <td class="fw-bold fs-5">TOTAL NET :</td>
                        <td class="fw-bold fs-4 text-success"><?= number_format($data['total_amount'], 2) ?> DA</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- PIED DE PAGE & SIGNATURES -->
    <div class="row pt-5 mt-5 border-top">
        <div class="col-6 text-center">
            <p class="fw-bold mb-5">Signature Client</p>
            <small class="text-muted">("Lu et approuvé")</small>
        </div>
        <div class="col-6 text-center">
            <p class="fw-bold mb-5">Cachet & Signature Établissement</p>
        </div>
    </div>
</div>

<script>
// Déclenche l'impression automatique lors du chargement de la page
window.onload = function() {
    window.print();
};
</script>

</body>
</html>