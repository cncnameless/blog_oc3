<?php
class ControllerInformationAuthor extends Controller {
    public function index() {
        $this->load->language('information/author');
        
        $this->load->model('catalog/author');
        $this->load->model('catalog/information');
        $this->load->model('tool/image');

        // Получаем настройки размеров изображений из конфига модуля
        $author_page_width = $this->config->get('blog_author_page_width') ?: 400;
        $author_page_height = $this->config->get('blog_author_page_height') ?: 400;
        $author_list_width = $this->config->get('blog_author_list_image_width') ?: 300;
        $author_list_height = $this->config->get('blog_author_list_image_height') ?: 300;
        $article_image_width = $this->config->get('blog_article_image_width') ?: 400;
        $article_image_height = $this->config->get('blog_article_image_height') ?: 300;

        if (isset($this->request->get['author_id'])) {
            $this->showAuthor((int)$this->request->get['author_id'], $author_page_width, $author_page_height, $article_image_width, $article_image_height);
        } else {
            $this->showAuthorsList($author_list_width, $author_list_height);
        }
    }

    private function showAuthor($author_id, $author_page_width, $author_page_height, $article_image_width, $article_image_height) {
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return $this->load->controller('error/not_found');
        }

        $author_info = $this->model_catalog_author->getAuthor($author_id);

        if ($author_info && $author_info['status']) {
            $this->document->setTitle($author_info['meta_title'] ? $author_info['meta_title'] : $author_info['name']);
            $this->document->setDescription($author_info['meta_description']);
            $this->document->setKeywords($author_info['meta_keyword']);

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_blog'),
                'href' => $this->url->link('information/blog_category')
            );

            // Получаем SEO данные для списка авторов
            $author_list_data = $this->getAuthorListData();
            $data['breadcrumbs'][] = array(
                'text' => $author_list_data['name'],
                'href' => $this->url->link('information/author')
            );

            $data['breadcrumbs'][] = array(
                'text' => $author_info['name'],
                'href' => ''
            );

            $data['heading_title'] = $author_info['name'];
            $data['description'] = html_entity_decode($author_info['description'], ENT_QUOTES, 'UTF-8');
            $data['job_title'] = $author_info['job_title'];
            $data['bio'] = html_entity_decode($author_info['bio'], ENT_QUOTES, 'UTF-8');

            // Обработка изображения автора с использованием настроек
            $image_width = $author_page_width;
            $image_height = $author_page_height;
            $data['image_original'] = '';
            
            if ($author_info['image'] && file_exists(DIR_IMAGE . $author_info['image'])) {
                $data['image'] = $this->model_tool_image->resize($author_info['image'], $author_page_width, $author_page_height);
                $data['image_original'] = $this->config->get('config_url') . 'image/' . $author_info['image'];
                
                // Получаем реальные размеры изображения
                $image_path = DIR_IMAGE . $author_info['image'];
                if (file_exists($image_path)) {
                    $image_info = getimagesize($image_path);
                    if ($image_info) {
                        $image_width = $image_info[0];
                        $image_height = $image_info[1];
                    }
                }
            } else {
                $data['image'] = $this->model_tool_image->resize('placeholder.png', $author_page_width, $author_page_height);
                $data['image_original'] = $this->config->get('config_url') . 'image/placeholder.png';
                
                $image_path = DIR_IMAGE . 'placeholder.png';
                if (file_exists($image_path)) {
                    $image_info = getimagesize($image_path);
                    if ($image_info) {
                        $image_width = $image_info[0];
                        $image_height = $image_info[1];
                    }
                }
            }

            // Получаем статьи автора
            $data['articles'] = array();

            $filter_data = array(
                'filter_author_id' => $author_id,
                'start' => 0,
                'limit' => 20
            );

