-- MediCloud Database Schema
-- Used by admin pages and MediCloud/main/ pages
-- Created for: Cabinet Management, Invoice Management, User Authentication

-- Drop existing tables if they exist
DROP TABLE IF EXISTS feedbacks;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS cabinets;
DROP TABLE IF EXISTS admin_users;

-- Admin Users Table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cabinets Table
CREATE TABLE cabinets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_cabinet VARCHAR(255) NOT NULL,
    prenom VARCHAR(100),
    nom VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    telephone VARCHAR(20),
    wilaya VARCHAR(100),
    commune VARCHAR(100),
    adresse TEXT,
    tarif DECIMAL(10, 2) DEFAULT 0,
    plus_code VARCHAR(50),
    name VARCHAR(255),
    address TEXT,
    phone VARCHAR(20),
    specialty VARCHAR(100),
    description TEXT,
    database_name VARCHAR(100) COMMENT 'Cabinet database name (e.g., hippocare_1)',
    statut ENUM('active', 'pending', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wilaya (wilaya),
    INDEX idx_email (email),
    INDEX idx_status (statut),
    INDEX idx_database (database_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscriptions Table
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    type_abonnement VARCHAR(50) NOT NULL,
    date_debut DATE,
    date_fin DATE,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    INDEX idx_cabinet_id (cabinet_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices Table
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(50) UNIQUE NOT NULL,
    cabinet_id INT NOT NULL,
    subscription_id INT,
    montant DECIMAL(10, 2) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    date_creation DATE,
    date_paiement DATE,
    methode_paiement VARCHAR(50),
    reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    INDEX idx_cabinet_id (cabinet_id),
    INDEX idx_status (status),
    INDEX idx_numero (numero_facture),
    INDEX idx_date (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctors Table
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    email VARCHAR(255),
    telephone VARCHAR(20),
    specialty VARCHAR(100),
    years_experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    INDEX idx_cabinet_id (cabinet_id),
    INDEX idx_specialty (specialty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feedbacks Table (for cabinet ratings from patients)
CREATE TABLE feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    doctor_id INT,
    consultation_id INT,
    rating_security TINYINT CHECK (rating_security >= 1 AND rating_security <= 5),
    rating_equipment TINYINT CHECK (rating_equipment >= 1 AND rating_equipment <= 5),
    rating_hygiene TINYINT CHECK (rating_hygiene >= 1 AND rating_hygiene <= 5),
    rating_availability TINYINT CHECK (rating_availability >= 1 AND rating_availability <= 5),
    rating_skills TINYINT CHECK (rating_skills >= 1 AND rating_skills <= 5),
    rating_experience TINYINT CHECK (rating_experience >= 1 AND rating_experience <= 5),
    rating_location TINYINT CHECK (rating_location >= 1 AND rating_location <= 5),
    rating_price TINYINT CHECK (rating_price >= 1 AND rating_price <= 5),
    rating_reception TINYINT CHECK (rating_reception >= 1 AND rating_reception <= 5),
    rating_punctuality TINYINT CHECK (rating_punctuality >= 1 AND rating_punctuality <= 5),
    comment TEXT,
    source_database VARCHAR(50) COMMENT 'Source cabinet database (e.g., hippocare_1)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    INDEX idx_cabinet_id (cabinet_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_created_at (created_at),
    INDEX idx_source_db (source_database)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample admin user (password: admin123)
INSERT INTO admin_users (email, prenom, nom, password) VALUES 
('admin@medicloud.com', 'Admin', 'User', '$2y$12$CtzbYiISa2RymuGUlX9ql.5lgMpGlHYRYfu5382qAuXDtqb7BPB3y');

-- Insert sample cabinets
INSERT INTO cabinets (nom_cabinet, prenom, nom, email, telephone, wilaya, commune, adresse, tarif, plus_code, name, address, phone, specialty, description, database_name, statut) VALUES 
('Cabinet Médical Centre', 'David', 'Durand', 'cabinet1@medicloud.com', '0123456789', 'Alger', 'Alger Centre', '123 Rue de la Paix', 2500, '16000', 'Medical Center Cabinet', '123 Peace Street, Algiers Center', '0123456789', 'generaliste', 'Centre médical avec médecins généralistes expérimentés', 'hippocare_1', 'active'),
('Clinique Bien-Être', 'Emma', 'Garcia', 'cabinet2@medicloud.com', '0987654321', 'Tipaza', 'Tipaza', '456 Avenue de la Liberté', 3000, '42000', 'Wellness Clinic', '456 Liberty Avenue, Tipaza', '0987654321', 'pediatre', 'Clinique spécialisée en pédiatrie', 'hippocare_2', 'active'),
('Cabinet Général', 'Felix', 'Mercier', 'cabinet3@medicloud.com', '0555555555', 'Blida', 'Blida', '789 Boulevard Principal', 2000, '09000', 'General Cabinet', '789 Main Boulevard, Blida', '0555555555', 'cardiologue', 'Cabinet cardiologie de référence', 'hippocare_3', 'active'),
('Cabinet Samir', 'Gabrielle', 'Clement', 'cabinet.samir@medicloud.com', '0661234567', 'Alger', 'Ben Aknoun', '123 Rue de Liberté', 2500, '16306', 'Samir Cabinet', '123 Liberty Street, Ben Aknoun', '0661234567', 'dermatologue', 'Dermatologie et cosmétologie', 'hippocare_4', 'active'),
('Clinique Al-Noor', 'Hugo', 'Moreau', 'clinique.alnoor@medicloud.com', '0662345678', 'Alger', 'Hydra', '456 Avenue Didouche Mourad', 3000, '16035', 'Al-Noor Clinic', '456 Didouche Mourad Avenue, Hydra', '0662345678', 'generaliste', 'Clinique générale avec urgences', 'hippocare_5', 'active'),
('Cabinet Dr. Zahra', 'Iris', 'Girard', 'dr.zahra@medicloud.com', '0663456789', 'Tipaza', 'Tipaza', '789 Boulevard Principal', 2000, '42000', 'Dr. Zahra Cabinet', '789 Main Boulevard, Tipaza', '0663456789', 'pediatre', 'Pédiatrie et vaccination', 'hippocare_6', 'active'),
('Clinique Bien-Être 2', 'Jules', 'Faure', 'clinique.bienetre2@medicloud.com', '0664567890', 'Blida', 'Blida', '321 Rue de la Paix', 2800, '09000', 'Wellness Clinic 2', '321 Peace Street, Blida', '0664567890', 'generaliste', 'Clinique générale bien équipée', 'hippocare_7', 'active'),
('Cabinet Médical Tizi', 'Karen', 'Henry', 'cabinet.tizi@medicloud.com', '0665678901', 'Tizi Ouzou', 'Tizi Ouzou', '654 Avenue Indépendance', 2200, '15000', 'Medical Tizi Cabinet', '654 Independence Avenue, Tizi Ouzou', '0665678901', 'gynécologue', 'Gynécologie et obstétrique', 'hippocare_8', 'active'),
('Clinique Moderne', 'Louis', 'Michel', 'clinique.moderne@medicloud.com', '0666789012', 'Alger', 'Bab El Oued', '987 Rue Didouche', 3500, '16000', 'Modern Clinic', '987 Didouche Street, Bab El Oued', '0666789012', 'ophtalmologue', 'Ophtalmologie avec équipements modernes', 'hippocare_9', 'active'),
('Cabinet Dr. Ahmed', 'Marie', 'Lambert', 'dr.ahmed.c@medicloud.com', '0667890123', 'Alger', 'Kouba', '147 Rue de la Révolution', 2600, '16014', 'Dr. Ahmed Cabinet', '147 Revolution Street, Kouba', '0667890123', 'generaliste', 'Médecine générale et prévention', 'hippocare_10', 'active'),
('Clinique Sante Plus', 'Nicolas', 'Perrin', 'clinique.santeplus@medicloud.com', '0668901234', 'Tipaza', 'Menaceur', '258 Boulevard de la Paix', 2400, '42700', 'Health Plus Clinic', '258 Peace Boulevard, Menaceur', '0668901234', 'dentiste', 'Cabinet dentaire avec implantologie', 'hippocare_11', 'active'),
('Cabinet Guérison', 'Olivia', 'Lefevre', 'cabinet.guerison@medicloud.com', '0669012345', 'Blida', 'Ouled Yaïch', '369 Avenue du 1er Novembre', 2100, '09200', 'Healing Cabinet', '369 November 1st Avenue, Ouled Yaïch', '0669012345', 'generaliste', 'Cabinet médical généraliste', 'hippocare_12', 'active'),
('Clinique Excellence', 'Paul', 'Fontaine', 'clinique.excellence@medicloud.com', '0670123456', 'Tizi Ouzou', 'Azazga', '741 Rue de la Santé', 2900, '15100', 'Excellence Clinic', '741 Health Street, Azazga', '0670123456', 'cardiologue', 'Cardiologie et échocardiographie', 'hippocare_13', 'active'),
('Cabinet Vision', 'Quentin', 'Guerin', 'cabinet.vision@medicloud.com', '0671234567', 'Alger', 'Sidi Bel Abbès', '852 Boulevard du Commerce', 3200, '16045', 'Vision Cabinet', '852 Commerce Boulevard, Sidi Bel Abbès', '0671234567', 'ophtalmologue', 'Ophtalmologie pédiatrique', 'hippocare_14', 'active'),
('Clinique Vital', 'Rose', 'Collin', 'clinique.vital@medicloud.com', '0672345678', 'Alger', 'Hussein Dey', '963 Rue de la Liberté', 2700, '16012', 'Vital Clinic', '963 Liberty Street, Hussein Dey', '0672345678', 'generaliste', 'Clinique générale 24h/24', 'hippocare_15', 'active'),
('Cabinet Harmonie', 'Simon', 'Simon', 'cabinet.harmonie@medicloud.com', '0673456789', 'Tipaza', 'Hadjout', '111 Avenue Principale', 2300, '42600', 'Harmony Cabinet', '111 Main Avenue, Hadjout', '0673456789', 'pediatre', 'Pédiatrie avec consultation en ligne', 'hippocare_16', 'active'),
('Clinique Nouvelle', 'Tania', 'Dupont', 'clinique.nouvelle@medicloud.com', '0674567890', 'Blida', 'Chiffa', '222 Rue de la République', 2500, '09140', 'New Clinic', '222 Republic Street, Chiffa', '0674567890', 'gynécologue', 'Gynécologie et maternité', 'hippocare_17', 'active'),
('Cabinet Renaissance', 'Victor', 'Vidal', 'cabinet.renaissance@medicloud.com', '0675678901', 'Tizi Ouzou', 'Boumerdes', '333 Boulevard Indépendance', 2800, '15110', 'Renaissance Cabinet', '333 Independence Boulevard, Boumerdes', '0675678901', 'generaliste', 'Médecine générale intégrative', 'hippocare_18', 'active'),
('Clinique Progrès', 'Wendy', 'Martin', 'clinique.progres@medicloud.com', '0676789012', 'Alger', 'Bordj El Kiffan', '444 Rue Mohamed', 3100, '16027', 'Progress Clinic', '444 Mohamed Street, Bordj El Kiffan', '0676789012', 'dermatologue', 'Dermatologie avancée', 'hippocare_19', 'active'),
('Cabinet Santé', 'Xavier', 'Roux', 'cabinet.sante@medicloud.com', '0677890123', 'Alger', 'Dar El Beida', '555 Avenue de la Paix', 2400, '16040', 'Health Cabinet', '555 Peace Avenue, Dar El Beida', '0677890123', 'generaliste', 'Cabinet de médecine générale', 'hippocare_20', 'active'),
('Clinique Avenir', 'Yvonne', 'Blanc', 'clinique.avenir@medicloud.com', '0678901234', 'Tipaza', 'Cherchell', '666 Rue de l''Indépendance', 2600, '42120', 'Future Clinic', '666 Independence Street, Cherchell', '0678901234', 'pediatre', 'Pédiatrie avec garde d''enfants', 'hippocare_21', 'active'),
('Cabinet Confiance', 'Zoe', 'Marchand', 'cabinet.confiance@medicloud.com', '0679012345', 'Blida', 'Mitidja', '777 Boulevard Principal', 2200, '09100', 'Trust Cabinet', '777 Main Boulevard, Mitidja', '0679012345', 'dentiste', 'Dentisterie générale et cosmétique', 'hippocare_22', 'active'),
('Clinique Réussite', 'Alice', 'Simon', 'clinique.reussite@medicloud.com', '0680123456', 'Tizi Ouzou', 'Larba', '888 Avenue Principale', 2700, '15200', 'Success Clinic', '888 Main Avenue, Larba', '0680123456', 'cardiologue', 'Cardiologie et prévention', 'hippocare_23', 'active');

-- Insert sample doctors
INSERT INTO doctors (cabinet_id, nom, prenom, email, specialty, years_experience) VALUES 
(1, 'Dupont', 'Bruno', 'dr.dupont51@medicloud.com', 'generaliste', 10),
(1, 'Vidal', 'Claire', 'dr.vidal52@medicloud.com', 'pediatre', 8),
(2, 'Martin', 'David', 'dr.martin53@medicloud.com', 'cardiologue', 15),
(3, 'Roux', 'Emma', 'dr.roux54@medicloud.com', 'dermatologue', 5),
(4, 'Blanc', 'Felix', NULL, 'generaliste', 12),
(4, 'Marchand', 'Gabrielle', NULL, 'pediatre', 7),
(5, 'Laurent', 'Hugo', NULL, 'cardiologue', 20),
(5, 'Bonnet', 'Iris', NULL, 'dermatologue', 9),
(6, 'Andre', 'Jules', NULL, 'generaliste', 10),
(6, 'Durand', 'Karen', NULL, 'pediatre', 8),
(7, 'Garcia', 'Louis', NULL, 'cardiologue', 15),
(8, 'Mercier', 'Marie', NULL, 'dermatologue', 5),
(9, 'Clement', 'Nicolas', NULL, 'generaliste', 12),
(9, 'Moreau', 'Olivia', NULL, 'pediatre', 7),
(10, 'Girard', 'Paul', NULL, 'cardiologue', 20),
(11, 'Faure', 'Quentin', NULL, 'dermatologue', 9),
(12, 'Henry', 'Rose', NULL, 'generaliste', 10),
(13, 'Michel', 'Simon', NULL, 'pediatre', 8),
(14, 'Lambert', 'Tania', NULL, 'cardiologue', 15),
(15, 'Perrin', 'Victor', NULL, 'dermatologue', 5),
(16, 'Lefevre', 'Wendy', NULL, 'generaliste', 12),
(16, 'Fontaine', 'Xavier', NULL, 'pediatre', 7),
(17, 'Guerin', 'Yvonne', NULL, 'cardiologue', 20),
(18, 'Collin', 'Zoe', NULL, 'dermatologue', 9);

-- Insert sample subscriptions
INSERT INTO subscriptions (cabinet_id, type_abonnement, date_debut, date_fin, status) VALUES 
(1, 'premium', '2024-01-01', '2025-01-01', 'active'),
(2, 'standard', '2024-06-01', '2025-06-01', 'active'),
(3, 'basic', '2024-12-01', '2025-12-01', 'active');

-- Insert sample invoices
INSERT INTO invoices (numero_facture, cabinet_id, subscription_id, montant, amount, status, date_creation, date_paiement, methode_paiement) VALUES 
('INV-20241201-0001', 1, 1, 5000, 5000, 'paid', '2024-12-01', '2024-12-05', 'card'),
('INV-20241202-0002', 2, 2, 3000, 3000, 'pending', '2024-12-02', NULL, NULL),
('INV-20241203-0003', 3, 3, 2000, 2000, 'paid', '2024-12-03', '2024-12-10', 'bank_transfer');

-- Insert sample feedbacks
INSERT INTO feedbacks (cabinet_id, doctor_id, consultation_id, rating_security, rating_equipment, rating_hygiene, rating_availability, rating_skills, rating_experience, rating_location, rating_price, rating_reception, rating_punctuality, comment, source_database) VALUES 
(1, 1, 1, 5, 4, 5, 4, 5, 5, 4, 4, 5, 5, 'Excellent service and professional staff', 'hippocare_1'),
(2, 3, 2, 4, 4, 4, 4, 5, 5, 4, 3, 4, 4, 'Good experience overall', 'hippocare_2'),
(3, 4, 3, 4, 3, 4, 3, 4, 4, 4, 4, 4, 4, 'Satisfactory service', 'hippocare_3'),
(4, 5, 4, 5, 4, 5, 5, 5, 4, 4, 4, 5, 5, 'Très bon service. Recommandé!', 'hippocare'),
(4, 5, 5, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 'Professionnel et sympathique.', 'hippocare'),
(4, 6, 6, 5, 5, 5, 4, 5, 5, 4, 3, 5, 4, 'Très bon service. Recommandé!', 'hippocare'),
(5, 7, 7, 4, 4, 4, 5, 5, 5, 4, 4, 4, 5, 'Professionnel et sympathique.', 'hippocare'),
(5, 8, 8, 5, 4, 5, 4, 4, 5, 3, 4, 5, 4, 'Très bon service. Recommandé!', 'hippocare'),
(6, 9, 9, 4, 3, 4, 4, 5, 4, 4, 4, 4, 4, 'Professionnel et sympathique.', 'hippocare'),
(6, 10, 10, 5, 5, 5, 5, 5, 5, 4, 3, 5, 5, 'Très bon service. Recommandé!', 'hippocare'),
(7, 11, 11, 4, 4, 4, 4, 4, 4, 3, 4, 4, 4, 'Professionnel et sympathique.', 'hippocare'),
(8, 12, 12, 5, 4, 5, 4, 5, 5, 4, 4, 5, 5, 'Très bon service. Recommandé!', 'hippocare'),
(9, 13, 13, 4, 4, 4, 5, 5, 4, 4, 4, 4, 5, 'Professionnel et sympathique.', 'hippocare'),
(9, 14, 14, 5, 5, 5, 4, 5, 5, 4, 3, 5, 4, 'Très bon service. Recommandé!', 'hippocare'),
(10, 15, 15, 4, 3, 4, 4, 4, 4, 4, 4, 4, 4, 'Professionnel et sympathique.', 'hippocare'),
(11, 16, 16, 5, 4, 5, 5, 5, 5, 4, 4, 5, 5, 'Très bon service. Recommandé!', 'hippocare'),
(12, 17, 17, 4, 4, 4, 4, 5, 4, 4, 4, 4, 4, 'Professionnel et sympathique.', 'hippocare'),
(13, 18, 18, 5, 5, 5, 4, 5, 5, 4, 3, 5, 5, 'Très bon service. Recommandé!', 'hippocare'),
(14, 19, 19, 4, 3, 4, 4, 4, 4, 3, 4, 4, 4, 'Professionnel et sympathique.', 'hippocare'),
(15, 20, 20, 5, 4, 5, 4, 5, 5, 4, 4, 5, 5, 'Très bon service. Recommandé!', 'hippocare');
