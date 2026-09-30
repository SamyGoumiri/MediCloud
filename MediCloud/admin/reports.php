<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';

$admin = secure_admin_page($conn);

// Prevent cache so filters reflect fresh data
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Helpers
function dt(string $date): string {
    return date('Y-m-d', strtotime($date));
}

// Resolve filter and dates
$filter = $_GET['filter'] ?? 'month';
$today = date('Y-m-d');

// Default period based on filter
switch ($filter) {
    case 'quarter':
        $currentMonth = (int)date('n');
        $quarterStartMonth = $currentMonth - (($currentMonth - 1) % 3);
        $start_date = date('Y-m-01', strtotime(date('Y') . '-' . $quarterStartMonth . '-01'));
        $end_date = date('Y-m-t', strtotime($start_date . ' +2 months'));
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    default:
        $filter = 'month';
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
}

// Override with custom dates if provided
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start_date = dt($_GET['start_date']);
    $end_date = dt($_GET['end_date']);
}

// Revenue stats
$revenue = [
    'invoice_count' => 0,
    'total_revenue' => 0.0,
    'paid_revenue' => 0.0
];

// Total invoices and revenue (issue date)
$stmt = $conn->prepare("SELECT COUNT(*) AS invoice_count, COALESCE(SUM(amount),0) AS total_revenue FROM invoices WHERE date_creation BETWEEN ? AND ?");
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$revenue['invoice_count'] = (int)($res['invoice_count'] ?? 0);
$revenue['total_revenue'] = (float)($res['total_revenue'] ?? 0);
$stmt->close();

// Paid revenue (use payment date when available, fallback to creation date)
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS paid FROM invoices WHERE status = 'paid' AND COALESCE(date_paiement, date_creation) BETWEEN ? AND ?");
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$revenue['paid_revenue'] = (float)($res['paid'] ?? 0);
$stmt->close();

// Renewal data: subscriptions ending in period
$renewals = [];
$renewalRate = 0;
$churnRate = 0;

$stmt = $conn->prepare("SELECT s.*, c.nom_cabinet FROM subscriptions s LEFT JOIN cabinets c ON s.cabinet_id = c.id WHERE s.date_fin BETWEEN ? AND ? ORDER BY s.date_fin ASC LIMIT 10");
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$renewals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Renewal/churn rates based on subscription status distribution
$totalSubs = 0; $activeSubs = 0; $cancelledSubs = 0;
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM subscriptions GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $totalSubs += (int)$row['cnt'];
    if ($row['status'] === 'active') {
        $activeSubs += (int)$row['cnt'];
    }
    if ($row['status'] === 'cancelled') {
        $cancelledSubs += (int)$row['cnt'];
    }
}
if ($totalSubs > 0) {
    $renewalRate = round(($activeSubs / $totalSubs) * 100);
    $churnRate = round(($cancelledSubs / $totalSubs) * 100);
}

