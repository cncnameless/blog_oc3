<?php
class ModelCatalogInformation extends Model {
    public function addInformation($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "information SET 
            sort_order = '" . (int)$data['sort_order'] . "', 
            bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', 
            status = '" . (int)$data['status'] . "', 
            no_index = '" . (int)$data['no_index'] . "',
            date_added = '" . $this->db->escape($data['date_added']) . "',
            schema_type = '" . $this->db->escape($data['schema_type']) . "',
            rating_value = " . (isset($data['rating_value']) && $data['rating_value'] !== '' ? "'" . (float)$data['rating_value'] . "'" : "NULL"));

        $information_id = $this->db->getLastId();

        foreach ($data['information_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "information_description SET 
                information_id = '" . (int)$information_id . "', 
                language_id = '" . (int)$language_id . "', 
                title = '" . $this->db->escape($value['title']) . "', 
                description = '" . $this->db->escape($value['description']) . "', 
                meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', 
                meta_title = '" . $this->db->escape($value['meta_title']) . "', 
                meta_description = '" . $this->db->escape($value['meta_description']) . "', 
                meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        if (isset($data['information_store'])) {
            foreach ($data['information_store'] as $store_id) {
                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "information_to_store SET 
                    information_id = '" . (int)$information_id . "', 
                    store_id = '" . (int)$store_id . "'");
            }
        }

        // Проверяем существование таблицы перед работой с ней
        $blog_category_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if ($blog_category_table_exists->num_rows && isset($data['information_blog_category'])) {
            foreach ($data['information_blog_category'] as $blog_category_id) {
                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "information_to_blog_category SET 
                    information_id = '" . (int)$information_id . "', 
                    blog_category_id = '" . (int)$blog_category_id . "'");
            }
        }

        // === ДОБАВЛЯЕМ СОХРАНЕНИЕ АВТОРОВ ===
        // Проверяем существование таблицы перед работой с ней
        $author_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if ($author_table_exists->num_rows && isset($data['information_author'])) {
            foreach ($data['information_author'] as $author) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "information_to_author SET 
                    information_id = '" . (int)$information_id . "', 
                    author_id = '" . (int)$author['author_id'] . "', 
                    sort_order = '" . (int)$author['sort_order'] . "',
                    is_primary = '" . (int)$author['is_primary'] . "'");
            }
        }
        // === КОНЕЦ БЛОКА АВТОРОВ ===

        // SEO URL
        if (isset($data['information_seo_url'])) {
            foreach ($data['information_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET 
                            store_id = '" . (int)$store_id . "', 
                            language_id = '" . (int)$language_id . "', 
                            query = 'information_id=" . (int)$information_id . "', 
                            keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }
        
        if (isset($data['information_layout'])) {
            foreach ($data['information_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "information_to_layout SET 
                    information_id = '" . (int)$information_id . "', 
                    store_id = '" . (int)$store_id . "', 
                    layout_id = '" . (int)$layout_id . "'");
            }
        }

        $this->cache->delete('information');

        return $information_id;
    }

    public function editInformation($information_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "information SET 
            sort_order = '" . (int)$data['sort_order'] . "', 
            bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', 
            status = '" . (int)$data['status'] . "', 
            no_index = '" . (int)$data['no_index'] . "',
            date_added = '" . $this->db->escape($data['date_added']) . "',
            schema_type = '" . $this->db->escape($data['schema_type']) . "',
            rating_value = " . (isset($data['rating_value']) && $data['rating_value'] !== '' ? "'" . (float)$data['rating_value'] . "'" : "NULL") . ",
            date_modified = NOW() 
            WHERE information_id = '" . (int)$information_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "information_description WHERE information_id = '" . (int)$information_id . "'");

        foreach ($data['information_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "information_description SET 
                information_id = '" . (int)$information_id . "', 
                language_id = '" . (int)$language_id . "', 
                title = '" . $this->db->escape($value['title']) . "', 
                description = '" . $this->db->escape($value['description']) . "', 
                meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', 
                meta_title = '" . $this->db->escape($value['meta_title']) . "', 
                meta_description = '" . $this->db->escape($value['meta_description']) . "', 
                meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "information_to_store WHERE information_id = '" . (int)$information_id . "'");

        // Проверяем существование таблицы перед удалением
        $blog_category_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if ($blog_category_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$information_id . "'");
        }

        // === ДОБАВЛЯЕМ УДАЛЕНИЕ СТАРЫХ АВТОРОВ ===
        // Проверяем существование таблицы перед удалением
        $author_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if ($author_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "information_to_author WHERE information_id = '" . (int)$information_id . "'");
        }
        // === КОНЕЦ БЛОКА ===

        if (isset($data['information_store'])) {
            foreach ($data['information_store'] as $store_id) {
                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "information_to_store SET 
                    information_id = '" . (int)$information_id . "', 
                    store_id = '" . (int)$store_id . "'");
            }
        }

        if ($blog_category_table_exists->num_rows && isset($data['information_blog_category'])) {
            foreach ($data['information_blog_category'] as $blog_category_id) {
                $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "information_to_blog_category SET 
                    information_id = '" . (int)$information_id . "', 
                    blog_category_id = '" . (int)$blog_category_id . "'");
            }
        }

        // === ДОБАВЛЯЕМ СОХРАНЕНИЕ НОВЫХ АВТОРОВ ===
        if ($author_table_exists->num_rows && isset($data['information_author'])) {
            foreach ($data['information_author'] as $author) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "information_to_author SET 
                    information_id = '" . (int)$information_id . "', 
                    author_id = '" . (int)$author['author_id'] . "', 
                    sort_order = '" . (int)$author['sort_order'] . "',
                    is_primary = '" . (int)$author['is_primary'] . "'");
            }
        }
        // === КОНЕЦ БЛОКА ===

        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$information_id . "'");

        if (isset($data['information_seo_url'])) {
            foreach ($data['information_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (trim($keyword)) {
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET 
                            store_id = '" . (int)$store_id . "', 
                            language_id = '" . (int)$language_id . "', 
                            query = 'information_id=" . (int)$information_id . "', 
                            keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "information_to_layout` WHERE information_id = '" . (int)$information_id . "'");

        if (isset($data['information_layout'])) {
            foreach ($data['information_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "information_to_layout` SET 
                    information_id = '" . (int)$information_id . "', 
                    store_id = '" . (int)$store_id . "', 
                    layout_id = '" . (int)$layout_id . "'");
            }
        }

        $this->cache->delete('information');
    }

    public function deleteInformation($information_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "information` WHERE information_id = '" . (int)$information_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "information_description` WHERE information_id = '" . (int)$information_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "information_to_store` WHERE information_id = '" . (int)$information_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "information_to_layout` WHERE information_id = '" . (int)$information_id . "'");
        
        // Проверяем существование таблиц перед удалением
        $blog_category_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if ($blog_category_table_exists->num_rows) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "information_to_blog_category` WHERE information_id = '" . (int)$information_id . "'");
        }
        
        // === ДОБАВЛЯЕМ УДАЛЕНИЕ АВТОРОВ ===
        $author_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if ($author_table_exists->num_rows) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "information_to_author` WHERE information_id = '" . (int)$information_id . "'");
        }
        // === КОНЕЦ БЛОКА ===
        
