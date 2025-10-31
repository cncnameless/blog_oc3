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

        // НОВЫЕ ТАБЛИЦЫ ДЛЯ SEO
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_home_description` (
                `language_id` int(11) NOT NULL,
                `name` varchar(255) NOT NULL,
                `h1` varchar(255) NOT NULL,
                `meta_title` varchar(255) NOT NULL,
                `meta_description` varchar(1000) DEFAULT NULL,
                `meta_keyword` varchar(500) DEFAULT NULL,
                `description` text,
                PRIMARY KEY (`language_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "author_list_description` (
                `language_id` int(11) NOT NULL,
                `name` varchar(255) NOT NULL,
                `h1` varchar(255) NOT NULL,
                `meta_title` varchar(255) NOT NULL,
                `meta_description` varchar(1000) DEFAULT NULL,
                `meta_keyword` varchar(500) DEFAULT NULL,
                `description` text,
                PRIMARY KEY (`language_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        // Проверяем и добавляем необходимые колонки в information
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
        
        // Добавляем начальные данные для SEO
        $this->addInitialSeoData();
    }

    public function uninstall() {
        // Не удаляем таблицы при деинсталляции, так как они могут содержать данные
        // Удаляем только настройки модуля
        
        $this->db->query("DELETE FROM `" . DB_PREFIX . "module` WHERE `code` = 'blog_category'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'blog_category'");
    }

    private function addBasicSettings() {
        // Добавляем модуль в таблицу module если его нет
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "module` WHERE `code` = 'blog_category'");
        if (!$query->num_rows) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "module` (`name`, `code`, `setting`) VALUES ('Блог', 'blog_category', '{\"name\":\"Блог\",\"status\":\"1\"}')");
        }

        // Добавляем настройки модуля если их нет
        $settings_to_add = [
            'blog_category_status' => '1',
            'blog_author_article_width' => '80',
            'blog_author_article_height' => '80',
            'blog_author_page_width' => '400',
            'blog_author_page_height' => '400',
            'blog_author_list_image_width' => '300',
            'blog_author_list_image_height' => '300',
            'blog_article_image_width' => '400',
            'blog_article_image_height' => '300',
            'blog_category_image_width' => '800',
            'blog_category_image_height' => '400'
        ];

        foreach ($settings_to_add as $key => $value) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `code` = 'blog_category' AND `key` = '" . $key . "'");
            if (!$query->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'blog_category', '" . $key . "', '" . $value . "', 0)");
            }
        }
    }
    
    private function addInitialSeoData() {
        // Добавляем начальные данные для SEO главной блога
        $languages = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language");
        foreach ($languages->rows as $language) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_home_description WHERE language_id = '" . (int)$language['language_id'] . "'");
            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "blog_home_description SET 
                    language_id = '" . (int)$language['language_id'] . "',
                    name = 'Блог',
                    h1 = 'Блог', 
                    meta_title = 'Блог',
                    meta_description = 'Читайте интересные статьи и новости в нашем блоге',
                    description = 'Добро пожаловать в наш блог!'");
            }
        }
        
        // Добавляем начальные данные для SEO списка авторов
        foreach ($languages->rows as $language) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "author_list_description WHERE language_id = '" . (int)$language['language_id'] . "'");
            if (!$query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "author_list_description SET 
                    language_id = '" . (int)$language['language_id'] . "',
                    name = 'Авторы',
                    h1 = 'Наши авторы',
                    meta_title = 'Авторы',
                    meta_description = 'Познакомьтесь с нашими авторами - экспертами в своей области',
                    description = 'Наша команда авторов'");
            }
        }
    }

    /**
     * Сохраняет настройки размеров изображений
     */
    public function saveImageSettings($data) {
        $image_settings = [
            'blog_author_article_width', 'blog_author_article_height',
            'blog_author_page_width', 'blog_author_page_height',
            'blog_author_list_image_width', 'blog_author_list_image_height',
            'blog_article_image_width', 'blog_article_image_height',
            'blog_category_image_width', 'blog_category_image_height'
        ];
        
        foreach ($image_settings as $setting) {
            if (isset($data[$setting])) {
                // Удаляем старую настройку
                $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'blog_category' AND `key` = '" . $this->db->escape($setting) . "'");
                
                // Добавляем новую настройку
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET 
                    `store_id` = 0, 
                    `code` = 'blog_category', 
                    `key` = '" . $this->db->escape($setting) . "', 
                    `value` = '" . $this->db->escape($data[$setting]) . "', 
                    `serialized` = 0");
            }
        }
        
        // Очищаем кэш настроек
        $this->cache->delete('setting');
    }

    /**
     * Сохраняет SEO данные главной страницы блога
     */
    public function saveBlogHomeData($data) {
        foreach ($data as $language_id => $values) {
            $this->db->query("REPLACE INTO " . DB_PREFIX . "blog_home_description SET 
                language_id = '" . (int)$language_id . "',
                name = '" . $this->db->escape($values['name']) . "',
                h1 = '" . $this->db->escape($values['h1']) . "',
                meta_title = '" . $this->db->escape($values['meta_title']) . "',
                meta_description = '" . $this->db->escape($values['meta_description']) . "',
                description = '" . $this->db->escape($values['description']) . "'");
        }
    }

    /**
     * Сохраняет SEO данные страницы списка авторов
     */
    public function saveAuthorListData($data) {
        foreach ($data as $language_id => $values) {
            $this->db->query("REPLACE INTO " . DB_PREFIX . "author_list_description SET 
                language_id = '" . (int)$language_id . "',
                name = '" . $this->db->escape($values['name']) . "',
                h1 = '" . $this->db->escape($values['h1']) . "',
                meta_title = '" . $this->db->escape($values['meta_title']) . "',
                meta_description = '" . $this->db->escape($values['meta_description']) . "',
                description = '" . $this->db->escape($values['description']) . "'");
        }
    }

    /**
     * Получает SEO данные главной страницы блога
     */
    public function getBlogHomeData($language_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_home_description WHERE language_id = '" . (int)$language_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        }
        
        return [
            'name' => 'Блог',
            'h1' => 'Блог',
            'meta_title' => 'Блог',
            'meta_description' => '',
            'description' => ''
        ];
    }

    /**
     * Получает SEO данные страницы списка авторов
     */
    public function getAuthorListData($language_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "author_list_description WHERE language_id = '" . (int)$language_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        }
        
        return [
            'name' => 'Авторы',
            'h1' => 'Наши авторы',
            'meta_title' => 'Авторы',
            'meta_description' => '',
            'description' => ''
        ];
    }
}
?>