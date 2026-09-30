# MediCloud

> Plateforme multi-cabinets de gestion de pratique médicale, avec recherche de cabinets classés par les avis des patients.

![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-003545?logo=mariadb&logoColor=white)
![HTML/CSS/JS](https://img.shields.io/badge/HTML%20%7C%20CSS%20%7C%20JS-E34F26?logo=html5&logoColor=white)
![Licence](https://img.shields.io/badge/licence-MIT-green)

## Contexte

Projet universitaire réalisé en équipe de quatre pour le module **PAW (Programmation Avancée Web)**, en **Licence 3 ISIL** à l'**ESST** (École Supérieure des Sciences et de la Technologie), année 2024/2025.

Le sujet : concevoir une architecture **multi-tenant** où une plateforme centrale (**MediCloud**) gère plusieurs cabinets médicaux, chacun exécutant sa propre instance de gestion (**HippoCare**) avec une base de données isolée. Les avis des patients remontent automatiquement vers la plateforme, qui les exploite pour recommander des cabinets.

## Fonctionnalités

**MediCloud (niveau plateforme)**
- Gestion des cabinets, avec une base de données isolée par cabinet
- Abonnements, facturation et tableau de bord analytique global
- Recherche de cabinets classés selon les avis patients, avec deux méthodes d'aide à la décision multicritère, **WSM** et **TOPSIS**, et des pondérations de critères définies par l'utilisateur

**HippoCare (niveau cabinet)**
- Cinq rôles avec authentification dédiée : administrateur de plateforme, médecin chef, médecin, assistant, patient
- Prise, report et annulation de rendez-vous sur calendrier, avec vérification des disponibilités
- Consultations, dossiers médicaux et génération de documents
- Évaluation des consultations par le patient sur 10 critères, synchronisée automatiquement vers MediCloud

**Sécurité** : hachage bcrypt des mots de passe, requêtes préparées, gestion de sessions et protection des pages par rôle.

## Architecture

```
┌────────────────────────── MediCloud ──────────────────────────┐
│  Admin, abonnements, factures, recherche WSM / TOPSIS          │
│  Base : medicloud                                              │
└───────────────▲────────────────────────────────────────────────┘
                │ synchronisation des avis patients
┌───────────────┴───────────┐   ┌───────────────────────────────┐
│ HippoCare, cabinet 1      │   │ HippoCare, cabinet N          │
│ Base : hippocare          │   │ Base dédiée                   │
└───────────────────────────┘   └───────────────────────────────┘
```

## Stack technique

| Couche | Technologies |
|---|---|
| Backend | PHP (mysqli, requêtes préparées) |
| Base de données | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript, Font Awesome |
| Algorithmes | WSM et TOPSIS, implémentés en PHP |

## Structure du projet

```
MediCloud/       Plateforme : administration, page publique, recherche de cabinets
cab-template/    Modèle de cabinet HippoCare (rôles : chief_doctor, doctor, assistant, patient)
start.php        Point d'entrée
```

## Installation

**Prérequis** : PHP 8+ avec `mysqli`, et un serveur MySQL ou MariaDB (XAMPP, WAMP, ou installation séparée).

1. Cloner le dépôt dans le dossier web sous le nom `medicloud` (les liens internes en dépendent) :
   ```bash
   git clone https://github.com/SamyGoumiri/MediCloud.git medicloud
   ```
2. Créer et importer les deux bases :
   ```bash
   mysql -u root -e "CREATE DATABASE medicloud CHARACTER SET utf8mb4"
   mysql -u root medicloud < medicloud/MediCloud/assets/backend/medicloud.sql
   mysql -u root -e "CREATE DATABASE hippocare CHARACTER SET utf8mb4"
   mysql -u root hippocare < medicloud/cab-template/DB/Hippocare.sql
   ```
3. Les paramètres de connexion sont dans `MediCloud/admin/DB/connect.php` et `cab-template/DB/connect.php` (par défaut : `localhost`, `root`, sans mot de passe, pour un usage local uniquement).
4. Servir le dossier parent de `medicloud` avec Apache, ou en local :
   ```bash
   php -S localhost:8000
   ```
   puis ouvrir http://localhost:8000/medicloud/start.php

## Comptes de démonstration

Les données sont fictives.

| Rôle | Identifiant | Mot de passe | Connexion |
|---|---|---|---|
| Administrateur plateforme | `admin@medicloud.com` | `admin123` | `MediCloud/admin/login.php` |
| Médecin chef | `emma.michel@example.com` | `12345678` | `cab-template/actors/chief_doctor/chief_doctor_auth_login.php` |
| Patient | `xavier.garcia@example.com` | `12345678` | `cab-template/actors/patient/patient_auth_login.php` |

D'autres comptes (médecins, assistants, patients) sont présents dans `cab-template/DB/Hippocare.sql`, avec le mot de passe `12345678`.

## Limites connues

- Projet pédagogique : pas de tests automatisés, pas de protection CSRF, connexion MySQL en `root` sans mot de passe.
- La synchronisation des avis entre cabinets et plateforme suppose que les bases tournent sur le même serveur MySQL.
- Les chiffres de la page d'accueil (nombre de cabinets clients, etc.) sont des valeurs de démonstration.

## Équipe

Projet réalisé en équipe de quatre étudiants.

## Licence

Distribué sous licence [MIT](LICENSE).
