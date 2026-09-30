<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/invoice_helper.php';

$admin = secure_admin_page($conn);

// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    
    $invoice_id = $_POST['invoice_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';
    $payment_method = $_POST['payment_method'] ?? null;
    $payment_date = $_POST['payment_date'] ?? null;
    
    // Validate status
    if (!in_array($new_status, ['pending', 'paid', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit();
    }
    
    // If status is paid, require payment details
    if ($new_status === 'paid') {
        if (empty($payment_method)) {
            echo json_encode(['success' => false, 'message' => 'Méthode de paiement requise']);
            exit();
        }
        if (empty($payment_date)) {
            $payment_date = date('Y-m-d');
        }
    }
    
    $result = update_invoice_status($conn, $invoice_id, $new_status, $payment_method, $payment_date);
    
    if ($result['success']) {
        // Get updated invoice data
        $invoice = get_invoice_by_id($conn, $invoice_id);
        $result['invoice'] = $invoice;
    }
    
    echo json_encode($result);
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$filter_statut = $_GET['statut'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Get filtered invoices
$invoices = get_all_invoices($conn, $search, $filter_statut, $items_per_page, $offset);
$stats = get_invoice_stats($conn);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM invoices i LEFT JOIN cabinets c ON i.cabinet_id = c.id WHERE 1=1";
$count_params = [];
$count_types = '';

if (!empty($search)) {
    $count_query .= " AND (i.numero_facture LIKE ? OR c.nom_cabinet LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= 'ss';
}

if ($filter_statut !== 'all') {
    $count_query .= " AND i.status = ?";
    $count_params[] = $filter_statut;
    $count_types .= 's';
}

if (!empty($count_params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($count_types, ...$count_params);
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
} else {
    $count_result = $conn->query($count_query);
    $total_rows = $count_result->fetch_assoc()['total'] ?? 0;
}
$total_pages = max(1, ceil($total_rows / $items_per_page));

$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des factures — MediCloud Admin</title>
    <meta name="description" content="Créez et gérez les factures, suivez les paiements et relances de paiements">
    <link rel="icon" href="../assets/frontend/medicloud.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="../assets/frontend/common.css" />
    <link rel="stylesheet" href="assets/frontend/admin.css" />
    <meta name="theme-color" content="#2F81F7" />
    <style>
        .action-btns { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .status-cell { min-width: 150px; }

        /* Modal aligned with admin theme */
        .payment-form { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(15, 23, 42, 0.35); 
            backdrop-filter: blur(2px);
            display: none; 
            align-items: center; 
            justify-content: center; 
            z-index: 1000;
        }
        .payment-form.show { display: flex; }
        .payment-form-content { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 10px; 
            max-width: 520px; 
            width: 92%; 
            border: 1px solid #e5e7eb;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.18);
        }

        /* Toast notifications with page theme */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #0f172a 0%, #111827 100%);
            color: #f8fafc;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.35);
            display: none;
            z-index: 2000;
            animation: slideIn 0.3s ease;
            min-width: 280px;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }
        .notification.show { display: block; }
        .notification.success { border-left: 5px solid #10b981; }
        .notification.error { border-left: 5px solid #ef4444; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
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
        <div class="page-header-horizontal">
            <div>
                <h1 class="page-title">Gestion des factures</h1>
                <p class="page-subtitle">Création, suivi et gestion des paiements</p>
            </div>
            <div>
                <a href="invoice-add.php" class="btn btn-primary">➕ Nouvelle facture</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <section class="grid grid-3 mb-3">
            <div class="stat card hover-raise">
                <h4>Factures payées</h4>
                <div class="value"><?= $stats['paid_count'] ?? 0 ?></div>
                <p class="stat-trend positive"><?= number_format($stats['paid_revenue'] ?? 0, 0, ',', ' ') ?> DA</p>
            </div>
            <div class="stat card hover-raise">
                <h4>En attente</h4>
                <div class="value"><?= $stats['pending_count'] ?? 0 ?></div>
                <p class="stat-trend warning"><?= number_format($stats['pending_revenue'] ?? 0, 0, ',', ' ') ?> DA</p>
            </div>
            <div class="stat card hover-raise">
                <h4>Annulées</h4>
                <div class="value"><?= $stats['overdue_count'] ?? 0 ?></div>
                <p class="stat-trend warning"><?= number_format($stats['overdue_revenue'] ?? 0, 0, ',', ' ') ?> DA</p>
            </div>
        </section>

        <!-- Filters -->
        <section class="card padded mb-3">
            <div class="grid grid-2">
                <div class="field">
                    <label>🔍 Rechercher</label>
                    <input type="search" id="search-input" placeholder="N° facture, cabinet..." value="<?= htmlspecialchars($search) ?>" />
                </div>
                <div class="field">
                    <label>📊 Statut</label>
                    <select id="filter-statut">
                        <option value="all" <?= $filter_statut === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                        <option value="paid" <?= $filter_statut === 'paid' ? 'selected' : '' ?>>✅ Payée</option>
                        <option value="pending" <?= $filter_statut === 'pending' ? 'selected' : '' ?>>⏳ En attente</option>
                        <option value="cancelled" <?= $filter_statut === 'cancelled' ? 'selected' : '' ?>>❌ Annulée</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Invoices Table -->
        <section class="card padded">
            <h2 class="card-title mb-2">Liste des factures (<?= number_format($total_rows, 0, ',', ' ') ?>)</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Cabinet</th>
                            <th>Montant</th>
                            <th>Date d'émission</th>
                            <th class="status-cell">Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoices-tbody">
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">
                                <p style="color: #666;">📋 Aucune facture trouvée</p>
                                <p style="color: #999; font-size: 0.9rem;">Modifiez vos critères de recherche ou créez une nouvelle facture</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr data-invoice-id="<?= $invoice['id'] ?>">
                            <td><strong><?= htmlspecialchars($invoice['numero_facture']) ?></strong></td>
                            <td><?= htmlspecialchars($invoice['nom_cabinet'] ?? 'N/A') ?></td>
                            <td><strong><?= number_format($invoice['amount'] ?? 0, 0, ',', ' ') ?> DA</strong></td>
                            <td><?= date('d/m/Y', strtotime($invoice['date_creation'])) ?></td>
                            <td class="status-cell">
                                <span class="badge badge-<?= $invoice['status'] ?>" data-status="<?= $invoice['status'] ?>">
                                    <?php
                                    $labels = [
                                        'paid' => '✅ Payée',
                                        'pending' => '⏳ En attente',
                                        'cancelled' => '❌ Annulée'
                                    ];
                                    echo $labels[$invoice['status']] ?? ucfirst($invoice['status']);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="invoice-details.php?id=<?= urlencode($invoice['numero_facture']) ?>" class="btn btn-ghost btn-sm">👁️ Détails</a>
                                    
                                    <?php if ($invoice['status'] === 'pending'): ?>
                                        <button onclick="openPaymentForm(<?= $invoice['id'] ?>, '<?= htmlspecialchars($invoice['numero_facture']) ?>')" class="btn btn-primary btn-sm">💳 Payer</button>
                                        <button onclick="updateStatus(<?= $invoice['id'] ?>, 'cancelled')" class="btn btn-ghost btn-sm">❌</button>
                                    <?php elseif ($invoice['status'] === 'paid'): ?>
                                        <button onclick="updateStatus(<?= $invoice['id'] ?>, 'cancelled')" class="btn btn-ghost btn-sm">❌ Annuler</button>
                                    <?php elseif ($invoice['status'] === 'cancelled'): ?>
                                        <button onclick="updateStatus(<?= $invoice['id'] ?>, 'pending')" class="btn btn-ghost btn-sm">🔄 Réactiver</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex" style="justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                <div>
                    <span style="color:#666;">Page <?= $page ?> sur <?= $total_pages ?></span>
                </div>
                <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-ghost" href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($filter_statut) ?>&page=<?= $page - 1 ?>">← Précédent</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a class="btn <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>" href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($filter_statut) ?>&page=<?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a class="btn btn-ghost" href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($filter_statut) ?>&page=<?= $page + 1 ?>">Suivant →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Payment Form Modal -->
    <div id="payment-form" class="payment-form">
        <div class="payment-form-content">
            <h3 style="margin-bottom: 1.5rem;">💳 Confirmer le paiement</h3>
            <p style="margin-bottom: 1.5rem; color: #666;">Facture: <strong id="payment-invoice-number"></strong></p>
            
            <div class="field mb-2">
                <label>Méthode de paiement *</label>
                <select id="payment-method" required>
                    <option value="">Sélectionner...</option>
                    <option value="card">Carte bancaire</option>
                    <option value="bank_transfer">Virement bancaire</option>
                    <option value="cash">Espèces</option>
                    <option value="check">Chèque</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            
            <div class="field mb-3">
                <label>Date de paiement *</label>
                <input type="date" id="payment-date" value="<?= date('Y-m-d') ?>" required />
            </div>
            
            <div class="flex gap-1">
                <button onclick="submitPayment()" class="btn btn-primary">✅ Confirmer</button>
                <button onclick="closePaymentForm()" class="btn btn-ghost">❌ Annuler</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="notification" class="notification"></div>

    <script>
        let currentInvoiceId = null;

        // Apply filters
        function applyFilters() {
            const search = document.getElementById('search-input').value;
            const statut = document.getElementById('filter-statut').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (statut !== 'all') params.append('statut', statut);
            
            window.location.href = 'invoice-list.php?' + params.toString();
        }

        // Search on Enter
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });

        // Filter on change
        document.getElementById('filter-statut').addEventListener('change', applyFilters);

        // Show notification
        function showNotification(message, type = 'success') {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.className = `notification ${type} show`;
            
            setTimeout(() => {
                notif.classList.remove('show');
            }, 3000);
        }

        // Update invoice status
        async function updateStatus(invoiceId, newStatus) {
            if (newStatus === 'cancelled' && !confirm('Êtes-vous sûr de vouloir annuler cette facture ?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('invoice_id', invoiceId);
                formData.append('status', newStatus);

                const response = await fetch('invoice-list.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('✅ Statut mis à jour avec succès', 'success');
                    updateInvoiceRow(invoiceId, result.invoice);
                } else {
                    showNotification('❌ ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Erreur lors de la mise à jour', 'error');
            }
        }

        // Open payment form
        function openPaymentForm(invoiceId, invoiceNumber) {
            currentInvoiceId = invoiceId;
            document.getElementById('payment-invoice-number').textContent = invoiceNumber;
            document.getElementById('payment-form').classList.add('show');
            document.getElementById('payment-method').value = '';
            document.getElementById('payment-date').value = '<?= date('Y-m-d') ?>';
        }

        // Close payment form
        function closePaymentForm() {
            document.getElementById('payment-form').classList.remove('show');
            currentInvoiceId = null;
        }

        // Submit payment
        async function submitPayment() {
            const method = document.getElementById('payment-method').value;
            const date = document.getElementById('payment-date').value;

            if (!method) {
                showNotification('❌ Veuillez sélectionner une méthode de paiement', 'error');
                return;
            }

            if (!date) {
                showNotification('❌ Veuillez sélectionner une date de paiement', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('invoice_id', currentInvoiceId);
                formData.append('status', 'paid');
                formData.append('payment_method', method);
                formData.append('payment_date', date);

                const response = await fetch('invoice-list.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('✅ Paiement confirmé avec succès', 'success');
                    updateInvoiceRow(currentInvoiceId, result.invoice);
                    closePaymentForm();
                } else {
                    showNotification('❌ ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Erreur lors de la confirmation du paiement', 'error');
            }
        }

        // Update invoice row in table
        function updateInvoiceRow(invoiceId, invoice) {
            const row = document.querySelector(`tr[data-invoice-id="${invoiceId}"]`);
            if (!row) {
                // Reload page if row not found
                location.reload();
                return;
            }

            // Update status badge
            const statusLabels = {
                'paid': '✅ Payée',
                'pending': '⏳ En attente',
                'cancelled': '❌ Annulée'
            };

            const statusCell = row.querySelector('.status-cell');
            statusCell.innerHTML = `<span class="badge badge-${invoice.status}" data-status="${invoice.status}">${statusLabels[invoice.status]}</span>`;

            // Update action buttons
            const actionsCell = row.querySelector('.action-btns');
            let actionsHTML = `<a href="invoice-details.php?id=${encodeURIComponent(invoice.numero_facture)}" class="btn btn-ghost btn-sm">👁️ Détails</a>`;

            if (invoice.status === 'pending') {
                actionsHTML += `
                    <button onclick="openPaymentForm(${invoice.id}, '${invoice.numero_facture}')" class="btn btn-primary btn-sm">💳 Payer</button>
                    <button onclick="updateStatus(${invoice.id}, 'cancelled')" class="btn btn-ghost btn-sm">❌</button>
                `;
            } else if (invoice.status === 'paid') {
                actionsHTML += `<button onclick="updateStatus(${invoice.id}, 'cancelled')" class="btn btn-ghost btn-sm">❌ Annuler</button>`;
            } else if (invoice.status === 'cancelled') {
                actionsHTML += `<button onclick="updateStatus(${invoice.id}, 'pending')" class="btn btn-ghost btn-sm">🔄 Réactiver</button>`;
            }

            actionsCell.innerHTML = actionsHTML;

            // Add animation
            row.style.backgroundColor = '#f0fdf4';
            setTimeout(() => {
                row.style.backgroundColor = '';
            }, 1000);
        }

        // Close modal on outside click
        document.getElementById('payment-form').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentForm();
            }
        });
    </script>
</body>
</html>




