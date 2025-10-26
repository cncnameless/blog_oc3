<?php
class ControllerCatalogBlogCategory extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_category->addBlogCategory($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            $url .= $this->urlFilter();
            $url .= $this->urlSortAndPage();

            $this->response->redirect($this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_category->editBlogCategory($this->request->get['blog_category_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            $url .= $this->urlFilter();
            $url .= $this->urlSortAndPage();

            $this->response->redirect($this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $blog_category_id) {
                $this->model_catalog_blog_category->deleteBlogCategory($blog_category_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            $url .= $this->urlFilter();
            $url .= $this->urlSortAndPage();

            $this->response->redirect($this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
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
        $url .= $this->urlFilter();
        $url .= $this->urlSortAndPage();

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('catalog/blog_category/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/blog_category/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['blog_categories'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $blog_category_total = $this->model_catalog_blog_category->getTotalBlogCategories();

        $results = $this->model_catalog_blog_category->getBlogCategories($filter_data);

        foreach ($results as $result) {
            $data['blog_categories'][] = array(
                'blog_category_id' => $result['blog_category_id'],
                'name'            => $result['name'],
                'sort_order'      => $result['sort_order'],
                'edit'            => $this->url->link('catalog/blog_category/edit', 'user_token=' . $this->session->data['user_token'] . '&blog_category_id=' . $result['blog_category_id'] . $url, true)
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');

        $data['column_name'] = $this->language->get('column_name');
        $data['column_sort_order'] = $this->language->get('column_sort_order');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');

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

        $data['selected'] = isset($this->request->post['selected']) ? $this->request->post['selected'] : array();

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_sort_order'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $blog_category_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($blog_category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($blog_category_total - $this->config->get('config_limit_admin'))) ? $blog_category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $blog_category_total, ceil($blog_category_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_category_list', $data));
    }

    protected function getForm() {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_form'] = !isset($this->request->get['blog_category_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['text_none'] = $this->language->get('text_none');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_description'] = $this->language->get('entry_description');
        $data['entry_meta_title'] = $this->language->get('entry_meta_title');
        $data['entry_meta_description'] = $this->language->get('entry_meta_description');
        $data['entry_meta_keyword'] = $this->language->get('entry_meta_keyword');
        $data['entry_parent'] = $this->language->get('entry_parent');
        $data['entry_image'] = $this->language->get('entry_image');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_store'] = $this->language->get('entry_store');
        $data['entry_keyword'] = $this->language->get('entry_keyword');

        $data['help_keyword'] = $this->language->get('help_keyword');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = array();
        }

        if (isset($this->error['meta_title'])) {
            $data['error_meta_title'] = $this->error['meta_title'];
        } else {
            $data['error_meta_title'] = array();
        }

        if (isset($this->error['parent'])) {
            $data['error_parent'] = $this->error['parent'];
        } else {
            $data['error_parent'] = '';
        }

        if (isset($this->error['keyword'])) {
            $data['error_keyword'] = $this->error['keyword'];
        } else {
            $data['error_keyword'] = array();
        }

        $url = '';

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
            'href' => $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['blog_category_id'])) {
            $data['action'] = $this->url->link('catalog/blog_category/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/blog_category/edit', 'user_token=' . $this->session->data['user_token'] . '&blog_category_id=' . $this->request->get['blog_category_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['blog_category_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $blog_category_info = $this->model_catalog_blog_category->getBlogCategory($this->request->get['blog_category_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

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

        if (isset($this->request->post['blog_category_description'])) {
            $data['blog_category_description'] = $this->request->post['blog_category_description'];
        } elseif (isset($this->request->get['blog_category_id'])) {
            $data['blog_category_description'] = $this->model_catalog_blog_category->getBlogCategoryDescriptions($this->request->get['blog_category_id']);
        } else {
            $data['blog_category_description'] = array();
        }

        if (isset($this->request->post['path'])) {
            $data['path'] = $this->request->post['path'];
        } elseif (!empty($blog_category_info)) {
            $data['path'] = $blog_category_info['path'];
        } else {
            $data['path'] = '';
        }

        if (isset($this->request->post['parent_id'])) {
            $data['parent_id'] = $this->request->post['parent_id'];
        } elseif (!empty($blog_category_info)) {
            $data['parent_id'] = $blog_category_info['parent_id'];
        } else {
            $data['parent_id'] = 0;
        }

        if (isset($this->request->post['blog_category_seo_url'])) {
            $data['blog_category_seo_url'] = $this->request->post['blog_category_seo_url'];
        } elseif (isset($this->request->get['blog_category_id'])) {
            $data['blog_category_seo_url'] = $this->model_catalog_blog_category->getBlogCategorySeoUrls($this->request->get['blog_category_id']);
        } else {
            $data['blog_category_seo_url'] = array();
        }

        $this->load->model('tool/image');

        if (isset($this->request->post['image'])) {
            $data['image'] = $this->request->post['image'];
        } elseif (!empty($blog_category_info)) {
            $data['image'] = $blog_category_info['image'];
        } else {
            $data['image'] = '';
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
        } elseif (!empty($blog_category_info) && is_file(DIR_IMAGE . $blog_category_info['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($blog_category_info['image'], 100, 100);
        } else {
            $data['thumb'] = $data['placeholder'];
        }

        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = $this->request->post['sort_order'];
        } elseif (!empty($blog_category_info)) {
            $data['sort_order'] = $blog_category_info['sort_order'];
        } else {
            $data['sort_order'] = 0;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($blog_category_info)) {
            $data['status'] = $blog_category_info['status'];
        } else {
            $data['status'] = true;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_category_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'catalog/blog_category')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['blog_category_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }

            if ((utf8_strlen($value['meta_title']) < 1) || (utf8_strlen($value['meta_title']) > 255)) {
                $this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
            }
        }

        if (isset($this->request->get['blog_category_id']) && ($this->request->post['parent_id'] == $this->request->get['blog_category_id'])) {
            $this->error['parent'] = $this->language->get('error_parent');
        }

        // Валидация SEO URL
        if ($this->request->post['blog_category_seo_url']) {
            $this->load->model('design/seo_url');

            foreach ($this->request->post['blog_category_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        if (count(array_keys($language, $keyword)) > 1) {
                            $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
                        }

                        $seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

                        foreach ($seo_urls as $seo_url) {
                            if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['blog_category_id']) || ($seo_url['query'] != 'blog_category_id=' . $this->request->get['blog_category_id']))) {
                                $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');
                                break;
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
        if (!$this->user->hasPermission('modify', 'catalog/blog_category')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function urlFilter() {
        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        return $url;
    }

    protected function urlSortAndPage() {
        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        return $url;
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/blog_category');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'sort'        => 'name',
                'order'       => 'ASC',
                'start'       => 0,
                'limit'       => 5
            );

            $results = $this->model_catalog_blog_category->getBlogCategories($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'blog_category_id' => $result['blog_category_id'],
                    'name'             => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
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