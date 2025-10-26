<?php
class ControllerInformationBlogCategory extends Controller {
    public function index() {
        $this->load->language('information/blog_category');
        $this->load->model('catalog/blog_category');
        $this->load->model('catalog/information');
        $this->load->model('tool/image');
        $this->load->model('catalog/author'); // Добавляем модель авторов

        // Проверяем, это страница авторов?
        if (isset($this->request->get['authors_page'])) {
            $this->showAuthorsPage();
            return;
        }

        // Используем blog_path вместо blog_category_id для поддержки иерархии
        if (isset($this->request->get['blog_path'])) {
            $blog_path = $this->request->get['blog_path'];
            $parts = explode('_', $blog_path);
            $blog_category_id = (int)array_pop($parts);
        } else {
            $blog_category_id = 0;
            $blog_path = '';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $limit = 12;

        if ($blog_category_id > 0) {
            $category_info = $this->model_catalog_blog_category->getBlogCategory($blog_category_id);

            if ($category_info) {
                $this->document->setTitle($category_info['meta_title']);
                $this->document->setDescription($category_info['meta_description']);
                $this->document->setKeywords($category_info['meta_keyword']);

                $data['heading_title'] = $category_info['name'];
                $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');

                if ($category_info['image']) {
                    $data['thumb'] = $this->model_tool_image->resize($category_info['image'], 800, 400);
                } else {
                    $data['thumb'] = '';
                }

                $data['breadcrumbs'] = array();
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_home'),
                    'href' => $this->url->link('common/home')
                );
                
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category')
                );

                // Получаем полный путь категории для хлебных крошек
                $category_paths = $this->model_catalog_blog_category->getBlogCategoryPaths($blog_category_id);
                $current_path = '';
                
                foreach ($category_paths as $path) {
                    if ($path['blog_category_id'] != $blog_category_id) {
                        $current_path .= $current_path ? '_' . $path['blog_category_id'] : $path['blog_category_id'];
                        
                        $parent_info = $this->model_catalog_blog_category->getBlogCategory($path['blog_category_id']);
                        
                        if ($parent_info) {
                            $data['breadcrumbs'][] = array(
                                'text' => $parent_info['name'],
                                'href' => $this->url->link('information/blog_category', 'blog_path=' . $current_path)
                            );
                        }
                    }
                }

                // Текущая категория (без ссылки)
                $data['breadcrumbs'][] = array(
                    'text' => $category_info['name'],
                    'href' => ''
                );

                // === ДОБАВЛЯЕМ ПОЛУЧЕНИЕ ПОДКАТЕГОРИЙ ===
                $data['subcategories'] = array();
                $subcategories = $this->model_catalog_blog_category->getChildBlogCategories($blog_category_id);
                
                foreach ($subcategories as $subcategory) {
                    $sub_path = $blog_path ? $blog_path . '_' . $subcategory['blog_category_id'] : $subcategory['blog_category_id'];
                    
                    $data['subcategories'][] = array(
                        'blog_category_id' => $subcategory['blog_category_id'],
                        'name' => $subcategory['name'],
                        'href' => $this->url->link('information/blog_category', 'blog_path=' . $sub_path)
                    );
                }
                // === КОНЕЦ БЛОКА ПОДКАТЕГОРИЙ ===

                $data['articles'] = array();

                $filter_data = array(
                    'blog_category_id' => $blog_category_id,
                    'start' => ($page - 1) * $limit,
                    'limit' => $limit
                );

                $article_total = $this->model_catalog_information->getTotalInformationsByBlogCategory($blog_category_id);
                $results = $this->model_catalog_information->getInformationsByBlogCategory($filter_data);

                foreach ($results as $result) {
                    $image = $this->model_tool_image->resize('placeholder.png', 400, 300);

                    $data['articles'][] = array(
                        'information_id' => $result['information_id'],
                        'title' => $result['title'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'viewed' => $result['viewed'],
                        'reading_time' => $result['reading_time'],
                        'image' => $image,
                        'href' => $this->url->link('information/information', 'blog_path=' . $blog_path . '&information_id=' . $result['information_id'])
                    );
                }

                $url = '';

                if (isset($this->request->get['limit'])) {
                    $url .= '&limit=' . $this->request->get['limit'];
                }

                $pagination = new Pagination();
                $pagination->total = $article_total;
                $pagination->page = $page;
                $pagination->limit = $limit;
                $pagination->url = $this->url->link('information/blog_category', 'blog_path=' . $blog_path . '&page={page}');

                $data['pagination'] = $pagination->render();

                $data['results'] = sprintf($this->language->get('text_pagination'), ($article_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($article_total - $limit)) ? $article_total : ((($page - 1) * $limit) + $limit), $article_total, ceil($article_total / $limit));

                // Языковые переменные
                $data['text_views'] = $this->language->get('text_views');
                $data['text_reading_time'] = $this->language->get('text_reading_time');
                $data['button_read_more'] = $this->language->get('button_read_more');
                $data['text_subcategories'] = $this->language->get('text_subcategories');
                $data['text_articles_in_category'] = $this->language->get('text_articles_in_category');

                $data['column_left'] = $this->load->controller('common/column_left');
                $data['column_right'] = $this->load->controller('common/column_right');
                $data['content_top'] = $this->load->controller('common/content_top');
                $data['content_bottom'] = $this->load->controller('common/content_bottom');
                $data['footer'] = $this->load->controller('common/footer');
                $data['header'] = $this->load->controller('common/header');

                $this->response->setOutput($this->load->view('information/blog_category', $data));
            } else {
                // 404 for invalid category
                return $this->load->controller('error/not_found');
            }
        } else {
            // Главная страница блога (blog_category_id = 0)
            $this->document->setTitle($this->language->get('heading_title'));
            $this->document->setDescription($this->language->get('meta_description'));
            $this->document->setKeywords($this->language->get('meta_keyword'));

            $data['heading_title'] = $this->language->get('heading_title');
            $data['description'] = $this->language->get('text_welcome');

            $data['breadcrumbs'] = array();
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_blog'),
                'href' => ''
            );

            // === ДОБАВЛЯЕМ ПОЛУЧЕНИЕ КОРНЕВЫХ КАТЕГОРИЙ ===
            $data['root_categories'] = array();
            $root_categories = $this->model_catalog_blog_category->getChildBlogCategories(0);
            
            foreach ($root_categories as $category) {
                $data['root_categories'][] = array(
                    'blog_category_id' => $category['blog_category_id'],
                    'name' => $category['name'],
                    'href' => $this->url->link('information/blog_category', 'blog_path=' . $category['blog_category_id'])
                );
            }
            // === КОНЕЦ БЛОКА КОРНЕВЫХ КАТЕГОРИЙ ===

            // === ДОБАВЛЯЕМ ССЫЛКУ НА СТРАНИЦУ АВТОРОВ ===
            $data['authors_link'] = $this->url->link('information/blog_category', 'authors_page=1');
            
            // Языковые переменные для главной страницы блога
            $data['text_blog_categories'] = $this->language->get('text_blog_categories');
            $data['text_authors'] = $this->language->get('text_authors');
            $data['text_authors_description'] = $this->language->get('text_authors_description');
            $data['text_latest_articles'] = $this->language->get('text_latest_articles');
            $data['text_empty_blog'] = $this->language->get('text_empty_blog');
            // === КОНЕЦ БЛОКА АВТОРОВ ===

            // Показываем только статьи с категориями
            $data['articles'] = array();

            $filter_data = array(
                'start' => ($page - 1) * $limit,
                'limit' => $limit
            );

            $article_total = $this->model_catalog_information->getTotalBlogArticles();
            $results = $this->model_catalog_information->getBlogArticles($filter_data);

            foreach ($results as $result) {
                $image = $this->model_tool_image->resize('placeholder.png', 400, 300);

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

            $url = '';

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $pagination = new Pagination();
            $pagination->total = $article_total;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = $this->url->link('information/blog_category', 'page={page}');

            $data['pagination'] = $pagination->render();

            $data['results'] = sprintf($this->language->get('text_pagination'), ($article_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($article_total - $limit)) ? $article_total : ((($page - 1) * $limit) + $limit), $article_total, ceil($article_total / $limit));

            $data['text_views'] = $this->language->get('text_views');
            $data['text_reading_time'] = $this->language->get('text_reading_time');
            $data['button_read_more'] = $this->language->get('button_read_more');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('information/blog_category', $data));
        }
    }

    /**
     * Показывает страницу списка авторов
     */
    private function showAuthorsPage() {
        $this->document->setTitle($this->language->get('text_authors'));
        $this->document->setDescription($this->language->get('meta_description_authors'));

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
            'href' => ''
        );

        // Получаем всех авторов
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

        $data['heading_title'] = $this->language->get('text_authors');
        $data['text_no_authors'] = $this->language->get('text_no_authors');
        $data['text_articles_count'] = $this->language->get('text_articles_count');
        $data['button_view_author'] = $this->language->get('button_view_author');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        // Используем специальный шаблон для авторов или общий blog_category
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/information/authors.twig')) {
            $this->response->setOutput($this->load->view('information/authors', $data));
        } else {
            $this->response->setOutput($this->load->view('information/blog_category', $data));
        }
    }
}
?>