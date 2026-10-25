<?php
// Vérification d'accès si appelé hors du routeur
if (!defined('ABSPATH') && session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                
                <!-- En-tête -->
                <div class="card-header bg-primary text-white text-center py-4">
                    <i class="bi bi-headset display-4 d-block mb-2"></i>
                    <h4 class="fw-bold mb-0">Support Technique & Dépannage</h4>
                    <p class="small text-white-50 mb-0">Assistance dédiée - Smart POS</p>
                </div>

                <!-- Corps de la carte -->
                <div class="card-body p-4 text-center">
                    <p class="text-muted mb-3">
                        Scannez le QR code ci-dessous avec votre smartphone ou cliquez sur le bouton pour contacter le support technique.
                    </p>

                    <!-- Image du QR Code -->
                    <div class="d-inline-block p-3 bg-light border rounded-3 shadow-sm mb-3">
                        <img src="img/IMG_QR_Code.jpg" 
                             alt="QR Code Support" 
                             class="img-fluid rounded" 
                             style="max-width: 200px; height: auto;">
                    </div>

                    <!-- Bouton WhatsApp Direct (Remplacez 213XXXXXXXXX par votre numéro) -->
                    <div class="mb-4">
                        <a href="https://wa.me/213554544834?text=Bonjour,%20j'ai%20besoin%20d'assistance%20sur%20l'application%20Smart%20POS" 
                           target="_blank" 
                           class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                            <i class="bi bi-whatsapp me-2"></i> Contacter sur WhatsApp
                        </a>
                    </div>

                    <!-- Détails de la maintenance -->
                    <div class="row text-start bg-light rounded-3 p-3 g-2 small mb-3">
                        <div class="col-12 text-primary fw-bold mb-1">
                            <i class="bi bi-shield-check me-1"></i> Services inclus dans votre maintenance :
                        </div>
                        <div class="col-6 text-muted">
                            <i class="bi bi-check2-circle text-success me-1"></i> Hébergement & BDD
                        </div>
                        <div class="col-6 text-muted">
                            <i class="bi bi-check2-circle text-success me-1"></i> Mises à jour sécurité
                        </div>
                        <div class="col-6 text-muted">
                            <i class="bi bi-check2-circle text-success me-1"></i> Restauration Sauvegardes
                        </div>
                        <div class="col-6 text-muted">
                            <i class="bi bi-check2-circle text-success me-1"></i> Assistance système
                        </div>
                    </div>

                    <!-- Bouton de retour -->
                    <a href="index.php?page=dashboard" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left me-1"></i> Retour au Dashboard
                    </a>

                </div>
            </div>
        </div>
    </div>
</div>