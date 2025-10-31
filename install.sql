-- ===============================
-- УСТАНОВКА МОДУЛЯ "БЛОГ" 
-- Полная установка на чистую БД
-- ===============================

-- Отключаем предупреждения о дублировании
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;

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
  PRIMARY KEY (`blog_category_id`,`language_id`),
  KEY `keyword` (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Таблица путей категорий ===
CREATE TABLE IF NOT EXISTS `blog_category_path` (
  `blog_category_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`blog_category_id`,`path_id`),
  KEY `path_id` (`path_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Привязка категорий к магазинам ===
CREATE TABLE IF NOT EXISTS `blog_category_to_store` (
  `blog_category_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`blog_category_id`,`store_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Таблица авторов ===
CREATE TABLE IF NOT EXISTS `article_author` (
  `author_id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `company_employee` TINYINT(1) NOT NULL DEFAULT '0',
  `affiliation` VARCHAR(255) NOT NULL DEFAULT '',
  `knows_about` TEXT NOT NULL,
  `knows_language` TEXT NOT NULL,
  `same_as` TEXT NOT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Описания авторов ===
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

-- === Связь статей с авторами ===
CREATE TABLE IF NOT EXISTS `information_to_author` (
  `information_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`information_id`,`author_id`),
  KEY `author_id` (`author_id`),
  KEY `is_primary` (`is_primary`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Таблица для SEO данных главной страницы блога ===
CREATE TABLE IF NOT EXISTS `blog_home_description` (
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `h1` varchar(255) NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keyword` varchar(500) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Таблица для SEO данных страницы списка авторов ===
CREATE TABLE IF NOT EXISTS `author_list_description` (
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `h1` varchar(255) NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(1000) DEFAULT NULL,
  `meta_keyword` varchar(500) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- === Индекс для seo_url ===
CREATE INDEX IF NOT EXISTS `query_author` ON `seo_url` (`query`(20));

-- === Добавляем колонки в information ===
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `viewed` int(5) NOT NULL DEFAULT 0;
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `reading_time` int(3) NOT NULL DEFAULT 0;
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `no_index` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `image` varchar(255) DEFAULT NULL;
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `schema_type` VARCHAR(20) NOT NULL DEFAULT 'BlogPosting';
ALTER TABLE `information` ADD COLUMN IF NOT EXISTS `rating_value` DECIMAL(2,1) NULL DEFAULT NULL;

-- === Layouts ===
INSERT IGNORE INTO `layout` (`name`) VALUES ('Blog Category');
INSERT IGNORE INTO `layout` (`name`) VALUES ('Author Page');

-- === Привязка маршрутов ===
INSERT IGNORE INTO `layout_route` (`layout_id`, `store_id`, `route`)
SELECT `layout_id`, 0, 'information/blog_category'
FROM `layout` WHERE `name` = 'Blog Category' LIMIT 1;

INSERT IGNORE INTO `layout_route` (`layout_id`, `store_id`, `route`)
SELECT `layout_id`, 0, 'information/author'
FROM `layout` WHERE `name` = 'Author Page' LIMIT 1;

-- === Права администратору ===
-- Обновляем права для группы Administrator
UPDATE `user_group` 
SET `permission` = JSON_ARRAY_APPEND(
    JSON_ARRAY_APPEND(
        JSON_ARRAY_APPEND(
            JSON_ARRAY_APPEND(
                COALESCE(`permission`, JSON_ARRAY()), 
                '$', 'access|catalog/blog_category'
            ),
            '$', 'modify|catalog/blog_category'
        ),
        '$', 'access|catalog/author'
    ),
    '$', 'modify|catalog/author'
)
WHERE `name` = 'Administrator'
AND (
    JSON_SEARCH(`permission`, 'one', 'access|catalog/blog_category') IS NULL OR
    JSON_SEARCH(`permission`, 'one', 'modify|catalog/blog_category') IS NULL OR
    JSON_SEARCH(`permission`, 'one', 'access|catalog/author') IS NULL OR
    JSON_SEARCH(`permission`, 'one', 'modify|catalog/author') IS NULL
);

-- === Модуль ===
INSERT IGNORE INTO `module` (`name`, `code`, `setting`) 
VALUES ('Блог', 'blog_category', '{"name":"Блог","status":"1"}');

-- === Настройки модуля ===
INSERT IGNORE INTO `setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES 
(0, 'blog_category', 'blog_category_status', '1', 0),
(0, 'blog_category', 'blog_author_article_width', '80', 0),
(0, 'blog_category', 'blog_author_article_height', '80', 0),
(0, 'blog_category', 'blog_author_page_width', '150', 0),
(0, 'blog_category', 'blog_author_page_height', '150', 0),
(0, 'blog_category', 'blog_author_list_image_width', '150', 0),
(0, 'blog_category', 'blog_author_list_image_height', '150', 0),
(0, 'blog_category', 'blog_article_image_width', '400', 0),
(0, 'blog_category', 'blog_article_image_height', '300', 0),
(0, 'blog_category', 'blog_category_image_width', '800', 0),
(0, 'blog_category', 'blog_category_image_height', '400', 0);

-- === SEO URL для блога ===
DELETE FROM `seo_url` WHERE `query` = 'information/blog_category';

INSERT IGNORE INTO `seo_url` (`store_id`, `language_id`, `query`, `keyword`) 
SELECT 0, `language_id`, 'information/blog_category', 'blog' FROM `language`;

-- === Начальные данные для SEO ===
INSERT IGNORE INTO `blog_home_description` (`language_id`, `name`, `h1`, `meta_title`, `meta_description`, `meta_keyword`, `description`) 
SELECT `language_id`, 'Блог', 'Блог', 'Блог', 'Читайте интересные статьи и новости в нашем блоге', 'блог, статьи, новости', 'Добро пожаловать в наш блог!' 
FROM `language`;

INSERT IGNORE INTO `author_list_description` (`language_id`, `name`, `h1`, `meta_title`, `meta_description`, `meta_keyword`, `description`) 
SELECT `language_id`, 'Авторы', 'Наши авторы', 'Авторы', 'Познакомьтесь с нашими авторами - экспертами в своей области', 'авторы, эксперты, блог', 'Наша команда авторов' 
FROM `language`;

-- Включаем обратно предупреждения
SET SQL_NOTES=@OLD_SQL_NOTES;

-- ===============================
-- КОНЕЦ УСТАНОВКИ
-- ===============================