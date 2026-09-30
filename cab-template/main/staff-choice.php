<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choix Personnel - HIPPOCARE</title>
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
                <a href="actor-choice.php" class="btn btn-outline">← Retour</a>
            </div>
        </div>
    </nav>

    <section class="auth-section">
        <div class="container">
            <div class="auth-header">
                <h1 class="auth-main-title">Espace Personnel</h1>
                <p class="auth-subtitle">Selectionnez votre role pour acceder a votre espace de travail</p>
            </div>

            <div class="actor-choice-grid three-columns">

                <div class="actor-card">
                    <div class="actor-icon assistant-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 21C6 17.134 8.686 14 12 14C15.314 14 18 17.134 18 21" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h2 class="actor-title">Assistant(e) Medical</h2>
                    <p class="actor-description">
                        Gerez l'accueil des patients, la planification des rendez-vous et l'ensemble des taches administratives du cabinet medical.
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
                            <span>Accueil des patients</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Taches administratives</span>
                        </div>
                    </div>
                    <a href="../actors/assistant/assistant_auth_login.php" class="btn btn-primary btn-large btn-full">
                        Se connecter - Assistant
                    </a>
                    <div class="actor-links">
                        <span class="text-muted">Acces reserve au personnel</span>
                    </div>
                </div>

                <div class="actor-card staff-card">
                    <div class="actor-icon doctor-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7V17C2 20.866 6.477 24 12 24C17.523 24 22 20.866 22 17V7L12 2Z" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 9V15M9 12H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h2 class="actor-title">Medecin</h2>
                    <p class="actor-description">
                        Accedez a vos consultations, dossiers medicaux, outils de diagnostic et gerez le suivi complet de vos patients.
                    </p>
                    <div class="actor-features">
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Consultations medicales</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Dossiers patients</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Outils de diagnostic</span>
                        </div>
                    </div>
                    <a href="../actors/doctor/doctor_auth_login.php" class="btn btn-primary btn-large btn-full">
                        Se connecter - Medecin
                    </a>
                    <div class="actor-links">
                        <span class="text-muted">Acces reserve au personnel medical</span>
                    </div>
                </div>

                <div class="actor-card">
                    <div class="actor-icon chief-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L15 8H21L16 12L18 18L12 14L6 18L8 12L3 8H9L12 2Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h2 class="actor-title">Medecin Chef</h2>
                    <p class="actor-description">
                        Supervisez l'equipe medicale, gerez les ressources, optimisez les processus et assurez la coordination du cabinet medical.
                    </p>
                    <div class="actor-features">
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Gestion de l'equipe medicale</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Supervision globale</span>
                        </div>
                        <div class="actor-feature">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span>Optimisation des processus</span>
                        </div>
                    </div>
                    <a href="../actors/chief_doctor/chief_doctor_auth_login.php" class="btn btn-primary btn-large btn-full">
                        Se connecter - Medecin Chef
                    </a>
                    <div class="actor-links">
                        <span class="text-muted">Acces reserve aux chefs de service</span>
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
