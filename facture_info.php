<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif & Tarifs - SMART POS</title>
    <!-- Bootstrap Icons pour les icônes -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        /* Barre supérieure sombre avec le bouton centré */
        .top-bar {
            background-color: #1e293b;
            padding: 15px 30px;
            display: flex;
            justify-content: center; /* Centre le bouton horizontalement */
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        /* Style du bouton de retour */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #ffffff;
            color: #0f172a;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.2s ease-in-out;
        }
        .btn-back:hover {
            background-color: #f1f5f9;
            color: #000;
        }
        .container {
            max-width: 800px;
            background: #fff;
            margin: 0 auto 40px auto;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .section:last-child {
            border-bottom: none;
        }
        h2 {
            color: #27ae60;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        ul {
            padding-left: 20px;
            line-height: 1.6;
        }
        li {
            margin-bottom: 8px;
        }
        .pricing-grid {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        .pricing-card {
            flex: 1;
            background: #fdfefe;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            position: relative;
        }
        .pricing-card.featured {
            border: 2px solid #27ae60;
            background: #f4fcf7;
        }
        .badge-promo {
            position: absolute;
            top: -12px;
            right: 15px;
            background: #e74c3c;
            color: #fff;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .pricing-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 10px;
        }
        .price span {
            font-size: 0.9rem;
            color: #7f8c8d;
            font-weight: normal;
        }
        .old-price {
            text-decoration: line-through;
            color: #e74c3c;
            font-size: 1rem;
            margin-right: 5px;
        }
        .highlight-box {
            background: #e8f8f5;
            border-left: 5px solid #27ae60;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #95a5a6;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <!-- Barre supérieure sombre avec le bouton de retour centré -->
    <div class="top-bar">
        <a href="index.php?page=dashboard" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>

    <!-- Contenu Principal de la page -->
    <div class="container">
        <h1>SMART POS</h1>
        <div class="subtitle">Gestion Professionnelle & Suivi des Smartphones (IMEI / Numéro de série)</div>

        <div class="section">
            <h2>1. Fonctionnalités Clés de la Solution</h2>
            <ul>
                <li><strong>Suivi unitaire strict :</strong> Gestion des stocks par IMEI et numéros de série (SN) pour chaque smartphone.</li>
                <li><strong>Caisse POS intuitive :</strong> Interface de vente rapide adaptée aux spécificités de la téléphonie.</li>
                <li><strong>Sécurité renforcée :</strong> Authentification centralisée, gestion des rôles (SuperAdmin / Employé) et suivi des sessions actives.</li>
                <li><strong>Contrôle d'activité :</strong> Surveillance des connexions et déconnexion automatique en cas d'inactivité.</li>
            </ul>
        </div>

        <div class="section">
            <h2>2. Grille Tarifaire & Conditions</h2>
            <ul>
                <li><strong>Période de transition :</strong> Accès libre et illimité accordé <strong>jusqu'au 31 décembre</strong> pour une prise en main complète.</li>
                <li><strong>Accompagnement :</strong> Réunions de calage et ajustements personnalisés selon les besoins spécifiques du magasin.</li>
            </ul>

            <div class="pricing-grid">
                <div class="pricing-card">
                    <h3>Acquisition Solution</h3>
                    <div class="price">60.000 <span>DA</span></div>
                    <p style="font-size: 0.9rem; color: #666;">Mise en place initiale, configuration et intégration du système POS.</p>
                </div>

                <div class="pricing-card">
                    <h3>Abonnement Mensuel</h3>
                    <div class="price">6.500 <span>DA / mois</span></div>
                    <p style="font-size: 0.9rem; color: #666;">À partir du 1er janvier. Inclut l'<strong>hébergement serveur</strong>, les mises à jour et le support technique.</p>
                </div>

                <div class="pricing-card featured">
                    <div class="badge-promo">Offre Spéciale 1<sup>ère</sup> Année</div>
                    <h3>Formule Annuelle</h3>
                    <div class="price">
                        <span class="old-price">60.000 DA</span>
                        30.000 <span>DA / an</span>
                    </div>
                    <p style="font-size: 0.9rem; color: #666;">
                        Offre de lancement à partir du 1er janvier : <strong>-50% sur la 1<sup>ère</sup> année</strong> d'abonnement !<br>
                        Inclut l'<strong>hébergement serveur</strong> et le <strong>support 24/7 offert gratuitement</strong>.
                    </p>
                </div>
            </div>
        </div>

        <div class="highlight-box">
            <strong>Note importante :</strong> L'abonnement (mensuel ou annuel) couvre la prise en charge globale de l'infrastructure : <strong>hébergement web sécurisé</strong>, maintenance du système, sauvegardes et mises à jour continues.
        </div>

        <div class="footer">
            &copy; 2026 SMART POS - Tous droits réservés.
        </div>
    </div>

</body>
</html>