<?php
class ModelCatalogInformation extends Model {
    public function getInformation($information_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) WHERE i.information_id = '" . (int)$information_id . "' AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i.status = '1'");

        return $query->row;
    }

    public function getInformations() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i.status = '1' ORDER BY i.sort_order, LCASE(id.title) ASC");

        return $query->rows;
    }

    // Метод для получения информационных страниц для подвала (footer)
    public function getInformationsBottom() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i.status = '1' AND i.bottom = '1' ORDER BY i.sort_order, LCASE(id.title) ASC");

        return $query->rows;
    }

    public function getInformationLayoutId($information_id) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_layout'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_layout WHERE information_id = '" . (int)$information_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

        if ($query->num_rows) {
            return $query->row['layout_id'];
        } else {
            return 0;
        }
    }

    // Метод для увеличения счетчика просмотров
    public function updateViewed($information_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "information SET viewed = (viewed + 1) WHERE information_id = '" . (int)$information_id . "'");
    }

    // === ДОБАВЛЯЕМ МЕТОД ДЛЯ ПОЛУЧЕНИЯ ТЕГОВ СТАТЬИ ===
    public function getInformationTags($information_id) {
        // Проверяем существование таблиц
        $table_tag_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_tag'");
        $table_relation_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_information_to_tag'");
        
        if (!$table_tag_exists->num_rows || !$table_relation_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT t.tag_id, t.name FROM " . DB_PREFIX . "blog_tag t 
            LEFT JOIN " . DB_PREFIX . "blog_information_to_tag it ON t.tag_id = it.tag_id 
            WHERE it.information_id = '" . (int)$information_id . "' 
            AND t.status = 1 
            ORDER BY t.name ASC");

        return $query->rows;
    }

    // Методы для работы с блогом
    public function getInformationBlogCategories($information_id) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT blog_category_id FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$information_id . "'");
        
        $categories = array();
        foreach ($query->rows as $row) {
            $categories[] = $row['blog_category_id'];
        }
        
        return $categories;
    }

    public function getTotalInformationsByBlogCategory($blog_category_id) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        // Получаем все дочерние категории для текущей категории
        $child_categories = $this->getChildBlogCategoriesIds($blog_category_id);
        
        $sql = "SELECT COUNT(DISTINCT i.information_id) AS total FROM " . DB_PREFIX . "information i 
                LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc ON (i.information_id = i2bc.information_id) 
                WHERE (i2bc.blog_category_id = '" . (int)$blog_category_id . "'";
        
        // Добавляем дочерние категории, если они есть
        if (!empty($child_categories)) {
            $sql .= " OR i2bc.blog_category_id IN (" . implode(',', $child_categories) . ")";
        }
        
        $sql .= ") AND i.status = '1'";

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getInformationsByBlogCategory($data = array()) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $blog_category_id = (int)$data['blog_category_id'];
        
        // Получаем все дочерние категории для текущей категории
        $child_categories = $this->getChildBlogCategoriesIds($blog_category_id);
        
        // ИСПРАВЛЕНИЕ: Выбираем ВСЕ поля из information включая schema_type и rating_value
        $sql = "SELECT DISTINCT i.*, id.title, id.description FROM " . DB_PREFIX . "information i 
                LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
                LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc ON (i.information_id = i2bc.information_id) 
                WHERE (i2bc.blog_category_id = '" . $blog_category_id . "'";
        
        // Добавляем дочерние категории, если они есть
        if (!empty($child_categories)) {
            $sql .= " OR i2bc.blog_category_id IN (" . implode(',', $child_categories) . ")";
        }
        
        $sql .= ") AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                AND i.status = '1'";

        $sql .= " ORDER BY i.sort_order, id.title";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalBlogArticles() {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT COUNT(DISTINCT i.information_id) AS total FROM " . DB_PREFIX . "information i 
                                  LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc ON (i.information_id = i2bc.information_id) 
                                  WHERE i2bc.blog_category_id IS NOT NULL 
                                  AND i.status = '1'");

        return $query->row['total'];
    }

    public function getBlogArticles($data = array()) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        // ИСПРАВЛЕНИЕ: Выбираем ВСЕ поля из information включая schema_type и rating_value
        $sql = "SELECT DISTINCT i.*, id.title, id.description FROM " . DB_PREFIX . "information i 
                LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
                LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc ON (i.information_id = i2bc.information_id) 
                WHERE i2bc.blog_category_id IS NOT NULL 
                AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                AND i.status = '1'";

        $sql .= " ORDER BY i.sort_order, id.title";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // Метод для получения связанных статей
    public function getRelatedArticles($information_id, $limit = 5) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT i.*, id.title FROM " . DB_PREFIX . "information i 
                                  LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
                                  LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc1 ON (i.information_id = i2bc1.information_id) 
                                  WHERE i2bc1.blog_category_id IN (
                                      SELECT blog_category_id FROM " . DB_PREFIX . "information_to_blog_category 
                                      WHERE information_id = '" . (int)$information_id . "'
                                  ) 
                                  AND i.information_id != '" . (int)$information_id . "' 
                                  AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                  AND i.status = '1' 
                                  GROUP BY i.information_id 
                                  ORDER BY i.viewed DESC 
                                  LIMIT " . (int)$limit);

        return $query->rows;
    }

    // Метод для получения популярных статей
    public function getPopularArticles($limit = 5) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT i.*, id.title FROM " . DB_PREFIX . "information i 
                                  LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
                                  LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc ON (i.information_id = i2bc.information_id) 
                                  WHERE i2bc.blog_category_id IS NOT NULL 
                                  AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                  AND i.status = '1' 
                                  ORDER BY i.viewed DESC 
                                  LIMIT " . (int)$limit);

        return $query->rows;
    }

    // Метод для получения последних статей
    public function getLatestArticles($limit = 5) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT i.*, id.title FROM " . DB_PREFIX . "information i 
                                  LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
                                  LEFT JOIN " . DB_PREFIX . "information_to_blog_category i2bc ON (i.information_id = i2bc.information_id) 
                                  WHERE i2bc.blog_category_id IS NOT NULL 
                                  AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                  AND i.status = '1' 
                                  ORDER BY i.date_added DESC 
                                  LIMIT " . (int)$limit);

        return $query->rows;
    }

    // Новый метод: получает ID всех дочерних категорий для указанной категории
    private function getChildBlogCategoriesIds($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT blog_category_id FROM " . DB_PREFIX . "blog_category_path WHERE path_id = '" . (int)$blog_category_id . "' AND blog_category_id != '" . (int)$blog_category_id . "'");
        
        $child_categories = array();
        foreach ($query->rows as $row) {
            $child_categories[] = (int)$row['blog_category_id'];
        }
        
        return $child_categories;
    }

    // === ДОБАВЛЯЕМ МЕТОД ДЛЯ ПОЛУЧЕНИЯ АВТОРОВ СТАТЬИ ===
    public function getInformationAuthors($information_id) {
        // Проверяем существование таблицы information_to_author
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_author WHERE information_id = '" . (int)$information_id . "' ORDER BY is_primary DESC, sort_order ASC");
        return $query->rows;
    }
}
?>