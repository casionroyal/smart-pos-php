<?php
session_start();
require_once '../config/db.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'add_supplier' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);
    $contact_name = trim($_POST['contact_name']);
    $phone        = trim($_POST['phone']);
    $address      = trim($_POST['address']);

    if (!empty($company_name)) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (company_name, contact_name, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_name, $contact_name, $phone, $address]);
        header("Location: ../index.php?page=suppliers&success=" . urlencode("Fournisseur ajouté avec succès."));
        exit;
    }
}

if ($action === 'edit_supplier' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id  = (int)$_POST['supplier_id'];
    $company_name = trim($_POST['company_name']);
    $contact_name = trim($_POST['contact_name']);
    $phone        = trim($_POST['phone']);
    $address      = trim($_POST['address']);

    if ($supplier_id > 0 && !empty($company_name)) {
        $stmt = $pdo->prepare("UPDATE suppliers SET company_name = ?, contact_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$company_name, $contact_name, $phone, $address, $supplier_id]);
        header("Location: ../index.php?page=suppliers&success=" . urlencode("Fournisseur mis à jour."));
        exit;
    }
}

if ($action === 'delete_supplier') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ../index.php?page=suppliers&success=" . urlencode("Fournisseur supprimé."));
        exit;
    }
}

header("Location: ../index.php?page=suppliers");
exit;