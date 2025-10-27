<?php
class ControllerInformationInformation extends Controller {
    public function index() {
        $this->load->language('information/information');
        $this->load->language('information/blog_category'); // Загружаем языковой файл блога

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
            // Увеличиваем счетчик просмотров
            $this->model_catalog_information->updateViewed($information_id);

            $this->document->setTitle($information_info['meta_title']);
            $this->document->setDescription($information_info['meta_description']);
            $this->document->setKeywords($information_info['meta_keyword']);

            $data['breadcrumbs'] = array();

            // ИСПРАВЛЕНО: Иконка домика для главной страницы
            $data['breadcrumbs'][] = array(
                'text' => '<i class="fa fa-home"></i>',
                'href' => $this->url->link('common/home')
            );

            // Проверяем, является ли статья частью блога
            $blog_categories = $this->model_catalog_information->getInformationBlogCategories($information_id);
            
            if ($blog_categories) {
                // Это статья блога - формируем хлебные крошки через категории блога
                $blog_category_id = $blog_categories[0]; // Берем первую категорию
                
                // Добавляем главную страницу блога
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category')
                );

                // Получаем путь категории для хлебных крошек
                $category_paths = $this->model_catalog_blog_category->getBlogCategoryPaths($blog_category_id);
                
                // ИСПРАВЛЕНО: Убираем дублирование последней категории
                // Метод getBlogCategoryPaths возвращает полный путь, включая текущую категорию
                foreach ($category_paths as $path) {
                    // Добавляем все категории пути, включая текущую
                    $data['breadcrumbs'][] = array(
                        'text' => $path['name'],
                        'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $path['blog_category_id'])
                    );
                }
                
                // ИСПРАВЛЕНО: Убираем добавление текущей категории отдельно, так как она уже есть в category_paths
                // Теперь сразу переходим к добавлению статьи

            } else {
                // Обычная информационная страница - категории информационных страниц (если есть)
                $categories = $this->getInformationCategories($information_id);
                foreach ($categories as $category) {
                    $data['breadcrumbs'][] = array(
                        'text' => $category['name'],
                        'href' => $category['href']
                    );
                }
            }

            // ИСПРАВЛЕНО: Сама статья всегда без ссылки (во избежание дублей)
            $data['breadcrumbs'][] = array(
                'text' => $information_info['title'],
                'href' => ''
            );

            $data['heading_title'] = $information_info['title'];
            $data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

            // Данные для статей блога
            if ($blog_categories) {
                $data['date_added'] = date($this->language->get('date_format_short'), strtotime($information_info['date_added']));
                $data['viewed'] = $information_info['viewed'];
                $data['reading_time'] = $information_info['reading_time'];

                // Получаем авторов статьи
                $authors_data = $this->model_catalog_author->getAuthorsByInformation($information_id);
                $data['authors'] = array();

                foreach ($authors_data as $author) {
                    if ($author['image'] && file_exists(DIR_IMAGE . $author['image'])) {
                        $image = $this->model_tool_image->resize($author['image'], 80, 80);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', 80, 80);
                    }

                    $data['authors'][] = array(
                        'name' => $author['name'],
                        'job_title' => $author['job_title'],
                        'bio' => utf8_substr(strip_tags(html_entity_decode($author['bio'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                        'image' => $image,
                        'href' => $this->url->link('information/author', 'author_id=' . $author['author_id']),
                        'is_primary' => $author['is_primary']
                    );
                }

                // Microdata для Schema.org
                $data['microdata'] = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $information_info['title'],
                    'datePublished' => $information_info['date_added'],
                    'dateModified' => $information_info['date_modified'],
                    'mainEntityOfPage' => $this->url->link('information/information', 'information_id=' . $information_id),
                    'author' => array(),
                    'publisher' => array(
                        '@type' => 'Organization',
                        'name' => $this->config->get('config_name'),
                        'logo' => array(
                            '@type' => 'ImageObject',
                            'url' => $this->config->get('config_url') . 'image/' . $this->config->get('config_logo')
                        )
                    ),
                    'description' => strip_tags(html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8'))
                );

                // Добавляем авторов в микроразметку
                foreach ($data['authors'] as $author) {
                    $data['microdata']['author'][] = array(
                        '@type' => 'Person',
                        'name' => $author['name'],
                        'jobTitle' => $author['job_title']
                    );
                }

                // Языковые переменные для блога
                $data['text_views'] = $this->language->get('text_views');
                $data['text_reading_time'] = $this->language->get('text_reading_time');
            } else {
                // Для обычных информационных страниц
                $data['date_added'] = '';
                $data['viewed'] = '';
                $data['reading_time'] = '';
                $data['authors'] = array();
                $data['microdata'] = array();
                $data['text_views'] = '';
                $data['text_reading_time'] = '';
            }

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

            // ИСПРАВЛЕНО: Иконка домика для главной страницы
            $data['breadcrumbs'][] = array(
                'text' => '<i class="fa fa-home"></i>',
                'href' => $this->url->link('common/home')
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

    /**
     * Получает категории информационной страницы (для обычных страниц)
     */
    private function getInformationCategories($information_id) {
        // Если у вас есть категории для информационных страниц, реализуйте этот метод
        // Пока возвращаем пустой массив
        return array();
    }
}
?>