        $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'information_id=" . (int)$information_id . "'");

        $this->cache->delete('information');
    }

    public function getInformation($information_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "information WHERE information_id = '" . (int)$information_id . "'");

        return $query->row;
    }

    public function getInformations($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND id.title LIKE '" . $this->db->escape('%' . $data['filter_name'] . '%') . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND i.status = '" . (int)$data['filter_status'] . "'";
        }

        // Проверяем существование таблицы перед использованием в подзапросе
        $blog_category_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if ($blog_category_table_exists->num_rows && !empty($data['filter_blog_category'])) {
            $sql .= " AND i.information_id IN (SELECT information_id FROM " . DB_PREFIX . "information_to_blog_category WHERE blog_category_id = '" . (int)$data['filter_blog_category'] . "')";
        }

        $sort_data = array(
            'id.title',
            'i.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY id.title";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if (!isset($data['start']) || $data['start'] < 0) {
                $data['start'] = 0;
            }

            if (!isset($data['limit']) || $data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getInformationDescriptions($information_id) {
        $information_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_description WHERE information_id = '" . (int)$information_id . "'");

        foreach ($query->rows as $result) {
            $information_description_data[$result['language_id']] = array(
                'title'            => $result['title'],
                'description'      => $result['description'],
                'meta_h1'          => $result['meta_h1'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword']
            );
        }

        return $information_description_data;
    }

    public function getInformationStores($information_id) {
        $information_store_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_store WHERE information_id = '" . (int)$information_id . "'");

        foreach ($query->rows as $result) {
            $information_store_data[] = $result['store_id'];
        }

        return $information_store_data;
    }

    public function getInformationSeoUrls($information_id) {
        $information_seo_url_data = array();
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$information_id . "'");

        foreach ($query->rows as $result) {
            $information_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
        }

        return $information_seo_url_data;
    }

    public function getInformationLayouts($information_id) {
        $information_layout_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_layout WHERE information_id = '" . (int)$information_id . "'");

        foreach ($query->rows as $result) {
            $information_layout_data[$result['store_id']] = $result['layout_id'];
        }

        return $information_layout_data;
    }

    public function getTotalInformations($data = array()) {
        $sql = "SELECT COUNT(DISTINCT i.information_id) AS total FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND id.title LIKE '" . $this->db->escape('%' . $data['filter_name'] . '%') . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND i.status = '" . (int)$data['filter_status'] . "'";
        }

        // Проверяем существование таблицы перед использованием в подзапросе
        $blog_category_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_blog_category'");
        if ($blog_category_table_exists->num_rows && !empty($data['filter_blog_category'])) {
            $sql .= " AND i.information_id IN (SELECT information_id FROM " . DB_PREFIX . "information_to_blog_category WHERE blog_category_id = '" . (int)$data['filter_blog_category'] . "')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getTotalInformationsByLayoutId($layout_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "information_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

        return $query->row['total'];
    }

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

    // === ДОБАВЛЯЕМ НОВЫЙ МЕТОД ДЛЯ ПОЛУЧЕНИЯ АВТОРОВ СТАТЬИ ===
    public function getInformationAuthors($information_id) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_author WHERE information_id = '" . (int)$information_id . "' ORDER BY is_primary DESC, sort_order ASC");
        return $query->rows;
    }

    // Добавляем эти методы в конец класса ModelCatalogInformation

public function getBlogArticles($data = array()) {
    $sql = "SELECT * FROM " . DB_PREFIX . "information i 
            LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
            WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND i.status = '1'";

    $sql .= " ORDER BY i.date_added DESC";

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
    $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "information WHERE status = '1'");
    return $query->row['total'];
}
}
?>