<?php
class ModelCatalogAuthor extends Model {
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

        $query = $this->db->query("SELECT a.*, ad.name, ad.job_title, ad.bio, i2a.is_primary, i2a.sort_order 
                                  FROM " . DB_PREFIX . "information_to_author i2a 
                                  LEFT JOIN " . DB_PREFIX . "article_author a ON (i2a.author_id = a.author_id) 
                                  LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                                  WHERE i2a.information_id = '" . (int)$information_id . "'
                                  AND a.status = '1'
                                  AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
                                  ORDER BY i2a.is_primary DESC, i2a.sort_order ASC");

        return $query->rows;
    }

    // ДОБАВЛЕННЫЙ МЕТОД ДЛЯ СОВМЕСТИМОСТИ С КОНТРОЛЛЕРОМ BLOG_CATEGORY
    public function getAuthorsByInformationId($information_id) {
        return $this->getAuthorsByInformation($information_id);
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
                           a.company_employee, a.affiliation, a.knows_about, a.knows_language, a.same_as,
                           (SELECT COUNT(*) FROM " . DB_PREFIX . "information_to_author i2a 
                            WHERE i2a.author_id = a.author_id) as article_count
                    FROM " . DB_PREFIX . "article_author a 
                    LEFT JOIN " . DB_PREFIX . "article_author_description ad ON (a.author_id = ad.author_id) 
                    WHERE a.status = '1' 
                    AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        } else {
            // Если таблицы information_to_author нет, используем упрощенный запрос без подсчета статей
            $sql = "SELECT a.*, ad.name, ad.job_title, ad.bio, ad.description, 
                           a.company_employee, a.affiliation, a.knows_about, a.knows_language, a.same_as,
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

    // Новый метод для получения авторов с полными данными для микроразметки
    public function getAllAuthorsWithMicrodata($data = array()) {
        return $this->getAllAuthors($data);
    }
}
?>