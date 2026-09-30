<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/invoice_helper.php';

$admin = secure_admin_page($conn);
$invoice_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'paid';
    $methode_paiement = $_POST['methode_paiement'] ?? '';
    $date_paiement = $_POST['date_paiement'] ?? date('Y-m-d');
    
    $result = update_invoice_status($conn, $invoice_id, $status, $methode_paiement, $date_paiement);
    
    if ($result['success']) {
        // Redirect back to invoice details with success message and timestamp to prevent cache
        header("Location: invoice-details.php?id=" . urlencode($invoice_id) . "&success=payment_confirmed&t=" . time());
        exit();
    }
    
    header("Location: invoice-confirm.php?id=" . urlencode($invoice_id) . "&error=" . urlencode($result['message']));
    exit();
}

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
    <title>Confirmer le paiement — MediCloud Admin</title>
    <meta name="description" content="Enregistrer le paiement d'une facture et activer le cabinet">
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
        <a class="breadcrumb" href="invoice-details.php?id=<?php echo htmlspecialchars($invoice_id); ?>">← Détails facture</a>
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="alert warning mb-2" style="margin-top: 1rem;">❌ Erreur: ' . htmlspecialchars($_GET['error']) . '</div>';
        } elseif (isset($_GET['success'])) {
            echo '<div class="alert ok mb-2" style="margin-top: 1rem;">✅ Paiement confirmé avec succès</div>';
        }
        ?>
        
        <div class="page-header-horizontal">
            <div>
                <h1 class="page-title">Confirmer le paiement</h1>
                <p class="page-subtitle">Enregistrez les informations de paiement pour activer le cabinet</p>
            </div>
        </div>

        <form class="edit-form" action="invoice-confirm.php?id=<?= $invoice_id ?>" method="POST">
            <input type="hidden" name="id" value="<?= $invoice_id ?>" />
            <input type="hidden" name="status" value="paid" />
            
            <div class="form-grid">
                
                <div class="form-column">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Détails du paiement</h2>
                        </div>
                        <div class="padded">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label required" for="date_paiement">Date du paiement</label>
                                    <input class="form-input" id="date_paiement" type="date" name="date_paiement" required value="<?= date('Y-m-d') ?>" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="heure_paiement">Heure</label>
                                    <input class="form-input" id="heure_paiement" type="time" name="heure_paiement" value="<?= date('H:i') ?>" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="montant">Montant du paiement</label>
                                <input class="form-input" id="montant" type="number" name="montant" step="0.01" required value="<?php echo htmlspecialchars($invoice['montant']); ?>" />
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="methode_paiement">Méthode de paiement</label>
                                <select class="form-select" id="methode_paiement" name="methode_paiement" required>
                                    <option value="">Sélectionner la méthode</option>
                                    <option value="virement">Virement bancaire</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="especes">Espèces</option>
                                    <option value="carte">Carte bancaire</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="reference">Référence de transaction</label>
                                <input class="form-input" id="reference" type="text" name="reference" placeholder="Ex: VIR-20251226-001" />
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="form-column">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Résumé facture</h2>
                        </div>
                        <div class="padded">
                            <div class="info-list">
                                <div class="info-item">
                                    <label>Facture</label>
                                    <p><strong><?php echo htmlspecialchars($invoice['numero_facture']); ?></strong></p>
                                </div>
                                <div class="info-item">
                                    <label>Cabinet</label>
                                    <p><?php echo htmlspecialchars($invoice['nom_cabinet'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Montant</label>
                                    <p class="amount"><strong><?php echo number_format($invoice['montant'], 0, ',', ' '); ?> DA</strong></p>
                                </div>
                                <div class="info-item">
                                    <label>Émise le</label>
                                    <p><?php echo htmlspecialchars(date('d M Y', strtotime($invoice['date_creation']))); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex between mt-2">
                <a class="btn btn-ghost" href="invoice-details.php?id=<?php echo htmlspecialchars($invoice_id); ?>">Annuler</a>
                <button type="submit" class="btn btn-primary">✅ Confirmer le paiement</button>
            </div>
        </form>
    </main>
</body>
</html>
