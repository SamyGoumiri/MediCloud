<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/invoice_helper.php';

$admin = secure_admin_page($conn);

// Prevent page caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$invoice_id = $_GET['id'] ?? 0;

$invoice = get_invoice_by_id($conn, $invoice_id);
if (!$invoice) {
    header("Location: invoice-list.php?error=not_found");
    exit();
}

$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Facture #<?= htmlspecialchars($invoice['numero_facture']) ?> — MediCloud Admin</title>
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
        <div class="breadcrumb-container">
            <a class="breadcrumb" href="invoice-list.php">← Retour aux factures</a>
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="alert warning mb-2" style="margin-top: 1rem;">❌ Erreur: ' . htmlspecialchars($_GET['error']) . '</div>';
        } elseif (isset($_GET['success'])) {
            echo '<div class="alert ok mb-2" style="margin-top: 1rem;">✅ ' . htmlspecialchars($_GET['success']) . '</div>';
        }
        ?>
        </div>

        <div class="page-header-horizontal">
            <div>
                <h1 class="page-title">Facture #<?= htmlspecialchars($invoice['numero_facture']) ?></h1>
                <p class="page-subtitle">
                    <?= htmlspecialchars($invoice['nom_cabinet']) ?> • 
                    Émise le <?= date('d/m/Y', strtotime($invoice['date_creation'])) ?>
                </p>
            </div>
            <div class="flex gap-1">
                <span class="badge badge-<?= htmlspecialchars($invoice['status']) ?>">
                    <?php
                    $statut_labels = [
                        'paid' => '✅ Payée',
                        'pending' => '⏳ En attente',
                        'cancelled' => '❌ Annulée'
                    ];
                    echo $statut_labels[$invoice['status']] ?? ucfirst($invoice['status']);
                    ?>
                </span>
            </div>
        </div>

        <div class="grid grid-2">
            
            <section class="card padded">
                <h2 class="card-title">Informations de la facture</h2>
                
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Numéro de facture</span>
                        <span class="detail-value"><?= htmlspecialchars($invoice['numero_facture']) ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Montant</span>
                        <span class="detail-value"><?= number_format($invoice['montant'], 2) ?> DZD</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Date d'émission</span>
                        <span class="detail-value"><?= date('d/m/Y', strtotime($invoice['date_creation'])) ?></span>
                    </div>
                    
                    <?php if ($invoice['date_paiement']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Date de paiement</span>
                        <span class="detail-value"><?= date('d/m/Y', strtotime($invoice['date_paiement'])) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <span class="detail-label">Statut</span>
                        <span class="detail-value">
                            <span class="badge badge-<?= htmlspecialchars($invoice['status']) ?>">
                                <?= $statut_labels[$invoice['status']] ?? ucfirst($invoice['status']) ?>
                            </span>
                        </span>
                    </div>
                </div>
            </section>

            
            <section class="card padded">
                <h2 class="card-title">Informations client</h2>
                
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Cabinet</span>
                        <span class="detail-value">
                            <a href="cabinet-details.php?id=<?= $invoice['cabinet_id'] ?>" class="link">
                                <?= htmlspecialchars($invoice['nom_cabinet']) ?>
                            </a>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Responsable</span>
                        <span class="detail-value">
                            <?= htmlspecialchars(($invoice['prenom'] ?? '') . ' ' . ($invoice['nom'] ?? '')) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            <a href="mailto:<?= htmlspecialchars($invoice['cabinet_email']) ?>" class="link">
                                <?= htmlspecialchars($invoice['cabinet_email']) ?>
                            </a>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Téléphone</span>
                        <span class="detail-value">
                            <a href="tel:<?= htmlspecialchars($invoice['telephone']) ?>" class="link">
                                <?= htmlspecialchars($invoice['telephone']) ?>
                            </a>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Adresse</span>
                        <span class="detail-value">
                            <?= htmlspecialchars($invoice['adresse'] . ', ' . $invoice['commune'] . ', ' . $invoice['wilaya']) ?>
                        </span>
                    </div>
                </div>
            </section>
        </div>

        
        <?php if ($invoice['subscription_id']): ?>
        <section class="card padded">
            <h2 class="card-title">Abonnement</h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Type</span>
                    <span class="detail-value">
                        <span class="badge badge-<?= htmlspecialchars($invoice['type_abonnement']) ?>">
                            <?= ucfirst(htmlspecialchars($invoice['type_abonnement'])) ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Type d'abonnement</span>
                    <span class="detail-value"><?= htmlspecialchars($invoice['type_abonnement'] ?? 'N/A') ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Période</span>
                    <span class="detail-value">
                        <?= date('d/m/Y', strtotime($invoice['date_debut'])) ?> → 
                        <?= date('d/m/Y', strtotime($invoice['date_fin'])) ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Statut abonnement</span>
                    <span class="detail-value">
                        <span class="badge badge-<?= htmlspecialchars($invoice['subscription_status']) ?>">
                            <?= ucfirst(htmlspecialchars($invoice['subscription_status'])) ?>
                        </span>
                    </span>
                </div>
            </div>
        </section>
        <?php endif; ?>

        
        <?php if (!empty($payments)): ?>
        <section class="card padded">
            <h2 class="card-title">Historique des paiements</h2>
            
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Méthode</th>
                            <th>Montant</th>
                            <th>Référence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($payment['date_paiement'])) ?></td>
                            <td><?= date('H:i', strtotime($payment['heure_paiement'])) ?></td>
                            <td>
                                <?php
                                $methodes = [
                                    'virement' => '🏦 Virement',
                                    'cheque' => '📝 Chèque',
                                    'especes' => '💵 Espèces',
                                    'carte' => '💳 Carte'
                                ];
                                echo $methodes[$payment['methode']] ?? ucfirst($payment['methode']);
                                ?>
                            </td>
                            <td><?= number_format($payment['montant'], 2) ?> DZD</td>
                            <td><?= htmlspecialchars($payment['reference'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        
        <section class="card padded">
            <div class="flex gap-1 justify-end">
                <a href="invoice-list.php" class="btn btn-ghost">Retour à la liste</a>
                
                <?php if ($invoice['status'] === 'pending'): ?>
                <a href="invoice-confirm.php?invoice_id=<?= $invoice_id ?>" class="btn btn-primary">
                    Confirmer le paiement
                </a>
                <?php endif; ?>
                
                <button class="btn btn-secondary" onclick="window.print()">
                    🖨️ Imprimer
                </button>
            </div>
        </section>
    </main>
</body>
</html>
