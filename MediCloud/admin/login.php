<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'DB/connect.php';
    require_once 'DB/auth_helper.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=missing_fields");
        exit();
    }
    
    $result = admin_login($conn, $email, $password);
    
    if ($result['success']) {
        header("Location: dashboard.php");
        exit();
    }
    
    header("Location: login.php?error=" . urlencode($result['message']));
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Connexion - MediCloud Admin</title>
    <meta name="description" content="Connectez-vous au tableau de bord administrateur de MediCloud" />
    <link rel="icon" href="../assets/frontend/medicloud.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="../assets/frontend/common.css" />
    <link rel="stylesheet" href="assets/frontend/admin.css" />
    <meta name="theme-color" content="#2F81F7" />
    <style>
        body {
            padding-top: 0 !important;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            padding: 3rem 1.5rem;
        }

        .logo-wrapper {
            margin-bottom: 3.5rem;
            text-align: center;
            animation: fadeInDown 0.6s ease;
        }

        .logo-wrapper img {
            width: 120px;
            height: 120px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 8px 24px rgba(47, 129, 247, 0.25));
            transition: transform var(--transition);
        }

        .logo-wrapper img:hover {
            transform: scale(1.05);
        }

        .logo-wrapper h2 {
            font-size: 1.75rem;
            color: var(--text);
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .logo-wrapper p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0.5rem 0 0 0;
        }

        .card {
            max-width: 520px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
        }

        .form-section {
            margin-bottom: 2.5rem;
            text-align: center;
        }

        .form-section h1 {
            font-size: 1.35rem;
            margin: 0 0 0.75rem 0;
            color: var(--text);
            font-weight: 700;
        }

        .form-section p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-link-wrapper {
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="login-container">
        <div class="logo-wrapper">
            <img src="../assets/frontend/medicloud.svg" alt="MediCloud" />
            <h2>MediCloud Admin</h2>
            <p>Portail d'administration</p>
        </div>

        <div class="card padded">
            <div class="form-section">
                <h1>Se connecter</h1>
                <p>Accédez à votre tableau de bord MediCloud</p>
            </div>

            <form id="login-form" method="POST" action="login.php" novalidate autocomplete="off">
                <input type="hidden" id="account_type" name="account_type" value="0" />

                <div aria-live="polite" aria-atomic="true" id="login-messages">
                    <?php
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $messages = [
                            'invalid_credentials' => '❌ Email ou mot de passe incorrect',
                            'user_not_found' => '❌ Cet utilisateur n\'existe pas'
                        ];
                        echo '<div class="alert warning mb-2">';
                        echo $messages[$error] ?? 'Erreur de connexion';
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label required">Email</label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        class="form-input"
                        required 
                        placeholder="votre.email@exemple.com"
                        data-validate="required,email"
                    />
                </div>

                <div class="form-group">
                    <label for="password" class="form-label required">Mot de passe</label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        class="form-input"
                        required 
                        placeholder="Minimum 8 caractères"
                        data-validate="required,minlen:8"
                        minlength="8"
                    />
                </div>

                <button type="submit" id="login-submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                    🔐 Se connecter
                </button>
            </form>

            <div class="back-link-wrapper">
