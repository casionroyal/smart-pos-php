<?php
// 1. Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timeout de session PHP (40 minutes)
ini_set('session.gc_maxlifetime', 2400);

// 2. URL de redirection en cas de déconnexion
$login_page = "login.php"; // Ajustez si votre fichier s'appelle login_page.php

// 3. Vérification de la session active
if (!isset($_SESSION['user_id'])) {
    header("Location: $login_page");
    exit();
}

// Connexion à la base de données
require_once __DIR__ . '/config/db.php'; 

// 4. VERIFICATION DU STATUT ET DE L'EXPIRATION DU COMPTE
$stmt = $pdo->prepare("SELECT username, expires_at, is_active FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$now = new DateTime();
$expiry = ($user && !empty($user['expires_at'])) ? new DateTime($user['expires_at']) : null;

// Si le compte n'existe pas, est désactivé (is_active = 0) ou expiré
if (!$user || $user['is_active'] == 0 || ($expiry && $now > $expiry)) {
    session_unset();
    session_destroy();
    header("Location: $login_page?error=expired");
    exit();
}

// 5. GESTION DU TIMEOUT D'INACTIVITÉ (40 minutes)
$timeout_duration = 2400; // 40 minutes en secondes

if (isset($_SESSION['last_action'])) {
    $elapsed_time = time() - $_SESSION['last_action'];
    
    if ($elapsed_time >= $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: $login_page?error=timeout");
        exit();
    }
}

// Mise à jour du timestamp de dernière action
$_SESSION['last_action'] = time();


// =========================================================================
// ENREGISTREMENT ET NETTOYAGE DES UTILISATEURS ACTIFS
// =========================================================================

// Détection de l'adresse IP
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ip_array[0]);
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$username_to_track = $user['username'] ?? ('User_' . $_SESSION['user_id']);

try {
    // Insertion / Mise à jour dans active_users
    $stmt_active = $pdo->prepare("
        INSERT INTO active_users (username, ip_address, last_activity) 
        VALUES (:username, :ip, NOW()) 
        ON DUPLICATE KEY UPDATE ip_address = :ip_update, last_activity = NOW()
    ");
    
    $stmt_active->execute([
        'username'  => $username_to_track, 
        'ip'        => $ip,
        'ip_update' => $ip
    ]);

    // Nettoyage des sessions inactives depuis plus de 40 minutes
    $pdo->query("DELETE FROM active_users WHERE last_activity < NOW() - INTERVAL 40 MINUTE");

} catch (Exception $e) {
    // Silencieux pour ne pas bloquer l'utilisateur si une erreur de suivi survient
}
?>