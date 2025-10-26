<?php
class ModelExtensionModuleBlogCategory extends Model {
    public function install() {
        // Таблицы категорий блога
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_category` (
                `blog_category_id` int(11) NOT NULL AUTO_INCREMENT,
                `image` varchar(255) DEFAULT NULL,
                `parent_id` int(11) NOT NULL DEFAULT '0',
                `sort_order` int(3) NOT NULL DEFAULT '0',
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`blog_category_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_category_description` (
                `blog_category_id` int(11) NOT NULL,
                `language_id` int(11) NOT NULL,
                `name` varchar(255) NOT NULL,
                `description` text,
                `meta_title` varchar(255) NOT NULL,
                `meta_description` varchar(1000) DEFAULT NULL,
                `meta_keyword` varchar(500) DEFAULT NULL,
                `keyword` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`blog_category_id`,`language_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_category_path` (
                `blog_category_id` int(11) NOT NULL,
                `path_id` int(11) NOT NULL,
                `level` int(11) NOT NULL,
                PRIMARY KEY (`blog_category_id`,`path_id`),
                KEY `path_id` (`path_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_category_to_store` (
                `blog_category_id` int(11) NOT NULL,
                `store_id` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`blog_category_id`,`store_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        // Таблицы авторов
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "article_author` (
                `author_id` int(11) NOT NULL AUTO_INCREMENT,
                `image` varchar(255) DEFAULT NULL,
                `sort_order` int(3) NOT NULL DEFAULT '0',
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`author_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "article_author_description` (
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "article_author_to_store` (
                `author_id` int(11) NOT NULL,
                `store_id` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`author_id`,`store_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        // Таблицы связей
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "information_to_blog_category` (
                `information_id` int(11) NOT NULL,
                `blog_category_id` int(11) NOT NULL,
                PRIMARY KEY (`information_id`,`blog_category_id`),
                KEY `blog_category_id` (`blog_category_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "information_to_author` (
                `information_id` int(11) NOT NULL,
                `author_id` int(11) NOT NULL,
                `sort_order` int(3) NOT NULL DEFAULT '0',
                `is_primary` tinyint(1) DEFAULT '0' COMMENT 'Основной автор',
                PRIMARY KEY (`information_id`,`author_id`),
                KEY `author_id` (`author_id`),
                KEY `is_primary` (`is_primary`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        // Проверяем и добавляем все необходимые колонки в information
        $columns_to_add = [
            'date_added' => "ALTER TABLE `" . DB_PREFIX . "information` ADD COLUMN `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'date_modified' => "ALTER TABLE `" . DB_PREFIX . "information` ADD COLUMN `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            'viewed' => "ALTER TABLE `" . DB_PREFIX . "information` ADD COLUMN `viewed` int(5) NOT NULL DEFAULT '0'",
            'reading_time' => "ALTER TABLE `" . DB_PREFIX . "information` ADD COLUMN `reading_time` int(3) NOT NULL DEFAULT '0'",
            'no_index' => "ALTER TABLE `" . DB_PREFIX . "information` ADD COLUMN `no_index` tinyint(1) NOT NULL DEFAULT '0'",
            'image' => "ALTER TABLE `" . DB_PREFIX . "information` ADD COLUMN `image` varchar(255) DEFAULT NULL"
        ];

        foreach ($columns_to_add as $column => $sql) {
            $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "information` LIKE '" . $column . "'");
            if (!$query->num_rows) {
                $this->db->query($sql);
            }
        }

        // Создаем индекс для keyword
        $this->db->query("CREATE INDEX IF NOT EXISTS `keyword` ON `" . DB_PREFIX . "blog_category_description` (`keyword`)");

        // Добавляем базовые настройки
        $this->addBasicSettings();
    }

    public function uninstall() {
        // Удаляем таблицы
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "blog_category`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "blog_category_description`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "blog_category_path`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "blog_category_to_store`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "article_author`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "article_author_description`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "article_author_to_store`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "information_to_blog_category`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "information_to_author`");

        // Удаляем настройки модуля
        $this->db->query("DELETE FROM `" . DB_PREFIX . "module` WHERE `code` = 'blog_category'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'blog_category'");
    }

    private function addBasicSettings() {
        // Добавляем модуль в таблицу module если его нет
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "module` WHERE `code` = 'blog_category'");
        if (!$query->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "module` (`name`, `code`, `setting`) VALUES ('Категории блога', 'blog_category', '{\"name\":\"Категории блога\",\"status\":\"1\"}')");
        }

        // Добавляем настройки модуля если их нет
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `code` = 'blog_category' AND `key` = 'blog_category_status'");
        if (!$query->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'blog_category', 'blog_category_status', '1', 0)");
        }
    }
}