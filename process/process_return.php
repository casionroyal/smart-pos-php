<?php
// process/process_return.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_partial_return') {
        $sale_id = (int)$_POST['sale_id'];
        $returned_item_ids = $_POST['return_items'] ?? [];

        // Déterminer la page de redirection d'origine (par défaut deliveries)
        $redirect_page = $_POST['redirect'] ?? 'deliveries';

        if ($sale_id <= 0) {
            header("Location: ../index.php?page={$redirect_page}&error=" . urlencode("Vente invalide."));
            exit;
        }

        try {
            // Désactiver temporairement le mode strict de MySQL pour éviter les avertissements de troncature sur les statuts
            $pdo->exec("SET sql_mode = ''");
            $pdo->beginTransaction();

            if (!empty($returned_item_ids)) {
                // Traiter chaque article retourné par le client
                foreach ($returned_item_ids as $item_id) {
                    $item_id = (int)$item_id;

                    // Récupérer les infos de l'article (variant_id et imei)
                    $stmt_item = $pdo->prepare("SELECT variant_id, imei FROM sale_items WHERE id = ? AND sale_id = ?");
                    $stmt_item->execute([$item_id, $sale_id]);
                    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

                    if ($item) {
                        $variant_id = $item['variant_id'];
                        $imei_code = $item['imei'];

                        // 1. Remettre +1 dans le stock de la variante
                        $stmt_stock = $pdo->prepare("UPDATE phone_variants SET stock_qty = stock_qty + 1 WHERE id = ?");
                        $stmt_stock->execute([$variant_id]);

                        // 2. Remettre l'IMEI au statut 'available'
                        if (!empty($imei_code)) {
                            $stmt_imei = $pdo->prepare("UPDATE phone_imei SET status = 'available' WHERE imei_code = ?");
                            $stmt_imei->execute([$imei_code]);
                        }

                        // 3. Supprimer l'article retourné de la vente
                        $stmt_del = $pdo->prepare("DELETE FROM sale_items WHERE id = ?");
                        $stmt_del->execute([$item_id]);
                    }
                }

                // Vérifier s'il reste des articles dans cette vente
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE sale_id = ?");
                $stmt_check->execute([$sale_id]);
                $remaining_count = $stmt_check->fetchColumn();

                if ($remaining_count == 0) {
                    // Si tous les articles ont été retournés
                    $stmt_update_sale = $pdo->prepare("UPDATE sales SET delivery_status = 'returned', payment_status = 'ref', total_amount = 0 WHERE id = ?");
                    $stmt_update_sale->execute([$sale_id]);
                } else {
                    // S'il reste des articles, on recalcule le nouveau montant total
                    $stmt_recalc = $pdo->prepare("
                        UPDATE sales 
                        SET total_amount = (SELECT SUM(unit_price) FROM sale_items WHERE sale_id = ?),
                            delivery_status = 'delivered',
                            payment_status = 'paid'
                        WHERE id = ?
                    ");
                    $stmt_recalc->execute([$sale_id, $sale_id]);
                }

                $pdo->commit();
                header("Location: ../index.php?page={$redirect_page}&success=" . urlencode("Retour des articles traité avec succès."));
                exit;
            } else {
                $pdo->rollBack();
                header("Location: ../index.php?page={$redirect_page}&error=" . urlencode("Aucun article n'a été sélectionné pour le retour."));
                exit;
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: ../index.php?page={$redirect_page}&error=" . urlencode("Erreur lors du traitement du retour : " . $e->getMessage()));
            exit;
        }
    }
} else {
    header("Location: ../index.php?page=deliveries");
    exit;
}