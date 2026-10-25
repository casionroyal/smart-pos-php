<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    // Mettre show_promo à 0 dans la base de données pour cet utilisateur
    $stmt = $pdo->prepare("UPDATE users SET show_promo = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Mettre à jour aussi la variable de session en cours
    $_SESSION['show_promo'] = 0;
}

// Redirection vers la page des informations de facture
header('Location: facture_info.php');
exit;