<?php
require_once 'DB/connect.php';
require_once 'DB/secure_page.php';
require_once 'DB/auth_helper.php';

$admin = secure_admin_page($conn);
$admin_id = get_admin_id();
$admin_prenom = $admin['prenom'] ?? '';
$admin_nom = $admin['nom'] ?? '';
$admin_email = $admin['email'] ?? '';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $prenom = $_POST['prenom'] ?? '';
            $nom = $_POST['nom'] ?? '';
            $email = $_POST['email'] ?? '';
            
            $result = update_admin_profile($conn, $admin_id, $prenom, $nom, $email);
            
            if ($result['success']) {
                $success_message = $result['message'];
                $admin = secure_admin_page($conn);
            } else {
                $error_message = $result['message'];
            }
        } elseif ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($new_password !== $confirm_password) {
                $error_message = "Les mots de passe ne correspondent pas";
            } else {
                $result = update_admin_password($conn, $admin_id, $current_password, $new_password);
                
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            }
        }
    }
}

$admin_name = get_admin_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil – MediCloud Admin</title>
    <link rel="icon" href="../assets/frontend/medicloud.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="../assets/frontend/common.css">
    <link rel="stylesheet" href="assets/frontend/admin.css">

    <meta name="theme-color" content="#2F81F7">
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
        <a class="breadcrumb" href="dashboard.php">← Tableau de bord</a>
        <div class="page-header-horizontal">
            <div>
                <h1 class="page-title">Mon profil</h1>
                <p class="page-subtitle">Gérez vos informations personnelles</p>
            </div>
        </div>

        <?php
        if ($success_message) {
            echo '<div class="alert ok mb-3">✅ ' . htmlspecialchars($success_message) . '</div>';
        }
        if ($error_message) {
            echo '<div class="alert warning mb-3">❌ ' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <div class="grid grid-2">
            
            <section class="card padded">
                <div class="card-header">
                    <h2 class="card-title">Informations du compte</h2>
                </div>
                
                <form class="form" action="profile.php" method="POST">
                    <input type="hidden" name="action" value="update_profile" />
                    <div class="row-2">
                        <div class="field">
                            <label class="required">Prénom</label>
                            <input type="text" name="prenom" value="<?php echo htmlspecialchars($admin_prenom); ?>" required data-validate="required" />
                        </div>
                        <div class="field">
                            <label class="required">Nom</label>
                            <input type="text" name="nom" value="<?php echo htmlspecialchars($admin_nom); ?>" required data-validate="required" />
                        </div>
                    </div>
                    <div class="field">
                        <label class="required">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin_email); ?>" required data-validate="required,email" />
                    </div>
                    <div class="flex between center mt-2">
                        <span></span>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </section>

            
            <section class="card padded">
                <div class="card-header">
                    <h2 class="card-title">Sécurité</h2>
                </div>
                
                <form class="form" action="profile.php" method="POST">
                    <input type="hidden" name="action" value="change_password" />
                    <div class="field">
                        <label class="required">Mot de passe actuel</label>
                        <input type="password" name="current_password" required data-validate="required" />
                    </div>
                    <div class="row-2">
                        <div class="field">
                            <label class="required">Nouveau mot de passe</label>
                            <input type="password" name="new_password" minlength="6" required data-validate="required" />
                        </div>
                        <div class="field">
                            <label class="required">Confirmer</label>
                            <input type="password" name="confirm_password" minlength="6" required data-validate="required" />
                        </div>
                    </div>
                    <div class="flex between center mt-2">
                        <span></span>
                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>




