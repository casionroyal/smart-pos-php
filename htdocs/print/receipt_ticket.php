<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) && isset($db)) {
    $pdo = $db;
}

$variant_id = $_GET['variant_id'] ?? null;
$mode = $_GET['mode'] ?? 'single';

if (!$variant_id) {
    die("ID Variante manquant.");
}

$stmt_variant = $pdo->prepare("
    SELECT v.*, m.model_name, m.brand 
    FROM phone_variants v
    JOIN phone_models m ON v.model_id = m.id
    WHERE v.id = ?
");
$stmt_variant->execute([$variant_id]);
$variant = $stmt_variant->fetch(PDO::FETCH_ASSOC);

if (!$variant) {
    die("Variante introuvable.");
}

if ($mode === 'bulk') {
    $stmt = $pdo->prepare("SELECT * FROM phone_imei WHERE variant_id = ? AND status = 'available'");
    $stmt->execute([$variant_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $imei = $_GET['imei'] ?? '';
    $sn = $_GET['sn'] ?? '';
    $items = [['imei_code' => $imei, 'serial_number' => $sn]];
}

if (empty($items)) {
    die("Aucun appareil disponible en stock pour l'impression.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Impression Étiquettes</title>
    <style>
        /* Définition de la taille exacte de l'étiquette (ex: 50mm x 30mm ou 80mm x 40mm) */
        @page {
            size: 50mm 30mm; /* Changez ici selon vos étiquettes */
            margin: 0;
        }

        html, body {
            width: 50mm;
            height: 30mm;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .ticket {
            width: 50mm;
            height: 30mm;
            padding: 2mm;
            box-sizing: border-box;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            /* Force chaque ticket à se mettre sur une nouvelle étiquette */
            page-break-after: always;
            break-after: page;
        }

        .title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .details {
            font-size: 8px;
            color: #333;
            margin: 1px 0;
        }

        .price {
            font-size: 10px;
            font-weight: bold;
            margin: 1px 0;
        }

        .code-text {
            font-size: 8px;
            font-family: monospace;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <?php foreach ($items as $item): ?>
        <div class="ticket">
            <div class="title">
                <?= htmlspecialchars($variant['brand'] . ' ' . $variant['model_name']) ?>
            </div>
            <div class="details">
                <?= htmlspecialchars($variant['ram'] . '/' . $variant['storage']) ?> | <?= htmlspecialchars($variant['color']) ?>
            </div>
            <div class="price">
                <?= number_format($variant['selling_price'], 0, '.', ' ') ?> DA
            </div>
            <div class="code-text">
                <?= htmlspecialchars(!empty($item['imei_code']) ? $item['imei_code'] : ($item['serial_number'] ?: 'N/A')) ?>
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>