<?php
// process/process_delivery.php
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

    // VALIDATION GLOBALE DE TOUTE LA LIVRAISON
    if ($_POST['action'] === 'deliver_all') {
        try {
            $stmt = $pdo->prepare("UPDATE sales SET delivery_status = 'delivered', payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$sale_id]);

            header("Location: ../index.php?page=deliveries&success=" . urlencode("Livraison validée et payée avec succès !"));
            exit;
        } catch (PDOException $e) {
            header("Location: ../index.php?page=deliveries&error=" . urlencode("Erreur lors de la validation : " . $e->getMessage()));
            exit;
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] === 'deliver_all' && isset($_GET['id'])) {
    // Sécurité au cas où l'action est passée en GET (lien direct)
    $sale_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE sales SET delivery_status = 'delivered', payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$sale_id]);

        header("Location: ../index.php?page=deliveries&success=" . urlencode("Livraison validée et payée avec succès !"));
        exit;
    } catch (PDOException $e) {
        header("Location: ../index.php?page=deliveries&error=" . urlencode("Erreur lors de la validation : " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../index.php?page=deliveries");
    exit;
}