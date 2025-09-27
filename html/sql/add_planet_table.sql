-- Добавление таблицы planet
-- Таблица для хранения планет в игре

DROP TABLE IF EXISTS `planet`;
CREATE TABLE IF NOT EXISTS `planet` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Добавление внешнего ключа
ALTER TABLE `planet`
  ADD CONSTRAINT `planet_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`);