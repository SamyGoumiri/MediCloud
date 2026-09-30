<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/invoice_helper.php';
require_once 'DB/cabinet_helper.php';

$admin = secure_admin_page($conn);

// Prevent cache to show fresh messages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$error_message = null;
$success_message = null;

// Prefill defaults
$prefill = [
  'cabinet_id' => '',
  'date_creation' => date('Y-m-d'),
  'status' => 'pending',
  'amount' => '',
  'methode_paiement' => '',
  'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cabinet_id = (int)($_POST['cabinet_id'] ?? 0);
  $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
  $date_creation = !empty($_POST['date_creation']) ? $_POST['date_creation'] : date('Y-m-d');
  $status = $_POST['status'] ?? 'pending';
  $methode_paiement = $_POST['methode_paiement'] ?? null;
  $notes = $_POST['notes'] ?? null;

  // Persist entered values on validation error
  $prefill = [
    'cabinet_id' => $cabinet_id,
    'date_creation' => $date_creation,
    'status' => $status,
    'amount' => $amount ?: '',
    'methode_paiement' => $methode_paiement,
    'notes' => $notes
  ];

  $allowed_status = ['pending', 'paid', 'cancelled'];
  if (!in_array($status, $allowed_status, true)) {
    $status = 'pending';
  }

  // Validation
  if ($cabinet_id <= 0) {
    $error_message = 'Veuillez sélectionner un cabinet.';
  } elseif ($amount <= 0) {
    $error_message = 'Le montant doit être supérieur à 0.';
  } elseif ($status === 'paid' && empty($methode_paiement)) {
    $error_message = 'La méthode de paiement est requise pour une facture payée.';
  }

  if (!$error_message) {
    $result = create_invoice($conn, [
      'cabinet_id' => $cabinet_id,
      'subscription_id' => null,
      'amount' => $amount,
      'date_creation' => $date_creation,
      'status' => $status,
      'methode_paiement' => $methode_paiement,
      'reference' => $notes
    ]);

    if ($result['success']) {
      $success_message = 'Facture créée avec succès';
      header("Location: invoice-details.php?id=" . urlencode($result['invoice_number']) . "&success=created");
      exit();
    } else {
      $error_message = $result['message'] ?? 'Création impossible';
    }
  }
}

$cabinets = get_all_cabinets($conn);
$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nouvelle facture - MediCloud Admin</title>
  <meta name="description" content="Créer une nouvelle facture client dans MediCloud">
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
        <a href="cabinet-list.php" class="nav-link">Cabinets</a>
        <a href="invoice-list.php" class="nav-link active">Factures</a>
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
    <?php if ($error_message): ?>
      <div class="alert warning mb-2">❌ Erreur: <?= htmlspecialchars($error_message) ?></div>
    <?php elseif (isset($_GET['success'])): ?>
      <div class="alert ok mb-2">✅ Facture créée avec succès</div>
    <?php endif; ?>
    <div class="page-header-horizontal">
      <div>
        <h1 class="page-title">Nouvelle facture</h1>
        <p class="page-subtitle">Créer une facture pour un cabinet</p>
      </div>
    </div>

    
    <section class="card padded">
      <form class="form-container" action="invoice-add.php" method="POST">
        <div class="grid grid-2">
          
          <div class="field">
            <label>Cabinet client *</label>
            <select name="cabinet_id" required>
              <option value="">Sélectionner un cabinet</option>
              <?php foreach ($cabinets as $cabinet): ?>
              <option value="<?= $cabinet['id'] ?>" <?= (string)$prefill['cabinet_id'] === (string)$cabinet['id'] ? 'selected' : '' ?>
              ><?= htmlspecialchars($cabinet['nom_cabinet']) ?> [<?= ucfirst($cabinet['statut']) ?>]</option>
              <?php endforeach; ?>
            </select>
            <p class="subtitle mt-1">Créez des factures pour nouveaux abonnements ou renouvellements</p>
          </div>

          
          <div class="field">
            <label>Numéro de facture *</label>
            <input type="text" name="invoice_number" placeholder="Auto-généré" value="Généré automatiquement" readonly />
          </div>

          
          <div class="field">
            <label>Date d'émission *</label>
            <input type="date" name="date_creation" required value="<?= htmlspecialchars($prefill['date_creation']) ?>" />
          </div>

          
          <div class="field">
            <label>Type d'abonnement *</label>
            <select name="subscription_type">
              <option value="">Sélectionner le type</option>
              <option value="standard">Standard</option>
              <option value="premium">Premium</option>
            </select>
          </div>

          
          <div class="field">
            <label>Durée (mois) *</label>
            <select name="subscription_duration">
              <option value="">Sélectionner</option>
              <option value="6">6 mois</option>
              <option value="12">12 mois</option>
              <option value="24">24 mois</option>
            </select>
          </div>

          
          <div class="field">
            <label>Montant (DA) *</label>
            <input type="number" name="amount" placeholder="0" required step="1000" value="<?= htmlspecialchars($prefill['amount']) ?>" />
          </div>

          
          <div class="field">
            <label>Statut *</label>
            <select name="status" required>
              <option value="pending" <?= $prefill['status'] === 'pending' ? 'selected' : '' ?>>⏳ En attente</option>
              <option value="paid" <?= $prefill['status'] === 'paid' ? 'selected' : '' ?>>✅ Payée</option>
              <option value="cancelled" <?= $prefill['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Annulée</option>
            </select>
          </div>

          
          <div class="field">
            <label>Méthode de paiement</label>
            <select name="methode_paiement">
              <option value="" <?= $prefill['methode_paiement'] === '' ? 'selected' : '' ?>>Non spécifiée</option>
              <option value="virement" <?= $prefill['methode_paiement'] === 'virement' ? 'selected' : '' ?>>Virement bancaire</option>
              <option value="cheque" <?= $prefill['methode_paiement'] === 'cheque' ? 'selected' : '' ?>>Chèque</option>
              <option value="especes" <?= $prefill['methode_paiement'] === 'especes' ? 'selected' : '' ?>>Espèces</option>
              <option value="carte" <?= $prefill['methode_paiement'] === 'carte' ? 'selected' : '' ?>>Carte bancaire</option>
            </select>
          </div>
        </div>

        
        <div class="field mt-2">
          <label>Notes / Description</label>
          <textarea rows="4" name="notes" placeholder="Informations complémentaires sur la facture..."><?= htmlspecialchars($prefill['notes']) ?></textarea>
        </div>

        
        <div class="flex gap-1 mt-3">
          <a href="invoice-list.php" class="btn btn-ghost">Annuler</a>
          <button type="submit" class="btn btn-primary">📄 Créer la facture</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>




