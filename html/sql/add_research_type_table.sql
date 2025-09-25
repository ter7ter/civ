-- Добавление таблицы research_type
-- Таблица для хранения типов исследований

DROP TABLE IF EXISTS `research_type`;
CREATE TABLE IF NOT EXISTS `research_type` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `cost` int UNSIGNED NOT NULL DEFAULT '0',
  `requirements` text,
  `m_top` int NOT NULL DEFAULT '30',
  `m_left` int NOT NULL DEFAULT '0',
  `age` int UNSIGNED NOT NULL DEFAULT '1',
  `age_need` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
