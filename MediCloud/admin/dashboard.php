<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/dashboard_helper.php';

$admin = secure_admin_page($conn);
$stats = get_dashboard_stats($conn);
$recent_cabinets = $stats['recent_cabinets'] ?? [];
$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tableau de bord — MediCloud Admin</title>
    <meta name="description" content="Tableau de bord administrateur de MediCloud pour gérer les cabinets et abonnements">
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
                <h1 class="page-title">Tableau de bord</h1>
                <p class="page-subtitle">Bienvenue dans votre espace d'administration</p>
            </div>
            <div class="stats-overview">
                <div class="stat-box">
                    <span class="stat-label">Cabinets</span>
                    <span class="stat-number mono"><?= $stats['total_cabinets'] ?? 0 ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Revenus</span>
                    <span class="stat-number mono"><?= number_format(($stats['total_revenue'] ?? 0) / 1000000, 1) ?>M DA</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <section class="panel main-content">
                <div class="panel-header">
                    <h2>Cabinets médicaux</h2>
                    <a href="cabinet-list.php" class="btn-text">Voir tous →</a>
                </div>
                <div class="panel-body">
                    <div class="cabinet-list">
                        <?php foreach ($recent_cabinets as $cabinet): ?>
                        <div class="cabinet-item">
                            <div class="cabinet-info">
                                <h3><?= htmlspecialchars($cabinet['nom_cabinet']) ?></h3>
                                <span class="cabinet-location"><?= htmlspecialchars($cabinet['wilaya']) ?></span>
                            </div>
                            <span class="badge badge-<?= htmlspecialchars($cabinet['statut']) ?>"><?= ucfirst(htmlspecialchars($cabinet['statut'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <aside class="sidebar-actions">
                <section class="panel">
                    <div class="panel-header">
                        <h2>Actions rapides</h2>
                    </div>
                    <div class="panel-body">
                        <nav class="quick-nav">
                            <a href="cabinet-list.php" class="quick-nav-item">
                                <svg class="quick-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke-width="2"/><path d="M9 22V12h6v10" stroke-width="2"/></svg>
                                <span>Cabinets</span>
                            </a>
                            <a href="invoice-list.php" class="quick-nav-item">
                                <svg class="quick-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-width="2"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-width="2"/></svg>
                                <span>Factures</span>
                            </a>
                            <a href="reports.php" class="quick-nav-item">
                                <svg class="quick-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18" stroke-width="2"/><path d="M18 17l-5-5-4 4-4-4" stroke-width="2"/></svg>
                                <span>Rapports</span>
                            </a>
                            <a href="cabinet-add.php" class="quick-nav-item primary">
                                <svg class="quick-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round"/></svg>
                                <span>Nouveau cabinet</span>
                            </a>
                        </nav>
                    </div>
                </section>
            </aside>
        </div>
    </main>
</body>
</html>




