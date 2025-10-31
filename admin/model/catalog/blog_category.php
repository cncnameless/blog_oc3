<?php
class ModelCatalogBlogCategory extends Model {
    public function addBlogCategory($data) {
        // Сначала проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category'");
        if (!$table_exists->num_rows) {
            return false;
        }

        // ДОБАВЛЕНО: Сохранение изображения
        $this->db->query("INSERT INTO " . DB_PREFIX . "blog_category SET 
            parent_id = '" . (int)$data['parent_id'] . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            status = '" . (int)$data['status'] . "', 
            image = '" . $this->db->escape($data['image']) . "', 
            date_added = NOW(), 
            date_modified = NOW()");

        $blog_category_id = $this->db->getLastId();

        foreach ($data['blog_category_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "blog_category_description SET 
                blog_category_id = '" . (int)$blog_category_id . "', 
                language_id = '" . (int)$language_id . "', 
                name = '" . $this->db->escape($value['name']) . "', 
                description = '" . $this->db->escape($value['description']) . "', 
                meta_title = '" . $this->db->escape($value['meta_title']) . "', 
                meta_description = '" . $this->db->escape($value['meta_description']) . "', 
                meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        // MySQL Hierarchical Data Closure Table Pattern
        $level = 0;

        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if ($path_table_exists->num_rows) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_category_path` WHERE blog_category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");

            foreach ($query->rows as $result) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "blog_category_path` SET 
                    `blog_category_id` = '" . (int)$blog_category_id . "', 
                    `path_id` = '" . (int)$result['path_id'] . "', 
                    `level` = '" . (int)$level . "'");
                $level++;
            }

            $this->db->query("INSERT INTO `" . DB_PREFIX . "blog_category_path` SET 
                `blog_category_id` = '" . (int)$blog_category_id . "', 
                `path_id` = '" . (int)$blog_category_id . "', 
                `level` = '" . (int)$level . "'");
        }

        // Добавляем SEO URL
        if (isset($data['blog_category_seo_url'])) {
            foreach ($data['blog_category_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET 
                            store_id = '" . (int)$store_id . "', 
                            language_id = '" . (int)$language_id . "', 
                            query = 'blog_category_id=" . (int)$blog_category_id . "', 
                            keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        $this->cache->delete('blog_category');

        return $blog_category_id;
    }

    public function editBlogCategory($blog_category_id, $data) {
        // ДОБАВЛЕНО: Обновление изображения
        $this->db->query("UPDATE " . DB_PREFIX . "blog_category SET 
            parent_id = '" . (int)$data['parent_id'] . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            status = '" . (int)$data['status'] . "', 
            image = '" . $this->db->escape($data['image']) . "', 
            date_modified = NOW() 
            WHERE blog_category_id = '" . (int)$blog_category_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_category_description WHERE blog_category_id = '" . (int)$blog_category_id . "'");

        foreach ($data['blog_category_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "blog_category_description SET 
                blog_category_id = '" . (int)$blog_category_id . "', 
                language_id = '" . (int)$language_id . "', 
                name = '" . $this->db->escape($value['name']) . "', 
                description = '" . $this->db->escape($value['description']) . "', 
                meta_title = '" . $this->db->escape($value['meta_title']) . "', 
                meta_description = '" . $this->db->escape($value['meta_description']) . "', 
                meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        // Очищаем старые SEO URL
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_category_id=" . (int)$blog_category_id . "'");

        // Добавляем новые SEO URL
        if (isset($data['blog_category_seo_url'])) {
            foreach ($data['blog_category_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET 
                            store_id = '" . (int)$store_id . "', 
                            language_id = '" . (int)$language_id . "', 
                            query = 'blog_category_id=" . (int)$blog_category_id . "', 
                            keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if ($path_table_exists->num_rows) {
            // MySQL Hierarchical Data Closure Table Pattern
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_category_path` WHERE path_id = '" . (int)$blog_category_id . "' ORDER BY level ASC");

            if ($query->rows) {
                foreach ($query->rows as $blog_category_path) {
                    // Delete the path below the current one
                    $this->db->query("DELETE FROM `" . DB_PREFIX . "blog_category_path` WHERE blog_category_id = '" . (int)$blog_category_path['blog_category_id'] . "' AND level < '" . (int)$blog_category_path['level'] . "'");
                }

                $path = array();

                // Get the nodes new parents
                $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_category_path` WHERE blog_category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

                foreach ($query->rows as $result) {
                    $path[] = $result['path_id'];
                }

                // Get whats left of the nodes current path
                $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_category_path` WHERE blog_category_id = '" . (int)$blog_category_id . "' ORDER BY level ASC");

                foreach ($query->rows as $result) {
                    $path[] = $result['path_id'];
                }

                // Combine the paths with a new level
                $level = 0;

                foreach ($path as $path_id) {
                    $this->db->query("REPLACE INTO `" . DB_PREFIX . "blog_category_path` SET 
                        blog_category_id = '" . (int)$blog_category_id . "', 
                        `path_id` = '" . (int)$path_id . "', 
                        level = '" . (int)$level . "'");
                    $level++;
                }
            } else {
                // Delete the path below the current one
                $this->db->query("DELETE FROM `" . DB_PREFIX . "blog_category_path` WHERE blog_category_id = '" . (int)$blog_category_id . "'");

                // Build the new path
                $path = array();

                $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_category_path` WHERE blog_category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

                foreach ($query->rows as $result) {
                    $path[] = $result['path_id'];
                }

                $path[] = $blog_category_id;

                $level = 0;

                foreach ($path as $path_id) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "blog_category_path` SET 
                        blog_category_id = '" . (int)$blog_category_id . "', 
                        `path_id` = '" . (int)$path_id . "', 
                        level = '" . (int)$level . "'");
                    $level++;
                }
            }
        }

        $this->cache->delete('blog_category');
    }

    public function deleteBlogCategory($blog_category_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_category WHERE blog_category_id = '" . (int)$blog_category_id . "'");
        
        // Проверяем существование таблиц перед удалением
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if ($path_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "blog_category_path WHERE blog_category_id = '" . (int)$blog_category_id . "'");
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_category_description WHERE blog_category_id = '" . (int)$blog_category_id . "'");
        
        $info_to_blog_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if ($info_to_blog_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "information_to_blog_category WHERE blog_category_id = '" . (int)$blog_category_id . "'");
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_category_id=" . (int)$blog_category_id . "'");

        $this->cache->delete('blog_category');
    }

    public function getBlogCategory($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        
        if ($path_table_exists->num_rows) {
            $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR ' > ') FROM " . DB_PREFIX . "blog_category_path cp LEFT JOIN " . DB_PREFIX . "blog_category_description cd1 ON (cp.path_id = cd1.blog_category_id AND cp.blog_category_id != cp.path_id) WHERE cp.blog_category_id = c.blog_category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.blog_category_id) AS path FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (c.blog_category_id = cd.blog_category_id) WHERE c.blog_category_id = '" . (int)$blog_category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        } else {
            // Если таблицы blog_category_path нет, используем упрощенный запрос
            $query = $this->db->query("SELECT DISTINCT *, name AS path FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (c.blog_category_id = cd.blog_category_id) WHERE c.blog_category_id = '" . (int)$blog_category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        }

        return $query->row;
    }

    public function getBlogCategories($data = array()) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        
        if ($path_table_exists->num_rows) {
            $sql = "SELECT cp.blog_category_id AS blog_category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "blog_category_path cp LEFT JOIN " . DB_PREFIX . "blog_category c1 ON (cp.blog_category_id = c1.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category c2 ON (cp.path_id = c2.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_description cd1 ON (cp.path_id = cd1.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_description cd2 ON (cp.blog_category_id = cd2.blog_category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        } else {
            // Если таблицы blog_category_path нет, используем упрощенный запрос
            $sql = "SELECT c.blog_category_id, cd.name, c.parent_id, c.sort_order FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (c.blog_category_id = cd.blog_category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        }

        if (isset($data['filter_name']) && !is_null($data['filter_name'])) {
            $sql .= " AND cd2.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if ($path_table_exists->num_rows) {
            $sql .= " GROUP BY cp.blog_category_id";
        }

        $sort_data = array(
            'name',
            'sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

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

    public function getBlogCategoryDescriptions($blog_category_id) {
        $blog_category_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category_description WHERE blog_category_id = '" . (int)$blog_category_id . "'");

        foreach ($query->rows as $result) {
            $blog_category_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword']
            );
        }

        return $blog_category_description_data;
    }

    public function getBlogCategorySeoUrls($blog_category_id) {
        $blog_category_seo_url_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_category_id=" . (int)$blog_category_id . "'");

        foreach ($query->rows as $result) {
            $blog_category_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
        }

        return $blog_category_seo_url_data;
    }

    public function getTotalBlogCategories() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category");

        return $query->row['total'];
    }

    public function getInformationBlogCategories($information_id) {
        $blog_category_data = array();

        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if (!$table_exists->num_rows) {
            return $blog_category_data;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$information_id . "'");

        foreach ($query->rows as $result) {
            $blog_category_data[] = $result['blog_category_id'];
        }

        return $blog_category_data;
    }

    public function getBlogCategoryPath($blog_category_id) {
        // Проверяем существование таблицы blog_category_path
        $path_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category_path'");
        if (!$path_table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT bcp.path_id as blog_category_id, bcd.name FROM " . DB_PREFIX . "blog_category_path bcp LEFT JOIN " . DB_PREFIX . "blog_category_description bcd ON (bcp.path_id = bcd.blog_category_id) WHERE bcp.blog_category_id = '" . (int)$blog_category_id . "' AND bcd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bcp.level ASC");

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
}
?>