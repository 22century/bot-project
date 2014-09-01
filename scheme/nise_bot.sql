/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# テーブルのダンプ bots
# ------------------------------------------------------------

DROP TABLE IF EXISTS `bots`;

CREATE TABLE `bots` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `screen_name` varchar(30) NOT NULL DEFAULT '',
  `origin_name` varchar(30) NOT NULL DEFAULT '',
  `origin_id_str` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# テーブルのダンプ kaomoji
# ------------------------------------------------------------

DROP TABLE IF EXISTS `kaomoji`;

CREATE TABLE `kaomoji` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `face` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# テーブルのダンプ logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  `id_str` varchar(20) NOT NULL DEFAULT '',
  `bot_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `origin_id_str` varchar(20) NOT NULL DEFAULT '',
  `origin_text` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# テーブルのダンプ names
# ------------------------------------------------------------

DROP TABLE IF EXISTS `names`;

CREATE TABLE `names` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# テーブルのダンプ poses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `poses`;

CREATE TABLE `poses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) unsigned NOT NULL,
  `en` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(4) NOT NULL DEFAULT '',
  `text` varchar(20) NOT NULL DEFAULT '',
  `reading` varchar(40) DEFAULT NULL,
  `suffix_type` varchar(40) DEFAULT NULL,
  `suffix_text` varchar(50) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_id` (`bot_id`),
  KEY `prefix_type` (`type`,`suffix_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# テーブルのダンプ statuses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `statuses`;

CREATE TABLE `statuses` (
  `id_str` varchar(20) NOT NULL,
  `bot_id` int(11) unsigned NOT NULL,
  `text` text NOT NULL,
  `lf` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id_str`),
  KEY `bot_id` (`bot_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
