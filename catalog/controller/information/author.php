<?php
class ControllerInformationAuthor extends Controller {
    public function index() {
        $this->load->language('information/author');
        
        $this->load->model('catalog/author');
        $this->load->model('catalog/information');
        $this->load->model('tool/image');

        if (isset($this->request->get['author_id'])) {
            $this->showAuthor((int)$this->request->get['author_id']);
        } else {
            $this->showAuthorsList();
        }
    }

    private function showAuthor($author_id) {
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
                'text' => '<i class="fa fa-home"></i>',
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
                'text' => $author_info['name'],
                'href' => ''
            );

            $data['heading_title'] = $author_info['name'];
            $data['description'] = html_entity_decode($author_info['description'], ENT_QUOTES, 'UTF-8');
            $data['job_title'] = $author_info['job_title'];
            $data['bio'] = html_entity_decode($author_info['bio'], ENT_QUOTES, 'UTF-8');

            // Обработка изображения автора с получением реальных размеров
            $image_width = 400;
            $image_height = 400;
            $data['image_original'] = '';
            
            if ($author_info['image'] && file_exists(DIR_IMAGE . $author_info['image'])) {
                $data['image'] = $this->model_tool_image->resize($author_info['image'], 400, 400);
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
                $data['image'] = $this->model_tool_image->resize('placeholder.png', 400, 400);
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
                    $image = $this->model_tool_image->resize($result['image'], 400, 300);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', 400, 300);
                }

                $data['articles'][] = array(
                    'information_id' => $result['information_id'],
                    'title' => $result['title'],
                    'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'viewed' => $result['viewed'],
                    'reading_time' => $result['reading_time'],
                    'image' => $image,
                    'href' => $this->url->link('information/information', 'information_id=' . $result['information_id'])
                );
            }

            // Microdata для Schema.org
            $current_url = $this->url->link('information/author', 'author_id=' . $author_id, true);
            
            // Формируем knowsAbout и knowsLanguage массивы
            $knows_about = array();
            if (!empty($author_info['knows_about'])) {
                $knows_about = array_map('trim', explode(',', $author_info['knows_about']));
                $knows_about = array_filter($knows_about);
            }
            
            $knows_language = array();
            if (!empty($author_info['knows_language'])) {
                $knows_language = array_map('trim', explode(',', $author_info['knows_language']));
                $knows_language = array_filter($knows_language);
            }
            
            // Формируем sameAs массив
            $same_as = array();
            if (!empty($author_info['same_as'])) {
                $same_as_lines = array_map('trim', explode("\n", $author_info['same_as']));
                $same_as_lines = array_filter($same_as_lines);
                
                foreach ($same_as_lines as $line) {
                    $url = trim($line);
                    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                        $same_as[] = $url;
                    }
                }
            }

            // Определяем affiliation
            $affiliation = null;
            if ($author_info['company_employee']) {
                $affiliation = array(
                    '@type' => 'Organization',
                    'name' => $this->config->get('config_name'),
                    'url' => $this->config->get('config_url')
                );
            } elseif (!empty($author_info['affiliation'])) {
                $affiliation = array(
                    '@type' => 'Organization', 
                    'name' => $author_info['affiliation']
                );
            }

            // Формируем объект Person с правильным порядком полей
            $person_data = array(
                '@type' => 'Person',
                '@id' => $current_url . '#person',
                'name' => $author_info['name']
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

            // Добавляем affiliation
            if ($affiliation) {
                $person_data['affiliation'] = $affiliation;
            }

            // Добавляем image
            $person_data['image'] = array(
                '@type' => 'ImageObject',
                'url' => $data['image_original'],
                'width' => $image_width,
                'height' => $image_height
            );

            // Добавляем knowsAbout
            if (!empty($knows_about)) {
                $person_data['knowsAbout'] = $knows_about;
            }

            // Добавляем knowsLanguage
            if (!empty($knows_language)) {
                $person_data['knowsLanguage'] = $knows_language;
            }

            // Добавляем sameAs только если есть валидные URL
            if (!empty($same_as)) {
                $person_data['sameAs'] = $same_as;
            }

            // Основная микроразметка WebPage
            $microdata = array(
                '@context' => 'https://schema.org',
                '@type' => array('ProfilePage', 'WebPage'),
                '@id' => $current_url,
                'name' => $author_info['name'] . ' — Страница автора',
                'url' => $current_url,
                'mainEntity' => $person_data
            );

            // Добавляем mainEntityOfPage отдельно после mainEntity
            $microdata['mainEntityOfPage'] = array(
                '@type' => 'WebPage',
                '@id' => $current_url
            );

            $data['microdata'] = $microdata;

            $data['article_total'] = $article_total;
            $data['text_views'] = $this->language->get('text_views');
            $data['text_reading_time'] = $this->language->get('text_reading_time');
            $data['button_read_more'] = $this->language->get('button_read_more');
            $data['text_authors'] = $this->language->get('text_authors');
            $data['text_articles_by_author'] = $this->language->get('text_articles_by_author');
            $data['text_author_bio'] = $this->language->get('text_author_bio');
            $data['text_articles_count'] = $this->language->get('text_articles_count');

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
                'text' => '<i class="fa fa-home"></i>',
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

    private function showAuthorsList() {
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->setDescription($this->language->get('meta_description'));
        $this->document->setKeywords($this->language->get('meta_keyword'));
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => '<i class="fa fa-home"></i>',
            'href' => $this->url->link('common/home')
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_blog'),
            'href' => $this->url->link('information/blog_category')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_authors'),
            'href' => ''
        );

        $data['authors'] = array();
        $results = $this->model_catalog_author->getAllAuthors();

        foreach ($results as $result) {
            if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 300, 300);
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', 300, 300);
            }

            $data['authors'][] = array(
                'author_id' => $result['author_id'],
                'name' => $result['name'],
                'job_title' => $result['job_title'],
                'image' => $image,
                'bio' => utf8_substr(strip_tags(html_entity_decode($result['bio'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                'total_articles' => $result['article_count'],
                'href' => $this->url->link('information/author', 'author_id=' . $result['author_id'])
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');
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
}
?>