<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/cabinet_helper.php';

$admin = secure_admin_page($conn);
$cabinet_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $result = delete_cabinet($conn, $cabinet_id);
        
        if ($result['success']) {
            header("Location: cabinet-list.php?success=deleted");
            exit();
        }
        
        header("Location: cabinet-edit.php?id=$cabinet_id&error=" . urlencode($result['message']));
        exit();
    }
    
    $data = [
        'nom_cabinet' => $_POST['nom_cabinet'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'wilaya' => $_POST['wilaya'] ?? '',
        'commune' => $_POST['commune'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'plus_code' => $_POST['plus_code'] ?? null,
        'specialty' => $_POST['specialty'] ?? null,
        'description' => $_POST['description'] ?? null,
        'statut' => $_POST['statut'] ?? 'active'
    ];
    
    $result = update_cabinet($conn, $cabinet_id, $data);
    
    if ($result['success']) {
        header("Location: cabinet-details.php?id=$cabinet_id&success=updated");
        exit();
    }
    
    header("Location: cabinet-edit.php?id=$cabinet_id&error=" . urlencode($result['message']));
    exit();
}

$cabinet = get_cabinet_by_id($conn, $cabinet_id);
if (!$cabinet) {
    header("Location: cabinet-list.php?error=not_found");
    exit();
}

// Get cabinet stats for subscription display
$stats = get_cabinet_stats($conn, $cabinet_id);
$subscription = isset($stats['current_subscription']) ? $stats['current_subscription'] : null;

$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier Cabinet – MediCloud Admin</title>
    <meta name="description" content="Modifier les informations d'un cabinet médical" />
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
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="alert warning mb-3">❌ Erreur: ' . htmlspecialchars($_GET['error']) . '</div>';
        } elseif (isset($_GET['success'])) {
            echo '<div class="alert ok mb-3">✅ Cabinet mis à jour avec succès</div>';
        }
        ?>
        <div class="page-header-horizontal">
            <div>
                <a href="cabinet-list.php" class="breadcrumb">← Retour aux cabinets</a>
                <h1 class="page-title">Modifier le cabinet</h1>
                <p class="page-subtitle">Mettre à jour les informations du cabinet</p>
            </div>
            <div class="flex gap-1">
                <span class="badge badge-<?= htmlspecialchars($cabinet['statut']) ?>"><?= ucfirst(htmlspecialchars($cabinet['statut'])) ?></span>
                <?php if ($subscription): ?>
                <span class="badge badge-<?= htmlspecialchars($subscription['type_abonnement']) ?>"><?= ucfirst(htmlspecialchars($subscription['type_abonnement'])) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form action="cabinet-edit.php?id=<?= $cabinet_id ?>" method="POST" class="edit-form">
            <input type="hidden" name="id" value="<?= $cabinet_id ?>">
            <div class="grid grid-3 gap-2">
                
                <div style="grid-column: span 2;">
                    
                    <div class="card padded mb-3">
                        <h2 class="card-title mb-3">Informations générales</h2>
                        <div class="grid grid-2 gap-2">
                            <div class="form-group">
                                <label for="prenom" class="form-label required">Prénom du responsable</label>
                                <input type="text" id="prenom" name="prenom" class="form-input" value="<?= htmlspecialchars($cabinet['prenom'] ?? '') ?>" placeholder="Prénom" required />
                            </div>

                            <div class="form-group">
                                <label for="nom" class="form-label required">Nom du responsable</label>
                                <input type="text" id="nom" name="nom" class="form-input" value="<?= htmlspecialchars($cabinet['nom'] ?? '') ?>" placeholder="Nom" required />
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($cabinet['email']) ?>" required />
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label required">Téléphone</label>
                                <input type="tel" id="phone" name="telephone" class="form-input" value="<?= htmlspecialchars($cabinet['telephone']) ?>" required />
                            </div>

                            <div class="form-group">
                                <label for="wilaya" class="form-label required">Wilaya</label>
                                <input type="text" id="wilaya" name="wilaya" class="form-input" value="<?= htmlspecialchars($cabinet['wilaya']) ?>" required />
                            </div>

                            <div class="form-group">
                                <label for="commune" class="form-label required">Commune</label>
                                <input type="text" id="commune" name="commune" class="form-input" value="<?= htmlspecialchars($cabinet['commune']) ?>" required />
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label for="address" class="form-label required">Adresse</label>
                                <textarea id="address" name="adresse" class="form-textarea" rows="3" required><?= htmlspecialchars($cabinet['adresse']) ?></textarea>
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label" for="plus_code">Code Plus (Google Maps)</label>
                                <input class="form-input" id="plus_code" type="text" name="plus_code" placeholder="Ex: P3PP+VQJ Kouba" value="<?= htmlspecialchars($cabinet['plus_code']) ?>" />
                                <p class="subtitle mt-1">Obtenez le code sur Google Maps: clic droit → copier le code Plus</p>
                            </div>
                        </div>
                    </div>

                    
                    <div class="card padded mb-3">
                        <h2 class="card-title mb-3">Abonnement</h2>
                        <div class="grid grid-2 gap-2">
                            <div class="form-group">
                                <label for="subscription-plan" class="form-label">Type d'abonnement</label>
                                <select id="subscription-plan" name="type_abonnement" class="form-select">
                                    <option value="standard" <?= ($subscription['type_abonnement'] ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
                                    <option value="premium" <?= ($subscription['type_abonnement'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="start-date" class="form-label">Date de début</label>
                                <input type="date" id="start-date" name="date_debut" class="form-input" value="<?= htmlspecialchars($subscription['date_debut'] ?? '') ?>" />
                            </div>

                            <div class="form-group">
                                <label for="statut" class="form-label">Statut</label>
                                <select id="statut" name="statut" class="form-select">
                                    <option value="pending" <?= $cabinet['statut'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                    <option value="active" <?= $cabinet['statut'] === 'active' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactive" <?= $cabinet['statut'] === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div>
                    
                    <div class="card padded mb-3">
                        <h2 class="card-title mb-3">Statut du cabinet</h2>
                        <div class="flex flex-column gap-2">
                            <span class="badge badge-actif">✅ Actif</span>
                            <span class="badge badge-premium">⭐ Premium</span>
                        </div>
                    </div>

                    
                    <div class="card padded">
                        <h2 class="card-title mb-3">Actions</h2>
                        <div class="flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                💾 Enregistrer les modifications
                            </button>
                            <a href="cabinet-details.php?id=<?= $cabinet_id ?>" class="btn btn-ghost btn-block">
                                ← Annuler
                            </a>
                            <form style="display:inline;" action="cabinet-edit.php?id=<?= $cabinet_id ?>&action=delete" method="POST" onsubmit="return confirm('Êtes-vous sûr ? Cette action est irréversible.');">
                                <input type="hidden" name="id" value="<?= $cabinet_id ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-block">
                                    🗑️ Supprimer le cabinet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

</body>
</html>
