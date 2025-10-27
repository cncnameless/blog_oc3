-- ===============================
-- УСТАНОВКА МОДУЛЯ "Блог с авторами"
-- ===============================

-- === Таблица категорий блога ===
CREATE TABLE IF NOT EXISTS `blog_category` (
  `blog_category_id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`blog_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Описание категорий по языкам ===
CREATE TABLE IF NOT EXISTS `blog_category_description` (
  `blog_category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keyword` varchar(500) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`blog_category_id`,`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Таблица путей категорий блога для иерархии ===
CREATE TABLE IF NOT EXISTS `blog_category_path` (
  `blog_category_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`blog_category_id`,`path_id`),
  KEY `path_id` (`path_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Привязка категорий блога к магазинам ===
CREATE TABLE IF NOT EXISTS `blog_category_to_store` (
  `blog_category_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`blog_category_id`,`store_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Основная таблица авторов ===
CREATE TABLE IF NOT EXISTS `article_author` (
  `author_id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Описания авторов по языкам ===
CREATE TABLE IF NOT EXISTS `article_author_description` (
  `author_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(1000) NOT NULL,
  `meta_keyword` varchar(500) NOT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `bio` text,
  `social_links` text COMMENT 'JSON с социальными сетями',
  PRIMARY KEY (`author_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Привязка авторов к магазинам ===
CREATE TABLE IF NOT EXISTS `article_author_to_store` (
  `author_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`author_id`,`store_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Привязка статей к категориям ===
CREATE TABLE IF NOT EXISTS `information_to_blog_category` (
  `information_id` int(11) NOT NULL,
  `blog_category_id` int(11) NOT NULL,
  PRIMARY KEY (`information_id`,`blog_category_id`),
  KEY `blog_category_id` (`blog_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Связь статей с авторами (M2M) ===
CREATE TABLE IF NOT EXISTS `information_to_author` (
  `information_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0' COMMENT 'Основной автор',
  PRIMARY KEY (`information_id`,`author_id`),
  KEY `author_id` (`author_id`),
  KEY `is_primary` (`is_primary`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Упрощенное добавление полей в таблицу information ===
-- Создаем временную процедуру для добавления столбцов
DELIMITER $$
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(64),
    IN columnName VARCHAR(64),
    IN columnDefinition TEXT
)
BEGIN
    DECLARE column_exists INT;

    SELECT COUNT(*)
    INTO column_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = tableName
    AND COLUMN_NAME = columnName;

    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Добавляем столбцы
CALL AddColumnIfNotExists('information', 'date_added', 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL AddColumnIfNotExists('information', 'date_modified', 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL AddColumnIfNotExists('information', 'viewed', 'int(5) NOT NULL DEFAULT 0');
CALL AddColumnIfNotExists('information', 'reading_time', 'int(3) NOT NULL DEFAULT 0');
CALL AddColumnIfNotExists('information', 'no_index', 'tinyint(1) NOT NULL DEFAULT 0');
CALL AddColumnIfNotExists('information', 'image', 'varchar(255) DEFAULT NULL');
CALL AddColumnIfNotExists('information', 'schema_type', 'VARCHAR(20) NOT NULL DEFAULT \"BlogPosting\"');
CALL AddColumnIfNotExists('information', 'rating_value', 'DECIMAL(2,1) NULL DEFAULT NULL');

-- Удаляем временную процедуру
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Создаем временную процедуру для создания индексов
DELIMITER $$
CREATE PROCEDURE AddIndexIfNotExists(
    IN tableName VARCHAR(64),
    IN indexName VARCHAR(64),
    IN indexColumns VARCHAR(255)
)
BEGIN
    DECLARE index_exists INT;

    SELECT COUNT(*)
    INTO index_exists
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = tableName
    AND INDEX_NAME = indexName;

    IF index_exists = 0 THEN
        SET @sql = CONCAT('CREATE INDEX `', indexName, '` ON `', tableName, '` (', indexColumns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Создаем индексы
CALL AddIndexIfNotExists('blog_category_description', 'keyword', '`keyword`');
CALL AddIndexIfNotExists('seo_url', 'query_author', '`query`(20)');

-- Удаляем временную процедуру
DROP PROCEDURE IF EXISTS AddIndexIfNotExists;

-- === Добавляем layout для вывода категорий блога ===
INSERT IGNORE INTO `layout` (`name`) VALUES ('Blog Category');

-- === Добавляем layout для вывода страницы автора ===
INSERT IGNORE INTO `layout` (`name`) VALUES ('Author Page');

-- === Привязываем маршрут для блога ===
INSERT IGNORE INTO `layout_route` (`layout_id`, `store_id`, `route`)
SELECT `layout_id`, 0, 'information/blog_category'
FROM `layout`
WHERE `name` = 'Blog Category';

-- === Привязываем маршрут для авторов ===
INSERT IGNORE INTO `layout_route` (`layout_id`, `store_id`, `route`)
SELECT `layout_id`, 0, 'information/author'
FROM `layout`
WHERE `name` = 'Author Page';

-- === Добавляем права доступа администратору ===
-- Более безопасный способ добавления прав
UPDATE `user_group` 
SET `permission` = CONCAT(
  `permission`, 
  ',"access|catalog/blog_category","modify|catalog/blog_category","access|extension/module/blog_category","modify|extension/module/blog_category","access|catalog/author","modify|catalog/author"'
)
WHERE `name` = 'Administrator'
AND `permission` NOT LIKE '%catalog/blog_category%';

-- === Добавляем модуль "Категории блога" в таблицу module ===
INSERT IGNORE INTO `module` (`name`, `code`, `setting`) 
VALUES ('Категории блога', 'blog_category', '{"name":"Категории блога","status":"1"}');

-- === Добавляем настройки модуля ===
INSERT IGNORE INTO `setting` (`store_id`, `code`, `key`, `value`, `serialized`) 
VALUES (0, 'blog_category', 'blog_category_status', '1', 0);

-- === Удаляем старый маршрут из seo_url ===
DELETE FROM `seo_url` WHERE `query` = 'information/blog_category';

-- === Добавляем маршрут для категорий блога в seo_url (для каждой языковой версии) ===
INSERT IGNORE INTO `seo_url` (`store_id`, `language_id`, `query`, `keyword`) 
SELECT 0, `language_id`, 'information/blog_category', 'blog' 
FROM `language`;