// Top clients (paid revenue)
$topClients = [];
$stmt = $conn->prepare("SELECT c.nom_cabinet, COALESCE(SUM(i.amount),0) AS total_spent
                         FROM invoices i
                         LEFT JOIN cabinets c ON i.cabinet_id = c.id
                         WHERE i.status = 'paid'
                         GROUP BY i.cabinet_id, c.nom_cabinet
                         ORDER BY total_spent DESC
                         LIMIT 5");
$stmt->execute();
$topClients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Monthly chart (last 6 months paid revenue)
$monthly_chart = [];
$stmt = $conn->prepare("SELECT DATE_FORMAT(COALESCE(date_paiement, date_creation), '%Y-%m') AS ym,
                               COALESCE(SUM(amount),0) AS total
                        FROM invoices
                        WHERE status = 'paid'
                          AND COALESCE(date_paiement, date_creation) >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
                        GROUP BY ym
                        ORDER BY ym ASC");
$stmt->execute();
$monthly_chart = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Admin name (if needed in header)
$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rapports — MediCloud Admin</title>
    <meta name="description" content="Statistiques et analyses de votre activité MediCloud">
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
                <a href="invoice-list.php" class="nav-link">Factures</a>
                <a href="reports.php" class="nav-link active">Rapports</a>
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
                <h1 class="page-title">Rapports & Analyses</h1>
                <p class="page-subtitle">Statistiques complètes et tendances de votre activité</p>
            </div>
            <div class="flex gap-1" style="flex-wrap: wrap; justify-content: flex-end; align-items: center;">
                <form method="get" class="flex gap-1" style="flex-wrap: wrap; align-items: center;">
                    <select class="filter-select" name="filter" onchange="this.form.submit()">
                        <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Ce mois</option>
                        <option value="quarter" <?= $filter === 'quarter' ? 'selected' : '' ?>>Ce trimestre</option>
                        <option value="year" <?= $filter === 'year' ? 'selected' : '' ?>>Cette année</option>
                    </select>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" />
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" />
                    <button class="btn btn-ghost" type="submit">Mettre à jour</button>
                </form>
            </div>
        </div>

        <section class="card padded mb-3">
            <h2 class="card-title mb-3">💵 Revenus</h2>
            <div class="grid grid-3">
                <div class="stat" style="text-align: center;">
                    <h4>Revenus période</h4>
                    <div class="value"><?= number_format($revenue['total_revenue'] ?? 0, 0, ',', ' ') ?> DA</div>
                    <p class="stat-trend positive"><?= $revenue['invoice_count'] ?> factures</p>
                </div>
                <div class="stat" style="text-align: center;">
                    <h4>Montant payé</h4>
                    <div class="value"><?= number_format($revenue['paid_revenue'] ?? 0, 0, ',', ' ') ?> DA</div>
                    <p class="stat-trend positive"><?= ($revenue['total_revenue'] > 0) ? round(($revenue['paid_revenue'] / max($revenue['total_revenue'], 1)) * 100) : 0 ?>% collecté</p>
                </div>
                <div class="stat" style="text-align: center;">
                    <h4>Revenu moyen par facture</h4>
                    <div class="value"><?= ($revenue['invoice_count'] > 0) ? number_format($revenue['total_revenue'] / $revenue['invoice_count'], 0, ',', ' ') : '0' ?> DA</div>
                    <p class="stat-trend neutral"><?= $revenue['invoice_count'] ?> factures</p>
                </div>
            </div>
        </section>

        <section class="card padded mb-3">
            <h2 class="card-title mb-3">📈 Revenus (6 derniers mois)</h2>
            <div class="info-list">
                <?php if (empty($monthly_chart)): ?>
                    <div class="info-item"><p>Aucune donnée disponible</p></div>
                <?php else: ?>
                    <?php foreach ($monthly_chart as $row): ?>
                    <div class="info-item">
                        <label><?= htmlspecialchars($row['ym']) ?></label>
                        <p><strong><?= number_format($row['total'], 0, ',', ' ') ?> DA</strong></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="card padded mb-3">
            <h2 class="card-title mb-3">🔄 Renouvellements</h2>
            <div class="grid grid-3">
                <div class="stat" style="text-align: center;">
                    <h4>À renouveler sur la période</h4>
                    <div class="value"><?= count($renewals) ?></div>
                    <p class="stat-trend warning">
                        <?php 
                        if (count($renewals) > 0) {
                            echo htmlspecialchars($renewals[0]['nom_cabinet'] ?? '');
                        } else {
                            echo 'Aucun renouvellement';
                        }
                        ?>
                    </p>
                </div>
                <div class="stat" style="text-align: center;">
                    <h4>Taux de renouvellement</h4>
                    <div class="value"><?= $renewalRate ?>%</div>
                    <p class="stat-trend <?= $renewalRate >= 80 ? 'positive' : 'warning' ?>">
                        <?= $renewalRate >= 80 ? 'Excellent' : 'À améliorer' ?>
                    </p>
                </div>
                <div class="stat" style="text-align: center;">
                    <h4>Taux de résiliation</h4>
                    <div class="value"><?= $churnRate ?>%</div>
                    <p class="stat-trend <?= $churnRate === 0 ? 'positive' : 'danger' ?>">
                        <?= $churnRate === 0 ? 'Aucune résiliation' : 'Attention' ?>
                    </p>
                </div>
            </div>
            <?php if (!empty($renewals)): ?>
            <div class="info-list mt-2">
                <?php foreach ($renewals as $renewal): ?>
                <div class="info-item">
                    <label><?= htmlspecialchars($renewal['nom_cabinet'] ?? 'Cabinet') ?></label>
                    <p>Expire le <?= htmlspecialchars($renewal['date_fin']) ?> — Abonnement <?= htmlspecialchars($renewal['type_abonnement'] ?? 'N/A') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="card padded mb-3">
            <h2 class="card-title mb-3">🏆 Meilleurs clients</h2>
            <div class="info-list">
                <?php if (empty($topClients)): ?>
                    <div class="info-item"><p>Aucun client trouvé</p></div>
                <?php else: ?>
                    <?php foreach ($topClients as $index => $client): ?>
                    <div class="info-item">
                        <label><?= $index + 1 ?>. <?= htmlspecialchars($client['nom_cabinet'] ?? 'Cabinet') ?></label>
                        <p><strong><?= number_format($client['total_spent'], 0, ',', ' ') ?> DA</strong></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
    </main>

</body>

</html><?php

require_once 'DB/connect.php';

