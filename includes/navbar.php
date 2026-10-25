<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <!-- BRAND / LOGO DE LA BOUTIQUE -->
    <a class="navbar-brand fw-bold text-info d-flex align-items-center gap-2" href="index.php">
      <img src="img/logo_boutique.jpg" alt="Logo" class="rounded" style="max-height: 35px; width: auto; object-fit: contain;">
      <span>PLOU PHONE</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=pos"><i class="bi bi-cart-check"></i> Caisse / Vente</a></li>
    	<li class="nav-item"><a class="nav-link" href="index.php?page=suppliers"><i class="bi bi-building-gear"></i> Fournisseurs</a></li>
        <li class="nav-item"><a class="nav-link text-warning" href="index.php?page=deliveries"><i class="bi bi-truck"></i> Livraisons</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=stock"><i class="bi bi-box-seam"></i> Stock & Modèles</a></li>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
            <li class="nav-item"><a class="nav-link" href="index.php?page=users"><i class="bi bi-people"></i> Gestion Utilisateurs</a></li>
        <?php endif; ?>
		<li class="nav-item"><a class="nav-link" href="index.php?page=support"><i class="bi bi-headset"></i> Support & dépanage</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <!-- Nom d'utilisateur simple (non cliquable) -->
        <span class="navbar-text text-light me-3">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? 'Utilisateur') ?> 
            <span class="badge <?= ($_SESSION['role'] ?? '') === 'superadmin' ? 'bg-danger' : 'bg-info' ?>">
                <?= strtoupper($_SESSION['role'] ?? 'EMPLOYE') ?>
            </span>
        </span>
        <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
      </div>
    </div>
  </div>
</nav>