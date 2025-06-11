-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le :  mer. 11 juin 2025 à 14:05
-- Version du serveur :  5.7.26
-- Version de PHP :  7.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `astropret`
--

-- --------------------------------------------------------

--
-- Structure de la table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `image_filename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `ordre_affichage` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `gallery`
--

INSERT INTO `gallery` (`id`, `titre`, `description`, `image_filename`, `updated_at`, `ordre_affichage`, `is_active`) VALUES
(1, 'Nébuleuse d\'Orion', 'Une magnifique formation d\'étoiles visible à l\'œil nu.', 'nebuleuse-orion-full-68493f8546008.jpg', '2025-06-11 12:44:27', NULL, 1),
(2, 'Galaxie d\'Andromède', 'Notre plus proche voisine galactique majeure.', 'galaxie-andromede-full-68493fc6dc252.jpg', '2025-06-11 08:35:27', NULL, 1),
(3, 'Les Pléiades (M45)', 'Un amas ouvert d\'étoiles jeunes et bleues, aussi connu sous le nom des Sept Sœurs.', 'pleiades-full-68493ffaceb7e.jpg', '2025-06-11 08:36:18', NULL, 1),
(4, 'Cratère lunaire Tycho', 'Un cratère d\'impact proéminent sur la Lune, visible lors de la pleine lune.', 'lune-cratere-full-6849406b75f59.jpg', '2025-06-11 08:38:11', NULL, 1),
(5, 'Jupiter et sa Grande Tache Rouge', 'La plus grande planète du système solaire avec sa tempête anticyclonique géante.', 'jupiter-grt-full-684940b04f972.jpg', '2025-06-11 08:39:20', NULL, 1),
(6, 'Saturne et ses anneaux', 'Célèbre pour son système d\'anneaux spectaculaire composé de glace et de roche.', 'saturne-anneaux-full-684941125c7d4.jpg', '2025-06-11 08:40:58', NULL, 1),
(7, 'La Voie Lactée', 'Notre galaxie vue de l\'intérieur, une bande lumineuse d\'étoiles.', 'voie-lactee-full-6849413fbdf50.jpg', '2025-06-11 08:41:43', NULL, 1),
(8, 'Nébuleuse de la Tête de Cheval', 'Une nébuleuse obscure dans la constellation d\'Orion.', 'nebuleuse-tete-cheval-full-684941da85be4.jpg', '2025-06-11 08:44:18', NULL, 1),
(9, 'Le Mont Olympe sur Mars', 'Le plus grand volcan connu du système solaire.', 'mars-olympus-mons-full-6849420b3e7d0.jpg', '2025-06-11 08:45:07', NULL, 1),
(10, 'Nébuleuse de la Carène', 'Une vaste région de formation d\'étoiles dans l\'hémisphère sud.', 'nebuleuse-carina-full-68494245b33b0.jpg', '2025-06-11 08:46:05', NULL, 1),
(11, 'Comète NEOWISE (2020)', 'Une comète spectaculaire visible à l\'œil nu en 2020.', 'comete-neowise-full-684942815ebc8.jpg', '2025-06-11 08:47:05', NULL, 1),
(12, 'Éclipse solaire totale', 'Lorsque la Lune passe directement entre le Soleil et la Terre.', 'eclipse-solaire-full-684942bcba848.jpg', '2025-06-11 08:48:04', NULL, 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
