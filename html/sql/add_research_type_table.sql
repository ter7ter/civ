-- Добавление таблицы research_type
-- Таблица для хранения типов исследований

DROP TABLE IF EXISTS `research_type`;
CREATE TABLE IF NOT EXISTS `research_type` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `cost` int UNSIGNED NOT NULL DEFAULT '0',
  `m_top` int NOT NULL DEFAULT '30',
  `m_left` int NOT NULL DEFAULT '0',
  `age` int UNSIGNED NOT NULL DEFAULT '1',
  `age_need` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Таблица связей требований исследований
DROP TABLE IF EXISTS `research_requirements`;
CREATE TABLE IF NOT EXISTS `research_requirements` (
  `research_type_id` int UNSIGNED NOT NULL,
  `required_research_type_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`research_type_id`, `required_research_type_id`),
  FOREIGN KEY (`research_type_id`) REFERENCES `research_type` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`required_research_type_id`) REFERENCES `research_type` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
