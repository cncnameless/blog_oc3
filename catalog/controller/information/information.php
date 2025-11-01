<?php
class ControllerInformationInformation extends Controller {
    public function index() {
        $this->load->language('information/information');
        $this->load->language('information/blog_category');

        $this->load->model('catalog/information');
        $this->load->model('catalog/blog_category');
        $this->load->model('catalog/author');
        $this->load->model('tool/image');

        if (isset($this->request->get['information_id'])) {
            $information_id = (int)$this->request->get['information_id'];
        } else {
            $information_id = 0;
        }

        $information_info = $this->model_catalog_information->getInformation($information_id);

        if ($information_info) {
            $this->model_catalog_information->updateViewed($information_id);

            $this->document->setTitle($information_info['meta_title']);
            $this->document->setDescription($information_info['meta_description']);
            $this->document->setKeywords($information_info['meta_keyword']);

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', '', true)
            );

            // Проверяем, является ли статья частью блога
            $blog_categories = $this->model_catalog_information->getInformationBlogCategories($information_id);
            
            if ($blog_categories) {
                // Это статья блога - формируем хлебные крошки через категории блога
                $blog_category_id = $blog_categories[0];
                
                // Добавляем главную страницу блога
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category', '', true)
                );

                // Получаем путь категории для хлебных крошек
                $category_paths = $this->model_catalog_blog_category->getBlogCategoryPaths($blog_category_id);
                
                foreach ($category_paths as $path) {
                    $data['breadcrumbs'][] = array(
                        'text' => $path['name'],
                        'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $path['blog_category_id'], true)
                    );
                }

            } else {
                // Обычная информационная страница
                $categories = $this->getInformationCategories($information_id);
                foreach ($categories as $category) {
                    $data['breadcrumbs'][] = array(
                        'text' => $category['name'],
                        'href' => $category['href']
                    );
                }
            }

            // ИСПРАВЛЕНИЕ: Последняя крошка без ссылки
            $data['breadcrumbs'][] = array(
                'text' => $information_info['title'],
                'href' => ''
            );

            $data['heading_title'] = $information_info['title'];
            $data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

            // ДОБАВЛЯЕМ: Загрузка изображения статьи
            if ($information_info['image']) {
                $article_image_width = $this->config->get('blog_article_image_width') ?: 800;
                $article_image_height = $this->config->get('blog_article_image_height') ?: 400;
                $data['image'] = $this->model_tool_image->resize($information_info['image'], $article_image_width, $article_image_height);
            } else {
                $data['image'] = '';
            }