            $info_to_author_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "information_to_author'");
            if ($info_to_author_table_exists->num_rows) {
                $article_total = $this->model_catalog_author->getTotalArticlesByAuthor($author_id);
                $results = $this->model_catalog_author->getArticlesByAuthor($filter_data);
            } else {
                $article_total = 0;
                $results = array();
            }

            foreach ($results as $result) {
                if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
                    $image = $this->model_tool_image->resize($result['image'], $article_image_width, $article_image_height);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $article_image_width, $article_image_height);
                }

                $data['articles'][] = array(
                    'information_id' => $result['information_id'],
                    'title' => $result['title'],
                    'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'viewed' => $result['viewed'],
                    'reading_time' => $result['reading_time'],
                    'thumb' => $image,
                    'href' => $this->url->link('information/information', 'information_id=' . $result['information_id'])
                );
            }

            // ===== РАЗДЕЛЕННАЯ МИКРОРАЗМЕТКА ДЛЯ СТРАНИЦЫ АВТОРА =====
            $current_url = $this->url->link('information/author', 'author_id=' . $author_id, true);
            
            // 1. BreadcrumbList (отдельный блок)
            $breadcrumb_microdata = array(
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => array()
            );

            $position = 1;
            foreach ($data['breadcrumbs'] as $breadcrumb) {
                $breadcrumb_microdata['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => strip_tags($breadcrumb['text']),
                    'item' => $breadcrumb['href'] ? $this->getAbsoluteUrl($breadcrumb['href']) : $current_url
                );
                $position++;
            }

            // 2. Person (отдельный блок)
            $person_data = array(
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                '@id' => $current_url . '#person',
                'name' => $author_info['name'],
                'url' => $current_url
            );

            // Добавляем description только если он заполнен
            $clean_description = strip_tags(html_entity_decode($author_info['description'], ENT_QUOTES, 'UTF-8'));
            if (!empty(trim($clean_description))) {
                $person_data['description'] = $clean_description;
            }

            // Добавляем jobTitle только если он заполнен
            if (!empty($author_info['job_title'])) {
                $person_data['jobTitle'] = $author_info['job_title'];
            }

            // Добавляем image
            $person_data['image'] = array(
                '@type' => 'ImageObject',
                'url' => $data['image_original'],
                'width' => $image_width,
                'height' => $image_height
            );

            // Формируем knowsAbout и knowsLanguage массивы
            if (!empty($author_info['knows_about'])) {
                $knows_about = array_map('trim', explode(',', $author_info['knows_about']));
                $knows_about = array_filter($knows_about);
                if (!empty($knows_about)) {
                    $person_data['knowsAbout'] = $knows_about;
                }
            }
            
            if (!empty($author_info['knows_language'])) {
                $knows_language = array_map('trim', explode(',', $author_info['knows_language']));
                $knows_language = array_filter($knows_language);
                if (!empty($knows_language)) {
                    $person_data['knowsLanguage'] = $knows_language;
                }
            }
            
            // Формируем sameAs массив
            if (!empty($author_info['same_as'])) {
                $same_as_lines = array_map('trim', explode("\n", $author_info['same_as']));
                $same_as_lines = array_filter($same_as_lines);
                $same_as = array();
                
                foreach ($same_as_lines as $line) {
                    $url = trim($line);
                    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                        $same_as[] = $url;
                    }
                }
                if (!empty($same_as)) {
                    $person_data['sameAs'] = $same_as;
                }
            }

            // Определяем affiliation
            if ($author_info['company_employee']) {
                $person_data['affiliation'] = array(
                    '@type' => 'Organization',
                    'name' => $this->config->get('config_name'),
                    'url' => $this->config->get('config_url')
                );
            } elseif (!empty($author_info['affiliation'])) {
                $person_data['affiliation'] = array(
                    '@type' => 'Organization', 
                    'name' => $author_info['affiliation']
                );
            }

            // 3. ProfilePage (отдельный блок)
            $profile_microdata = array(
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                '@id' => $current_url,
                'name' => $author_info['name'] . ' — Страница автора',
                'url' => $current_url,
                'description' => $this->document->getDescription(),
                'mainEntity' => array(
                    '@id' => $current_url . '#person'
                )
            );

            // Сохраняем все блоки микроразметки
            $data['microdata'] = array(
                'breadcrumb' => $breadcrumb_microdata,
                'person' => $person_data,
                'profile' => $profile_microdata
            );
            // ===== КОНЕЦ МИКРОРАЗМЕТКИ =====

            $data['article_total'] = $article_total;
            $data['text_views'] = $this->language->get('text_views');
            $data['text_reading_time'] = $this->language->get('text_reading_time');
            $data['button_read_more'] = $this->language->get('button_read_more');
            $data['text_authors'] = $this->language->get('text_authors');
            $data['text_articles_by_author'] = $this->language->get('text_articles_by_author');
            $data['text_author_bio'] = $this->language->get('text_author_bio');
            $data['text_articles_count'] = $this->language->get('text_articles_count');
            $data['text_no_articles'] = $this->language->get('text_no_articles');

            $data['continue'] = $this->url->link('common/home');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('information/author', $data));
        } else {
            $this->load->language('error/not_found');
            
            $this->document->setTitle($this->language->get('text_error'));

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_blog'),
                'href' => $this->url->link('information/blog_category')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_authors'),
                'href' => $this->url->link('information/author')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_error'),
                'href' => ''
            );

            $data['heading_title'] = $this->language->get('text_error');
            $data['text_error'] = $this->language->get('text_error');
            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    private function showAuthorsList($author_list_width, $author_list_height) {
        // Получаем SEO данные для списка авторов
        $author_list_data = $this->getAuthorListData();
        
        $this->document->setTitle($author_list_data['meta_title']);
        $this->document->setDescription($author_list_data['meta_description']);
        $this->document->setKeywords($author_list_data['meta_keyword']);
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_blog'),
            'href' => $this->url->link('information/blog_category')
        );

        $data['breadcrumbs'][] = array(
            'text' => $author_list_data['name'],
            'href' => ''
        );

        if ($author_list_data['h1']) {
            $data['heading_title'] = $author_list_data['h1'];
        } else {
            $data['heading_title'] = $author_list_data['name'];
        }
        
        $data['description'] = html_entity_decode($author_list_data['description'], ENT_QUOTES, 'UTF-8');

        $data['authors'] = array();
        $results = $this->model_catalog_author->getAllAuthors();

        foreach ($results as $result) {
            // Получаем оригинальное изображение и его размеры
            $image_width = $author_list_width;
            $image_height = $author_list_height;
            $image_original = '';
            
            if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], $author_list_width, $author_list_height);
                $image_original = $this->config->get('config_url') . 'image/' . $result['image'];
                $image_path = DIR_IMAGE . $result['image'];
                
                // Получаем реальные размеры оригинального изображения
                if (file_exists($image_path)) {
                    $image_info = getimagesize($image_path);
                    if ($image_info) {
                        $image_width = $image_info[0];
                        $image_height = $image_info[1];
                    }
                }
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', $author_list_width, $author_list_height);
                $image_original = $this->config->get('config_url') . 'image/placeholder.png';
                $image_path = DIR_IMAGE . 'placeholder.png';
                
                if (file_exists($image_path)) {
                    $image_info = getimagesize($image_path);
                    if ($image_info) {
                        $image_width = $image_info[0];
                        $image_height = $image_info[1];
                    }
                }
            }

            $data['authors'][] = array(
                'author_id' => $result['author_id'],
                'name' => $result['name'],
                'job_title' => $result['job_title'],
                'image' => $image,
                'thumb' => $image,
                'image_original' => $image_original,
                'image_width' => (int)$image_width,
                'image_height' => (int)$image_height,
                'bio' => utf8_substr(strip_tags(html_entity_decode($result['bio'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                'total_articles' => $result['article_count'],
                'href' => $this->url->link('information/author', 'author_id=' . $result['author_id'])
            );
        }

        // ===== РАЗДЕЛЕННАЯ МИКРОРАЗМЕТКА ДЛЯ СПИСКА АВТОРОВ =====
        $current_url = $this->url->link('information/author', '', true);
        
        // 1. BreadcrumbList (отдельный блок)
        $breadcrumb_microdata = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );

        $position = 1;
        
        // Главная страница
        $breadcrumb_microdata['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $this->language->get('text_home'),
            'item' => $this->url->link('common/home')
        );
        
        // Блог
        $breadcrumb_microdata['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $this->language->get('text_blog'),
            'item' => $this->url->link('information/blog_category')
        );
        
        // Авторы (текущая страница)
        $breadcrumb_microdata['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $author_list_data['name'],
            'item' => $current_url
        );

        // 2. ItemList для списка авторов (отдельный блок)
        $itemListElement = array();
        $author_position = 1;
        foreach ($data['authors'] as $author) {
            $itemListElement[] = array(
                '@type' => 'ListItem',
                'position' => $author_position++,
                'item' => array(
                    '@type' => 'Person',
                    '@id' => $author['href'] . '#person',
                    'name' => $author['name'],
                    'url' => $author['href'],
                    'image' => array(
                        '@type' => 'ImageObject',
                        'url' => $author['image_original'],
                        'width' => $author['image_width'],
                        'height' => $author['image_height']
                    )
                )
            );
        }

        $itemlist_microdata = array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Список авторов',
            'numberOfItems' => count($data['authors']),
            'itemListElement' => $itemListElement
        );

        // 3. CollectionPage (отдельный блок)
        $collection_microdata = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $current_url,
            'name' => $data['heading_title'],
            'description' => $this->document->getDescription(),
            'url' => $current_url,
            'mainEntity' => array(
                '@id' => $current_url . '#itemlist'
            )
        );

        // Сохраняем все блоки микроразметки
        $data['microdata'] = array(
            'breadcrumb' => $breadcrumb_microdata,
            'itemlist' => $itemlist_microdata,
            'collection' => $collection_microdata
        );
        // ===== КОНЕЦ МИКРОРАЗМЕТКИ =====

        $data['text_no_authors'] = $this->language->get('text_no_authors');
        $data['text_articles_count'] = $this->language->get('text_articles_count');
        $data['button_view_author'] = $this->language->get('button_view_author');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('information/author_list', $data));
    }

    private function getAuthorListData() {
        $language_id = (int)$this->config->get('config_language_id');
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "author_list_description WHERE language_id = '" . $language_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        }
        
        // Возвращаем данные по умолчанию
        return array(
            'name' => 'Авторы',
            'h1' => 'Наши авторы',
            'meta_title' => 'Авторы',
            'meta_description' => '',
            'meta_keyword' => '',
            'description' => ''
        );
    }

    private function getAbsoluteUrl($relative_url) {
        if (empty($relative_url)) {
            return '';
        }
        
        if (strpos($relative_url, 'http') === 0) {
            return $relative_url;
        }
        
        // Если URL уже абсолютный
        if (strpos($relative_url, HTTP_SERVER) === 0) {
            return $relative_url;
        }
        
        // Для внутренних ссылок OpenCart
        if (strpos($relative_url, 'index.php?route=') !== false || strpos($relative_url, '?route=') !== false) {
            return $this->url->link($relative_url, '', true);
        }
        
        // Для относительных URL
        return HTTP_SERVER . ltrim($relative_url, '/');
    }
}
?>