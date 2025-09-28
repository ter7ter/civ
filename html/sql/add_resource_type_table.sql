-- Добавление таблицы resource_type
-- Таблица для хранения типов ресурсов

DROP TABLE IF EXISTS `resource_type`;
CREATE TABLE IF NOT EXISTS `resource_type` (
  `id` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` enum('mineral','luxury','bonus') NOT NULL DEFAULT 'mineral',
  `work` int NOT NULL DEFAULT '0',
  `eat` int NOT NULL DEFAULT '0',
  `money` int NOT NULL DEFAULT '0',
  `req_research` text,
  `cell_types` text,
  `chance` float NOT NULL DEFAULT '0',
  `min_amount` int UNSIGNED NOT NULL DEFAULT '0',
  `max_amount` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
