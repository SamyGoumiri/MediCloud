<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>MediCloud - Gestion de cabinet medical (RDV, Dossiers, Facturation)</title>
	<meta name="description" content="MediCloud digitalise votre cabinet medical : prise de rendez-vous, dossiers patients, facturation et analytics dans une plateforme cloud simple et securisee." />
	<link rel="icon" href="./assets/frontend/medicloud.svg" type="image/svg+xml" />
	<link rel="stylesheet" href="./assets/frontend/common.css" />
	<link rel="stylesheet" href="./assets/frontend/index.css" />
	<meta name="theme-color" content="#2F81F7" />
	<meta name="robots" content="index,follow" />
</head>
<body>
	<header class="header">
		<div class="header-inner">
			<a class="brand" href="index.php">
				<img src="./assets/frontend/medicloud.svg" alt="" class="brand-logo" />
				<span class="brand-name">MediCloud</span>
			</a>
			<nav class="nav">
			<a href="index.php" class="nav-link active" aria-current="page">Accueil</a>
			<a href="#services" class="nav-link">Fonctionnalites</a>
			<a href="#pricing" class="nav-link">Plans</a>
		<a href="find-cabinet.php" class="btn btn-primary">Trouver un cabinet</a>
			</nav>
		</div>
	</header>

	<section class="hero hero-large">
		<div class="hero-content">
			<div class="hero-badge">🏆 Solution SaaS N°1 en Algerie</div>
			<h1 class="hero-title">Digitalisez votre Cabinet Medical avec MediCloud</h1>
			<p class="hero-subtitle">La plateforme cloud tout-en-un pour moderniser la gestion de votre etablissement de sante</p>
			<div class="hero-features">
				<span class="feature-pill">📅 Agenda intelligent</span>
				<span class="feature-pill">📂 Dossiers patients</span>
				<span class="feature-pill">💳 Facturation</span>
			</div>
			<div class="hero-actions">
				<a href="#pricing" class="btn btn-primary btn-large">🚀 Voir nos plans</a>
				<a href="#services" class="btn btn-secondary btn-large">Decouvrir les fonctionnalites</a>
			</div>
			<div class="hero-stats">
				<div class="stat-item">
					<div class="stat-value">150+</div>
					<div class="stat-label">Cabinets clients</div>
				</div>
				<div class="stat-item">
					<div class="stat-value">500+</div>
					<div class="stat-label">Medecins utilisateurs</div>
				</div>
				<div class="stat-item">
					<div class="stat-value">50K+</div>
					<div class="stat-label">Patients geres</div>
				</div>
			</div>
		</div>
	</section>

	<main class="container">
		<section id="services" class="services-section">
			<div class="section-header">
				<span class="section-badge">Fonctionnalites</span>
				<h2 class="section-title">Une solution complete pour votre cabinet</h2>
				<p class="section-subtitle">Tous les outils essentiels reunis dans une seule plateforme intuitive</p>
			</div>

			<div class="cabinets-grid">
				<div class="cabinet-card">
					<div class="cabinet-body">
						<h3 class="cabinet-name">Gestion des Rendez-vous</h3>
						<p class="cabinet-description">Agenda intelligent pour medecins et assistants, prise de RDV en ligne 24/7</p>
						<ul class="service-features">
							<li>Calendrier synchronise multi-medecins</li>
							<li>Reservation en ligne par les patients</li>
							<li>Salle d'attente virtuelle</li>
							<li>Historique des consultations</li>
						</ul>
					</div>
				</div>

				<div class="cabinet-card">
					<div class="cabinet-body">
						<h3 class="cabinet-name">Dossiers Patients electroniques</h3>
						<p class="cabinet-description">Centralisation complete des donnees medicales, securise et conforme RGPD</p>
						<ul class="service-features">
							<li>Historique medical complet</li>
							<li>Ordonnances electroniques</li>
							<li>Documents & imagerie</li>
						</ul>
					</div>
				</div>

				<div class="cabinet-card">
					<div class="cabinet-body">
						<h3 class="cabinet-name">Gestion des Abonnements</h3>
						<p class="cabinet-description">Gestion centralisee des abonnements cabinets avec suivi des paiements</p>
						<ul class="service-features">
							<li>Facturation des abonnements</li>
							<li>Suivi des paiements</li>
							<li>Rapports financiers</li>
						</ul>
					</div>
				</div>

				<div class="cabinet-card">
					<div class="cabinet-body">
						<h3 class="cabinet-name">Gestion du Personnel</h3>
						<p class="cabinet-description">Organisation de l'equipe medicale et administrative avec roles et permissions</p>
						<ul class="service-features">
							<li>Multi-utilisateurs & roles</li>
							<li>Medecins, assistants & personnel</li>
							<li>Gestion des acces</li>
							<li>Profils personnalisables</li>
						</ul>
					</div>
				</div>
			</div>
		</section>

		<section id="pricing" class="pricing-section">
			<div class="section-header">
				<span class="section-badge">Nos Plans</span>
				<h2 class="section-title">Des formules adaptees a votre pratique</h2>
				<p class="section-subtitle">Choisissez le plan qui correspond a la taille de votre cabinet</p>
			</div>

			<div class="pricing-grid">
				<div class="pricing-card">
					<div class="pricing-header">
						<h3 class="pricing-name">Standard</h3>
						<p class="pricing-desc">Pour les cabinets individuels</p>
					</div>
					<ul class="pricing-features">
						<li>1-5 medecins</li>
						<li>Gestion patients & rendez-vous</li>
						<li>Dossiers medicaux</li>
						<li>Documents & ordonnances</li>
						<li>Salle d'attente virtuelle</li>
						<li>Support email</li>
					</ul>
				</div>

				<div class="pricing-card featured">
					<div class="pricing-badge">⭐ Populaire</div>
					<div class="pricing-header">
						<h3 class="pricing-name">Premium</h3>
						<p class="pricing-desc">Pour les cabinets de groupe & cliniques</p>
					</div>
					<ul class="pricing-features">
						<li>Medecins illimites</li>
						<li>Patients illimites</li>
						<li>Toutes les fonctionnalites Standard</li>
						<li>Rapports detailles</li>
						<li>Parametrage avance</li>
						<li>Support prioritaire</li>
					</ul>
				</div>
			</div>
		</section>
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
					<a href="#services">Fonctionnalites</a>
					<a href="#pricing">Plans & Tarifs</a>
				</nav>
				<nav class="footer-links-group">
				<a href="find-cabinet.php" aria-current="page">Trouver un cabinet</a>
				</nav>
			</div>
		</div>
		<div class="footer-bottom">
			<p>© 2025 MediCloud. Tous droits reserves. Made with ❤️ in Algeria</p>
		</div>
	</footer>
</body>
</html>