-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 21 2020 г., 09:06
-- Версия сервера: 5.7.23
-- Версия PHP: 7.2.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `civ`
--

-- --------------------------------------------------------

--
-- Структура таблицы `building`
--

DROP TABLE IF EXISTS `building`;
CREATE TABLE IF NOT EXISTS `building` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `city_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Структура таблицы `cell`
--

DROP TABLE IF EXISTS `cell`;
CREATE TABLE IF NOT EXISTS `cell` (
  `x` int(5) UNSIGNED NOT NULL,
  `y` int(5) UNSIGNED NOT NULL,
  `planet` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `type` enum('plains','plains2','forest','hills','mountains','desert','water1','water2','water3') NOT NULL,
  `owner` int(10) UNSIGNED DEFAULT NULL,
  `owner_culture` int(11) NOT NULL DEFAULT '0',
  `road` enum('none','road','iron') NOT NULL DEFAULT 'none',
  `improvement` enum('none','mine','irrigation') NOT NULL DEFAULT 'none',
  PRIMARY KEY (`x`,`y`,`planet`) USING BTREE,
  KEY `cell_ibfk_1` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `cell`
--

-- --------------------------------------------------------

--
-- Структура таблицы `city`
--

DROP TABLE IF EXISTS `city`;
CREATE TABLE IF NOT EXISTS `city` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(40) NOT NULL,
  `x` int(5) UNSIGNED NOT NULL,
  `y` int(5) UNSIGNED NOT NULL,
  `planet` int(10) UNSIGNED NOT NULL,
  `population` int(3) UNSIGNED NOT NULL DEFAULT '1',
  `people_dis` int(3) UNSIGNED NOT NULL DEFAULT '0',
  `people_norm` int(3) UNSIGNED NOT NULL DEFAULT '1',
  `people_happy` int(3) UNSIGNED NOT NULL DEFAULT '0',
  `people_artist` int(3) NOT NULL DEFAULT '0',
  `eat` int(11) NOT NULL DEFAULT '0',
  `eat_up` int(11) NOT NULL DEFAULT '20',
  `culture` int(11) NOT NULL DEFAULT '0',
  `culture_level` int(11) NOT NULL DEFAULT '0',
  `production` int(5) DEFAULT NULL,
  `production_type` enum('unit','buil') NOT NULL,
  `production_complete` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pwork` int(5) UNSIGNED NOT NULL DEFAULT '1',
  `peat` int(5) UNSIGNED NOT NULL DEFAULT '3',
  `pmoney` int(5) NOT NULL DEFAULT '1',
  `presearch` int(11) NOT NULL DEFAULT '0',
  `is_coastal` tinyint(1) NOT NULL DEFAULT '0',
  `resource_group` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `x` (`x`,`y`,`planet`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Структура таблицы `city_people`
--

DROP TABLE IF EXISTS `city_people`;
CREATE TABLE IF NOT EXISTS `city_people` (
  `x` int(5) UNSIGNED NOT NULL,
  `y` int(5) UNSIGNED NOT NULL,
  `planet` int(11) UNSIGNED NOT NULL,
  `city_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`x`,`y`,`planet`) USING BTREE,
  KEY `city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Структура таблицы `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('research','city_building','city_unit') NOT NULL,
  `source` int(10) UNSIGNED DEFAULT NULL,
  `object` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


--
-- Структура таблицы `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE IF NOT EXISTS `game` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `map_w` int(5) UNSIGNED NOT NULL,
  `map_h` int(5) UNSIGNED NOT NULL,
  `turn_type` enum('concurrently','byturn','onewindow') NOT NULL,
  `turn_num` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

--
-- Структура таблицы `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('system','chat') NOT NULL,
  `from_id` int(10) UNSIGNED DEFAULT NULL,
  `to_id` int(10) UNSIGNED DEFAULT NULL,
  `text` varchar(2000) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_id` (`from_id`),
  KEY `to_id` (`to_id`)
) ENGINE=InnoDB AUTO_INCREMENT=413 DEFAULT CHARSET=utf8;



-- --------------------------------------------------------

--
-- Структура таблицы `mission_order`
--

DROP TABLE IF EXISTS `mission_order`;
CREATE TABLE IF NOT EXISTS `mission_order` (
  `unit_id` int(10) UNSIGNED NOT NULL,
  `number` int(5) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `target_x` int(5) UNSIGNED NOT NULL,
  `target_y` int(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`unit_id`,`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Структура таблицы `research`
--

DROP TABLE IF EXISTS `research`;
CREATE TABLE IF NOT EXISTS `research` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `resource`
--

DROP TABLE IF EXISTS `resource`;
CREATE TABLE IF NOT EXISTS `resource` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `x` int(5) NOT NULL,
  `y` int(5) NOT NULL,
  `planet` int(11) NOT NULL,
  `amount` float UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=34517 DEFAULT CHARSET=utf8;

--
-- Структура таблицы `resource_group`
--

DROP TABLE IF EXISTS `resource_group`;
CREATE TABLE IF NOT EXISTS `resource_group` (
  `group_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `resource_id` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`group_id`,`user_id`,`resource_id`),
  KEY `resource_id` (`resource_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `resource_group`
--

INSERT INTO `resource_group` (`group_id`, `user_id`, `resource_id`) VALUES
(1, 1, 31420),
(1, 1, 31433),
(1, 1, 31450);

-- --------------------------------------------------------

--
-- Структура таблицы `unit`
--

DROP TABLE IF EXISTS `unit`;
CREATE TABLE IF NOT EXISTS `unit` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` int(10) UNSIGNED NOT NULL,
  `x` int(5) UNSIGNED NOT NULL,
  `y` int(5) UNSIGNED NOT NULL,
  `planet` int(10) UNSIGNED NOT NULL,
  `health` int(10) UNSIGNED NOT NULL,
  `health_max` int(11) NOT NULL DEFAULT '3',
  `points` float UNSIGNED NOT NULL,
  `mission_points` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Сколько уже вложено очков в выполнение текущей миссии',
  `mission` varchar(20) DEFAULT NULL,
  `auto` enum('none','work') NOT NULL DEFAULT 'none',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `x` (`x`,`y`,`planet`)
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8;


--
-- Структура таблицы `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` varchar(30) NOT NULL,
  `color` varchar(20) NOT NULL,
  `pass` varchar(30) NOT NULL,
  `game` int(10) UNSIGNED NOT NULL,
  `money` int(11) NOT NULL DEFAULT '0' COMMENT 'Общее число денег в наличии',
  `income` int(11) NOT NULL DEFAULT '0',
  `research_amount` int(11) NOT NULL COMMENT 'Сколько денег за ход идёт на науку',
  `research_percent` int(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Выставленный % на науку *10',
  `process_research_type` int(10) UNSIGNED NOT NULL COMMENT 'Какое исследование сейчас ведётся',
  `process_research_complete` int(10) UNSIGNED NOT NULL COMMENT 'Сколько едениц исследования уже завершено',
  `process_research_turns` int(3) UNSIGNED NOT NULL COMMENT 'Сколько ходов уже идёт исследование',
  `age` int(2) NOT NULL DEFAULT '1',
  `turn_status` enum('play','end','wait') NOT NULL DEFAULT 'wait',
  `turn_order` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;



--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `building`
--
ALTER TABLE `building`
  ADD CONSTRAINT `building_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`);

--
-- Ограничения внешнего ключа таблицы `cell`
--
ALTER TABLE `cell`
  ADD CONSTRAINT `cell_ibfk_1` FOREIGN KEY (`owner`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Ограничения внешнего ключа таблицы `city`
--
ALTER TABLE `city`
  ADD CONSTRAINT `city_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `city_ibfk_2` FOREIGN KEY (`x`,`y`,`planet`) REFERENCES `cell` (`x`, `y`, `planet`);

--
-- Ограничения внешнего ключа таблицы `city_people`
--
ALTER TABLE `city_people`
  ADD CONSTRAINT `city_people_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`),
  ADD CONSTRAINT `city_people_ibfk_2` FOREIGN KEY (`x`,`y`,`planet`) REFERENCES `cell` (`x`, `y`, `planet`);

--
-- Ограничения внешнего ключа таблицы `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`from_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`to_id`) REFERENCES `user` (`id`);

--
-- Ограничения внешнего ключа таблицы `mission_order`
--
ALTER TABLE `mission_order`
  ADD CONSTRAINT `mission_order_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`);

--
-- Ограничения внешнего ключа таблицы `research`
--
ALTER TABLE `research`
  ADD CONSTRAINT `research_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Ограничения внешнего ключа таблицы `resource_group`
--
ALTER TABLE `resource_group`
  ADD CONSTRAINT `resource_group_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`),
  ADD CONSTRAINT `resource_group_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Ограничения внешнего ключа таблицы `unit`
--
ALTER TABLE `unit`
  ADD CONSTRAINT `unit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `unit_ibfk_2` FOREIGN KEY (`x`,`y`,`planet`) REFERENCES `cell` (`x`, `y`, `planet`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
