<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCloud - Trouver un cabinet</title>
    <meta name="description" content="Repondez a quelques questions pour trouver le cabinet medical le plus adapte a vos besoins via MediCloud.">
    <link rel="icon" href="./assets/frontend/medicloud.svg" type="image/svg+xml">
    <link rel="stylesheet" href="./assets/frontend/common.css">
    <link rel="stylesheet" href="./assets/frontend/index.css">
    <link rel="stylesheet" href="./assets/frontend/search.css">
    <meta name="theme-color" content="#2F81F7">
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <a class="brand" href="index.php">
                <img class="brand-logo" src="./assets/frontend/medicloud.svg" alt="">
                <span class="brand-name">MediCloud</span>
            </a>
            <nav class="nav">
                <a href="index.php" class="nav-link">Accueil</a>
                <a href="index.php#services" class="nav-link">Fonctionnalites</a>
                <a href="index.php#pricing" class="nav-link">Plans</a>
                <a href="find-cabinet.php" class="btn btn-primary" aria-current="page">Trouver un cabinet</a>
            </nav>
        </div>
    </header>

    <main class="container search-layout">
        <section class="search-hero">
            <div class="hero-badge">Assistant de recommandation</div>
            <h1>Trouvez votre cabinet medical ideal</h1>
            <p class="subtitle">Repondez a quelques questions claires et nous vous proposons les cabinets les plus adaptes, sans perte de temps ni mauvaises surprises.</p>
            <ul class="hero-meta">
                <li class="meta-pill">⏱ Parcours rapide (2 min)</li>
                <li class="meta-pill">🔒 Donnees securisees</li>
                <li class="meta-pill">🎯 Recommandations ciblees</li>
            </ul>
        </section>

        <div class="panel-grid">
            <form id="preferencesForm" class="preferences-form">
                <div class="form-section">
                    <div class="section-header">
                        <p class="section-kicker">Etape 1</p>
                        <h2>Informations de base</h2>
                    </div>

                    <div class="form-grid-two">
                        <div class="form-group">
                            <label for="specialty">Quel type de specialiste recherchez-vous ?</label>
                            <select id="specialty" name="specialty" required>
                                <option value="">-- Selectionnez une specialite --</option>
                                <option value="generaliste">Medecin Generaliste</option>
                                <option value="cardiologue">Cardiologue</option>
                                <option value="pediatre">Pediatre</option>
                                <option value="dermatologue">Dermatologue</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="wilaya">Dans quelle wilaya ?</label>
                            <select id="wilaya" name="wilaya" required>
                                <option value="">-- Selectionnez une wilaya --</option>
                                <option value="Alger">Alger</option>
                                <option value="Tipaza">Tipaza</option>
                                <option value="Blida">Blida</option>
                                <option value="Tizi Ouzou">Tizi Ouzou</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-header">
                        <p class="section-kicker">Etape 2</p>
                        <h2>Choisissez vos criteres prioritaires</h2>
                        <p class="section-subtitle">Selectionnez au moins 2 criteres importants. Vos preferences sont ensuite pesees pour calculer la meilleure correspondance.</p>
                        <div class="criteria-counter" id="criteria-counter">
                            <span class="counter-text">Selectionnes (min 2) :</span>
                            <span class="counter-value">0</span>
                        </div>
                    </div>

                    <div class="criteria-warning" id="criteria-warning" style="display: none;">
                        <span aria-hidden="true">⚠️</span>
                        <span>Selectionnez au moins 2 criteres.</span>
                    </div>

                    <div class="criteria-cards" id="criteria-group">
                        <div class="criteria-card" data-value="localisation">
                            <input type="checkbox" name="criteria" value="localisation" id="crit-localisation">
                            <label for="crit-localisation">
                                <div class="card-icon" aria-hidden="true">📍</div>
                                <div class="card-content">
                                    <h3>Localisation</h3>
                                    <p>Proximite geographique et facilite d'acces</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="disponibilite">
                            <input type="checkbox" name="criteria" value="disponibilite" id="crit-disponibilite">
                            <label for="crit-disponibilite">
                                <div class="card-icon" aria-hidden="true">⏰</div>
                                <div class="card-content">
                                    <h3>Disponibilite</h3>
                                    <p>Delais de rendez-vous et amplitudes horaires</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="accueil">
                            <input type="checkbox" name="criteria" value="accueil" id="crit-accueil">
                            <label for="crit-accueil">
                                <div class="card-icon" aria-hidden="true">🤝</div>
                                <div class="card-content">
                                    <h3>Accueil</h3>
                                    <p>Qualite de l'accueil et convivialite du personnel</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="competence">
                            <input type="checkbox" name="criteria" value="competence" id="crit-competence">
                            <label for="crit-competence">
                                <div class="card-icon" aria-hidden="true">🩺</div>
                                <div class="card-content">
                                    <h3>Competence</h3>
                                    <p>Expertise medicale et specialisations</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="experience">
                            <input type="checkbox" name="criteria" value="experience" id="crit-experience">
                            <label for="crit-experience">
                                <div class="card-icon" aria-hidden="true">🏆</div>
                                <div class="card-content">
                                    <h3>Experience</h3>
                                    <p>Annees de pratique et reputation du praticien</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="equipement">
                            <input type="checkbox" name="criteria" value="equipement" id="crit-equipement">
                            <label for="crit-equipement">
                                <div class="card-icon" aria-hidden="true">🧪</div>
                                <div class="card-content">
                                    <h3>Equipement / materiel</h3>
                                    <p>Qualite du plateau technique et examens</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="hygiene">
                            <input type="checkbox" name="criteria" value="hygiene" id="crit-hygiene">
                            <label for="crit-hygiene">
                                <div class="card-icon" aria-hidden="true">🧼</div>
                                <div class="card-content">
                                    <h3>Hygiene</h3>
                                    <p>Proprete des locaux et respect des normes</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="securite">
                            <input type="checkbox" name="criteria" value="securite" id="crit-securite">
                            <label for="crit-securite">
                                <div class="card-icon" aria-hidden="true">🛡️</div>
                                <div class="card-content">
                                    <h3>Securite</h3>
                                    <p>Procedures de securite et gestion des risques</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="frais">
                            <input type="checkbox" name="criteria" value="frais" id="crit-frais">
                            <label for="crit-frais">
                                <div class="card-icon" aria-hidden="true">💰</div>
                                <div class="card-content">
                                    <h3>Frais</h3>
                                    <p>Tarifs et accessibilite financiere</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>

                        <div class="criteria-card" data-value="ponctualite">
                            <input type="checkbox" name="criteria" value="ponctualite" id="crit-ponctualite">
                            <label for="crit-ponctualite">
                                <div class="card-icon" aria-hidden="true">⌛</div>
                                <div class="card-content">
                                    <h3>Ponctualite</h3>
                                    <p>Respect des horaires de rendez-vous</p>
                                </div>
                                <div class="card-check" aria-hidden="true">✔</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-header">
                        <p class="section-kicker">Etape 3</p>
                        <h2>Poids des criteres</h2>
                        <p class="section-subtitle">Definissez l'importance relative de chaque critere selectionne (1 = peu important, 10 = tres important). Des poids clairs evitent les classements incoherents.</p>
                    </div>
                    <div id="weights-container" class="weights-container"></div>
                </div>

                <div class="form-section">
                    <div class="section-header">
                        <p class="section-kicker">Etape 4</p>
                        <h2>Methode de recherche</h2>
                        <p class="section-subtitle">Choisissez la methode de calcul du score qui servira a classer les cabinets.</p>
                    </div>

                    <div class="form-group">
                        <div class="radio-group">
                            <label><input type="radio" name="method" value="wsm" checked> WSM (Weighted Sum Model)</label>
                            <label><input type="radio" name="method" value="topsis"> TOPSIS</label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">🔎 Trouver mon cabinet ideal</button>
                </div>
            </form>
        </div>

        <div id="results" class="results-section hidden">
            <h2>Recommandations pour vous</h2>
            <div id="selection-summary" class="selection-summary"></div>
            <div id="recommendedCabinets" class="cabinets-grid"></div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="brand">
                    <img src="../assets/icons/medicloud.svg" alt="" class="footer-logo" />
                    <span class="brand-name">MediCloud</span>
                </div>
                <p>Plateforme cloud tout-en-un pour la gestion moderne de votre cabinet medical en Algerie</p>
            </div>
            <div class="footer-nav">
                <nav class="footer-links-group">
                    <a href="index.php#services">Fonctionnalites</a>
                    <a href="index.php#pricing">Plans & Tarifs</a>
                </nav>
                <nav class="footer-links-group">
                    <a href="search-api.php" aria-current="page">Trouver un cabinet</a>
                </nav>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 MediCloud. Tous droits reserves. Made with ❤️ in Algeria</p>
        </div>
    </footer>

    <script src="./assets/frontend/search.js"></script>
</body>
</html>
