---------------------   hash et changement mdp     ------------------------
<?php
// Remplacez 'MonNouveauMotDePasse' par le mot de passe souhaité
echo password_hash('pswd', PASSWORD_DEFAULT);
?>



-----------   prolongation user test par date     -----------------


    UPDATE users 
    SET expires_at = '2026-04-03 23:59:59' 
    WHERE username LIKE 'username';

---------------------------------------------------------------------