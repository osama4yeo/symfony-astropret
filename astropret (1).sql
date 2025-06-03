-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le :  mar. 03 juin 2025 à 12:24
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
('DoctrineMigrations\\Version20250528071132', '2025-05-28 07:11:56', 138),
('DoctrineMigrations\\Version20250603063249', '2025-06-03 06:33:37', 100);

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
  `uid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `event`
--

INSERT INTO `event` (`id`, `title`, `start`, `end`, `description`, `all_day`, `uid`, `source`) VALUES
(65, 'New Year\'s Day', '2024-01-01 00:00:00', '2024-01-01 00:00:00', 'Visit https://calendarlabs.com/holidays/us/new-years-day.php to know more about New Year\'s Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1691744018101@calendarlabs.com', 'ics'),
(66, 'M L King Day', '2024-01-15 00:00:00', '2024-01-15 00:00:00', 'Visit https://calendarlabs.com/holidays/us/martin-luther-king-day.php to know more about M L King Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b17c1744018101@calendarlabs.com', 'ics'),
(67, 'Presidents\' Day', '2024-02-19 00:00:00', '2024-02-19 00:00:00', 'Visit https://calendarlabs.com/holidays/us/presidents-day.php to know more about Presidents\' Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1891744018101@calendarlabs.com', 'ics'),
(68, 'Good Friday', '2024-03-29 00:00:00', '2024-03-29 00:00:00', 'Visit https://calendarlabs.com/holidays/us/good-friday.php to know more about Good Friday. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1941744018101@calendarlabs.com', 'ics'),
(69, 'Easter Sunday', '2024-03-31 00:00:00', '2024-03-31 00:00:00', 'Visit https://calendarlabs.com/holidays/us/easter.php to know more about Easter Sunday. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1a01744018101@calendarlabs.com', 'ics'),
(70, 'Memorial Day', '2024-05-27 00:00:00', '2024-05-27 00:00:00', 'Visit https://calendarlabs.com/holidays/us/memorial-day.php to know more about Memorial Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1ab1744018101@calendarlabs.com', 'ics'),
(71, 'Juneteenth', '2024-06-19 00:00:00', '2024-06-19 00:00:00', 'Visit https://calendarlabs.com/holidays/us/juneteenth.php to know more about Juneteenth. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1b61744018101@calendarlabs.com', 'ics'),
(72, 'Independence Day', '2024-07-04 00:00:00', '2024-07-04 00:00:00', 'Visit https://calendarlabs.com/holidays/us/independence-day.php to know more about Independence Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1c21744018101@calendarlabs.com', 'ics'),
(73, 'Labor Day', '2024-09-02 00:00:00', '2024-09-02 00:00:00', 'Visit https://calendarlabs.com/holidays/us/labor-day.php to know more about Labor Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1cd1744018101@calendarlabs.com', 'ics'),
(74, 'Columbus Day', '2024-10-14 00:00:00', '2024-10-14 00:00:00', 'Visit https://calendarlabs.com/holidays/us/columbus-day.php to know more about Columbus Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1d81744018101@calendarlabs.com', 'ics'),
(75, 'Veterans Day', '2024-11-11 00:00:00', '2024-11-11 00:00:00', 'Visit https://calendarlabs.com/holidays/us/veterans-day.php to know more about Veterans Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1e41744018101@calendarlabs.com', 'ics'),
(76, 'Thanksgiving Day', '2024-11-28 00:00:00', '2024-11-28 00:00:00', 'Visit https://calendarlabs.com/holidays/us/thanksgiving-day.php to know more about Thanksgiving Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1f01744018101@calendarlabs.com', 'ics'),
(77, 'Christmas', '2024-12-25 00:00:00', '2024-12-25 00:00:00', 'Visit https://calendarlabs.com/holidays/us/christmas.php to know more about Christmas. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b1fb1744018101@calendarlabs.com', 'ics'),
(78, 'New Year\'s Day', '2025-01-01 00:00:00', '2025-01-01 00:00:00', 'Visit https://calendarlabs.com/holidays/us/new-years-day.php to know more about New Year\'s Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2071744018101@calendarlabs.com', 'ics'),
(79, 'M L King Day', '2025-01-20 00:00:00', '2025-01-20 00:00:00', 'Visit https://calendarlabs.com/holidays/us/martin-luther-king-day.php to know more about M L King Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2121744018101@calendarlabs.com', 'ics'),
(80, 'Presidents\' Day', '2025-02-17 00:00:00', '2025-02-17 00:00:00', 'Visit https://calendarlabs.com/holidays/us/presidents-day.php to know more about Presidents\' Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b21e1744018101@calendarlabs.com', 'ics'),
(81, 'Good Friday', '2025-04-18 00:00:00', '2025-04-18 00:00:00', 'Visit https://calendarlabs.com/holidays/us/good-friday.php to know more about Good Friday. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2291744018101@calendarlabs.com', 'ics'),
(82, 'Easter Sunday', '2025-04-20 00:00:00', '2025-04-20 00:00:00', 'Visit https://calendarlabs.com/holidays/us/easter.php to know more about Easter Sunday. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2341744018101@calendarlabs.com', 'ics'),
(83, 'Memorial Day', '2025-05-26 00:00:00', '2025-05-26 00:00:00', 'Visit https://calendarlabs.com/holidays/us/memorial-day.php to know more about Memorial Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2441744018101@calendarlabs.com', 'ics'),
(84, 'Juneteenth', '2025-06-19 00:00:00', '2025-06-19 00:00:00', 'Visit https://calendarlabs.com/holidays/us/juneteenth.php to know more about Juneteenth. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2501744018101@calendarlabs.com', 'ics'),
(85, 'Independence Day', '2025-07-04 00:00:00', '2025-07-04 00:00:00', 'Visit https://calendarlabs.com/holidays/us/independence-day.php to know more about Independence Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b25c1744018101@calendarlabs.com', 'ics'),
(86, 'Labor Day', '2025-09-01 00:00:00', '2025-09-01 00:00:00', 'Visit https://calendarlabs.com/holidays/us/labor-day.php to know more about Labor Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2681744018101@calendarlabs.com', 'ics'),
(87, 'Columbus Day', '2025-10-13 00:00:00', '2025-10-13 00:00:00', 'Visit https://calendarlabs.com/holidays/us/columbus-day.php to know more about Columbus Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2751744018101@calendarlabs.com', 'ics'),
(88, 'Veterans Day', '2025-11-11 00:00:00', '2025-11-11 00:00:00', 'Visit https://calendarlabs.com/holidays/us/veterans-day.php to know more about Veterans Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b2811744018101@calendarlabs.com', 'ics'),
(89, 'Thanksgiving Day', '2025-11-27 00:00:00', '2025-11-27 00:00:00', 'Visit https://calendarlabs.com/holidays/us/thanksgiving-day.php to know more about Thanksgiving Day. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b28d1744018101@calendarlabs.com', 'ics'),
(90, 'Christmas', '2025-12-25 00:00:00', '2025-12-25 00:00:00', 'Visit https://calendarlabs.com/holidays/us/christmas.php to know more about Christmas. \n\n Like us on Facebook: http://fb.com/calendarlabs to get updates', 0, '67f39ab55b29a1744018101@calendarlabs.com', 'ics');

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
(1, 'Celestron NexStar 8SE', 'loue', NULL),
(2, 'SkyWatcher Explorer 130P', 'indisponible', NULL),
(3, 'Meade LX90', 'loue', NULL),
(4, 'Orion SkyQuest XT10', 'indisponible', NULL),
(5, 'Vixen R130Sf', 'loue', NULL),
(6, 'Celestron CPC 925', 'loue', NULL),
(7, 'SkyWatcher Dobson 200', 'libre', NULL),
(8, 'Meade ETX125', 'indisponible', NULL),
(9, 'Orion StarBlast 6i', 'indisponible', NULL),
(10, 'Bresser Messier AR102', 'libre', NULL),
(11, 'Celestron AstroMaster 70AZ', 'libre', NULL),
(12, 'SkyWatcher Skymax 127', 'indisponible', NULL),
(13, 'Meade StarNavigator NG', 'indisponible', NULL),
(14, 'Orion GoScope 80', 'indisponible', NULL),
(15, 'Bresser Pollux 150', 'loue', NULL),
(16, 'Celestron PowerSeeker 80EQ', 'indisponible', NULL),
(17, 'SkyWatcher StarTravel 102', 'libre', NULL),
(18, 'Meade Infinity 90', 'indisponible', NULL),
(19, 'Orion Observer 80ST', 'loue', NULL),
(20, 'Bresser Sirius 70/900', 'indisponible', NULL);

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
(6, 'admin@gmail.com', '$2y$13$9TSNliD6m3e2E.XOQbp9eunlnZ28Juxn6IHgN1QgzfqIKBacyJnZq', '[\"ROLE_ADMIN\", \"ROLE_USER\"]', 'Nikolas', 'Sarkozy', '0756565656', '1955-01-28', 'Meade-ETX125-68371a026044d.jpg');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `FK_42C8495516880AAF` FOREIGN KEY (`materiel_id`) REFERENCES `materiel` (`id`),
  ADD CONSTRAINT `FK_42C84955A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
