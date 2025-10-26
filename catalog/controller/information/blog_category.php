<?php
class ControllerInformationBlogCategory extends Controller {
    public function index() {
        $this->load->language('information/blog_category');
        $this->load->model('catalog/blog_category');
        $this->load->model('catalog/information');
        $this->load->model('tool/image');

        if (isset($this->request->get['blog_category_id'])) {
            $blog_category_id = (int)$this->request->get['blog_category_id'];
        } else {
            $blog_category_id = 0;
        }

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = 12;

        // Хлебные крошки
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => '<i class="fa fa-home"></i>',
            'href' => $this->url->link('common/home')
        ];

        if ($blog_category_id > 0) {
            $category_info = $this->model_catalog_blog_category->getBlogCategory($blog_category_id);

            if ($category_info) {
                $this->document->setTitle($category_info['meta_title']);
                $this->document->setDescription($category_info['meta_description']);
                $this->document->setKeywords($category_info['meta_keyword']);

                $data['heading_title'] = $category_info['name'];
                $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
                $data['thumb'] = $category_info['image'] ? $this->model_tool_image->resize($category_info['image'], 800, 400) : '';

                // ИСПРАВЛЕНО: Добавляем ссылку на главную блога только для категорий
                $data['breadcrumbs'][] = [
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category')
                ];

                // Путь категории
                $category_paths = $this->model_catalog_blog_category->getBlogCategoryPaths($blog_category_id);
                
                foreach ($category_paths as $path) {
                    if ($path['blog_category_id'] != $blog_category_id) {
                        $parent_info = $this->model_catalog_blog_category->getBlogCategory($path['blog_category_id']);
                        
                        if ($parent_info) {
                            $data['breadcrumbs'][] = [
                                'text' => $parent_info['name'],
                                'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $path['blog_category_id'])
                            ];
                        }
                    }
                }

                // Текущая категория (без ссылки)
                $data['breadcrumbs'][] = [
                    'text' => $category_info['name'],
                    'href' => ''
                ];

                // Подкатегории
                $data['subcategories'] = [];
                $subcategories = $this->model_catalog_blog_category->getChildBlogCategories($blog_category_id);
                
                foreach ($subcategories as $subcategory) {
                    $data['subcategories'][] = [
                        'blog_category_id' => $subcategory['blog_category_id'],
                        'name' => $subcategory['name'],
                        'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $subcategory['blog_category_id'])
                    ];
                }

                $data['articles'] = [];
                $filter_data = [
                    'blog_category_id' => $blog_category_id,
                    'start' => ($page - 1) * $limit,
                    'limit' => $limit
                ];

                $article_total = $this->model_catalog_information->getTotalInformationsByBlogCategory($blog_category_id);
                $results = $this->model_catalog_information->getInformationsByBlogCategory($filter_data);

                foreach ($results as $result) {
                    $image = $this->model_tool_image->resize('placeholder.png', 400, 300);
                    $data['articles'][] = [
                        'information_id' => $result['information_id'],
                        'title' => $result['title'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'viewed' => $result['viewed'],
                        'reading_time' => $result['reading_time'],
                        'image' => $image,
                        'href' => $this->url->link('information/information', 'information_id=' . $result['information_id'])
                    ];
                }

                $pagination = new Pagination();
                $pagination->total = $article_total;
                $pagination->page = $page;
                $pagination->limit = $limit;
                $pagination->url = $this->url->link('information/blog_category', 'blog_category_id=' . $blog_category_id . '&page={page}');

                $data['pagination'] = $pagination->render();
                $data['results'] = sprintf($this->language->get('text_pagination'), ($article_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($article_total - $limit)) ? $article_total : ((($page - 1) * $limit) + $limit), $article_total, ceil($article_total / $limit));
                $data['text_views'] = $this->language->get('text_views');
                $data['text_reading_time'] = $this->language->get('text_reading_time');
                $data['button_read_more'] = $this->language->get('button_read_more');
                $data['text_subcategories'] = $this->language->get('text_subcategories');
                $data['text_articles_in_category'] = $this->language->get('text_articles_in_category');
                $data['blog_category_id'] = $blog_category_id;

                $data['column_left'] = $this->load->controller('common/column_left');
                $data['column_right'] = $this->load->controller('common/column_right');
                $data['content_top'] = $this->load->controller('common/content_top');
                $data['content_bottom'] = $this->load->controller('common/content_bottom');
                $data['footer'] = $this->load->controller('common/footer');
                $data['header'] = $this->load->controller('common/header');

                $this->response->setOutput($this->load->view('information/blog_category', $data));
            } else {
                return $this->load->controller('error/not_found');
            }
        } else {
            // Главная блога
            $this->document->setTitle($this->language->get('heading_title'));
            $data['heading_title'] = $this->language->get('heading_title');
            
            // ИСПРАВЛЕНО: Для главной блога только одна запись "Блог" без ссылки
            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_blog'),
                'href' => ''
            ];

            // Корневые категории
            $data['root_categories'] = [];
            $root_categories = $this->model_catalog_blog_category->getChildBlogCategories(0);
            
            foreach ($root_categories as $category) {
                $data['root_categories'][] = [
                    'blog_category_id' => $category['blog_category_id'],
                    'name' => $category['name'],
                    'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $category['blog_category_id'])
                ];
            }
            
            // Ссылка на страницу авторов
            $data['authors_link'] = $this->url->link('information/author');
            $data['text_authors'] = $this->language->get('text_authors');
            $data['text_authors_description'] = $this->language->get('text_authors_description');
            $data['text_empty_blog'] = $this->language->get('text_empty_blog');

            $data['articles'] = [];
            $filter_data = [
                'start' => ($page - 1) * $limit,
                'limit' => $limit
            ];

            $article_total = $this->model_catalog_information->getTotalBlogArticles();
            $results = $this->model_catalog_information->getBlogArticles($filter_data);

            foreach ($results as $result) {
                $image = $this->model_tool_image->resize('placeholder.png', 400, 300);
                $data['articles'][] = [
                    'information_id' => $result['information_id'],
                    'title' => $result['title'],
                    'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'viewed' => $result['viewed'],
                    'reading_time' => $result['reading_time'],
                    'image' => $image,
                    'href' => $this->url->link('information/information', 'information_id=' . $result['information_id'])
                ];
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
            $data['blog_category_id'] = 0;

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('information/blog_category', $data));
        }
    }
}
?>