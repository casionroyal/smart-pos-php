<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $sale_id = (int)$_POST['sale_id'];

    if ($sale_id <= 0) {
        header("Location: ../index.php?page=deliveries&error=" . urlencode("Identifiant de vente invalide."));
        exit;
    }

    // 1. CONFIRMATION DE LIVRAISON (LIVRÉ & PAYÉ)
    if ($_POST['action'] === 'confirm_delivery') {
        try {
            $stmt = $pdo->prepare("UPDATE sales SET delivery_status = 'delivered', payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$sale_id]);

            header("Location: ../index.php?page=deliveries&success=" . urlencode("Livraison confirmée avec succès !"));
            exit;
        } catch (PDOException $e) {
            header("Location: ../index.php?page=deliveries&error=" . urlencode("Erreur lors de la confirmation : " . $e->getMessage()));
            exit;
        }
    }

    // 2. RETOUR ARTICLE (RÉINTÉGRATION DU STOCK & REMBOURSEMENT)
    if ($_POST['action'] === 'return_delivery') {
        $pdo->beginTransaction();
        try {
            // Récupérer la variante et l'IMEI associé à la vente
            $stmtItem = $pdo->prepare("SELECT variant_id, imei_code FROM sale_items WHERE sale_id = ?");
            $stmtItem->execute([$sale_id]);
            $item = $stmtItem->fetch();

            if ($item) {
                // Remettre +1 en stock
                $stmtStock = $pdo->prepare("UPDATE phone_variants SET stock_qty = stock_qty + 1 WHERE id = ?");
                $stmtStock->execute([$item['variant_id']]);

                // Réactiver l'IMEI si présent
                if (!empty($item['imei_code'])) {
                    $stmtImei = $pdo->prepare("UPDATE product_imeis SET status = 'available' WHERE imei_code = ?");
                    $stmtImei->execute([$item['imei_code']]);
                }

                // Mettre à jour le statut de la vente
                $stmtSale = $pdo->prepare("UPDATE sales SET delivery_status = 'returned', payment_status = 'refunded' WHERE id = ?");
                $stmtSale->execute([$sale_id]);

                $pdo->commit();
                header("Location: ../index.php?page=deliveries&returned=" . urlencode("Article retourné et réintégré au stock avec succès !"));
                exit;
            } else {
                $pdo->rollBack();
                header("Location: ../index.php?page=deliveries&error=" . urlencode("Aucun article trouvé pour cette vente."));
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: ../index.php?page=deliveries&error=" . urlencode("Erreur de retour : " . $e->getMessage()));
            exit;
        }
    }
} else {
    header("Location: ../index.php?page=deliveries");
    exit;
}