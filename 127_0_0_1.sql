-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le :  mar. 03 juin 2025 à 14:31
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
CREATE DATABASE IF NOT EXISTS `astropret` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `astropret`;

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250527073651', '2025-05-27 07:38:02', 196),
('DoctrineMigrations\\Version20250528071132', '2025-05-28 07:11:56', 138);

-- --------------------------------------------------------

--
-- Structure de la table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `all_day` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `materiel`
--

DROP TABLE IF EXISTS `materiel`;
CREATE TABLE IF NOT EXISTS `materiel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `etat` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `image_filename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `materiel`
--

INSERT INTO `materiel` (`id`, `nom`, `etat`, `image_filename`) VALUES
(1, 'Celestron NexStar 8SE', 'loue', 'Celestron-NexStar-8SE-68372b763d840.jpg'),
(2, 'SkyWatcher Explorer 130P', 'libre', 'SkyWatcher-Explorer-130P-68372ce6ac08a.jpg'),
(3, 'Meade LX90', 'loue', 'Meade-LX90-68372cf58c7f9.jpg'),
(4, 'Orion SkyQuest XT10', 'libre', 'Orion-SkyQuest-XT10-68372d03475f8.jpg'),
(5, 'Vixen R130Sf', 'loue', NULL),
(6, 'Celestron CPC 925', 'loue', 'Celestron-CPC-925-68372d0e71afa.jpg'),
(7, 'SkyWatcher Dobson 200', 'libre', 'SkyWatcher-Dobson-200-68372d21c97a5.jpg'),
(8, 'Meade ETX125', 'libre', 'Meade-ETX125-68372d2c6317d.jpg'),
(9, 'Orion StarBlast 6i', 'libre', 'Orion-StarBlast-6i-68372d402f559.jpg'),
(10, 'Bresser Messier AR102', 'libre', 'Bresser-Messier-AR102-68372d4daead1.jpg'),
(11, 'Celestron AstroMaster 70AZ', 'libre', 'Celestron-AstroMaster-70AZ-68372d5b932bd.jpg'),
(12, 'SkyWatcher Skymax 127', 'libre', 'SkyWatcher-Skymax-127-68372d730f2e5.jpg'),
(13, 'Meade StarNavigator NG', 'libre', 'Meade-StarNavigator-NG-68372d81dc517.jpg'),
(14, 'Orion GoScope 80', 'libre', 'Orion-GoScope-80-68372d90875d0.jpg'),
(15, 'Bresser Pollux 150', 'loue', 'Bresser-Pollux-150-68372d9f85c2e.jpg'),
(16, 'Celestron PowerSeeker 80EQ', 'libre', 'Celestron-PowerSeeker-80EQ-68372dab1524f.jpg'),
(17, 'SkyWatcher StarTravel 102', 'libre', 'SkyWatcher-StarTravel-102-68372db8af249.jpg'),
(18, 'Meade Infinity 90', 'libre', 'Meade-Infinity-90-68372dcfbfbbc.jpg'),
(19, 'Orion Observer 80ST', 'loue', 'Orion-Observer-80ST-68372de26aeb1.jpg'),
(20, 'Bresser Sirius 70/900', 'libre', 'Bresser-Sirius-70-900-68372df2d104a.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
--

DROP TABLE IF EXISTS `reservation`;
CREATE TABLE IF NOT EXISTS `reservation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `materiel_id` int(11) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `date_debut` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `date_fin` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `user_id` int(11) DEFAULT NULL,
  `nom_locataire` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_42C8495516880AAF` (`materiel_id`),
  KEY `IDX_42C84955A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`id`, `materiel_id`, `latitude`, `longitude`, `date_debut`, `date_fin`, `user_id`, `nom_locataire`) VALUES
(1, 1, NULL, NULL, '2025-04-18 10:49:00', '2025-04-18 10:51:00', NULL, NULL),
(2, 3, NULL, NULL, '2025-04-19 10:53:00', '2025-04-26 10:53:00', NULL, NULL),
(5, 19, '78.6662000', '-88.2800000', '2025-05-30 10:32:00', '2025-06-19 10:32:00', 2, NULL),
(6, 5, '63.4754000', '-67.3493000', '2025-05-27 15:09:00', '2025-05-29 15:09:00', 2, NULL),
(7, 15, '4.7911330', '45.7526330', '2025-05-28 10:00:00', '2025-05-29 11:00:00', 3, NULL),
(8, 6, '45.7525120', '4.7911490', '2025-05-28 08:13:00', '2025-05-30 08:13:00', 2, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `prenom` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `avatar` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `password`, `roles`, `prenom`, `nom`, `telephone`, `date_naissance`, `avatar`) VALUES
(2, 'mailtest1@gmail.com', '$2y$13$KTX2jSCF6UiDAeewUYNXOejGAgDTdfAhe/1UwvHeHLsBJ1ZD4aOoe', '[]', 'Eddy', 'Graradji', '0625252525', '2004-01-19', 'Celestron-NexStar-8SE-68370274951d4.jpg'),
(3, 'mailtest2@gmail.com', '$2y$13$JgtSSWbzAXOVw6T9EyLIrubJu0Pek6IPFsbm0G6P9hPGH0bcH0Fde', '[]', 'Osama', 'Taliby', '0649494949', '2025-05-21', NULL),
(4, 'mailtest3@gmail.com', '$2y$13$Vv9dJJsHKHVE27C0Ng/SiOO.Gs0RvWQPvlNd7PNUKxb6K2CpRMD/S', '[]', 'Sylvain', 'Defrance', '0678787878', '2025-05-15', NULL),
(5, 'mailtest4@gmail.com', '$2y$13$ZCTrEikca4905f9DZ.owBuMd7buSMgEhCH3fV/XT.jOrWMN/i7Q9i', '[]', 'Mickael', 'Baccam', '078888888', '2025-05-23', NULL),
(6, 'admin@gmail.com', '$2y$13$9TSNliD6m3e2E.XOQbp9eunlnZ28Juxn6IHgN1QgzfqIKBacyJnZq', '[\"ROLE_ADMIN\", \"ROLE_USER\"]', 'Edouard', 'Branly', '0756565656', '1955-01-28', 'Meade-ETX125-68371a026044d.jpg');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `FK_42C8495516880AAF` FOREIGN KEY (`materiel_id`) REFERENCES `materiel` (`id`),
  ADD CONSTRAINT `FK_42C84955A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
--
-- Base de données :  `bibliotheque`
--
CREATE DATABASE IF NOT EXISTS `bibliotheque` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `bibliotheque`;

-- --------------------------------------------------------

--
-- Structure de la table `emprunteurs`
--

DROP TABLE IF EXISTS `emprunteurs`;
CREATE TABLE IF NOT EXISTS `emprunteurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `emprunts`
--

DROP TABLE IF EXISTS `emprunts`;
CREATE TABLE IF NOT EXISTS `emprunts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `livre_id` int(11) DEFAULT NULL,
  `emprunteur_id` int(11) DEFAULT NULL,
  `date_emprunt` date DEFAULT NULL,
  `date_retour_prevue` date DEFAULT NULL,
  `date_retour_effective` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `livre_id` (`livre_id`),
  KEY `emprunteur_id` (`emprunteur_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

DROP TABLE IF EXISTS `livres`;
CREATE TABLE IF NOT EXISTS `livres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) NOT NULL,
  `auteur` varchar(100) NOT NULL,
  `annee_publication` int(11) DEFAULT NULL,
  `isbn` varchar(13) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
--
-- Base de données :  `sagacité`
--
CREATE DATABASE IF NOT EXISTS `sagacité` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `sagacité`;

-- --------------------------------------------------------

--
-- Structure de la table `comptes_clients`
--

DROP TABLE IF EXISTS `comptes_clients`;
CREATE TABLE IF NOT EXISTS `comptes_clients` (
  `nomutilisateur` char(20) DEFAULT NULL,
  `age` int(20) DEFAULT NULL,
  `email` char(20) DEFAULT NULL,
  `mdp` int(20) DEFAULT NULL,
  `id` int(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `comptes_clients`
--

INSERT INTO `comptes_clients` (`nomutilisateur`, `age`, `email`, `mdp`, `id`) VALUES
('test', 17, 'test@test', 1, 1),
('s', 21, 's@ssm', 2, 2);

-- --------------------------------------------------------

--
-- Structure de la table `comptes_groupes`
--

DROP TABLE IF EXISTS `comptes_groupes`;
CREATE TABLE IF NOT EXISTS `comptes_groupes` (
  `nomEquipe` varchar(11) DEFAULT NULL,
  `membres` varchar(11) DEFAULT NULL,
  `niveau` tinyint(1) DEFAULT NULL,
  `difficulté` varchar(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `comptes_groupes`
--

INSERT INTO `comptes_groupes` (`nomEquipe`, `membres`, `niveau`, `difficulté`) VALUES
('testE', 'Maks, Marv', 0, 'debutant');

-- --------------------------------------------------------

--
-- Structure de la table `duree_jeu`
--

DROP TABLE IF EXISTS `duree_jeu`;
CREATE TABLE IF NOT EXISTS `duree_jeu` (
  `dureeEnfant` time NOT NULL,
  `dureeAdulte` time NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `duree_jeu`
--

INSERT INTO `duree_jeu` (`dureeEnfant`, `dureeAdulte`) VALUES
('00:00:00', '00:00:00'),
('00:00:00', '00:00:00'),
('00:05:00', '00:10:00');

-- --------------------------------------------------------

--
-- Structure de la table `partie`
--

DROP TABLE IF EXISTS `partie`;
CREATE TABLE IF NOT EXISTS `partie` (
  `tempsJoueur` varchar(50) NOT NULL,
  `nombredecoupsJouer` int(11) NOT NULL,
  `resultat` int(11) NOT NULL,
  `nomEquipe` varchar(50) DEFAULT NULL,
  `nomJoueur` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
--
-- Base de données :  `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `Prenom` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`Prenom`) VALUES
('Max');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
