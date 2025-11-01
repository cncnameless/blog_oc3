<?php
class ControllerCatalogInformation extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/information');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/information');

        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/information');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/information');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_information->addInformation($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_blog_category'])) {
                $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/information');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/information');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_information->editInformation($this->request->get['information_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_blog_category'])) {
                $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/information');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/information');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $information_id) {
                $this->model_catalog_information->deleteInformation($information_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_blog_category'])) {
                $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_blog_category'])) {
            $filter_blog_category = $this->request->get['filter_blog_category'];
        } else {
            $filter_blog_category = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'id.title';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_blog_category'])) {
            $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('catalog/information/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/information/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['informations'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_blog_category' => $filter_blog_category,
            'filter_status' => $filter_status,
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $information_total = $this->model_catalog_information->getTotalInformations($filter_data);

        $results = $this->model_catalog_information->getInformations($filter_data);

        foreach ($results as $result) {
            $data['informations'][] = array(
                'information_id' => $result['information_id'],
                'title'          => $result['title'],
                'sort_order'     => $result['sort_order'],
                'status'         => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'view'           => HTTPS_CATALOG . 'index.php?route=information/information&information_id=' . $result['information_id'],
                'edit'           => $this->url->link('catalog/information/edit', 'user_token=' . $this->session->data['user_token'] . '&information_id=' . $result['information_id'] . $url, true)
            );
        }

        $data['filter_name'] = $filter_name;
        $data['filter_blog_category'] = $filter_blog_category;
        $data['filter_status'] = $filter_status;

        $data['sort'] = $sort;
        $data['order'] = $order;

        // ИСПРАВЛЕНИЕ: Добавляем проверку перед загрузкой категорий блога
        $data['blog_categories'] = array();
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category'");
        if ($table_exists->num_rows) {
            $this->load->model('catalog/blog_category');
            $data['blog_categories'] = $this->model_catalog_blog_category->getBlogCategories(array());
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_blog_category'])) {
            $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_title'] = $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . '&sort=id.title' . $url, true);
        $data['sort_sort_order'] = $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . '&sort=i.sort_order' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_blog_category'])) {
            $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $information_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($information_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($information_total - $this->config->get('config_limit_admin'))) ? $information_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $information_total, ceil($information_total / $this->config->get('config_limit_admin')));

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/information_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['information_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['title'])) {
            $data['error_title'] = $this->error['title'];
        } else {
            $data['error_title'] = array();
        }

        if (isset($this->error['description'])) {
            $data['error_description'] = $this->error['description'];
        } else {
            $data['error_description'] = array();
        }

        if (isset($this->error['meta_title'])) {
            $data['error_meta_title'] = $this->error['meta_title'];
        } else {
            $data['error_meta_title'] = array();
        }

        if (isset($this->error['keyword'])) {
            $data['error_keyword'] = $this->error['keyword'];
        } else {
            $data['error_keyword'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_blog_category'])) {
            $url .= '&filter_blog_category=' . urlencode(html_entity_decode($this->request->get['filter_blog_category'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['information_id'])) {
            $data['action'] = $this->url->link('catalog/information/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/information/edit', 'user_token=' . $this->session->data['user_token'] . '&information_id=' . $this->request->get['information_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['information_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $information_info = $this->model_catalog_information->getInformation($this->request->get['information_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['information_description'])) {
            $data['information_description'] = $this->request->post['information_description'];
        } elseif (isset($this->request->get['information_id'])) {
            $data['information_description'] = $this->model_catalog_information->getInformationDescriptions($this->request->get['information_id']);
        } else {
            $data['information_description'] = array();
        }

        $this->load->model('setting/store');

        $data['stores'] = array();
        
        $data['stores'][] = array(
            'store_id' => 0,
            'name'     => $this->language->get('text_default')
        );
        
        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $data['stores'][] = array(
                'store_id' => $store['store_id'],
                'name'     => $store['name']
            );
        }

        if (isset($this->request->post['information_store'])) {
            $data['information_store'] = $this->request->post['information_store'];
        } elseif (isset($this->request->get['information_id'])) {
            $data['information_store'] = $this->model_catalog_information->getInformationStores($this->request->get['information_id']);
        } else {
            $data['information_store'] = array(0);
        }

        // ИСПРАВЛЕНИЕ: Добавляем проверку перед загрузкой категорий блога
        $data['blog_categories'] = array();
        $table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "blog_category'");
        if ($table_exists->num_rows) {
            $this->load->model('catalog/blog_category');
            $data['blog_categories'] = $this->model_catalog_blog_category->getBlogCategories(array());
        }

        if (isset($this->request->post['information_blog_category'])) {
            $data['information_blog_category'] = $this->request->post['information_blog_category'];
        } elseif (isset($this->request->get['information_id'])) {
            $data['information_blog_category'] = $this->model_catalog_information->getInformationBlogCategories($this->request->get['information_id']);
        } else {
            $data['information_blog_category'] = array();
        }

        // === ДОБАВЛЯЕМ БЛОК АВТОРОВ ===
        $data['authors'] = array();
        $author_table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "article_author'");
        if ($author_table_exists->num_rows) {
            $this->load->model('catalog/author');
            $data['authors'] = $this->model_catalog_author->getAuthors(array('filter_status' => 1));
        }

        if (isset($this->request->post['information_author'])) {
            $data['information_author'] = $this->request->post['information_author'];
        } elseif (isset($this->request->get['information_id'])) {
            $data['information_author'] = $this->model_catalog_information->getInformationAuthors($this->request->get['information_id']);
        } else {
            $data['information_author'] = array();
        }

        // Формируем массив выбранных авторов
        $data['selected_authors'] = array();
        foreach ($data['information_author'] as $author_data) {
            foreach ($data['authors'] as $author) {
                if ((int)$author['author_id'] == (int)$author_data['author_id']) {
                    $data['selected_authors'][] = array(
                        'author_id' => $author['author_id'],
                        'name' => $author['name'],
                        'sort_order' => $author_data['sort_order'],
                        'is_primary' => $author_data['is_primary']
                    );
                    break;
                }
            }
        }
        // === КОНЕЦ БЛОКА АВТОРОВ ===

        // === ДОБАВЛЯЕМ БЛОК ТЕГОВ ===
        if (isset($this->request->post['information_tags'])) {
            $data['information_tags'] = $this->request->post['information_tags'];
        } elseif (isset($this->request->get['information_id'])) {
            $tags_data = $this->model_catalog_information->getInformationTags($this->request->get['information_id']);
            $data['information_tags'] = array();
            foreach ($tags_data as $tag) {
                $data['information_tags'][] = $tag['name'];
            }
        } else {
            $data['information_tags'] = array();
        }

        // Формируем массив выбранных тегов для отображения
        $data['selected_tags'] = array();
        if (isset($data['information_tags']) && is_array($data['information_tags'])) {
            foreach ($data['information_tags'] as $tag_name) {
                $data['selected_tags'][] = array(
                    'tag_id' => 0, // Временный ID, не используется
                    'name' => $tag_name
                );
            }
        }
        // === КОНЕЦ БЛОКА ТЕГОВ ===

        // Формируем массив выбранных категорий
        $data['selected_blog_categories'] = array();
        foreach ($data['information_blog_category'] as $blog_category_id) {
            foreach ($data['blog_categories'] as $blog_category) {
                if ((int)$blog_category['blog_category_id'] == (int)$blog_category_id) {
                    $data['selected_blog_categories'][] = $blog_category;
                    break;
                }
            }
        }

        // Добавляем поле даты
        if (isset($this->request->post['date_added'])) {
            $data['date_added'] = $this->request->post['date_added'];
        } elseif (!empty($information_info)) {
            $data['date_added'] = ($information_info['date_added'] != '0000-00-00 00:00:00') ? $information_info['date_added'] : date('Y-m-d H:i:s');
        } else {
            $data['date_added'] = date('Y-m-d H:i:s');
        }

        $data['entry_date_added'] = $this->language->get('entry_date_added');

        // === ДОБАВЛЯЕМ ДАННЫЕ ДЛЯ МИКРОРАЗМЕТКИ ===
        if (isset($this->request->post['schema_type'])) {
            $data['schema_type'] = $this->request->post['schema_type'];
        } elseif (!empty($information_info)) {
            $data['schema_type'] = $information_info['schema_type'];
        } else {
            $data['schema_type'] = 'BlogPosting';
        }

        if (isset($this->request->post['rating_value'])) {
            $data['rating_value'] = $this->request->post['rating_value'];
        } elseif (!empty($information_info)) {
            $data['rating_value'] = $information_info['rating_value'];
        } else {
            $data['rating_value'] = '';
        }

        // Типы разметки для select
        $data['schema_types'] = array(
            'Organization' => 'Organization',
            'Review' => 'Review', 
            'BlogPosting' => 'BlogPosting',
            'NewsArticle' => 'NewsArticle'
        );
        // === КОНЕЦ БЛОКА МИКРОРАЗМЕТКИ ===

        // === ДОБАВЛЯЕМ БЛОК ИЗОБРАЖЕНИЯ ДЛЯ СТАТЬИ ===
        $this->load->model('tool/image');

        if (isset($this->request->post['image'])) {
            $data['image'] = $this->request->post['image'];
        } elseif (!empty($information_info)) {
            $data['image'] = $information_info['image'];
        } else {
            $data['image'] = '';
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
        } elseif (!empty($information_info) && is_file(DIR_IMAGE . $information_info['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($information_info['image'], 100, 100);
        } else {
            $data['thumb'] = $data['placeholder'];
        }
        // === КОНЕЦ БЛОКА ИЗОБРАЖЕНИЯ ===

        if (isset($this->request->post['bottom'])) {
            $data['bottom'] = $this->request->post['bottom'];
        } elseif (!empty($information_info)) {
            $data['bottom'] = $information_info['bottom'];
        } else {
            $data['bottom'] = 0;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($information_info)) {
            $data['status'] = $information_info['status'];
        } else {
            $data['status'] = true;
        }

        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = $this->request->post['sort_order'];
        } elseif (!empty($information_info)) {
            $data['sort_order'] = $information_info['sort_order'];
        } else {
            $data['sort_order'] = '';
        }

        if (isset($this->request->post['no_index'])) {
            $data['no_index'] = $this->request->post['no_index'];
        } elseif (isset($information_info['no_index'])) {
            $data['no_index'] = $information_info['no_index'];
        } else {
            $data['no_index'] = 0;
        }

        if (isset($this->request->post['information_seo_url'])) {
            $data['information_seo_url'] = $this->request->post['information_seo_url'];
        } elseif (isset($this->request->get['information_id'])) {
            $data['information_seo_url'] = $this->model_catalog_information->getInformationSeoUrls($this->request->get['information_id']);
        } else {
            $data['information_seo_url'] = array();
        }

        if (isset($this->request->post['information_layout'])) {
            $data['information_layout'] = $this->request->post['information_layout'];
        } elseif (isset($this->request->get['information_id'])) {
            $data['information_layout'] = $this->model_catalog_information->getInformationLayouts($this->request->get['information_id']);
        } else {
            $data['information_layout'] = array();
        }

        $this->load->model('design/layout');

        $data['layouts'] = $this->model_design_layout->getLayouts();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/information_form', $data));
    }

    protected function validateForm() {
        // Автоматически заполняем meta_title из title, если он пустой
        if (isset($this->request->post['information_description'])) {
            foreach ($this->request->post['information_description'] as $language_id => $value) {
                if (empty($value['meta_title']) && !empty($value['title'])) {
                    $this->request->post['information_description'][$language_id]['meta_title'] = $value['title'];
                }
            }
        }

        if (!$this->user->hasPermission('modify', 'catalog/information')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // Проверяем наличие description данных
        if (!isset($this->request->post['information_description']) || !is_array($this->request->post['information_description'])) {
            $this->error['warning'] = $this->language->get('error_warning');
            return false;
        }

        foreach ($this->request->post['information_description'] as $language_id => $value) {
            if ((!isset($value['title']) || utf8_strlen($value['title']) < 1) || (utf8_strlen($value['title']) > 64)) {
                $this->error['title'][$language_id] = $this->language->get('error_title');
            }

            if (!isset($value['description']) || utf8_strlen($value['description']) < 3) {
                $this->error['description'][$language_id] = $this->language->get('error_description');
            }

            // Проверка meta_title только на максимальную длину
            if (isset($value['meta_title']) && utf8_strlen($value['meta_title']) > 255) {
                $this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
            }
        }

        // Проверка рейтинга для типа Review
        if (isset($this->request->post['schema_type']) && $this->request->post['schema_type'] == 'Review') {
            if (!isset($this->request->post['rating_value']) || $this->request->post['rating_value'] === '') {
                $this->error['warning'] = 'Для типа Review необходимо указать значение рейтинга';
            } else {
                $rating = (float)$this->request->post['rating_value'];
                if ($rating < 1 || $rating > 5) {
                    $this->error['warning'] = 'Значение рейтинга должно быть от 1 до 5';
                }
            }
        }

        if (isset($this->request->post['information_seo_url'])) {
            $this->load->model('design/seo_url');

            foreach ($this->request->post['information_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        if (count(array_keys($language, $keyword)) > 1) {
                            $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
                        }

                        $seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

                        foreach ($seo_urls as $seo_url) {
                            if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['information_id']) || ($seo_url['query'] != 'information_id=' . $this->request->get['information_id']))) {
                                $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');
                            }
                        }
                    }
                }
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'catalog/information')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->load->model('setting/store');

        foreach ($this->request->post['selected'] as $information_id) {
            if ($this->config->get('config_account_id') == $information_id) {
                $this->error['warning'] = $this->language->get('error_account');
            }

            if ($this->config->get('config_checkout_id') == $information_id) {
                $this->error['warning'] = $this->language->get('error_checkout');
            }

            if ($this->config->get('config_return_id') == $information_id) {
                $this->error['warning'] = $this->language->get('error_return');
            }

            $store_total = $this->model_setting_store->getTotalStoresByInformationId($information_id);

            if ($store_total) {
                $this->error['warning'] = sprintf($this->language->get('error_store'), $store_total);
            }
        }

        return !$this->error;
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/information');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'sort'        => 'id.title',
                'order'       => 'ASC',
                'start'       => 0,
                'limit'       => 5
            );

            $results = $this->model_catalog_information->getInformations($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'information_id' => $result['information_id'],
                    'title'          => strip_tags(html_entity_decode($result['title'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['title'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // === ДОБАВЛЯЕМ МЕТОД ДЛЯ АВТОДОПОЛНЕНИЯ ТЕГОВ ===
    public function autocompleteTags() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/information');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name']
            );

            $results = $this->model_catalog_information->getTags($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'tag_id' => $result['tag_id'],
                    'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
?>