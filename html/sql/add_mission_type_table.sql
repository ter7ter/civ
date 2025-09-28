-- Добавление таблицы mission_type
-- Таблица для хранения типов миссий

DROP TABLE IF EXISTS `mission_type`;
CREATE TABLE IF NOT EXISTS `mission_type` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `need_points` text,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
