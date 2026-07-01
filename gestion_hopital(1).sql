-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 01 juil. 2026 à 17:39
-- Version du serveur : 8.2.0
-- Version de PHP : 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_hopital`
--

-- --------------------------------------------------------

--
-- Structure de la table `consultation`
--

DROP TABLE IF EXISTS `consultation`;
CREATE TABLE IF NOT EXISTS `consultation` (
  `idConsultation` int NOT NULL AUTO_INCREMENT,
  `diagnostic` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `traitement` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `observations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dateConsultation` date NOT NULL,
  `idMedecin` int NOT NULL,
  `idRdv` int NOT NULL,
  PRIMARY KEY (`idConsultation`),
  UNIQUE KEY `idRdv` (`idRdv`),
  KEY `fk_consultation_medecin` (`idMedecin`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `consultation`
--

INSERT INTO `consultation` (`idConsultation`, `diagnostic`, `traitement`, `observations`, `dateConsultation`, `idMedecin`, `idRdv`) VALUES
(3, 'dd', 'dd', 'dd', '2026-07-01', 5, 3);

-- --------------------------------------------------------

--
-- Structure de la table `medecin`
--

DROP TABLE IF EXISTS `medecin`;
CREATE TABLE IF NOT EXISTS `medecin` (
  `idMedecin` int NOT NULL AUTO_INCREMENT,
  `nomMedecin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenomMedecin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `motDePasse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numOrdre` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idService` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMedecin`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `numOrdre` (`numOrdre`),
  KEY `fk_medecin_service` (`idService`),
  KEY `idx_medecin_nom` (`nomMedecin`,`prenomMedecin`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medecin`
--

INSERT INTO `medecin` (`idMedecin`, `nomMedecin`, `prenomMedecin`, `telephone`, `email`, `motDePasse`, `numOrdre`, `idService`) VALUES
(5, 'cyc', 'cyc', '655659053', 'cyc@gmail.com', '$2y$10$2DuybUsrbiarXkRRo/XooOr1mS.OTmDusD3XBt0C8SCGqhPYkmpm.', 'ORD-20260629-8750', 1),
(6, 'simo', 'patriica', '671487167', 'simotekamdjolovelinpatriica@gmail.com', '$2y$10$KDBH4yCqydF2lIZqXqI.buKGLmGPDsF4YlsdeV0SCqgRi5o4NVU1S', 'ORD-20260701-7464', 1);

-- --------------------------------------------------------

--
-- Structure de la table `ordonnance`
--

DROP TABLE IF EXISTS `ordonnance`;
CREATE TABLE IF NOT EXISTS `ordonnance` (
  `idOrdonnance` int NOT NULL AUTO_INCREMENT,
  `medicament` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duree` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dateOrdonnance` date NOT NULL,
  `idConsultation` int NOT NULL,
  PRIMARY KEY (`idOrdonnance`),
  KEY `fk_ordonnance_consultation` (`idConsultation`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ordonnance`
--

INSERT INTO `ordonnance` (`idOrdonnance`, `medicament`, `dosage`, `duree`, `instructions`, `dateOrdonnance`, `idConsultation`) VALUES
(1, 'para', '500', '3', 'cyc', '2026-07-01', 3),
(2, 'metro', '250', '7', 'ccc', '2026-07-01', 3);

-- --------------------------------------------------------

--
-- Structure de la table `patient`
--

DROP TABLE IF EXISTS `patient`;
CREATE TABLE IF NOT EXISTS `patient` (
  `idPatient` int NOT NULL AUTO_INCREMENT,
  `nomPatient` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenomPatient` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateNaissance` date NOT NULL,
  `sexe` enum('M','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `motDePasse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupeSanguin` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateCreationDossier` date NOT NULL,
  PRIMARY KEY (`idPatient`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_patient_nom` (`nomPatient`,`prenomPatient`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `patient`
--

INSERT INTO `patient` (`idPatient`, `nomPatient`, `prenomPatient`, `dateNaissance`, `sexe`, `telephone`, `email`, `motDePasse`, `adresse`, `groupeSanguin`, `dateCreationDossier`) VALUES
(3, 'cyc', 'cyc', '2026-06-06', 'M', '655659053', 'cyc@gmail.com', '$2y$10$2cAlxUQ3f.Os1SkABdoULu3GsGcvqmqkAdkX7STzmRAb.ANYhbqkW', 'cyc', 'A-', '2026-06-29'),
(4, 'bb', 'bb', '2026-06-29', 'F', '655656598', 'bb@gmail.com', '$2y$10$X85jcOSRlB1MMkoqegWUdegud9EgXXjdJZdKnWm2QKRDN.XQ8vBMi', 'bb', 'B-', '2026-07-01');

-- --------------------------------------------------------

--
-- Structure de la table `rendezvous`
--

DROP TABLE IF EXISTS `rendezvous`;
CREATE TABLE IF NOT EXISTS `rendezvous` (
  `idRdv` int NOT NULL AUTO_INCREMENT,
  `dateRdv` date NOT NULL,
  `heureRdv` time NOT NULL,
  `motif` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('programme','confirme','annule','termine') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'programme',
  `idPatient` int NOT NULL,
  `idService` int NOT NULL,
  PRIMARY KEY (`idRdv`),
  KEY `fk_rdv_patient` (`idPatient`),
  KEY `fk_rdv_service` (`idService`),
  KEY `idx_rdv_date` (`dateRdv`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `rendezvous`
--

INSERT INTO `rendezvous` (`idRdv`, `dateRdv`, `heureRdv`, `motif`, `statut`, `idPatient`, `idService`) VALUES
(3, '2026-07-01', '16:57:28', 'Consultation médicale', 'programme', 4, 1),
(4, '2026-07-03', '04:11:00', 'cyc', 'programme', 4, 1);

-- --------------------------------------------------------

--
-- Structure de la table `resultat`
--

DROP TABLE IF EXISTS `resultat`;
CREATE TABLE IF NOT EXISTS `resultat` (
  `idResultat` int NOT NULL AUTO_INCREMENT,
  `typeTest` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valeurNormale` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateTest` date NOT NULL,
  `idConsultation` int NOT NULL,
  PRIMARY KEY (`idResultat`),
  KEY `fk_resultat_consultation` (`idConsultation`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `resultat`
--

INSERT INTO `resultat` (`idResultat`, `typeTest`, `resultat`, `valeurNormale`, `dateTest`, `idConsultation`) VALUES
(1, 'cyv', 'ghj', '100 mg', '2026-07-01', 3);

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

DROP TABLE IF EXISTS `service`;
CREATE TABLE IF NOT EXISTS `service` (
  `idService` int NOT NULL AUTO_INCREMENT,
  `nomService` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `localisation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idService`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `service`
--

INSERT INTO `service` (`idService`, `nomService`, `description`, `localisation`) VALUES
(1, 'Cardiologie', 'Service des maladies cardiovasculaires', 'Bâtiment A, 1er étage'),
(2, 'Pédiatrie', 'Service de médecine pour enfants', 'Bâtiment B, CMR'),
(3, 'Médecine générale', 'Consultations générales', 'Bâtiment A, CMR');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `fk_consultation_medecin` FOREIGN KEY (`idMedecin`) REFERENCES `medecin` (`idMedecin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_consultation_rdv` FOREIGN KEY (`idRdv`) REFERENCES `rendezvous` (`idRdv`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `medecin`
--
ALTER TABLE `medecin`
  ADD CONSTRAINT `fk_medecin_service` FOREIGN KEY (`idService`) REFERENCES `service` (`idService`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD CONSTRAINT `fk_ordonnance_consultation` FOREIGN KEY (`idConsultation`) REFERENCES `consultation` (`idConsultation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  ADD CONSTRAINT `fk_rdv_patient` FOREIGN KEY (`idPatient`) REFERENCES `patient` (`idPatient`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rdv_service` FOREIGN KEY (`idService`) REFERENCES `service` (`idService`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `resultat`
--
ALTER TABLE `resultat`
  ADD CONSTRAINT `fk_resultat_consultation` FOREIGN KEY (`idConsultation`) REFERENCES `consultation` (`idConsultation`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
