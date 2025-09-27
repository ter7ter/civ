-- Добавление таблицы unit_type
-- Таблица для хранения типов юнитов

DROP TABLE IF EXISTS `unit_type`;
CREATE TABLE IF NOT EXISTS `unit_type` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `points` int UNSIGNED NOT NULL DEFAULT '1',
  `cost` int UNSIGNED NOT NULL DEFAULT '0',
  `population_cost` int UNSIGNED NOT NULL DEFAULT '0',
  `type` enum('land','water','air') NOT NULL DEFAULT 'land',
  `attack` int UNSIGNED NOT NULL DEFAULT '0',
  `defence` int UNSIGNED NOT NULL DEFAULT '0',
  `health` int UNSIGNED NOT NULL DEFAULT '1',
  `movement` int UNSIGNED NOT NULL DEFAULT '1',
  `upkeep` int UNSIGNED NOT NULL DEFAULT '0',
  `can_found_city` tinyint NOT NULL DEFAULT '0',
  `can_build` tinyint NOT NULL DEFAULT '0',
  `need_research` text,
  `description` text,
  `mission_points` text,
  `age` int UNSIGNED NOT NULL DEFAULT '1',
  `missions` text,
  `req_research` text,
  `req_resources` text,
  `can_move` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;