-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 04 jan. 2026 à 14:31
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `hippocare`
--

-- --------------------------------------------------------

--
-- Structure de la table `appointment`
--

CREATE TABLE `appointment` (
  `id_appointment` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','blocked','completed','canceled','missed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Déchargement des données de la table `appointment`
--

INSERT INTO `appointment` (`id_appointment`, `id_doctor`, `id_patient`, `appointment_date`, `start_time`, `end_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2023-05-15', '10:00:00', '10:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(2, 1, 2, '2023-05-15', '11:00:00', '11:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(3, 1, 3, '2023-05-16', '09:00:00', '09:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(4, 1, 1, '2023-05-16', '10:00:00', '10:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(5, 1, 2, '2023-05-17', '11:00:00', '11:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(6, 1, 1, '2023-05-18', '14:00:00', '14:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(7, 1, 3, '2023-05-22', '13:00:00', '13:30:00', 'completed', '2026-01-03 18:22:38', '2026-01-03 18:22:38'),
(12, 1, 5, '2023-06-05', '09:00:00', '09:30:00', 'canceled', '2026-01-03 18:22:38', '2026-01-03 18:23:05'),
(13, 1, 1, '2026-01-10', '08:00:00', '09:00:00', 'pending', '2026-01-03 22:22:56', '2026-01-03 22:22:56'),
(14, 1, 1, '2026-01-08', '10:00:00', '11:00:00', 'pending', '2026-01-04 12:37:04', '2026-01-04 12:37:04'),
(15, 1, 1, '2026-01-17', '09:00:00', '10:00:00', 'pending', '2026-01-04 12:40:00', '2026-01-04 12:40:00');

-- --------------------------------------------------------

--
-- Structure de la table `assistant`
--

CREATE TABLE `assistant` (
  `id_assistant` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `recruitment_date` date DEFAULT NULL COMMENT 'Date hired by the clinic',
  `id_doctor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `assistant`
--

INSERT INTO `assistant` (`id_assistant`, `last_name`, `first_name`, `email`, `phone`, `password`, `birth_date`, `national_id`, `recruitment_date`, `id_doctor`) VALUES
(1, 'Moreau', 'Alice', 'alice.moreau@example.com', '0550000000', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1990-09-03', '100000000000', '2015-03-15', 1),
(2, 'Girard', 'Bruno', 'bruno.girard@example.com', '0550000001', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1992-10-12', '100000000001', '2016-09-01', 1),
(3, 'Faure', 'Claire', 'claire.faure@example.com', '0550000002', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1989-03-21', '100000000002', '2014-06-10', 1),
(4, 'Henry', 'David', 'david.henry@example.com', '0550000003', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1994-12-07', '100000000003', '2018-01-20', 1);

-- --------------------------------------------------------

--
-- Structure de la table `consultation`
--

CREATE TABLE `consultation` (
  `id_consult` int(11) NOT NULL,
  `id_appointment` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `questionnaire` text DEFAULT NULL,
  `id_doctor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consultation`
--

INSERT INTO `consultation` (`id_consult`, `id_appointment`, `diagnosis`, `treatment`, `fee`, `questionnaire`, `id_doctor`) VALUES
(1, 1, 'Acute bronchitis', 'Prescribed antibiotics and cough syrup', 3500.00, 'Patient reported coughing and chest pain for 5 days', 1),
(2, 2, 'Seasonal allergic rhinitis', 'Prescribed antihistamines', 2500.00, 'Patient reported sneezing and itchy eyes', 1),
(3, 3, 'Hypertension follow-up', 'Adjusted medication dosage', 3000.00, 'Blood pressure monitoring', 1),
(4, 4, 'Type 2 Diabetes', 'Prescribed metformin', 3500.00, 'Patient needs blood sugar management', 1),
(5, 5, 'Migraine', 'Prescribed pain relief medication', 2800.00, 'Patient reported severe headaches', 1),
(6, 6, 'Gastroenteritis', 'Prescribed rehydration therapy', 2500.00, 'Patient reported nausea and diarrhea', 1),
(7, 7, 'Upper respiratory infection', 'Prescribed decongestants and rest', 2500.00, 'Patient reported sore throat and runny nose', 1),
(8, 1, 'Back pain', 'Prescribed muscle relaxants', 3200.00, 'Patient reported lower back pain', 1),
(9, 2, 'Skin rash', 'Prescribed topical cream', 2600.00, 'Allergic reaction to detergent', 1),
(10, 3, 'Asthma check-up', 'Adjusted inhaler dosage', 3100.00, 'Regular respiratory assessment', 1),
(11, 4, 'Ear infection', 'Prescribed antibiotics', 2700.00, 'Patient reported ear pain', 1),
(12, 5, 'Anxiety disorder', 'Prescribed anxiolytics', 3300.00, 'Patient needs mental health support', 1),
(13, 6, 'Sinusitis', 'Prescribed decongestants', 2800.00, 'Patient reported facial pain', 1),
(14, 7, 'Arthritis follow-up', 'Prescribed anti-inflammatory', 3400.00, 'Joint pain management', 1),
(15, 1, 'Vitamin D deficiency', 'Prescribed supplements', 2400.00, 'Blood test results review', 1),
(16, 2, 'Insomnia', 'Sleep hygiene counseling', 2900.00, 'Patient reported sleep difficulties', 1),
(17, 3, 'High cholesterol', 'Prescribed statins', 3200.00, 'Lipid profile review', 1),
(18, 4, 'UTI', 'Prescribed antibiotics', 2700.00, 'Patient reported urinary symptoms', 1),
(19, 5, 'Thyroid disorder', 'Prescribed levothyroxine', 3100.00, 'Thyroid function test review', 1),
(20, 6, 'Hemorrhoids', 'Prescribed topical treatment', 2600.00, 'Patient reported discomfort', 1);

-- --------------------------------------------------------

--
-- Structure de la table `consultation_feedback`
--

CREATE TABLE `consultation_feedback` (
  `id` int(11) NOT NULL,
  `id_consultation` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `accueil` tinyint(4) NOT NULL,
  `ponctualite` tinyint(4) NOT NULL,
  `disponibilite` tinyint(4) NOT NULL,
  `competence` tinyint(4) NOT NULL,
  `experience` tinyint(4) NOT NULL,
  `equipement` tinyint(4) NOT NULL,
  `hygiene` tinyint(4) NOT NULL,
  `securite` tinyint(4) NOT NULL,
  `parking` tinyint(4) NOT NULL,
  `cout` tinyint(4) NOT NULL,
  `comments` text DEFAULT NULL,
  `synced_to_platform` tinyint(1) DEFAULT 0 COMMENT 'Whether this feedback was synced to platform DB',
  `platform_feedback_id` int(11) DEFAULT NULL COMMENT 'ID of corresponding feedback in platform DB',
  `synced_at` datetime DEFAULT NULL COMMENT 'When this feedback was synced to platform',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `doctor`
--

CREATE TABLE `doctor` (
  `id_doctor` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `speciality` varchar(100) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL COMMENT 'Consultation price in DZD',
  `recruitment_date` date DEFAULT NULL COMMENT 'Date hired by the clinic',
  `practice_start_year` int(4) DEFAULT NULL COMMENT 'Year started practicing as doctor',
  `role` enum('doctor','chief_doctor') DEFAULT 'doctor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `doctor`
--

INSERT INTO `doctor` (`id_doctor`, `last_name`, `first_name`, `email`, `phone`, `password`, `birth_date`, `national_id`, `speciality`, `consultation_fee`, `recruitment_date`, `practice_start_year`, `role`) VALUES
(1, 'Michel', 'Emma', 'emma.michel@example.com', '0550000004', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1985-05-15', '100000000004', 'General Practitioner', 3000.00, '2010-01-15', 2010, 'chief_doctor'),
(2, 'Lambert', 'Felix', 'felix.lambert@example.com', '0550000005', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1995-10-12', '100000000005', 'Pediatrician', 2500.00, '2020-09-01', 2020, 'doctor'),
(3, 'Perrin', 'Gabrielle', 'gabrielle.perrin@example.com', '0550000006', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1982-03-20', '100000000006', 'Cardiologist', 4000.00, '2012-06-15', 2008, 'doctor'),
(4, 'Lefevre', 'Hugo', 'hugo.lefevre@example.com', '0550000007', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1988-07-12', '100000000007', 'Dermatologist', 3500.00, '2015-09-01', 2014, 'doctor'),
(5, 'Fontaine', 'Iris', 'iris.fontaine@example.com', '0550000008', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1980-11-05', '100000000008', 'Orthopedist', 4500.00, '2011-03-20', 2007, 'doctor'),
(6, 'Guerin', 'Jules', 'jules.guerin@example.com', '0550000009', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1986-09-18', '100000000009', 'Ophthalmologist', 3800.00, '2016-01-10', 2013, 'doctor'),
(7, 'Collin', 'Karen', 'karen.collin@example.com', '0550000010', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1983-04-25', '100000000010', 'Neurologist', 5000.00, '2013-08-15', 2010, 'doctor'),
(8, 'Simon', 'Louis', 'louis.simon@example.com', '0550000011', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1984-12-08', '100000000011', 'Psychiatrist', 4200.00, '2014-05-20', 2011, 'doctor'),
(9, 'Dupont', 'Marie', 'marie.dupont@example.com', '0550000012', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1981-06-14', '100000000012', 'ENT Specialist', 3600.00, '2012-11-01', 2009, 'doctor'),
(10, 'Vidal', 'Nicolas', 'nicolas.vidal@example.com', '0550000013', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1987-02-22', '100000000013', 'Gynecologist', 4000.00, '2016-04-15', 2015, 'doctor');

-- --------------------------------------------------------

--
-- Structure de la table `emergency_contact`
--

CREATE TABLE `emergency_contact` (
  `id_contact` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `relation` enum('parent','spouse','child','sibling','friend','relative','other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `emergency_contact`
--

INSERT INTO `emergency_contact` (`id_contact`, `id_patient`, `last_name`, `first_name`, `phone`, `relation`) VALUES
(1, 1, 'Martin', 'Olivia', '0660000014', 'parent'),
(2, 1, 'Roux', 'Paul', '0660000015', 'friend'),
(3, 2, 'Blanc', 'Quentin', '0660000016', 'sibling'),
(4, 3, 'Marchand', 'Rose', '0660000017', 'spouse'),
(5, 3, 'Laurent', 'Simon', '0660000018', 'parent'),
(6, 4, 'Bonnet', 'Tania', '0660000019', 'parent'),
(7, 5, 'Andre', 'Victor', '0660000020', 'spouse'),
(8, 6, 'Durand', 'Wendy', '0660000021', 'parent');

-- --------------------------------------------------------

--
-- Structure de la table `medical_record`
--

CREATE TABLE `medical_record` (
  `id_record` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `medical_history` text DEFAULT NULL,
  `current_treatment` text DEFAULT NULL,
  `medical_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_record`
--

INSERT INTO `medical_record` (`id_record`, `id_patient`, `medical_history`, `current_treatment`, `medical_notes`) VALUES
(1, 1, 'Mild asthma since childhood', 'Ventolin inhaler as needed', 'Patient responds well to current treatment'),
(2, 2, 'Hypertension diagnosed in 2018', 'Lisinopril 10mg daily', 'Blood pressure generally under control with medication'),
(3, 3, 'Type 2 diabetes diagnosed in 2015', 'Metformin 500mg twice daily', 'HbA1c levels stable in recent check-ups'),
(4, 4, 'Seasonal allergies', 'Cetirizine 10mg as needed during spring', 'Consider immunotherapy if symptoms worsen'),
(5, 5, 'History of gastric ulcer', 'Omeprazole 20mg daily', 'Last endoscopy showed healing, continue treatment for 2 more months'),
(6, 6, 'Migraine with aura', 'Sumatriptan as needed, max 100mg daily', 'Patient reports triggers include lack of sleep and stress');

-- --------------------------------------------------------

--
-- Structure de la table `patient`
--

CREATE TABLE `patient` (
  `id_patient` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `national_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patient`
--

INSERT INTO `patient` (`id_patient`, `last_name`, `first_name`, `email`, `phone`, `password`, `birth_date`, `national_id`) VALUES
(1, 'Garcia', 'Xavier', 'xavier.garcia@example.com', '0550000022', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '2005-10-12', '100000000022'),
(2, 'Mercier', 'Yvonne', 'yvonne.mercier@example.com', '0550000023', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1988-04-12', '100000000023'),
(3, 'Clement', 'Zoe', 'zoe.clement@example.com', '0550000024', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '2000-01-30', '100000000024'),
(4, 'Laurent', 'Alice', 'alice.laurent@example.com', '0550000025', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1972-09-18', '100000000025'),
(5, 'Bonnet', 'Bruno', 'bruno.bonnet@example.com', '0550000026', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1999-11-05', '100000000026'),
(6, 'Andre', 'Claire', 'claire.andre@example.com', '0550000027', '$2y$10$shBN/SSP4XqpCqVLHR676.YR2vc9Kx3lLH6fCG3x4U4Hu5cx8sN0a', '1985-07-20', '100000000027');

-- --------------------------------------------------------

--
-- Structure de la table `patient_address`
--

CREATE TABLE `patient_address` (
  `id_address` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patient_address`
--

INSERT INTO `patient_address` (`id_address`, `id_patient`, `street`, `city`, `region`, `postal_code`, `country`) VALUES
(1, 1, '123 Main Street', 'Algiers', 'Algiers', '16000', 'Algeria'),
(2, 2, '45 Ahmed Street', 'Oran', 'Oran', '31000', 'Algeria'),
(3, 3, '78 Didouche Mourad', 'Constantine', 'Constantine', '25000', 'Algeria'),
(4, 4, '15 Ben Badis Ave', 'Annaba', 'Annaba', '23000', 'Algeria'),
(5, 5, '32 November 1st Blvd', 'Batna', 'Batna', '05000', 'Algeria'),
(6, 6, '101 Revolution Road', 'Setif', 'Setif', '19000', 'Algeria');

-- --------------------------------------------------------

--
-- Structure de la table `schedule`
--

CREATE TABLE `schedule` (
  `id_schedule` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `schedule`
--

INSERT INTO `schedule` (`id_schedule`, `id_doctor`, `day`, `start_time`, `end_time`) VALUES
(1, 1, 'monday', '08:00:00', '17:00:00'),
(2, 1, 'tuesday', '08:00:00', '17:00:00'),
(3, 1, 'wednesday', '08:00:00', '17:00:00'),
(4, 1, 'thursday', '08:00:00', '17:00:00'),
(5, 1, 'saturday', '08:00:00', '17:00:00'),
(6, 1, 'sunday', '08:00:00', '17:00:00');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`id_appointment`),
  ADD UNIQUE KEY `unique_appointment_slot` (`id_doctor`,`appointment_date`,`start_time`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_appointment_status` (`status`),
  ADD KEY `idx_doctor_date` (`id_doctor`,`appointment_date`);

--
-- Index pour la table `assistant`
--
ALTER TABLE `assistant`
  ADD PRIMARY KEY (`id_assistant`),
  ADD UNIQUE KEY `unique_assistant_email` (`email`),
  ADD UNIQUE KEY `unique_assistant_national_id` (`national_id`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `idx_assistant_email` (`email`);

--
-- Index pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`id_consult`),
  ADD KEY `id_appointment` (`id_appointment`),
  ADD KEY `id_doctor` (`id_doctor`);

--
-- Index pour la table `consultation_feedback`
--
ALTER TABLE `consultation_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `idx_synced` (`synced_to_platform`),
  ADD KEY `idx_platform_id` (`platform_feedback_id`);

--
-- Index pour la table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`id_doctor`),
  ADD UNIQUE KEY `unique_doctor_email` (`email`),
  ADD UNIQUE KEY `unique_doctor_national_id` (`national_id`),
  ADD KEY `idx_doctor_role` (`role`),
  ADD KEY `idx_doctor_email` (`email`);

--
-- Index pour la table `emergency_contact`
--
ALTER TABLE `emergency_contact`
  ADD PRIMARY KEY (`id_contact`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `medical_record`
--
ALTER TABLE `medical_record`
  ADD PRIMARY KEY (`id_record`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`id_patient`),
  ADD UNIQUE KEY `unique_patient_email` (`email`),
  ADD UNIQUE KEY `unique_patient_national_id` (`national_id`),
  ADD KEY `idx_patient_email` (`email`),
  ADD KEY `idx_patient_name` (`last_name`,`first_name`);

--
-- Index pour la table `patient_address`
--
ALTER TABLE `patient_address`
  ADD PRIMARY KEY (`id_address`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id_schedule`),
  ADD KEY `id_doctor` (`id_doctor`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `id_appointment` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `assistant`
--
ALTER TABLE `assistant`
  MODIFY `id_assistant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `consultation`
--
ALTER TABLE `consultation`
  MODIFY `id_consult` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `consultation_feedback`
--
ALTER TABLE `consultation_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `id_doctor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `emergency_contact`
--
ALTER TABLE `emergency_contact`
  MODIFY `id_contact` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `id_record` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `patient`
--
ALTER TABLE `patient`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `patient_address`
--
ALTER TABLE `patient_address`
  MODIFY `id_address` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id_schedule` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE;

--
-- Contraintes pour la table `assistant`
--
ALTER TABLE `assistant`
  ADD CONSTRAINT `assistant_doctor_fk` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`) ON DELETE SET NULL;

--
-- Contraintes pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `consultation_ibfk_1` FOREIGN KEY (`id_appointment`) REFERENCES `appointment` (`id_appointment`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_ibfk_2` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`) ON DELETE CASCADE;

--
-- Contraintes pour la table `consultation_feedback`
--
ALTER TABLE `consultation_feedback`
  ADD CONSTRAINT `consultation_feedback_ibfk_1` FOREIGN KEY (`id_consultation`) REFERENCES `consultation` (`id_consult`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_feedback_ibfk_2` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_feedback_ibfk_3` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`) ON DELETE CASCADE;

-- Insert sample feedbacks (synced with MediCloud feedbacks table)
INSERT INTO `consultation_feedback` (`id_consultation`, `id_patient`, `id_doctor`, `accueil`, `ponctualite`, `disponibilite`, `competence`, `experience`, `equipement`, `hygiene`, `securite`, `parking`, `cout`, `comments`) VALUES
(1, 1, 1, 5, 5, 4, 5, 5, 4, 5, 5, 4, 4, 'Excellent service and professional staff'),
(2, 2, 1, 4, 4, 4, 5, 5, 4, 4, 4, 4, 3, 'Good experience overall'),
(3, 3, 1, 4, 4, 3, 4, 4, 3, 4, 4, 4, 4, 'Satisfactory service'),
(4, 1, 1, 5, 5, 5, 5, 4, 4, 5, 5, 4, 4, 'Très bon service. Recommandé!'),
(5, 2, 1, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 'Professionnel et sympathique.'),
(6, 1, 1, 5, 5, 5, 4, 5, 5, 4, 3, 5, 4, 'Très bon service. Recommandé!'),
(7, 2, 1, 4, 5, 5, 5, 5, 4, 4, 4, 4, 4, 'Professionnel et sympathique.'),
(8, 1, 1, 5, 4, 5, 4, 4, 5, 3, 4, 5, 4, 'Très bon service. Recommandé!'),
(9, 2, 1, 4, 3, 4, 4, 5, 4, 4, 4, 4, 4, 'Professionnel et sympathique.'),
(10, 1, 1, 5, 5, 5, 5, 5, 5, 4, 3, 5, 5, 'Très bon service. Recommandé!'),
(11, 2, 1, 4, 4, 4, 4, 4, 4, 3, 4, 4, 4, 'Professionnel et sympathique.'),
(12, 1, 1, 5, 5, 4, 5, 5, 4, 5, 5, 4, 4, 'Très bon service. Recommandé!'),
(13, 2, 1, 4, 5, 5, 5, 4, 4, 4, 4, 4, 4, 'Professionnel et sympathique.'),
(14, 1, 1, 5, 5, 5, 4, 5, 5, 4, 3, 5, 4, 'Très bon service. Recommandé!'),
(15, 2, 1, 4, 3, 4, 4, 4, 4, 4, 4, 4, 4, 'Professionnel et sympathique.'),
(16, 1, 1, 5, 4, 5, 5, 5, 5, 4, 4, 5, 5, 'Très bon service. Recommandé!'),
(17, 2, 1, 4, 4, 4, 4, 5, 4, 4, 4, 4, 4, 'Professionnel et sympathique.'),
(18, 1, 1, 5, 5, 5, 4, 5, 5, 4, 3, 5, 5, 'Très bon service. Recommandé!'),
(19, 2, 1, 4, 3, 4, 4, 4, 4, 3, 4, 4, 4, 'Professionnel et sympathique.'),
(20, 1, 1, 5, 4, 5, 4, 5, 5, 4, 4, 5, 5, 'Très bon service. Recommandé!');

--
-- Contraintes pour la table `emergency_contact`
--
ALTER TABLE `emergency_contact`
  ADD CONSTRAINT `emergency_contact_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE;

--
-- Contraintes pour la table `medical_record`
--
ALTER TABLE `medical_record`
  ADD CONSTRAINT `medical_record_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE;

--
-- Contraintes pour la table `patient_address`
--
ALTER TABLE `patient_address`
  ADD CONSTRAINT `patient_address_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE;

--
-- Contraintes pour la table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
