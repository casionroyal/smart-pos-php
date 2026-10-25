<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($pdo) && isset($db)) {
    $pdo = $db;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = $_SESSION['user_id'];
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $imei_code  = trim($_POST['imei'] ?? '');

    // Conversion flexible du prix (gestion des espaces et virgules)
    $raw_price     = str_replace([' ', ','], ['', '.'], $_POST['unit_price'] ?? '0');
    $selling_price = floatval($raw_price);

    $sale_type      = trim($_POST['sale_type'] ?? 'direct'); // direct ou delivery
    $client_name    = trim($_POST['client_name'] ?? '');
    $client_phone   = trim($_POST['client_phone'] ?? '');
    $client_address = trim($_POST['client_address'] ?? '');

    // Vérification des données obligatoires
    if ($variant_id <= 0 || $selling_price <= 0) {
        header('Location: ../index.php?page=pos&error=' . urlencode("Veuillez sélectionner un produit valide avec un prix correct."));
        exit;
    }

    // Définition des statuts selon la logique métier
    $delivery_status = ($sale_type === 'delivery') ? 'pending' : 'none';
    $payment_status  = ($sale_type === 'direct') ? 'paid' : 'pending';

    try {
        $pdo->beginTransaction();

        // 1. Insertion de la vente principale dans la table 'sales' (avec user_id)
        $stmt_sale = $pdo->prepare("
            INSERT INTO sales (user_id, sale_type, delivery_status, payment_status, client_name, client_phone, client_address, total_amount, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt_sale->execute([
            $user_id,
            $sale_type,
            $delivery_status,
            $payment_status,
            !empty($client_name) ? $client_name : NULL,
            !empty($client_phone) ? $client_phone : NULL,
            !empty($client_address) ? $client_address : NULL,
            $selling_price
        ]);
        $sale_id = $pdo->lastInsertId();

        // 2. Insertion du produit dans 'sale_items'
        $stmt_item = $pdo->prepare("
            INSERT INTO sale_items (sale_id, variant_id, imei, quantity, unit_price) 
            VALUES (?, ?, ?, 1, ?)
        ");
        $stmt_item->execute([
            $sale_id,
            $variant_id,
            !empty($imei_code) ? $imei_code : NULL,
            $selling_price
        ]);

        // 3. Mise à jour du statut de l'IMEI à 'sold' si renseigné
        if (!empty($imei_code)) {
            $stmt_imei = $pdo->prepare("UPDATE phone_imei SET status = 'sold' WHERE imei_code = ? AND variant_id = ?");
            $stmt_imei->execute([$imei_code, $variant_id]);
        }

        // 4. Décrémentation du stock disponible
        $stmt_stock = $pdo->prepare("UPDATE phone_variants SET stock_qty = GREATEST(0, stock_qty - 1) WHERE id = ?");
        $stmt_stock->execute([$variant_id]);

        $pdo->commit();

        // 5. Redirection avec le paramètre print_id pour déclencher l'impression automatique du document
        $msg = ($sale_type === 'delivery') ? urlencode("Vente par livraison enregistrée avec succès !") : urlencode("Vente comptoir validée avec succès !");
        
        header("Location: ../index.php?page=pos&success={$msg}&print_id={$sale_id}&type={$sale_type}");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../index.php?page=pos&error=' . urlencode("Erreur lors de l'enregistrement : " . $e->getMessage()));
        exit;
    }
} else {
    header('Location: ../index.php?page=pos');
    exit;
}