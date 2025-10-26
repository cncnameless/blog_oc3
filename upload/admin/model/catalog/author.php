<?php
class ModelCatalogAuthor extends Model {
    public function addAuthor($data) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return false;
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "article_author SET image = '" . $this->db->escape($data['image']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_added = NOW(), date_modified = NOW()");

        $author_id = $this->db->getLastId();

        foreach ($data['author_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "article_author_description SET author_id = '" . (int)$author_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', job_title = '" . $this->db->escape($value['job_title']) . "', bio = '" . $this->db->escape($value['bio']) . "', social_links = '" . $this->db->escape(isset($value['social_links']) ? json_encode($value['social_links']) : '') . "'");
        }

        if (isset($data['author_store'])) {
            $store_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author_to_store'");
            if ($store_table_exists->num_rows) {
                foreach ($data['author_store'] as $store_id) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "article_author_to_store SET author_id = '" . (int)$author_id . "', store_id = '" . (int)$store_id . "'");
                }
            }
        }

        // SEO URL
        if (isset($data['author_seo_url'])) {
            foreach ($data['author_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'author_id=" . (int)$author_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        $this->cache->delete('author');

        return $author_id;
    }

    public function editAuthor($author_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "article_author SET image = '" . $this->db->escape($data['image']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE author_id = '" . (int)$author_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "article_author_description WHERE author_id = '" . (int)$author_id . "'");

        foreach ($data['author_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "article_author_description SET author_id = '" . (int)$author_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', job_title = '" . $this->db->escape($value['job_title']) . "', bio = '" . $this->db->escape($value['bio']) . "', social_links = '" . $this->db->escape(isset($value['social_links']) ? json_encode($value['social_links']) : '') . "'");
        }

        // Проверяем существование таблицы перед удалением
        $store_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author_to_store'");
        if ($store_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "article_author_to_store WHERE author_id = '" . (int)$author_id . "'");

            if (isset($data['author_store'])) {
                foreach ($data['author_store'] as $store_id) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "article_author_to_store SET author_id = '" . (int)$author_id . "', store_id = '" . (int)$store_id . "'");
                }
            }
        }

        // SEO URL
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'author_id=" . (int)$author_id . "'");

        if (isset($data['author_seo_url'])) {
            foreach ($data['author_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'author_id=" . (int)$author_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        $this->cache->delete('author');
    }

    public function deleteAuthor($author_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "article_author WHERE author_id = '" . (int)$author_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "article_author_description WHERE author_id = '" . (int)$author_id . "'");
        
        // Проверяем существование таблиц перед удалением
        $store_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author_to_store'");
        if ($store_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "article_author_to_store WHERE author_id = '" . (int)$author_id . "'");
        }
        
        $info_to_author_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if ($info_to_author_table_exists->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "information_to_author WHERE author_id = '" . (int)$author_id . "'");
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'author_id=" . (int)$author_id . "'");

        $this->cache->delete('author');
    }

    public function getAuthor($author_id) {
        // Проверяем существование таблиц
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "article_author a LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) WHERE a.author_id = '" . (int)$author_id . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getAuthors($data = array()) {
        // Проверяем существование таблиц
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $sql = "SELECT * FROM " . DB_PREFIX . "article_author a LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND ad.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_status'])) {
            $sql .= " AND a.status = '" . (int)$data['filter_status'] . "'";
        }

        $sql .= " GROUP BY a.author_id";

        $sort_data = array(
            'ad.name',
            'a.sort_order',
            'a.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY ad.name";
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

    public function getAuthorDescriptions($author_id) {
        $author_description_data = array();

        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author_description'");
        if (!$table_exists->num_rows) {
            return $author_description_data;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_author_description WHERE author_id = '" . (int)$author_id . "'");

        foreach ($query->rows as $result) {
            $author_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword'],
                'job_title'        => $result['job_title'],
                'bio'              => $result['bio'],
                'social_links'     => $result['social_links'] ? json_decode($result['social_links'], true) : array()
            );
        }

        return $author_description_data;
    }

    public function getAuthorStores($author_id) {
        $author_store_data = array();

        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author_to_store'");
        if (!$table_exists->num_rows) {
            return $author_store_data;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_author_to_store WHERE author_id = '" . (int)$author_id . "'");

        foreach ($query->rows as $result) {
            $author_store_data[] = $result['store_id'];
        }

        return $author_store_data;
    }

    public function getAuthorSeoUrls($author_id) {
        $author_seo_url_data = array();
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'author_id=" . (int)$author_id . "'");

        foreach ($query->rows as $result) {
            $author_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
        }

        return $author_seo_url_data;
    }

    public function getTotalAuthors() {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "article_author");

        return $query->row['total'];
    }

    public function getAuthorsByInformationId($information_id) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_author WHERE information_id = '" . (int)$information_id . "' ORDER BY is_primary DESC, sort_order ASC");

        return $query->rows;
    }

    // НОВЫЙ МЕТОД: Получить автора по keyword
    public function getAuthorByKeyword($keyword) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($keyword) . "' AND query LIKE 'author_id=%'");

        if ($query->num_rows) {
            $author_id = (int)str_replace('author_id=', '', $query->row['query']);
            return $this->getAuthor($author_id);
        }

        return false;
    }
}
?>