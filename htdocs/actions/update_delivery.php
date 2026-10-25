<?php
require_once '../config/db.php';

$sale_id = $_GET['id'] ?? null;
$action  = $_GET['action'] ?? null;

if (!$sale_id || !$action) {
    header("Location: ../index.php?page=deliveries&error=" . urlencode("Paramètres manquants."));
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'confirm') {
        // Validation de la vente : livraison effectuée et payée
        $stmt = $pdo->prepare("
            UPDATE sales 
            SET delivery_status = 'delivered', 
                payment_status = 'paid' 
            WHERE id = ?
        ");
        $stmt->execute([$sale_id]);

        $message = "La livraison a été confirmée et la vente finalisée avec succès.";

    } elseif ($action === 'return') {
        // 1. Mise à jour du statut de livraison et annulation du paiement
        $stmt = $pdo->prepare("
            UPDATE sales 
            SET delivery_status = 'returned', 
                payment_status = 'unpaid' 
            WHERE id = ?
        ");
        $stmt->execute([$sale_id]);

        // 2. Remettre le téléphone / IMEI en stock
        $stmtItems = $pdo->prepare("SELECT variant_id, imei FROM sale_items WHERE sale_id = ?");
        $stmtItems->execute([$sale_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // Remettre le statut de l'IMEI à 'available' (en stock)
            if (!empty($item['imei'])) {
                $stmtImei = $pdo->prepare("
                    UPDATE phone_imei 
                    SET status = 'available' 
                    WHERE imei_code = ? AND variant_id = ?
                ");
                $stmtImei->execute([$item['imei'], $item['variant_id']]);
            }
        }

        $message = "Le retour a été enregistré et le téléphone a été remis en stock.";
    }

    $pdo->commit();
    header("Location: ../index.php?page=deliveries&success=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../index.php?page=deliveries&error=" . urlencode("Erreur : " . $e->getMessage()));
    exit;
}