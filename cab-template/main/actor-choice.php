<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choix d'Authentification - HIPPOCARE</title>
    <link rel="stylesheet" href="assets/main.css">
</head>
<body class="actor-choice-page">

    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7V17C2 20.866 6.477 24 12 24C17.523 24 22 20.866 22 17V7L12 2Z" fill="#00A896"/>
                            <circle cx="12" cy="12" r="3" fill="white"/>
                            <path d="M12 9V15M9 12H15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="logo-text">HIPPOCARE</span>
                </div>
                <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </div>
    </nav>

    <section class="auth-section">
        <div class="container">
            <div class="auth-header">
                <h1 class="auth-main-title">Bienvenue sur HIPPOCARE</h1>
                <p class="auth-subtitle">Choisissez votre type de compte pour vous connecter</p>
            </div>

            <div class="actor-choice-grid">

                <div class="actor-card">
                    <div class="actor-icon patient-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 21C6 17.134 8.686 14 12 14C15.314 14 18 17.134 18 21" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h2 class="actor-title">Espace Patient</h2>
                    <p class="actor-description">
                        Accedez a votre espace personnel pour prendre rendez-vous, consulter votre historique medical et gerer vos informations.
                    </p>
                    <div class="actor-features">
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Prise de rendez-vous en ligne</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Historique des consultations</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Resultats d'examens</span>
                        </div>
                    </div>
                    <a href="../actors/patient/patient_auth_login.php" class="btn btn-primary btn-large btn-full">
                        Se connecter - Patient
                    </a>
                    <div class="actor-links">
                        <a href="../actors/patient/patient_auth_register.php" class="link-primary">Creer un compte</a>
                    </div>
                </div>

                <div class="actor-card staff-card">
                    <div class="actor-icon staff-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7V17C2 20.866 6.477 24 12 24C17.523 24 22 20.866 22 17V7L12 2Z" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 9V15M9 12H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h2 class="actor-title">Espace Personnel</h2>
                    <p class="actor-description">
                        Acces reserve au personnel medical : medecins, assistants et medecins-chefs. Gerez les consultations et les dossiers patients.
                    </p>
                    <div class="actor-features">
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Gestion des rendez-vous</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Dossiers medicaux patients</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Outils de gestion</span>
                        </div>
                    </div>
                    <a href="staff-choice.php" class="btn btn-primary btn-large btn-full">
                        Se connecter - Personnel Medical
                    </a>
                    <div class="actor-links">
                        <span class="text-muted">Acces restreint au personnel autorise</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer footer-simple">
        <div class="container">
            <div class="footer-simple-content">
                <p>&copy; 2025 HIPPOCARE. Tous droits reserves.</p>
            </div>
        </div>
    </footer>
</body>
</html>
