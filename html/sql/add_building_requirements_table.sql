-- Добавление таблицы building_requirements
-- Таблица для хранения требований исследований для типов построек

DROP TABLE IF EXISTS `building_requirements_research`;
CREATE TABLE IF NOT EXISTS `building_requirements_research` (
  `building_type_id` int UNSIGNED NOT NULL,
  `required_research_type_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`building_type_id`, `required_research_type_id`),
  FOREIGN KEY (`building_type_id`) REFERENCES `building_type` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`required_research_type_id`) REFERENCES `research_type` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `building_requirements_resources`;
CREATE TABLE IF NOT EXISTS `building_requirements_resources` (
  `building_type_id` int UNSIGNED NOT NULL,
  `required_resource_type_id` varchar(50) NOT NULL,
  PRIMARY KEY (`building_type_id`, `required_resource_type_id`),
  FOREIGN KEY (`building_type_id`) REFERENCES `building_type` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`required_resource_type_id`) REFERENCES `resource_type` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
