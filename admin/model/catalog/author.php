<?php
class ModelCatalogAuthor extends Model {
    public function addAuthor($data) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return false;
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "article_author SET 
            image = '" . $this->db->escape($data['image']) . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            status = '" . (int)$data['status'] . "',
            company_employee = '" . (int)$data['company_employee'] . "',
            affiliation = '" . $this->db->escape($data['affiliation']) . "',
            knows_about = '" . $this->db->escape($data['knows_about']) . "',
            knows_language = '" . $this->db->escape($data['knows_language']) . "',
            date_added = NOW(), 
            date_modified = NOW()");

        $author_id = $this->db->getLastId();

        foreach ($data['author_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "article_author_description SET 
                author_id = '" . (int)$author_id . "', 
                language_id = '" . (int)$language_id . "', 
                name = '" . $this->db->escape($value['name']) . "', 
                description = '" . $this->db->escape($value['description']) . "', 
                meta_title = '" . $this->db->escape($value['meta_title']) . "', 
                meta_description = '" . $this->db->escape($value['meta_description']) . "', 
                meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', 
                job_title = '" . $this->db->escape($value['job_title']) . "', 
                bio = '" . $this->db->escape($value['bio']) . "', 
                social_links = '" . $this->db->escape(isset($value['social_links']) ? json_encode($value['social_links']) : '') . "'");
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
        $this->db->query("UPDATE " . DB_PREFIX . "article_author SET 
            image = '" . $this->db->escape($data['image']) . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            status = '" . (int)$data['status'] . "',
            company_employee = '" . (int)$data['company_employee'] . "',
            affiliation = '" . $this->db->escape($data['affiliation']) . "',
            knows_about = '" . $this->db->escape($data['knows_about']) . "',
            knows_language = '" . $this->db->escape($data['knows_language']) . "',
            date_modified = NOW() 
            WHERE author_id = '" . (int)$author_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "article_author_description WHERE author_id = '" . (int)$author_id . "'");

        foreach ($data['author_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "article_author_description SET 
                author_id = '" . (int)$author_id . "', 
                language_id = '" . (int)$language_id . "', 
                name = '" . $this->db->escape($value['name']) . "', 
                description = '" . $this->db->escape($value['description']) . "', 
                meta_title = '" . $this->db->escape($value['meta_title']) . "', 
                meta_description = '" . $this->db->escape($value['meta_description']) . "', 
                meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', 
                job_title = '" . $this->db->escape($value['job_title']) . "', 
                bio = '" . $this->db->escape($value['bio']) . "', 
                social_links = '" . $this->db->escape(isset($value['social_links']) ? json_encode($value['social_links']) : '') . "'");
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

        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "article_author a 
                                  LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                                  WHERE a.author_id = '" . (int)$author_id . "' 
                                  AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
                                  AND a.status = '1'");

        return $query->row;
    }

    public function getAuthors($data = array()) {
        // Проверяем существование таблиц
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $sql = "SELECT * FROM " . DB_PREFIX . "article_author a 
                LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                WHERE a.status = '1' 
                AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND ad.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        $sql .= " ORDER BY a.sort_order, ad.name";

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

    public function getArticlesByAuthor($data = array()) {
        // Проверяем существование таблицы information_to_author
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $sql = "SELECT i.*, id.title, id.description, i.image 
                FROM " . DB_PREFIX . "information i 
                LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
                LEFT JOIN " . DB_PREFIX . "information_to_author i2a ON (i.information_id = i2a.information_id) 
                WHERE i2a.author_id = '" . (int)$data['filter_author_id'] . "' 
                AND i.status = '1' 
                AND id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

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

    public function getTotalArticlesByAuthor($author_id) {
        // Проверяем существование таблицы information_to_author
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT COUNT(DISTINCT i.information_id) AS total 
                                  FROM " . DB_PREFIX . "information i 
                                  LEFT JOIN " . DB_PREFIX . "information_to_author i2a ON (i.information_id = i2a.information_id) 
                                  WHERE i2a.author_id = '" . (int)$author_id . "' 
                                  AND i.status = '1'");

        return $query->row['total'];
    }

    public function getAuthorsByInformation($information_id) {
        // Проверяем существование таблицы information_to_author
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        $query = $this->db->query("SELECT a.*, ad.name, ad.job_title, ad.bio, i2a.sort_order 
                                  FROM " . DB_PREFIX . "information_to_author i2a 
                                  LEFT JOIN " . DB_PREFIX . "article_author a ON (i2a.author_id = a.author_id) 
                                  LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                                  WHERE i2a.information_id = '" . (int)$information_id . "'
                                  AND a.status = '1'
                                  AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
                                  ORDER BY i2a.sort_order ASC");

        return $query->rows;
    }

    public function getAllAuthors($data = array()) {
        // Проверяем существование таблиц
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return array();
        }

        // Проверяем существование таблицы information_to_author для подзапроса
        $info_to_author_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
        
        if ($info_to_author_exists->num_rows) {
            $sql = "SELECT a.*, ad.name, ad.job_title, ad.bio, ad.description,
                           a.company_employee, a.affiliation, a.knows_about, a.knows_language,
                           (SELECT COUNT(*) FROM " . DB_PREFIX . "information_to_author i2a 
                            LEFT JOIN " . DB_PREFIX . "information i ON (i2a.information_id = i.information_id)
                            WHERE i2a.author_id = a.author_id AND i.status = '1') as article_count
                    FROM " . DB_PREFIX . "article_author a 
                    LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                    WHERE a.status = '1' 
                    AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        } else {
            // Если таблицы information_to_author нет, используем упрощенный запрос без подсчета статей
            $sql = "SELECT a.*, ad.name, ad.job_title, ad.bio, ad.description, 
                           a.company_employee, a.affiliation, a.knows_about, a.knows_language,
                           0 as article_count
                    FROM " . DB_PREFIX . "article_author a 
                    LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                    WHERE a.status = '1' 
                    AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        }

        $sql .= " ORDER BY a.sort_order, ad.name";

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

    public function getTotalAuthors() {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return 0;
        }

        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "article_author WHERE status = '1'");
        return $query->row['total'];
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
}
?>