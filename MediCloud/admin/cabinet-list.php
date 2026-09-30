<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/cabinet_helper.php';

$admin = secure_admin_page($conn);
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$cabinets = get_all_cabinets($conn, $search, $status_filter);
$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cabinets clients — MediCloud Admin</title>
    <meta name="description" content="Gérez vos clients et leurs abonnements MediCloud, consultez les statuts et détails des cabinets">
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
                <h1 class="page-title">Cabinets clients</h1>
                <p class="page-subtitle">Gérez vos clients et leurs abonnements</p>
            </div>
            <div class="flex gap-1">
                <input type="search" id="cabinet-search" placeholder="🔍 Rechercher..." class="search-input" />
                <select id="status-filter" class="filter-select">
                    <option value="all">Tous les statuts</option>
                    <option value="active">✅ Actifs</option>
                    <option value="pending">⏳ En attente</option>
                    <option value="inactive">❌ Inactifs</option>
                </select>
                <select id="type-filter" class="filter-select">
                    <option value="all">Tous les types</option>
                    <option value="premium">⭐ Premium</option>
                    <option value="standard">🏫 Standard</option>
                </select>
                <a href="cabinet-add.php" class="btn btn-primary" style="margin-left: auto;">+ Ajouter un cabinet</a>
            </div>
        </div>

        <section class="card padded">
            <div class="cabinets-table-wrapper">
                <div class="cabinets-list">
                    
                    <div class="cabinet-row cabinet-row-header">
                        <div class="cabinet-col cabinet-col-name">Cabinet</div>
                        <div class="cabinet-col cabinet-col-director">Client</div>
                        <div class="cabinet-col cabinet-col-type">Type</div>
                        <div class="cabinet-col cabinet-col-status">Statut</div>
                        <div class="cabinet-col cabinet-col-subscription">Abonnement</div>
                        <div class="cabinet-col cabinet-col-actions">Actions</div>
                    </div>

                    <?php foreach ($cabinets as $cabinet): ?>
                    <div class="cabinet-row">
                        <div class="cabinet-col cabinet-col-name">
                            <a href="cabinet-details.php?id=<?= $cabinet['id'] ?>" class="cabinet-link"><?= htmlspecialchars($cabinet['nom_cabinet']) ?></a>
                        </div>
                        <div class="cabinet-col cabinet-col-director">
                            <span><?= htmlspecialchars(($cabinet['prenom'] ?? '') . ' ' . ($cabinet['nom'] ?? '')) ?></span>
                        </div>
                        <div class="cabinet-col cabinet-col-type">
                            <span class="badge badge-<?= htmlspecialchars($cabinet['type_abonnement'] ?? 'standard') ?>"><?= ucfirst(htmlspecialchars($cabinet['type_abonnement'] ?? 'Standard')) ?></span>
                        </div>
                        <div class="cabinet-col cabinet-col-status">
                            <span class="badge badge-<?= htmlspecialchars($cabinet['statut']) ?>"><?= ucfirst(htmlspecialchars($cabinet['statut'])) ?></span>
                        </div>
                        <div class="cabinet-col cabinet-col-subscription">
                            <div class="subscription-info">
                                <span class="subscription-type"><?= $cabinet['date_debut'] && $cabinet['date_fin'] ? round((strtotime($cabinet['date_fin']) - strtotime($cabinet['date_debut'])) / (30 * 24 * 60 * 60)) : '-' ?> mois</span>
                                <span class="subscription-date"><?= $cabinet['date_debut'] && $cabinet['date_fin'] ? (date('d M', strtotime($cabinet['date_debut'])) . ' - ' . date('d M Y', strtotime($cabinet['date_fin']))) : '-' ?></span>
                            </div>
                        </div>
                        <div class="cabinet-col cabinet-col-actions">
                            <div class="action-buttons">
                                <a href="https://maps.google.com/maps?q=<?= urlencode($cabinet['plus_code'] ?? '') ?>" class="btn-icon-small" target="_blank" title="Localiser">📍</a>
                                <a href="cabinet-details.php?id=<?= $cabinet['id'] ?>" class="btn-icon-small" title="Voir détails">👁️</a>
                                <a href="cabinet-edit.php?id=<?= $cabinet['id'] ?>" class="btn-icon-small" title="Éditer">✏️</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>




