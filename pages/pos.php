<?php
// pages/pos.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialisation des paniers multiples (3 clients au comptoir) s'ils n'existent pas
if (!isset($_SESSION['pos_carts'])) {
    $_SESSION['pos_carts'] = [
        1 => ['client_name' => 'Client 1', 'items' => []],
        2 => ['client_name' => 'Client 2', 'items' => []],
        3 => ['client_name' => 'Client 3', 'items' => []]
    ];
}

// Client actif (par défaut le client 1)
$active_client = isset($_GET['client']) ? (int)$_GET['client'] : 1;
if (!array_key_exists($active_client, $_SESSION['pos_carts'])) {
    $active_client = 1;
}

$message = '';
$error = '';

// 1. Gestion de l'ajout d'un article par IMEI avec son prix de vente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_imei'])) {
    $imei_input = trim($_POST['imei_code']);
    $custom_price = floatval($_POST['custom_price'] ?? 0);
    
    if (!empty($imei_input)) {
        // Recherche du téléphone dans la table phone_imei et phone_variants
        $stmt = $pdo->prepare("
            SELECT v.id as variant_id, v.purchase_price, v.color, v.ram, v.storage, m.brand, m.model_name, i.id as imei_id
            FROM phone_imei i
            JOIN phone_variants v ON i.variant_id = v.id
            JOIN phone_models m ON v.model_id = m.id
            WHERE i.imei_code = ? AND i.status = 'available'
        ");
        $stmt->execute([$imei_input]);
        $phone = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($phone) {
            // Vérifier si l'IMEI n'est pas déjà dans le panier actif
            $already_in_cart = false;
            foreach ($_SESSION['pos_carts'][$active_client]['items'] as $item) {
                if ($item['imei_code'] === $imei_input) {
                    $already_in_cart = true;
                    break;
                }
            }

            if (!$already_in_cart) {
                // Ajouter au panier du client actif avec le prix saisi par le vendeur
                $_SESSION['pos_carts'][$active_client]['items'][] = [
                    'imei_id' => $phone['imei_id'],
                    'variant_id' => $phone['variant_id'],
                    'imei_code' => $imei_input,
                    'name' => $phone['brand'] . ' ' . $phone['model_name'] . ' (' . $phone['color'] . ', ' . $phone['ram'] . 'GB/' . $phone['storage'] . 'GB)',
                    'price' => $custom_price > 0 ? $custom_price : 0 // Prix fixé par le vendeur
                ];
                $message = "Téléphone IMEI $imei_input ajouté avec succès.";
            } else {
                $error = "Cet IMEI est déjà présent dans le panier de ce client.";
            }
        } else {
            $error = "Aucun téléphone disponible trouvé avec cet IMEI : " . htmlspecialchars($imei_input);
        }
    }
}

// 2. Suppression d'un article du panier
if (isset($_GET['remove'])) {
    $index = (int)$_GET['remove'];
    if (isset($_SESSION['pos_carts'][$active_client]['items'][$index])) {
        unset($_SESSION['pos_carts'][$active_client]['items'][$index]);
        $_SESSION['pos_carts'][$active_client]['items'] = array_values($_SESSION['pos_carts'][$active_client]['items']);
        header("Location: index.php?page=pos&client=" . $active_client);
        exit;
    }
}

// Calcul du total du panier actif
$total_amount = 0;
foreach ($_SESSION['pos_carts'][$active_client]['items'] as $item) {
    $total_amount += $item['price'];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><i class="fas fa-cash-register"></i> Caisse - Vente par IMEI</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Sélecteur de Client au Comptoir (3 clients) -->
    <div class="card mb-4">
        <div class="card-body bg-light">
            <h5 class="card-title">Gestion des Clients au Comptoir</h5>
            <div class="btn-group" role="group">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <?php 
                        $count_items = count($_SESSION['pos_carts'][$i]['items']);
                        $is_active = ($active_client === $i);
                    ?>
                    <a href="index.php?page=pos&client=<?= $i; ?>" class="btn <?= $is_active ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <?= $_SESSION['pos_carts'][$i]['client_name']; ?> 
                        <span class="badge bg-light text-dark"><?= $count_items; ?> art.</span>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulaire de recherche par IMEI + Saisie du Prix de Vente -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-barcode"></i> Scanner l'IMEI & Fixer le Prix
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=pos&client=<?= $active_client; ?>">
                        <div class="mb-3">
                            <label for="imei_code" class="form-label fw-bold">Code IMEI du Smartphone</label>
                            <input type="text" class="form-control form-control-lg" id="imei_code" name="imei_code" placeholder="Scannez ou tapez l'IMEI..." autofocus required>
                        </div>
                        <div class="mb-3">
                            <label for="custom_price" class="form-label fw-bold">Prix de Vente (DA)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg text-success fw-bold" id="custom_price" name="custom_price" placeholder="Ex: 75000" required>
                        </div>
                        <button type="submit" name="add_imei" class="btn btn-success w-100 btn-lg fw-bold">
                            <i class="fas fa-cart-plus"></i> Ajouter au Panier (<?= $_SESSION['pos_carts'][$active_client]['client_name']; ?>)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panier du Client Actif -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-shopping-cart"></i> Panier de : <strong><?= $_SESSION['pos_carts'][$active_client]['client_name']; ?></strong></span>
                    <span class="badge bg-warning text-dark"><?= count($_SESSION['pos_carts'][$active_client]['items']); ?> articles</span>
                </div>
                <div class="card-body">
                    <?php if (empty($_SESSION['pos_carts'][$active_client]['items'])): ?>
                        <p class="text-muted text-center py-4">Le panier est vide. Scannez un IMEI et indiquez son prix pour commencer.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Produit / Modèle</th>
                                        <th>IMEI</th>
                                        <th>Prix (DA)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['pos_carts'][$active_client]['items'] as $index => $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']); ?></td>
                                        <td><code><?= htmlspecialchars($item['imei_code']); ?></code></td>
                                        <td class="fw-bold text-success"><?= number_format($item['price'], 2); ?> DA</td>
                                        <td>
                                            <a href="index.php?page=pos&client=<?= $active_client; ?>&remove=<?= $index; ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Total :</h4>
                            <h3 class="text-success fw-bold"><?= number_format($total_amount, 2); ?> DA</h3>
                        </div>

                        <!-- Formulaire de validation de la vente (génère la facture globale avec tous les articles) -->
                        <form method="POST" action="process/process_sales.php">
                            <input type="hidden" name="client_slot" value="<?= $active_client; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nom du Client</label>
                                <input type="text" class="form-control" name="client_name" placeholder="Nom et Prénom du client..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Téléphone du Client</label>
                                <input type="text" class="form-control" name="client_phone" placeholder="0550...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Type de Vente</label>
                                <select class="form-select" name="sale_type">
                                    <option value="direct">Vente Directe au Comptoir</option>
                                    <option value="delivery">Vente avec Livraison</option>
                                </select>
                            </div>

                            <button type="submit" name="validate_sale" class="btn btn-primary w-100 btn-lg fw-bold">
                                <i class="fas fa-check-circle"></i> Valider et Enregistrer la Vente (Facture Globale)
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>