<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/cabinet_helper.php';

$admin = secure_admin_page($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom_cabinet' => $_POST['nom_cabinet'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'wilaya' => $_POST['wilaya'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'commune' => $_POST['commune'] ?? '',
        'plus_code' => $_POST['plus_code'] ?? null,
        'specialty' => $_POST['specialty'] ?? null,
        'description' => $_POST['description'] ?? null
    ];
    
    $result = create_cabinet($conn, $data);
    
    if ($result['success']) {
        header("Location: cabinet-details.php?id=" . $result['cabinet_id'] . "&success=created");
        exit();
    }
    
    header("Location: cabinet-add.php?error=" . urlencode($result['message']));
    exit();
}

$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ajouter un cabinet — MediCloud Admin</title>
    <meta name="description" content="Ajouter un nouveau cabinet client à MediCloud">
    <link rel="icon" href="../assets/frontend/medicloud.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="../assets/frontend/common.css" />
    <link rel="stylesheet" href="assets/frontend/admin.css" />

    <meta name="theme-color" content="#2F81F7" />
    <script src="assets/frontend/admin.js" defer></script>
</head>
<body>
  
  <header class="header">
    <div class="header-inner">
      <a class="brand" href="dashboard.php">
        <img src="../assets/frontend/medicloud.svg" alt="" class="brand-logo" />
        <span class="brand-name">MediCloud Admin</span>
      </a>
      <nav class="nav">
        <a href="cabinet-list.php" class="nav-link active">Cabinets</a>
        <a href="invoice-list.php" class="nav-link">Factures</a>
        <a href="reports.php" class="nav-link">Rapports</a>
      </nav>
      <div class="header-actions">
        <a class="icon-btn" href="profile.php" aria-label="Mon profil">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8a4 4 0 0 0 0 8z" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20a8 8 0 0 1 16 0" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a class="icon-btn danger" href="DB/logout.php" aria-label="Déconnexion">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M15 12H3" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 8l-4 4l4 4" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 4h-6v16h6" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>
    </div>
  </header>

  <main class="page-container">
    <a class="breadcrumb" href="cabinet-list.php">← Cabinets clients</a>
    <div class="page-header-horizontal">
      <div>
        <h1 class="page-title">Ajouter un cabinet</h1>
        <p class="page-subtitle">Renseignez les informations du cabinet et l'abonnement</p>
      </div>
    </div>



    <?php
    if (isset($_GET['error'])) {
        echo '<div class="alert warning mb-2">❌ Erreur: ' . htmlspecialchars($_GET['error']) . '</div>';
    } elseif (isset($_GET['success'])) {
        echo '<div class="alert ok mb-2">✅ Cabinet créé avec succès</div>';
    }
    ?>
    <form class="edit-form" action="cabinet-add.php" method="POST">
      <div class="form-grid">
        
        <div class="form-column">
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">Informations générales</h2>
            </div>
            <div class="padded">
              <div class="form-group">
                <label class="form-label required" for="nom_cabinet">Nom du cabinet</label>
                <input class="form-input" id="nom_cabinet" type="text" name="nom_cabinet" required />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required" for="prenom">Prénom du responsable</label>
                  <input class="form-input" id="prenom" type="text" name="prenom" required />
                </div>
                <div class="form-group">
                  <label class="form-label required" for="nom">Nom du responsable</label>
                  <input class="form-input" id="nom" type="text" name="nom" required />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required" for="email">Email</label>
                  <input class="form-input" id="email" type="email" name="email" required />
                </div>
                <div class="form-group">
                  <label class="form-label required" for="telephone">Téléphone</label>
                  <input class="form-input" id="telephone" type="tel" name="telephone" required />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required" for="wilaya">Wilaya</label>
                  <input class="form-input" id="wilaya" type="text" name="wilaya" required />
                </div>
                <div class="form-group">
                  <label class="form-label required" for="commune">Commune</label>
                  <input class="form-input" id="commune" type="text" name="commune" required />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="adresse">Adresse du cabinet</label>
                <textarea class="form-textarea" id="adresse" name="adresse" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label class="form-label" for="plus_code">Code Plus (Google Maps)</label>
                <input class="form-input" id="plus_code" type="text" name="plus_code" placeholder="Ex: P3PP+VQJ Kouba" required />
                <p class="subtitle mt-1">Obtenez le code sur Google Maps: clic droit → copier le code Plus</p>
              </div>            </div>
          </div>
        </div>

        
        <div class="form-column">
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">Abonnement</h2>
            </div>
            <div class="padded">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required" for="type_abonnement">Type d'abonnement</label>
                  <select class="form-select" id="type_abonnement" name="type_abonnement" required>
                    <option value="">Sélectionner</option>
                    <option value="standard">Standard</option>
                    <option value="premium">Premium</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label required" for="date_debut">Date de début</label>
                <input class="form-input" id="date_debut" type="date" name="date_debut" required />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex between mt-2">
        <a class="btn btn-ghost" href="cabinet-list.php">Annuler</a>
        <button type="submit" class="btn btn-primary">Créer le cabinet</button>
      </div>
    </form>
  </main>
</body>
</html>




