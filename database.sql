-- ============================================================
-- Script de création de la base de données
-- Gestion de carnet patients et rendez-vous - Hôpital
-- Conforme au MLD (Merise)
-- SGBD cible : MySQL / MariaDB
-- ============================================================

DROP DATABASE IF EXISTS gestion_hopital;
CREATE DATABASE gestion_hopital
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestion_hopital;

-- ============================================================
-- Table : SERVICE
-- ============================================================
CREATE TABLE service (
    idService           INT AUTO_INCREMENT PRIMARY KEY,
    nomService           VARCHAR(100)        NOT NULL,
    description          TEXT,
    localisation         VARCHAR(100)
) ENGINE=InnoDB;

-- ============================================================
-- Table : MEDECIN
-- Association APPARTENIR (1,n - 1,1) : idService migre ici
-- ============================================================
CREATE TABLE medecin (
    idMedecin            INT AUTO_INCREMENT PRIMARY KEY,
    nomMedecin           VARCHAR(50)         NOT NULL,
    prenomMedecin        VARCHAR(50)         NOT NULL,
    telephone            VARCHAR(20),
    email                VARCHAR(100)        NOT NULL UNIQUE,
    motDePasse           VARCHAR(255)        NOT NULL,
    numOrdre             VARCHAR(30)         UNIQUE,
    idService            INT                 NOT NULL,

    CONSTRAINT fk_medecin_service
        FOREIGN KEY (idService) REFERENCES service(idService)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Table : PATIENT
-- ============================================================
CREATE TABLE patient (
    idPatient            INT AUTO_INCREMENT PRIMARY KEY,
    nomPatient           VARCHAR(50)         NOT NULL,
    prenomPatient        VARCHAR(50)         NOT NULL,
    dateNaissance        DATE                NOT NULL,
    sexe                 ENUM('M', 'F')      NOT NULL,
    telephone            VARCHAR(20),
    email                VARCHAR(100)        NOT NULL UNIQUE,
    motDePasse           VARCHAR(255)        NOT NULL,
    adresse              VARCHAR(150),
    groupeSanguin        VARCHAR(5),
    dateCreationDossier  DATE                NOT NULL DEFAULT (CURRENT_DATE)
) ENGINE=InnoDB;

-- ============================================================
-- Table : RENDEZVOUS
-- Association CONCERNER (1,n - 1,1) : idPatient migre ici
-- Association DEMANDER  (1,n - 1,1) : idService migre ici
-- ============================================================
CREATE TABLE rendezvous (
    idRdv                INT AUTO_INCREMENT PRIMARY KEY,
    dateRdv              DATE                NOT NULL,
    heureRdv             TIME                NOT NULL,
    motif                VARCHAR(255),
    statut               ENUM('programme', 'confirme', 'annule', 'termine')
                                              NOT NULL DEFAULT 'programme',
    idPatient            INT                 NOT NULL,
    idService            INT                 NOT NULL,

    CONSTRAINT fk_rdv_patient
        FOREIGN KEY (idPatient) REFERENCES patient(idPatient)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_rdv_service
        FOREIGN KEY (idService) REFERENCES service(idService)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Table : CONSULTATION
-- Association REALISER (1,n - 1,1) : idMedecin migre ici
-- Association ABOUTIR   (1,1 - 0,1) : idRdv migre ici (UNIQUE)
-- ============================================================
CREATE TABLE consultation (
    idConsultation       INT AUTO_INCREMENT PRIMARY KEY,
    diagnostic           TEXT,
    traitement           TEXT,
    observations         TEXT,
    dateConsultation     DATE                NOT NULL,
    idMedecin            INT                 NOT NULL,
    idRdv                INT                 NOT NULL UNIQUE,

    CONSTRAINT fk_consultation_medecin
        FOREIGN KEY (idMedecin) REFERENCES medecin(idMedecin)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_consultation_rdv
        FOREIGN KEY (idRdv) REFERENCES rendezvous(idRdv)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Index utiles pour les recherches fréquentes
-- ============================================================
CREATE INDEX idx_rdv_date ON rendezvous(dateRdv);
CREATE INDEX idx_patient_nom ON patient(nomPatient, prenomPatient);
CREATE INDEX idx_medecin_nom ON medecin(nomMedecin, prenomMedecin);

-- ============================================================
-- Données de test (optionnel)
-- ============================================================
INSERT INTO service (nomService, description, localisation) VALUES
('Cardiologie', 'Service des maladies cardiovasculaires', 'Bâtiment A, 1er étage'),
('Pédiatrie', 'Service de médecine pour enfants', 'Bâtiment B, RDC'),
('Médecine générale', 'Consultations générales', 'Bâtiment A, RDC');

-- NOTE : les mots de passe ci-dessous sont des hachages bcrypt fictifs
-- (à générer côté application avec password_hash() en PHP, bcrypt en Django, etc.)
-- Ne JAMAIS stocker de mot de passe en clair dans la base.

INSERT INTO medecin (nomMedecin, prenomMedecin, telephone, email, motDePasse, numOrdre, idService) VALUES
('Fankem', 'Michael', '699000001', 'fankem.michael@hopital.cm', '$2y$10$exempleHachageBcrypt1', 'ORD-001', 1),
('Yonzo', 'Paul', '699000002', 'yonzo.paul@hopital.cm', '$2y$10$exempleHachageBcrypt2', 'ORD-002', 2),
('Woungang', 'Romuald', '699000003', 'woungang.romuald@hopital.cm', '$2y$10$exempleHachageBcrypt3', 'ORD-003', 3);

INSERT INTO patient (nomPatient, prenomPatient, dateNaissance, sexe, telephone, email, motDePasse, adresse, groupeSanguin) VALUES
('Talla', 'Cyrias', '2000-05-12', 'M', '655000001', 'cyrias.talla@gmail.com', '$2y$10$exempleHachageBcrypt4', 'Bafoussam, Cameroun', 'O+'),
('Makamto', 'Handersone', '1995-08-23', 'F', '655000002', 'handersone.makamto@gmail.com', '$2y$10$exempleHachageBcrypt5', 'Bafoussam, Cameroun', 'A+');

INSERT INTO rendezvous (dateRdv, heureRdv, motif, statut, idPatient, idService) VALUES
('2026-06-25', '09:00:00', 'Douleurs thoraciques', 'confirme', 1, 1),
('2026-06-26', '10:30:00', 'Consultation de routine', 'programme', 2, 3);

INSERT INTO consultation (diagnostic, traitement, observations, dateConsultation, idMedecin, idRdv) VALUES
('Tension artérielle légèrement élevée', 'Repos et suivi tensionnel hebdomadaire', 'Patient à revoir dans 1 mois', '2026-06-25', 1, 1);
