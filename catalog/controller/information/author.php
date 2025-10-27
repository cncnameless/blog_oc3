<?php
class ControllerInformationAuthor extends Controller {
    public function index() {
        $this->load->language('information/author');
        
        $this->load->model('catalog/author');
        $this->load->model('catalog/information');
        $this->load->model('tool/image');

        // Определяем, показываем ли список авторов или конкретного автора
        if (isset($this->request->get['author_id'])) {
            $this->showAuthor((int)$this->request->get['author_id']);
        } else {
            $this->showAuthorsList();
        }
    }

    private function showAuthor($author_id) {
        // Проверяем существование таблицы
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if (!$table_exists->num_rows) {
            return $this->load->controller('error/not_found');
        }

        // Получаем информацию об авторе
        $author_info = $this->model_catalog_author->getAuthor($author_id);

        if ($author_info && $author_info['status']) {
            $this->document->setTitle($author_info['meta_title'] ? $author_info['meta_title'] : $author_info['name']);
            $this->document->setDescription($author_info['meta_description']);
            $this->document->setKeywords($author_info['meta_keyword']);

            $data['breadcrumbs'] = array();

            // ИСПРАВЛЕНО: Иконка домика для главной страницы
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

            // ИСПРАВЛЕНО: Последняя крошка без ссылки
            $data['breadcrumbs'][] = array(
                'text' => $author_info['name'],
                'href' => ''
            );

            $data['heading_title'] = $author_info['name'];
            $data['description'] = html_entity_decode($author_info['description'], ENT_QUOTES, 'UTF-8');
            $data['job_title'] = $author_info['job_title'];
            $data['bio'] = html_entity_decode($author_info['bio'], ENT_QUOTES, 'UTF-8');

            // Обработка изображения автора
            if ($author_info['image'] && file_exists(DIR_IMAGE . $author_info['image'])) {
                $data['image'] = $this->model_tool_image->resize($author_info['image'], 400, 400);
            } else {
                $data['image'] = $this->model_tool_image->resize('placeholder.png', 400, 400);
            }

            // Получаем статьи автора
            $data['articles'] = array();

            $filter_data = array(
                'filter_author_id' => $author_id,
                'start' => 0,
                'limit' => 20
            );

            // ИСПРАВЛЕНО: Проверяем существование таблицы перед подсчетом
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
            $data['microdata'] = array(
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'mainEntity' => array(
                    '@type' => 'Person',
                    'name' => $author_info['name'],
                    'jobTitle' => $author_info['job_title'],
                    'description' => strip_tags($author_info['description']),
                    'image' => $data['image']
                )
            );

            // ИСПРАВЛЕНО: Убрано дублирование - используем только article_total для шаблона
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
            // Автор не найден или неактивен
            $this->load->language('error/not_found');
            
            $this->document->setTitle($this->language->get('text_error'));

            $data['breadcrumbs'] = array();

            // ИСПРАВЛЕНО: Иконка домика для главной страницы
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
        
        // ИСПРАВЛЕНО: Иконка домика для главной страницы
        $data['breadcrumbs'][] = array(
            'text' => '<i class="fa fa-home"></i>',
            'href' => $this->url->link('common/home')
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_blog'),
            'href' => $this->url->link('information/blog_category')
        );

        // ИСПРАВЛЕНО: Последняя крошка без ссылки
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