-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Ven 29 Avril 2011 à 11:14
-- Version du serveur: 5.1.49
-- Version de PHP: 5.3.3-1ubuntu9.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `oce_dev`
--

-- --------------------------------------------------------

--
-- Structure de la table `menu_nodes`
--

CREATE TABLE IF NOT EXISTS `menu_nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `parent__menu_nodes_id` int(11) DEFAULT NULL,
  `action_family` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `command` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `iconCls` varchar(255) DEFAULT NULL,
  `expanded` tinyint(1) NOT NULL DEFAULT '0',
  `open` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=195 ;