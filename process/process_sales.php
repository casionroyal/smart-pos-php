<?php
// process/process_sales.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_sale'])) {
    $client_slot = (int)$_POST['client_slot'];
    $client_name = trim($_POST['client_name']);
    $client_phone = trim($_POST['client_phone']);
    $sale_type = $_POST['sale_type'];

    // Vérifier si le panier existe et n'est pas vide
    if (!isset($_SESSION['pos_carts'][$client_slot]) || empty($_SESSION['pos_carts'][$client_slot]['items'])) {
        header("Location: ../pages/pos.php?client=$client_slot&error=Paniervide");
        exit;
    }

    $cart_items = $_SESSION['pos_carts'][$client_slot]['items'];

    // Calculer le montant total
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'];
    }

    try {
        // Démarrer une transaction SQL pour garantir l'intégrité des données
        $pdo->beginTransaction();

        // 1. Insérer la vente dans la table sales
        $stmt_sale = $pdo->prepare("
            INSERT INTO sales (user_id, sale_type, payment_status, client_name, client_phone, total_amount, paid_amount, created_at)
            VALUES (?, ?, 'paid', ?, ?, ?, ?, NOW())
        ");
        // On suppose que l'utilisateur connecté est dans $_SESSION['user_id'] ou ID 1 par défaut
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
        $stmt_sale->execute([$user_id, $sale_type, $client_name, $client_phone, $total_amount, $total_amount]);
        $sale_id = $pdo->lastInsertId();

        // 2. Insérer chaque article du panier et mettre à jour le statut de l'IMEI
        $stmt_item = $pdo->prepare("
            INSERT INTO sale_items (sale_id, variant_id, imei, quantity, unit_price)
            VALUES (?, ?, ?, 1, ?)
        ");

        $stmt_update_imei = $pdo->prepare("
            UPDATE phone_imei SET status = 'sold' WHERE id = ?
        ");

        // Mettre à jour aussi la quantité dans phone_variants si nécessaire
        $stmt_update_stock = $pdo->prepare("
            UPDATE phone_variants SET stock_qty = stock_qty - 1 WHERE id = ?
        ");

        foreach ($cart_items as $item) {
            // Insérer dans sale_items
            $stmt_item->execute([$sale_id, $item['variant_id'], $item['imei_code'], $item['price']]);
            
            // Marquer l'IMEI comme vendu
            $stmt_update_imei->execute([$item['imei_id']]);

            // Décrémenter le stock de la variante
            $stmt_update_stock->execute([$item['variant_id']]);
        }

        // Valider la transaction
        $pdo->commit();

        // Vider le panier du client validé
        $_SESSION['pos_carts'][$client_slot]['items'] = [];

        // Redirection propre vers la page de caisse avec un message de succès et l'ID pour l'impression
        header("Location: ../index.php?page=pos&client=" . $client_slot . "&success=Vente+validée+avec+succès&print_id=" . $sale_id);
        exit;

    } catch (Exception $e) {
        // En cas d'erreur, annuler toute la transaction
        $pdo->rollBack();
        die("Erreur lors de l'enregistrement de la vente : " . $e->getMessage());
    }
} else {
    header("Location: ../pages/pos.php");
    exit;
}