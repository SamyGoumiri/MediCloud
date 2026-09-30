<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIPPOCARE - Cabinet Medical a Hydra, Alger</title>
    <link rel="stylesheet" href="assets/main.css">
</head>
<body>

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
                <ul class="nav-menu">
                    <li><a href="#accueil" class="nav-link active">Accueil</a></li>
                    <li><a href="#services" class="nav-link">Services</a></li>
                    <li><a href="#equipe" class="nav-link">Equipe</a></li>
                    <li><a href="#contact" class="nav-link">Contact</a></li>
                    <li><a href="../../MediCloud/find-cabinet.php" class="nav-link">Trouver un cabinet</a></li>
                </ul>
                <a href="actor-choice.php" class="btn btn-primary">Connexion</a>
            </div>
        </div>
    </nav>

    <section class="hero" id="accueil">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Votre Sante, Notre Priorite</h1>
                    <p class="hero-subtitle">
                        Cabinet medical moderne a Hydra, Alger. equipe medicale qualifiee et equipements de pointe pour votre bien-etre.
                    </p>
                    <div class="hero-buttons">
                        <a href="../../MediCloud/find-cabinet.php" class="btn btn-primary btn-large">Trouver un cabinet</a>
                        <a href="#services" class="btn btn-outline btn-large">Nos Services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="services" id="services">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Nos Services</h2>
                <p class="section-subtitle">
                    Des soins medicaux complets et professionnels adaptes a vos besoins
                </p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2C6.477 2 2 6.477 2 12C2 17.523 6.477 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 16V12M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Consultation Generale</h3>
                    <p>Examens medicaux et suivi medical regulier</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M19 14C20.49 12.54 22 10.79 22 8.5C22 7.04 21.36 5.86 20.27 5C19.2 4.15 17.79 3.75 16.5 4.07C15.31 4.37 14.18 5.24 13 6.5C11.82 5.24 10.69 4.37 9.5 4.07C8.21 3.75 6.8 4.15 5.73 5C4.64 5.86 4 7.04 4 8.5C4 10.79 5.51 12.54 7 14L13 20L19 14Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Cardiologie</h3>
                    <p>Surveillance cardiaque et prevention cardiovasculaire</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M9 4V2M15 4V2M3 10H21" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Analyses Medicales</h3>
                    <p>Laboratoire equipe pour tous types d'analyses biologiques</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Radiologie</h3>
                    <p>Imagerie medicale : radiographies et echographies</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Medecine Preventive</h3>
                    <p>Vaccination et depistage</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M20 21V5C20 3.9 19.1 3 18 3H6C4.9 3 4 3.9 4 5V21L12 18L20 21Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Urgences</h3>
                    <p>Service d'urgence 24h/24</p>
                </div>
            </div>
        </div>
    </section>

    <section class="team" id="equipe">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Notre Equipe Medicale</h2>
                <p class="section-subtitle">
                    Des professionnels diplomes et experimentes a votre service
                </p>
            </div>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-placeholder">
                            <svg viewBox="0 0 200 200" fill="none">
                                <circle cx="100" cy="100" r="100" fill="#E8F5F3"/>
                                <circle cx="100" cy="80" r="30" fill="#00A896"/>
                                <path d="M50 150C50 125 72 105 100 105C128 105 150 125 150 150" fill="#00A896"/>
                            </svg>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Dr. Olivia Petit</h3>
                        <p class="team-role">Medecin Generaliste</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-placeholder">
                            <svg viewBox="0 0 200 200" fill="none">
                                <circle cx="100" cy="100" r="100" fill="#E8F5F3"/>
                                <circle cx="100" cy="80" r="30" fill="#00A896"/>
                                <path d="M50 150C50 125 72 105 100 105C128 105 150 125 150 150" fill="#00A896"/>
                            </svg>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Dr. Paul Renard</h3>
                        <p class="team-role">Cardiologue</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-placeholder">
                            <svg viewBox="0 0 200 200" fill="none">
                                <circle cx="100" cy="100" r="100" fill="#E8F5F3"/>
                                <circle cx="100" cy="80" r="30" fill="#00A896"/>
                                <path d="M50 150C50 125 72 105 100 105C128 105 150 125 150 150" fill="#00A896"/>
                            </svg>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Dr. Rose Lemaire</h3>
                        <p class="team-role">Pediatre</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact" id="contact">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Contactez-nous</h2>
                <p class="section-subtitle">Notre equipe est disponible pour repondre a toutes vos questions</p>
            </div>
            <div class="contact-items-centered">
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 5.02944 7.02944 1 12 1C16.9706 1 21 5.02944 21 10Z" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Adresse</h4>
                        <p>Rue Docteur Saadane, Hydra<br>Alger 16035, Algerie</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M3 5C3 3.89543 3.89543 3 5 3H8.27924C8.70967 3 9.09181 3.27543 9.22792 3.68377L10.7257 8.17721C10.8831 8.64932 10.6694 9.16531 10.2243 9.38787L7.96701 10.5165C9.06925 12.9612 11.0388 14.9308 13.4835 16.033L14.6121 13.7757C14.8347 13.3306 15.3507 13.1169 15.8228 13.2743L20.3162 14.7721C20.7246 14.9082 21 15.2903 21 15.7208V19C21 20.1046 20.1046 21 19 21H18C9.71573 21 3 14.2843 3 6V5Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Telephone</h4>
                        <p>+213 (0)23 48 XX XX<br>+213 (0)555 XX XX XX</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M2 7L12 13L22 7" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Email</h4>
                        <p>contact@hippocare.dz<br>info@hippocare.dz</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Horaires</h4>
                        <p>Dim - Jeu: 08:00 - 18:00<br>Sam: Urgences uniquement<br>Ven: Ferme</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <div class="logo-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7V17C2 20.866 6.477 24 12 24C17.523 24 22 20.866 22 17V7L12 2Z" fill="#00A896"/>
                                <circle cx="12" cy="12" r="3" fill="white"/>
                                <path d="M12 9V15M9 12H15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <span class="logo-text">HIPPOCARE</span>
                    </div>
                    <p class="footer-description">
                        Votre partenaire sante de confiance a Hydra, Alger. Excellence medicale et soins personnalises depuis 2010.
                    </p>
                </div>
                <div class="footer-section">
                    <h3>Liens Rapides</h3>
                    <ul class="footer-links">
                        <li><a href="#accueil">Accueil</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#equipe">Equipe</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HIPPOCARE. Tous droits reserves. | Cabinet Medical a Hydra, Alger, Algerie</p>
            </div>
        </div>
    </footer>

    <button class="scroll-to-top" id="scrollToTop">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 19V5M12 5L5 12M12 5L19 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>

    <script>
        // Mettre a jour les liens de navigation actifs en fonction du scroll
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = document.querySelectorAll('section[id]');

        window.addEventListener('scroll', () => {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.scrollY >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Scroll to top button
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                scrollToTopBtn.style.display = 'flex';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>