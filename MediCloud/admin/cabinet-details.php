<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/cabinet_helper.php';

$admin = secure_admin_page($conn);
$cabinet_id = $_GET['id'] ?? 0;

$cabinet = get_cabinet_by_id($conn, $cabinet_id);
if (!$cabinet) {
    header("Location: cabinet-list.php?error=not_found");
    exit();
}

$stats = get_cabinet_stats($conn, $cabinet_id);
$subscription = $stats['current_subscription'] ?? null;
$invoices = $stats['recent_invoices'] ?? [];
$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($cabinet['nom_cabinet']) ?> – MediCloud Admin</title>
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
        
        <div class="page-header-horizontal">
            <div>
                <a href="cabinet-list.php" class="breadcrumb">← Retour aux cabinets</a>
                <h1 class="page-title"><?= htmlspecialchars($cabinet['nom_cabinet']) ?></h1>
                <p class="page-subtitle">Client : <?= htmlspecialchars(($cabinet['prenom'] ?? '') . ' ' . ($cabinet['nom'] ?? '')) ?></p>
            </div>
            <div class="flex gap-1">
                <span class="badge badge-<?= htmlspecialchars($cabinet['statut']) ?>"><?= ucfirst(htmlspecialchars($cabinet['statut'])) ?></span>
                <?php if ($subscription): ?>
                <span class="badge badge-<?= htmlspecialchars($subscription['type_abonnement']) ?>"><?= ucfirst(htmlspecialchars($subscription['type_abonnement'])) ?></span>
                <?php endif; ?>
                <a href="cabinet-edit.php?id=<?= $cabinet_id ?>" class="btn btn-ghost">✏️ Modifier</a>
            </div>
        </div>

        
        <div class="details-grid">
            
            <div class="details-column">
                <section class="card padded mb-3">
                    <h2 class="card-title mb-2">Informations générales</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <label>Nom du cabinet</label>
                            <p><?= htmlspecialchars($cabinet['nom_cabinet']) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Client responsable</label>
                            <p><?= htmlspecialchars(($cabinet['prenom'] ?? '') . ' ' . ($cabinet['nom'] ?? '')) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><a href="mailto:<?= htmlspecialchars($cabinet['email']) ?>" class="link-primary"><?= htmlspecialchars($cabinet['email']) ?></a></p>
                        </div>
                        <div class="info-item">
                            <label>Téléphone</label>
                            <p><a href="tel:<?= htmlspecialchars($cabinet['telephone']) ?>" class="link-primary"><?= htmlspecialchars($cabinet['telephone']) ?></a></p>
                        </div>
                        <div class="info-item">
                            <label>Adresse</label>
                            <p><?= htmlspecialchars($cabinet['adresse'] . ', ' . $cabinet['wilaya']) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Date de création</label>
                            <p><?= date('d F Y', strtotime($cabinet['created_at'])) ?></p>
                        </div>
                    </div>
                </section>

                <section class="card padded mb-3">
                    <h2 class="card-title mb-3">Localisation</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <label>Code Plus (Google Maps)</label>
                            <p><strong><?= htmlspecialchars($cabinet['plus_code']) ?></strong></p>
                        </div>
                    </div>
                    <a href="https://maps.google.com/maps?q=<?= urlencode($cabinet['plus_code']) ?>" target="_blank" class="btn btn-ghost" style="margin-top: 12px;">
                        🗺️ Ouvrir sur Google Maps
                    </a>
                </section>
            </div>

            
            <div class="details-column">
                <section class="card padded mb-3">
                    <h2 class="card-title mb-2">Abonnement</h2>
                    
                    <div class="subscription-status">
                        <div class="alert ok"><?= ($subscription && strtotime($subscription['date_fin']) > time()) ? '✅ Abonnement actif' : '❌ Abonnement expiré' ?></div>
                    </div>

                    <?php if ($subscription): ?>
                    <div class="info-list">
                        <div class="info-item">
                            <label>Type d'abonnement</label>
                            <p><strong><?= ucfirst(htmlspecialchars($subscription['type_abonnement'])) ?></strong></p>
                        </div>
                        <div class="info-item">
                            <label>Date de début</label>
                            <p><?= date('d F Y', strtotime($subscription['date_debut'])) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Date de fin</label>
                            <p><?= date('d F Y', strtotime($subscription['date_fin'])) ?></p>
                        </div>
                    </div>

                    <div class="subscription-progress mt-3">
                        <div class="progress-header mb-1">
                            <span class="subtitle">Progression</span>
                            <span class="progress-percent"><?php
                                $start = strtotime($subscription['date_debut']);
                                $end = strtotime($subscription['date_fin']);
                                $now = time();
                                $total = $end - $start;
                                $progress = max(0, min(100, (($now - $start) / $total) * 100));
                                echo round($progress) . '%';
                            ?></span>
                        </div>
                        <div class="progress-bar" style="--progress: <?php echo round($progress) ?>%">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-labels">
                            <span><?= date('d M Y', strtotime($subscription['date_debut'])) ?></span>
                            <span><?= date('d M Y', strtotime($subscription['date_fin'])) ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="subtitle mt-2">Aucun abonnement actif</p>
                    <?php endif; ?>
                </section>

                <section class="card padded">
                    <div class="flex between center mb-2">
                        <h2 class="card-title">Historique des paiements</h2>
                        <a href="invoice-list.php" class="btn-text">Voir tout →</a>
                    </div>
                    
                    <div class="payments-list">
                        <?php if (!empty($invoices)): ?>
                            <?php foreach ($invoices as $invoice): ?>
                        <div class="payment-item">
                            <div class="payment-header">
                                <div>
                                    <strong><?= htmlspecialchars($invoice['numero_facture']) ?></strong>
                                    <p class="table-subtitle"><?= date('d F Y', strtotime($invoice['date_creation'])) ?></p>
                                </div>
                                <span class="badge badge-<?= htmlspecialchars($invoice['status']) ?>">
                                    <?php
                                        $statuts = ['paid' => '✅ Payée', 'pending' => '⏳ En attente', 'cancelled' => '❌ Annulée'];
                                        echo $statuts[$invoice['status']] ?? htmlspecialchars($invoice['status']);
                                    ?>
                                </span>
                            </div>
                            <div class="payment-footer">
                                <div>
                                    <span class="table-subtitle">Montant</span>
                                    <strong><?php echo number_format($invoice['montant'], 0, ',', ' '); ?> DA</strong>
                                </div>
                                <a href="invoice-details.php?id=<?= urlencode($invoice['numero_facture']) ?>" class="btn-text">Détails →</a>
                            </div>
                        </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p class="subtitle">Aucune facture</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>