            // Данные для статей блога
            if ($blog_categories) {
                $data['date_added'] = date($this->language->get('date_format_short'), strtotime($information_info['date_added']));
                $data['viewed'] = $information_info['viewed'];
                $data['reading_time'] = $information_info['reading_time'];

                // Получаем авторов статьи
                $authors_data = $this->model_catalog_author->getAuthorsByInformation($information_id);
                $data['authors'] = array();

                // ИСПРАВЛЕНИЕ: Добавляем настройки размеров аватаров
                $author_article_width = $this->config->get('blog_author_article_width') ?: 80;
                $author_article_height = $this->config->get('blog_author_article_height') ?: 80;

                foreach ($authors_data as $author) {
                    // ИСПРАВЛЕНИЕ: Используем настройки размеров для аватаров
                    if ($author['image'] && file_exists(DIR_IMAGE . $author['image'])) {
                        $image = $this->model_tool_image->resize($author['image'], $author_article_width, $author_article_height);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $author_article_width, $author_article_height);
                    }

                    $data['authors'][] = array(
                        'name' => $author['name'],
                        'job_title' => $author['job_title'],
                        'bio' => utf8_substr(strip_tags(html_entity_decode($author['bio'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                        'image' => $image,
                        'href' => $this->url->link('information/author', 'author_id=' . $author['author_id'], true)
                    );
                }

                // === ДОБАВЛЯЕМ ПОЛУЧЕНИЕ ТЕГОВ ===
                $data['tags'] = $this->model_catalog_information->getInformationTags($information_id);

                // Microdata для Schema.org
                $data['json_ld'] = $this->generateSchemaMarkup($information_info, $data);

            } else {
                // Для обычных информационных страниц
                $data['date_added'] = '';
                $data['viewed'] = '';
                $data['reading_time'] = '';
                $data['authors'] = array();
                $data['tags'] = array();
                $data['json_ld'] = array();
            }

            $data['text_views'] = $this->language->get('text_views');
            $data['text_reading_time'] = $this->language->get('text_reading_time');

            $data['continue'] = $this->url->link('common/home');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('information/information', $data));
        } else {
            // Страница не найдена
            $this->load->language('error/not_found');

            $this->document->setTitle($this->language->get('text_error'));

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', '', true)
            );

            // ИСПРАВЛЕНИЕ: Последняя крошка без ссылки
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

    private function getInformationCategories($information_id) {
        return array();
    }

    /**
     * Генерация микроразметки JSON-LD
     */
    private function generateSchemaMarkup($information_info, $data) {
        $json_ld = array();
        $base_url = HTTP_SERVER;
        $current_url = $this->url->link('information/information', 'information_id=' . $information_info['information_id'], true);
        
        $schema_type = isset($information_info['schema_type']) ? $information_info['schema_type'] : 'BlogPosting';
        
        // Очищаем описание от HTML и лишних символов
        $clean_description = $this->cleanTextForSchema($information_info['description']);
        
        // 1. BreadcrumbList
        $breadcrumb_list = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );

        $position = 1;
        foreach ($data['breadcrumbs'] as $breadcrumb) {
            $name = strip_tags(html_entity_decode($breadcrumb['text'], ENT_QUOTES, 'UTF-8'));
            $name = preg_replace('/\s+/', ' ', trim($name));
            
            $item_url = $breadcrumb['href'] ? $this->getAbsoluteUrl($breadcrumb['href']) : $current_url;
            
            $breadcrumb_list['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
                'item' => $item_url
            );
            $position++;
        }

        $json_ld['breadcrumb'] = $breadcrumb_list;

        // 2. Основная микроразметка статьи
        if ($schema_type === 'Organization') {
            // Для Organization используем правильные свойства
            $article_data = array(
                '@context' => 'https://schema.org',
                '@type' => $schema_type,
                'name' => $information_info['title'], // используем name вместо headline
                'description' => $clean_description,
                'url' => $current_url
            );

            // Добавляем изображение если есть
            if (!empty($information_info['image']) && $information_info['image'] != 'placeholder.png') {
                $article_data['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $base_url . 'image/' . $information_info['image'],
                    'width' => 800,
                    'height' => 400
                );
            }

            // Для Organization не включаем publisher, так как это невалидно

        } else {
            // Для статей блога, новостей, обзоров
            $article_data = array(
                '@context' => 'https://schema.org',
                '@type' => $schema_type,
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id' => $current_url
                ),
                'headline' => $information_info['title'],
                'description' => $clean_description,
                'datePublished' => $information_info['date_added'],
                'dateModified' => $information_info['date_modified'] ? $information_info['date_modified'] : $information_info['date_added'],
                'url' => $current_url
            );

            // Добавляем изображение если есть
            if (!empty($information_info['image']) && $information_info['image'] != 'placeholder.png') {
                $article_data['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $base_url . 'image/' . $information_info['image'],
                    'width' => 800,
                    'height' => 400
                );
            }

            // Авторы только для статей, новостей и обзоров
            if (isset($data['authors']) && $data['authors']) {
                $authors_array = array();
                foreach ($data['authors'] as $author) {
                    $author_data = array(
                        '@type' => 'Person',
                        'name' => $author['name']
                    );
                    
                    if (!empty($author['job_title'])) {
                        $author_data['jobTitle'] = $author['job_title'];
                    }
                    
                    if (!empty($author['href'])) {
                        $author_data['url'] = $author['href'];
                    }
                    
                    if (!empty($author['image']) && $author['image'] != $this->model_tool_image->resize('placeholder.png', 80, 80)) {
                        $author_data['image'] = $author['image'];
                    }
                    
                    $authors_array[] = $author_data;
                }
                
                if (count($authors_array) === 1) {
                    $article_data['author'] = $authors_array[0];
                } else {
                    $article_data['author'] = $authors_array;
                }
            }

            // === ДОБАВЛЯЕМ ТЕГИ В МИКРОРАЗМЕТКУ ===
            if (isset($data['tags']) && $data['tags']) {
                $about_array = array();
                foreach ($data['tags'] as $tag) {
                    $about_array[] = array(
                        '@type' => 'Thing',
                        'name' => $tag['name']
                    );
                }
                $article_data['about'] = $about_array;
            }

            // Издатель (организация) - для всех типов кроме Organization
            $article_data['publisher'] = array(
                '@type' => 'Organization',
                'name' => $this->config->get('config_name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $base_url . 'image/' . $this->config->get('config_logo')
                )
            );
            
            // Специфичные поля для каждого типа
            switch ($schema_type) {
                case 'Review':
                    if (isset($information_info['rating_value']) && $information_info['rating_value'] !== null) {
                        $article_data['reviewRating'] = array(
                            '@type' => 'Rating',
                            'ratingValue' => (float)$information_info['rating_value'],
                            'bestRating' => 5,
                            'worstRating' => 1
                        );
                    }
                    break;
                    
                case 'NewsArticle':
                    $article_data['dateline'] = $information_info['date_added'];
                    break;
                    
                case 'BlogPosting':
                default:
                    break;
            }
        }

        $json_ld['article'] = $article_data;

        return $json_ld;
    }

    /**
     * Очистка текста для микроразметки
     */
    private function cleanTextForSchema($text) {
        $clean = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);
        return $clean;
    }

    /**
     * Получение абсолютного URL из относительного
     */
    private function getAbsoluteUrl($relative_url) {
        if (strpos($relative_url, HTTP_SERVER) === 0) {
            return $relative_url;
        }
        
        if (strpos($relative_url, 'http') === 0) {
            return $relative_url;
        }
        
        if (strpos($relative_url, 'route=') !== false) {
            return $this->url->link($relative_url, '', true);
        }
        
        return HTTP_SERVER . ltrim($relative_url, '/');
    }
}
?>