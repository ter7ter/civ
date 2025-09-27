-- Добавление таблицы building_type
-- Таблица для хранения типов построек

DROP TABLE IF EXISTS `building_type`;
CREATE TABLE IF NOT EXISTS `building_type` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `cost` int UNSIGNED NOT NULL DEFAULT '0',
  `req_research` text,
  `req_resources` text,
  `need_coastal` tinyint NOT NULL DEFAULT '0',
  `culture` int UNSIGNED NOT NULL DEFAULT '0',
  `upkeep` int UNSIGNED NOT NULL DEFAULT '0',
  `need_research` text,
  `culture_bonus` int NOT NULL DEFAULT '0',
  `research_bonus` int NOT NULL DEFAULT '0',
  `money_bonus` int NOT NULL DEFAULT '0',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;