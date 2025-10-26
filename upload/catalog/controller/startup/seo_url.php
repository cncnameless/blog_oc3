<?php
class ControllerStartupSeoUrl extends Controller {
    private $postfix = 'html';
    private $postfix_route = ['product/product', 'information/information'];
    private $enable_postfix = false;
    private $enable_slash = false;
    private $mode = 0;

    public function __construct($registry) {
        parent::__construct($registry);
        
        $this->enable_postfix = $this->config->get('config_seo_url_postfix');
        $this->enable_slash = $this->config->get('config_seo_url_slash');
        $this->mode = (int)$this->config->get('config_seo_url_mode');
    }

    public function index() {
        if ($this->config->get('config_seo_url')) {
            $this->url->addRewrite($this);
        }

        if (isset($this->request->get['_route_'])) {
            $route = $this->request->get['_route_'];
            $this->request->get['_route_'] = utf8_strtolower($route);
          
            $parts = explode('/', $this->request->get['_route_']);
            $parts = array_filter(array_map('trim', $parts));
            $parts = array_values($parts);

            error_log("=== SEO URL PROCESSING ===");
            error_log("Original _route_: " . $route);
            error_log("URL Parts: " . print_r($parts, true));

            // Обработка страницы списка авторов: /blog/authors
            if (count($parts) == 2 && $parts[0] == 'blog' && $parts[1] == 'authors') {
                error_log("Detected authors list page: /blog/authors");
                $this->request->get['route'] = 'information/blog_category';
                $this->request->get['authors_page'] = 1;
                return;
            }
            // Обработка отдельных авторов: /blog/authors/author-slug
            elseif (count($parts) == 3 && $parts[0] == 'blog' && $parts[1] == 'authors') {
                $author_keyword = $parts[2];
                error_log("Detected author page: /blog/authors/" . $author_keyword);
                $this->processAuthorUrl($author_keyword);
                return;
            }
            // Обработка блога
            else if (count($parts) > 0 && $parts[0] == 'blog') {
                error_log("Processing blog URL");
                array_shift($parts); // Убираем 'blog'
                
                if (empty($parts)) {
                    $this->request->get['route'] = 'information/blog_category';
                } else {
                    $this->processBlogUrl($parts);
                }
            }
            // Стандартная обработка
            else if (!empty($parts)) {
                error_log("Processing standard URL");
                $this->processStandardUrl($parts);
            }

            error_log("Final route set to: " . ($this->request->get['route'] ?? 'NOT SET'));
        }

        if (isset($this->request->get['route']) && $this->request->get['route'] == 'information/authors') {
            error_log("Direct call to information/authors detected");
        }

        if (empty($this->request->get['route'])) {
            $this->request->get['route'] = 'common/home';
        }
        
        // Отключаем редиректы для AJAX и POST запросов
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            return;
        }
        
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && utf8_strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return;
        }
        
        // Не редиректим блог, статьи и авторов
        if (isset($this->request->get['route']) && in_array($this->request->get['route'], ['information/blog_category', 'information/information', 'information/author', 'information/authors'])) {
            return;
        }
        
        // Стандартная логика редиректа
        if ($this->config->get('config_secure')) {
            $original_url = $this->config->get('config_ssl');
        } else {
            $original_url = $this->config->get('config_url');
        }
        
        $original_request = $this->request->server['REQUEST_URI'];
        
        $path = parse_url($original_url, PHP_URL_PATH);
        if ($path && strpos($original_request, $path) === 0) {
            $original_request = utf8_substr($original_request, utf8_strlen($path));
        }
        
        $original_url .= ltrim($original_request, '/');
      
        $params = array();
        foreach ($this->request->get as $key => $value) {
            if (in_array($key, ['route', 'blog_path'])) {
                continue;
            }
            
            $params[$key] = is_array($value) ? $value : html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($this->request->get['route']) && $this->request->get['route'] != 'error/not_found') {
            $seo_url = $this->url->link($this->request->get['route'], http_build_query($params), $this->config->get('config_secure'));

            if (rawurldecode($original_url) != rawurldecode($seo_url)) {
                $this->response->redirect($seo_url, 301);
            }
        }
    }

    /**
     * Обработка URL автора
     */
    private function processAuthorUrl($keyword) {
        error_log("=== PROCESSING AUTHOR URL ===");
        error_log("Author keyword: " . $keyword);
        
        $store_id = (int)$this->config->get('config_store_id');
        $language_id = (int)$this->config->get('config_language_id');
        
        error_log("Store ID: " . $store_id . ", Language ID: " . $language_id);
        
        // Ищем SEO URL с учетом store_id и language_id
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($keyword) . "' 
                                  AND (store_id = '" . $store_id . "' OR store_id = 0) 
                                  AND language_id = '" . $language_id . "' 
                                  ORDER BY store_id DESC LIMIT 1");
        
        if ($query->num_rows) {
            $url_query = $query->row['query'];
            error_log("Found SEO URL record: " . $url_query);
            
            if (strpos($url_query, 'author_id=') === 0) {
                $author_id = (int)str_replace('author_id=', '', $url_query);
                error_log("Extracted author ID: " . $author_id);
                
                // Проверяем, что автор существует и активен
                $this->load->model('catalog/author');
                $author_check = $this->model_catalog_author->getAuthor($author_id);
                if ($author_check) {
                    error_log("Author exists and is active");
                    $this->request->get['author_id'] = $author_id;
                    $this->request->get['route'] = 'information/author';
                    return true;
                } else {
                    error_log("Author not found or inactive in database");
                }
            } else {
                error_log("Unexpected query format: " . $url_query);
            }
        } else {
            error_log("SEO URL not found for keyword: " . $keyword);
        }
        
        // Если не нашли автора - 404
        error_log("Setting route to error/not_found");
        $this->request->get['route'] = 'error/not_found';
        return false;
    }

    public function rewrite($link) {
        $url_info = parse_url(str_replace('&amp;', '&', $link));

        $url = '';

        $data = array();

        parse_str($url_info['query'], $data);

        $route = isset($data['route']) ? $data['route'] : '';

        unset($data['route']);

        error_log("=== SEO URL REWRITE ===");
        error_log("Original link: " . $link);
        error_log("Route to rewrite: " . $route);
        error_log("Data parameters: " . print_r($data, true));

        // Обработка страницы списка авторов
        if ($route == 'information/blog_category' && isset($data['authors_page'])) {
            $url = '/blog/authors';
            error_log("Rewriting authors page to: " . $url);
            unset($data['authors_page']);
        }
        // Обработка авторов
        elseif ($route == 'information/author') {
            if (isset($data['author_id'])) {
                $author_id = (int)$data['author_id'];
                $keyword = $this->getKeyword('author_id=' . $author_id);
                if ($keyword) {
                    $url = '/blog/authors/' . $keyword;
                    error_log("Rewriting author page to: " . $url);
                } else {
                    // Если нет SEO URL, используем стандартный формат
                    $url .= '?route=information/author&author_id=' . $author_id;
                    error_log("No SEO URL found for author_id=" . $author_id . ", using standard format");
                }
                unset($data['author_id']);
            } else {
                error_log("No author_id parameter in author route");
            }
        }
        // Обработка категорий блога
        elseif ($route == 'information/blog_category') {
            if (isset($data['blog_path'])) {
                $blog_category_id = (int)str_replace('_', '', strrchr($data['blog_path'], '_')) ? (int)str_replace('_', '', strrchr($data['blog_path'], '_')) : (int)$data['blog_path'];
            } else {
                $blog_category_id = 0;
            }

            $url = $this->generateBlogCategoryUrl($blog_category_id);
            error_log("Rewriting blog category to: " . $url);
            unset($data['blog_path']);
        }
        // Обработка статей блога
        elseif ($route == 'information/information') {
            if (isset($data['information_id'])) {
                $information_id = (int)$data['information_id'];

                $categories = $this->getArticleBlogCategories($information_id);

                if ($categories) {
                    $blog_category_id = $categories[0];
                    $url = $this->generateBlogArticleUrl($information_id, $blog_category_id);
                } else {
                    $keyword = $this->getKeyword('information_id=' . $data['information_id']);
                    if ($keyword) {
                        $url .= '/blog/' . $keyword;
                    }
                }
                error_log("Rewriting information page to: " . $url);
                unset($data['information_id']);
            }
        }
        else {
            // Стандартная логика для других маршрутов
            if ($route == 'product/category') {
                if (isset($data['path'])) {
                    $category_id = (int)str_replace('_', '', strrchr($data['path'], '_')) ? (int)str_replace('_', '', strrchr($data['path'], '_')) : (int)$data['path'];

                    $keywords = $this->getKeywordsByCategory($category_id);

                    foreach ($keywords as $keyword) {
                        if ($keyword) {
                            $url .= '/' . $keyword;
                        }
                    }
                    unset($data['path']);
                }
            } elseif ($route == 'product/product') {
                if (isset($data['product_id'])) {
                    $keyword = $this->getKeyword('product_id=' . $data['product_id']);
                    if ($keyword) {
                        $url .= '/' . $keyword;
                    }
                    unset($data['product_id']);
                }
            } elseif ($route == 'product/manufacturer/info') {
                if (isset($data['manufacturer_id'])) {
                    $keyword = $this->getKeyword('manufacturer_id=' . $data['manufacturer_id']);
                    if ($keyword) {
                        $url .= '/' . $keyword;
                    }
                    unset($data['manufacturer_id']);
                }
            } elseif ($route == 'information/information') {
                if (isset($data['information_id'])) {
                    $keyword = $this->getKeyword('information_id=' . $data['information_id']);
                    if ($keyword) {
                        $url .= '/' . $keyword;
                    }
                    unset($data['information_id']);
                }
            }

            unset($data['author_id']);
        }

        if ($data) {
            $url .= '?' . http_build_query($data);
        }

        if ($this->enable_postfix && in_array($route, $this->postfix_route)) {
            $url .= '.' . $this->postfix;
        }

        if ($this->enable_slash && !$data && $url && substr($url, -1) != '/') {
            $url .= '/';
        }

        if ($url) {
            $result = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url;
            error_log("Final rewritten URL: " . $result);
            return $result;
        } else {
            error_log("No rewrite applied, returning original link");
            return $link;
        }
    }

    private function processBlogUrl($parts) {
        $blog_path = '';
        $parent_id = 0;
        $blog_category_id = 0;
        $current_path = '';

        foreach ($parts as $key => $part) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

            if ($query->num_rows) {
                $url_query = $query->row['query'];

                if (strpos($url_query, 'blog_category_id=') === 0) {
                    $category_id = (int)str_replace('blog_category_id=', '', $url_query);

                    if ($parent_id > 0) {
                        $path_check = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category_path WHERE blog_category_id = '" . (int)$category_id . "' AND path_id = '" . (int)$parent_id . "'");
                        if (!$path_check->num_rows) {
                            return;
                        }
                    }

                    $blog_category_id = $category_id;
                    $parent_id = $category_id;

                    if ($blog_path) {
                        $blog_path .= '_';
                    }
                    $blog_path .= $category_id;
                    
                    $current_path = $blog_path;
                } elseif (strpos($url_query, 'information_id=') === 0) {
                    $information_id = (int)str_replace('information_id=', '', $url_query);

                    if ($blog_category_id > 0) {
                        $tie_check = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$information_id . "' AND blog_category_id = '" . (int)$blog_category_id . "'");
                        if (!$tie_check->num_rows) {
                            $correct_category = $this->findCorrectBlogCategory($information_id, $current_path);
                            if ($correct_category) {
                                $blog_category_id = $correct_category;
                                $blog_path = $this->getPathByBlogCategory($blog_category_id);
                            } else {
                                return;
                            }
                        }
                    }

                    $this->request->get['information_id'] = $information_id;
                    $this->request->get['route'] = 'information/information';

                    if ($blog_path) {
                        $this->request->get['blog_path'] = $blog_path;
                    }

                    return;
                }
            } else {
                return;
            }
        }

        if ($blog_category_id > 0) {
            $this->request->get['blog_path'] = $blog_path;
            $this->request->get['route'] = 'information/blog_category';
        }
    }

    private function processStandardUrl($parts) {
        $path = '';
        $parent_id = 0;
        $category_id = 0;

        foreach ($parts as $key => $part) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

            if ($query->num_rows) {
                $url_query = $query->row['query'];

                if (strpos($url_query, 'category_id=') === 0) {
                    $category_id = (int)str_replace('category_id=', '', $url_query);

                    if ($parent_id > 0) {
                        $path_check = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "' AND path_id = '" . (int)$parent_id . "'");
                        if (!$path_check->num_rows) {
                            return;
                        }
                    }

                    $parent_id = $category_id;

                    if ($path) {
                        $path .= '_';
                    }
                    $path .= $category_id;
                } elseif (strpos($url_query, 'product_id=') === 0) {
                    $this->request->get['product_id'] = (int)str_replace('product_id=', '', $url_query);

                    if ($path) {
                        $this->request->get['path'] = $path;
                    }

                    return;
                } elseif (strpos($url_query, 'manufacturer_id=') === 0) {
                    $this->request->get['manufacturer_id'] = (int)str_replace('manufacturer_id=', '', $url_query);
                    return;
                } elseif (strpos($url_query, 'information_id=') === 0) {
                    $this->request->get['information_id'] = (int)str_replace('information_id=', '', $url_query);
                    return;
                } elseif (strpos($url_query, 'author_id=') === 0) {
                    $this->request->get['author_id'] = (int)str_replace('author_id=', '', $url_query);
                    $this->request->get['route'] = 'information/author';
                    return;
                }
            } else {
                return;
            }
        }

        if ($category_id > 0) {
            $this->request->get['path'] = $path;
        }
    }

    private function generateBlogCategoryUrl($blog_category_id) {
        $url = '/blog';
        
        if ($blog_category_id > 0) {
            $keywords = $this->getKeywordsByBlogCategory($blog_category_id);
            
            foreach ($keywords as $keyword) {
                if ($keyword) {
                    $url .= '/' . $keyword;
                }
            }
        }
        
        return $url;
    }

    private function generateBlogArticleUrl($information_id, $blog_category_id) {
        $url = '';
        
        if ($blog_category_id > 0) {
            $url = $this->generateBlogCategoryUrl($blog_category_id);
        } else {
            $url = '/blog';
        }
        
        $article_keyword = $this->getKeyword('information_id=' . $information_id);
        
        if ($article_keyword) {
            $url .= '/' . $article_keyword;
        }
        
        return $url;
    }

    private function findCorrectBlogCategory($information_id, $current_path) {
        $categories = $this->getArticleBlogCategories($information_id);
        
        foreach ($categories as $category_id) {
            $category_path = $this->getPathByBlogCategory($category_id);
            if ($category_path == $current_path) {
                return $category_id;
            }
        }
        
        return count($categories) > 0 ? $categories[0] : 0;
    }

    private $category_keywords = [];
    private $category_path = [];
    private $blog_category_keywords = [];
    private $blog_category_path = [];
    private $keyword = [];
    
    private function getKeywordsByCategory($category_id) {
        if (!isset($this->category_keywords[$category_id])) {
            $query = $this->db->query("SELECT su.keyword FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "seo_url su ON (su.query = CONCAT('category_id=', cp.path_id)) WHERE cp.category_id = '" . (int)$category_id . "' AND su.store_id = '" . (int)$this->config->get('config_store_id') . "' AND su.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cp.level");
            
            $this->category_keywords[$category_id] = array();
            foreach ($query->rows as $row) {
                $this->category_keywords[$category_id][] = $row['keyword'];
            }
        }
        
        return $this->category_keywords[$category_id];
    }
    
    private function getPathByCategory($category_id) {
        if (!isset($this->category_path[$category_id])) {
            $query = $this->db->query("SELECT GROUP_CONCAT(path_id ORDER BY level SEPARATOR '_') AS path FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");
            $this->category_path[$category_id] = $query->row['path'] ?? $category_id;
        }
        
        return $this->category_path[$category_id];
    }

    private function getKeywordsByBlogCategory($blog_category_id) {
        if (!isset($this->blog_category_keywords[$blog_category_id])) {
            $query = $this->db->query("SELECT su.keyword FROM " . DB_PREFIX . "blog_category_path bcp LEFT JOIN " . DB_PREFIX . "seo_url su ON (su.query = CONCAT('blog_category_id=', bcp.path_id) AND su.store_id = '" . (int)$this->config->get('config_store_id') . "' AND su.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE bcp.blog_category_id = '" . (int)$blog_category_id . "' ORDER BY bcp.level ASC");
            
            $this->blog_category_keywords[$blog_category_id] = array();
            foreach ($query->rows as $row) {
                if ($row['keyword']) {
                    $this->blog_category_keywords[$blog_category_id][] = $row['keyword'];
                }
            }
        }
        
        return $this->blog_category_keywords[$blog_category_id];
    }
    
    private function getPathByBlogCategory($blog_category_id) {
        if (!isset($this->blog_category_path[$blog_category_id])) {
            $query = $this->db->query("SELECT GROUP_CONCAT(path_id ORDER BY level SEPARATOR '_') AS path FROM " . DB_PREFIX . "blog_category_path WHERE blog_category_id = '" . (int)$blog_category_id . "'");
            $this->blog_category_path[$blog_category_id] = $query->row['path'] ?? $blog_category_id;
        }
        
        return $this->blog_category_path[$blog_category_id];
    }
    
    private function getKeyword($query_string) {
        if (!isset($this->keyword[$query_string])) {
            $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($query_string) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
            $this->keyword[$query_string] = $query->num_rows ? $query->row['keyword'] : false;
        }
        
        return $this->keyword[$query_string];
    }

    private function getArticleBlogCategories($information_id) {
        $query = $this->db->query("SELECT blog_category_id FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$information_id . "'");
        
        $categories = array();
        foreach ($query->rows as $row) {
            $categories[] = $row['blog_category_id'];
        }
        
        return $categories;
    }
}
?>