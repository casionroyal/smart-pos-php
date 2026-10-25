<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('Accès non autorisé.');
}

if (!isset($pdo) && isset($db)) { $pdo = $db; }

$sale_id  = intval($_GET['id'] ?? 0);
$doc_type = $_GET['type'] ?? 'delivery_note'; // Forcé par défaut en bon de livraison

// 1. Récupération des informations générales de la vente
$stmt = $pdo->prepare("
    SELECT s.*, u.username as vendor_name
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    exit('Document introuvable.');
}

// 2. Récupération de TOUS les articles associés à cette vente + N° Série
$stmt_items = $pdo->prepare("
    SELECT si.*, 
           v.network, v.color, v.ram, v.storage, 
           m.brand, m.model_name,
           pi.serial_number
    FROM sale_items si
    JOIN phone_variants v ON si.variant_id = v.id
    JOIN phone_models m ON v.model_id = m.id
    LEFT JOIN phone_imei pi ON si.imei = pi.imei_code
    WHERE si.sale_id = ?
");
$stmt_items->execute([$sale_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$title = 'BON DE LIVRAISON';

// Formatage du numéro de document (BL-2026-00001)
$sale_year = date('Y', strtotime($sale['created_at']));
$num_seq   = $sale['invoice_number'] ?? $sale['id'];
$prefix    = 'BL-';

$invoice_formatted_no = $prefix . $sale_year . "-" . sprintf('%05d', $num_seq);
?>

<?php
function numberToWordsPHP($number) {
    $number = floor($number);
    
    if ($number == 0) return 'zéro';

    $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    $tens  = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

    function convertGroup($n, $units, $tens) {
        $str = '';
        if ($n >= 100) {
            $hundreds = floor($n / 100);
            if ($hundreds == 1) {
                $str .= 'cent ';
            } else {
                $str .= $units[$hundreds] . ' cent ';
            }
            $n %= 100;
        }

        if ($n > 0) {
            if ($n < 20) {
                $str .= $units[$n];
            } else {
                $ten = floor($n / 10);
                $unit = $n % 10;

                if ($ten == 7 || $ten == 9) {
                    $ten_text = $tens[$ten];
                    $unit_text = $units[10 + $unit];
                    $str .= $ten_text . ($unit == 1 && $ten == 7 ? ' et ' : '-') . $unit_text;
                } else {
                    $ten_text = $tens[$ten];
                    $unit_text = $units[$unit];
                    if ($unit == 1 && $ten != 8) {
                        $str .= $ten_text . ' et un';
                    } elseif ($unit > 0) {
                        $str .= $ten_text . '-' . $unit_text;
                    } else {
                        $str .= $ten_text;
                    }
                }
            }
        }
        return trim($str);
    }

    $result = '';

    // Millions
    if ($number >= 1000000) {
        $millions = floor($number / 1000000);
        $result .= convertGroup($millions, $units, $tens) . ($millions > 1 ? ' millions ' : ' million ');
        $number %= 1000000;
    }

    // Milliers
    if ($number >= 1000) {
        $thousands = floor($number / 1000);
        if ($thousands == 1) {
            $result .= 'mille ';
        } else {
            $result .= convertGroup($thousands, $units, $tens) . ' mille ';
        }
        $number %= 1000;
    }

    // Reste
    if ($number > 0) {
        $result .= convertGroup($number, $units, $tens);
    }

    return trim($result);
}

// Récupération et conversion du montant
$total_amount = $sale['total_amount'] ?? 0;
$total_in_words = mb_strtoupper(numberToWordsPHP($total_amount), 'UTF-8');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> <?= $invoice_formatted_no ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f8f9fa; 
        }

        /* Conteneur A4 rigoureux en Flexbox identique à la facture */
        .page-a4 {
            width: 210mm;
            height: 297mm;
            padding: 10mm 15mm;
            margin: 10mm auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .main-content {
            flex-grow: 1;
        }

        .table td, .table th {
            padding: 0.4rem 0.5rem;
        }

        /* Badges de statut de livraison */
        .status-badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
        }
        .status-badge.delivered {
            background-color: #198754;
            color: #fff;
        }
        .status-badge.pending {
            background-color: #ffc107;
            color: #000;
        }

        .signature-block {
            margin-top: auto;
            padding-top: 15px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            
            body {
                background: #fff;
                font-size: 12px;
            }
            
            .no-print { 
                display: none !important; 
            }
            
            .page-a4 {
                width: 100% !important;
                height: 100vh !important;
                margin: 0 !important;
                padding: 8mm 12mm !important;
                box-shadow: none !important;
            }

            .table td, .table th {
                padding: 0.3rem 0.4rem !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center py-3 bg-dark text-white mb-3">
    <button onclick="window.print()" class="btn btn-warning text-dark fw-bold me-2"><i class="bi bi-printer-fill"></i> Imprimer Bon de Livraison (A4)</button>
    <a href="../index.php?page=deliveries" class="btn btn-light"><i class="bi bi-arrow-left"></i> Retour aux Livraisons</a>
</div>

<div class="page-a4 d-flex flex-column justify-content-between">
    <div>
        <!-- ENTÊTE -->
        <div class="row border-bottom pb-3 mb-4">
            <div class="col-6">
                <img src="../img/logo_boutique.jpg" alt="Logo Boutique" class="img-fluid" style="max-height: 160px; width: auto; object-fit: contain; margin-top: -20px; margin-bottom: 5px;">
                <p class="mb-0 text-muted">Vente & Service Smartphone</p>
                <small class="text-muted">Alger, Algérie | Tél: 0550 00 00 00</small>
            </div>
            <div class="col-6 text-end">
                <h4 class="fw-bold text-warning"><?= $title ?></h4>
                <p class="mb-0"><strong>N° :</strong> <?= $invoice_formatted_no ?></p>
                <p class="mb-0"><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></p>
                <p class="mb-0"><strong>Vendeur :</strong> <?= htmlspecialchars($sale['vendor_name'] ?? 'Comptoir') ?></p>
            </div>
        </div>

        <!-- INFOS CLIENT & LIVRAISON -->
        <div class="card bg-light border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-uppercase text-secondary mb-2">Informations Destinataire / Livraison</h6>
                <div class="row">
                    <div class="col-6">
                        <strong>Nom & Prénom :</strong> <?= htmlspecialchars($sale['client_name'] ?? 'Client Passager') ?><br>
                        <strong>Téléphone :</strong> <?= htmlspecialchars($sale['client_phone'] ?? 'Non renseigné') ?>
                    </div>
                    <div class="col-6">
                        <strong>Adresse de Livraison :</strong><br>
                        <?= htmlspecialchars($sale['client_address'] ?? 'À enlever sur place') ?>
                    </div>
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
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['brand'] . ' ' . $item['model_name']) ?></strong><br>
                            <small class="text-muted">
                                <?= htmlspecialchars($item['color']) ?> | 
                                <?= htmlspecialchars($item['ram']) ?> / <?= htmlspecialchars($item['storage']) ?>
                            </small>
                        </td>
                        <td class="text-center font-monospace">
                            <small><strong>IMEI:</strong> <?= htmlspecialchars($item['imei'] ?? 'N/A') ?></small><br>
                            <?php if (!empty($item['serial_number'])): ?>
                                <small class="text-muted"><strong>S/N:</strong> <?= htmlspecialchars($item['serial_number']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end"><?= number_format($item['unit_price'], 2) ?> DA</td>
                        <td class="text-end fw-bold"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> DA</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- TOTAL & MONTANT À ENCAISSER -->
        <div class="row align-items-center my-2">
            <div class="col-7">
                <div class="p-2 border rounded border-warning bg-light">
                    <small class="fw-bold text-warning d-block"><i class="bi bi-exclamation-triangle-fill"></i> Montant à Encaisser à la Livraison :</small>
                    <h5 class="fw-bold text-dark m-0"><?= number_format($sale['total_amount'], 2) ?> DZD</h5>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <strong>Arrêté le présent bon à la somme de :</strong> 
                    <span class="fw-bold text-dark"><?= htmlspecialchars($total_in_words) ?> DINARS</span>
                </p>
            </div>
            
            <div class="col-5 text-end">
                <table class="table table-sm table-borderless m-0">
                    <tr>
                        <td class="fw-bold fs-6 align-middle">TOTAL NET :</td>
                        <td class="fw-bold fs-5 text-warning align-middle"><?= number_format($sale['total_amount'], 2) ?> DA</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- SIGNATURES -->
    <div class="row pt-2 mt-3 border-top">
        <div class="col-4 text-center">
            <p class="fw-bold mb-4">Signature Livreur</p>
        </div>
        <div class="col-4 text-center">
            <p class="fw-bold mb-4">Signature Client</p>
            <small class="text-muted">("Reçu conforme")</small>
        </div>
        <div class="col-4 text-center">
            <p class="fw-bold mb-4">Cachet de l'Entreprise</p>
        </div>
    </div>
</div>

</body>
</html>