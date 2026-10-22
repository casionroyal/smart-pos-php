<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$action = $_REQUEST['action'] ?? '';

// 1. AJOUT D'UNE DÉCLINAISON MULTIPLE (AJOUT STOCK)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_variant') {
    $supplier_id    = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $brand          = trim($_POST['brand']);
    $model_name     = trim($_POST['model_name']);
    $network        = trim($_POST['network']);
    $color          = trim($_POST['color']);
    $ram            = trim($_POST['ram']);
    $storage        = trim($_POST['storage']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price  = floatval($_POST['selling_price']);
    $stock_qty      = intval($_POST['stock_qty']);

    $imei_codes     = $_POST['imei_codes'] ?? [];
    $serial_numbers = $_POST['serial_numbers'] ?? [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM phone_models WHERE brand = ? AND model_name = ?");
        $stmt->execute([$brand, $model_name]);
        $model = $stmt->fetch();

        if ($model) {
            $model_id = $model['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO phone_models (brand, model_name) VALUES (?, ?)");
            $stmt->execute([$brand, $model_name]);
            $model_id = $pdo->lastInsertId();
        }

        // Insertion avec supplier_id
        $stmt = $pdo->prepare("INSERT INTO phone_variants (model_id, supplier_id, network, color, ram, storage, purchase_price, selling_price, stock_qty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$model_id, $supplier_id, $network, $color, $ram, $storage, $purchase_price, $selling_price, $stock_qty]);
        $variant_id = $pdo->lastInsertId();

        $stmt_imei = $pdo->prepare("INSERT INTO phone_imei (variant_id, imei_code, serial_number, status) VALUES (?, ?, ?, 'available')");

        $first_imei = '';
        for ($i = 0; $i < $stock_qty; $i++) {
            $imei   = trim($imei_codes[$i] ?? '');
            $serial = trim($serial_numbers[$i] ?? '');

            if (!empty($imei) || !empty($serial)) {
                $stmt_imei->execute([$variant_id, $imei, $serial]);
                if (empty($first_imei) && !empty($imei)) {
                    $first_imei = $imei;
                }
            }
        }

        $pdo->commit();

        // Redirection avec les paramètres pour imprimer automatiquement le ticket code-barres de la boîte
        header('Location: ../index.php?page=stock&success=' . urlencode("Smartphone(s) ajouté(s) au stock avec succès !") . '&print_ticket_variant=' . $variant_id . '&imei=' . urlencode($first_imei));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../index.php?page=stock&error=' . urlencode("Erreur lors de l'ajout : " . $e->getMessage()));
        exit;
    }
}

// 2. MODIFICATION D'UN DÉTAIL D'APPAREIL / FAUTE DE FRAPPE OU AJOUT D'UNE UNITÉ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_variant') {
    $variant_id     = intval($_POST['variant_id']);
    $supplier_id    = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $network        = trim($_POST['network']);
    $color          = trim($_POST['color']);
    $ram            = trim($_POST['ram']);
    $storage        = trim($_POST['storage']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price  = floatval($_POST['selling_price']);

    $existing_imeis   = $_POST['existing_imei'] ?? [];
    $existing_serials = $_POST['existing_serial'] ?? [];
    $new_imei         = trim($_POST['new_imei'] ?? '');
    $new_serial       = trim($_POST['new_serial'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmt_update = $pdo->prepare("UPDATE phone_imei SET imei_code = ?, serial_number = ? WHERE id = ? AND variant_id = ?");
        foreach ($existing_imeis as $imei_id => $imei_code) {
            $code   = trim($imei_code);
            $serial = trim($existing_serials[$imei_id] ?? '');
            $stmt_update->execute([$code, $serial, intval($imei_id), $variant_id]);
        }

        if (!empty($new_imei) || !empty($new_serial)) {
            $stmt_add = $pdo->prepare("INSERT INTO phone_imei (variant_id, imei_code, serial_number, status) VALUES (?, ?, ?, 'available')");
            $stmt_add->execute([$variant_id, $new_imei, $new_serial]);
        }

        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM phone_imei WHERE variant_id = ? AND status = 'available'");
        $stmt_count->execute([$variant_id]);
        $real_stock = intval($stmt_count->fetchColumn());

        // Mise à jour avec supplier_id
        $stmt_var = $pdo->prepare("UPDATE phone_variants SET supplier_id = ?, network = ?, color = ?, ram = ?, storage = ?, purchase_price = ?, selling_price = ?, stock_qty = ? WHERE id = ?");
        $stmt_var->execute([$supplier_id, $network, $color, $ram, $storage, $purchase_price, $selling_price, $real_stock, $variant_id]);

        $pdo->commit();
        header('Location: ../index.php?page=stock&success=' . urlencode("Modifications et stocks synchronisés avec succès !"));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../index.php?page=stock&error=' . urlencode("Erreur lors de la modification : " . $e->getMessage()));
        exit;
    }
}

// 3. SUPPRESSION D'UN SEUL APPAREIL (PAR SON IMEI/ID)
if ($action === 'delete_single_imei' && isset($_GET['imei_id'], $_GET['variant_id'])) {
    $imei_id    = intval($_GET['imei_id']);
    $variant_id = intval($_GET['variant_id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM phone_imei WHERE id = ? AND variant_id = ? AND status = 'available'");
        $stmt->execute([$imei_id, $variant_id]);

        $stmt_qty = $pdo->prepare("UPDATE phone_variants SET stock_qty = GREATEST(0, stock_qty - 1) WHERE id = ?");
        $stmt_qty->execute([$variant_id]);

        $pdo->commit();
        header('Location: ../index.php?page=stock&success=' . urlencode("Appareil retiré du stock."));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../index.php?page=stock&error=' . urlencode("Erreur lors de la suppression : " . $e->getMessage()));
        exit;
    }
}

// 4. SUPPRESSION COMPLÈTE D'UNE VARIANTE (Superadmin)
if ($action === 'delete_variant' && isset($_GET['id'])) {
    if (($_SESSION['role'] ?? '') !== 'superadmin') {
        header('Location: ../index.php?page=stock&error=' . urlencode("Action non autorisée."));
        exit;
    }

    $variant_id = intval($_GET['id']);
    try {
        $pdo->beginTransaction();

        // 1. Supprimer les références dans les ventes
        $stmt_sales = $pdo->prepare("DELETE FROM sale_items WHERE variant_id = ?");
        $stmt_sales->execute([$variant_id]);

        // 2. Supprimer les numéros IMEI et de série rattachés
        $stmt_imei = $pdo->prepare("DELETE FROM phone_imei WHERE variant_id = ?");
        $stmt_imei->execute([$variant_id]);

        // 3. Supprimer la déclinaison / variante
        $stmt_variant = $pdo->prepare("DELETE FROM phone_variants WHERE id = ?");
        $stmt_variant->execute([$variant_id]);

        $pdo->commit();

        header('Location: ../index.php?page=stock&success=' . urlencode("Variante et l'ensemble de ses appareils/historiques ont été supprimés avec succès !"));
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: ../index.php?page=stock&error=' . urlencode("Erreur lors de la suppression : " . $e->getMessage()));
        exit;
    }
}