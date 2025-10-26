<?php
class ModelCatalogBlogCategory extends Model {
    public function getBlogCategory($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        
        if ($path_table_exists->num_rows) {
            $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR ' > ') FROM " . DB_PREFIX . "blog_category_path cp LEFT JOIN " . DB_PREFIX . "blog_category_description cd1 ON (cp.path_id = cd1.blog_category_id AND cp.blog_category_id != cp.path_id) WHERE cp.blog_category_id = c.blog_category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.blog_category_id) AS path FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd2 ON (c.blog_category_id = cd2.blog_category_id) WHERE c.blog_category_id = '" . (int)$blog_category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1'");
        } else {
            // Если таблицы blog_category_path нет, используем упрощенный запрос
            $query = $this->db->query("SELECT DISTINCT *, name AS path FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd2 ON (c.blog_category_id = cd2.blog_category_id) WHERE c.blog_category_id = '" . (int)$blog_category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1'");
        }

        return $query->row;
    }

    public function getBlogCategories($data = array()) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        
        if ($path_table_exists->num_rows) {
            $sql = "SELECT cp.blog_category_id AS blog_category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "blog_category_path cp LEFT JOIN " . DB_PREFIX . "blog_category c1 ON (cp.blog_category_id = c1.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category c2 ON (cp.path_id = c2.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_description cd1 ON (cp.path_id = cd1.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_description cd2 ON (cp.blog_category_id = cd2.blog_category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c1.status = '1'";
        } else {
            // Если таблицы blog_category_path нет, используем упрощенный запрос
            $sql = "SELECT c.blog_category_id, cd.name, c.parent_id, c.sort_order FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (c.blog_category_id = cd.blog_category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1'";
        }

        if (isset($data['parent_id'])) {
            $sql .= " AND c1.parent_id = '" . (int)$data['parent_id'] . "'";
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND cd2.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if ($path_table_exists->num_rows) {
            $sql .= " GROUP BY cp.blog_category_id";
        }

        $sql .= " ORDER BY name ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getBlogCategoryPath($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$path_table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT cp.path_id as blog_category_id, cd.name FROM " . DB_PREFIX . "blog_category_path cp LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (cp.path_id = cd.blog_category_id) WHERE cp.blog_category_id = '" . (int)$blog_category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cp.level ASC");

        return $query->rows;
    }

    public function getPathByBlogCategory($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$path_table_exists->num_rows) {
            return $blog_category_id;
        }

        $query = $this->db->query("SELECT GROUP_CONCAT(path_id ORDER BY level SEPARATOR '_') AS path FROM " . DB_PREFIX . "blog_category_path WHERE blog_category_id = '" . (int)$blog_category_id . "'");
        return $query->row['path'] ?? $blog_category_id;
    }

    // Новые методы для поддержки иерархии и хлебных крошек
    public function getBlogCategoryPaths($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$path_table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT cp.path_id as blog_category_id, cd.name FROM " . DB_PREFIX . "blog_category_path cp LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (cp.path_id = cd.blog_category_id) WHERE cp.blog_category_id = '" . (int)$blog_category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cp.level ASC");

        return $query->rows;
    }

    public function getBlogCategoriesByInformationId($information_id) {
        // Проверяем существование таблицы information_to_blog_category
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT bc.*, bcd.name FROM " . DB_PREFIX . "information_to_blog_category i2bc 
                                  LEFT JOIN " . DB_PREFIX . "blog_category bc ON (i2bc.blog_category_id = bc.blog_category_id) 
                                  LEFT JOIN " . DB_PREFIX . "blog_category_description bcd ON (bc.blog_category_id = bcd.blog_category_id) 
                                  WHERE i2bc.information_id = '" . (int)$information_id . "' 
                                  AND bcd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        
        return $query->rows;
    }

    public function getBlogCategoryLayoutId($blog_category_id) {
        // Проверяем существование таблицы blog_category_to_layout
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_to_layout'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category_to_layout WHERE blog_category_id = '" . (int)$blog_category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

        if ($query->num_rows) {
            return $query->row['layout_id'];
        } else {
            return 0;
        }
    }

    public function getTotalBlogCategories() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category WHERE status = '1'");

        return $query->row['total'];
    }

    public function getTotalBlogCategoriesByLayoutId($layout_id) {
        // Проверяем существование таблицы blog_category_to_layout
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_to_layout'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

        return $query->row['total'];
    }

    // Метод для получения дочерних категорий
    public function getChildBlogCategories($parent_id = 0) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category c 
                                  LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (c.blog_category_id = cd.blog_category_id) 
                                  WHERE c.parent_id = '" . (int)$parent_id . "' 
                                  AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                  AND c.status = '1' 
                                  ORDER BY c.sort_order, cd.name");

        return $query->rows;
    }

    // Метод для проверки, является ли категория дочерней
    public function isChildBlogCategory($blog_category_id, $parent_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$path_table_exists->num_rows) {
            // Если таблицы нет, проверяем прямую связь через parent_id
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category 
                                      WHERE blog_category_id = '" . (int)$blog_category_id . "' 
                                      AND parent_id = '" . (int)$parent_id . "'");
            return $query->row['total'] > 0;
        }

        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category_path 
                                  WHERE blog_category_id = '" . (int)$blog_category_id . "' 
                                  AND path_id = '" . (int)$parent_id . "'");

        return $query->row['total'] > 0;
    }

    // Метод для получения полного пути категории (включая все родительские)
    public function getFullBlogCategoryPath($blog_category_id) {
        $blog_category_path = array();
        
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$path_table_exists->num_rows) {
            // Если таблицы нет, возвращаем только текущую категорию
            $query = $this->db->query("SELECT blog_category_id FROM " . DB_PREFIX . "blog_category WHERE blog_category_id = '" . (int)$blog_category_id . "'");
            if ($query->num_rows) {
                $blog_category_path[] = $blog_category_id;
            }
            return $blog_category_path;
        }
        
        // Получаем все пути для этой категории
        $query = $this->db->query("SELECT path_id FROM " . DB_PREFIX . "blog_category_path 
                                  WHERE blog_category_id = '" . (int)$blog_category_id . "' 
                                  ORDER BY level ASC");
        
        foreach ($query->rows as $result) {
            $blog_category_path[] = $result['path_id'];
        }
        
        return $blog_category_path;
    }
}
?>