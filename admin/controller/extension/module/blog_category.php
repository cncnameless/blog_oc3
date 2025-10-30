<?php
class ControllerExtensionModuleBlogCategory extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/blog_category');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('localisation/language');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_blog_category', $this->request->post);
            
            // Сохраняем SEO URL для главной блога
            if (isset($this->request->post['module_blog_category_main_keyword'])) {
                $this->saveBlogMainSeoUrl($this->request->post['module_blog_category_main_keyword']);
            }
            
            // Сохраняем SEO данные для главной блога
            if (isset($this->request->post['blog_home_description'])) {
                $this->saveBlogHomeData($this->request->post['blog_home_description']);
            }
            
            // Сохраняем SEO данные для списка авторов
            if (isset($this->request->post['author_list_description'])) {
                $this->saveAuthorListData($this->request->post['author_list_description']);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/blog_category', 'user_token=' . $this->session->data['user_token'], true))
        );

        $data['action'] = $this->url->link('extension/module/blog_category', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['user_token'] = $this->session->data['user_token'];
        
        // Основные настройки
        if (isset($this->request->post['module_blog_category_status'])) {
            $data['module_blog_category_status'] = $this->request->post['module_blog_category_status'];
        } else {
            $data['module_blog_category_status'] = $this->config->get('module_blog_category_status');
        }
        
        // SEO URL главной блога
        if (isset($this->request->post['module_blog_category_main_keyword'])) {
            $data['module_blog_category_main_keyword'] = $this->request->post['module_blog_category_main_keyword'];
        } else {
            $data['module_blog_category_main_keyword'] = $this->getBlogMainKeyword();
        }

        // Загружаем языки
        $data['languages'] = $this->model_localisation_language->getLanguages();
        
        // SEO данные главной блога
        $data['blog_home_description'] = array();
        foreach ($data['languages'] as $language) {
            $home_data = $this->getBlogHomeData($language['language_id']);
            $data['blog_home_description'][$language['language_id']] = array(
                'name' => $home_data['name'],
                'h1' => $home_data['h1'],
                'meta_title' => $home_data['meta_title'],
                'meta_description' => $home_data['meta_description'],
                'meta_keyword' => $home_data['meta_keyword'],
                'description' => $home_data['description']
            );
        }
        
        // SEO данные списка авторов
        $data['author_list_description'] = array();
        foreach ($data['languages'] as $language) {
            $author_list_data = $this->getAuthorListData($language['language_id']);
            $data['author_list_description'][$language['language_id']] = array(
                'name' => $author_list_data['name'],
                'h1' => $author_list_data['h1'],
                'meta_title' => $author_list_data['meta_title'],
                'meta_description' => $author_list_data['meta_description'],
                'meta_keyword' => $author_list_data['meta_keyword'],
                'description' => $author_list_data['description']
            );
        }

        // Настройки размеров изображений
        $image_settings = array(
            'blog_author_article_width', 'blog_author_article_height',
            'blog_author_page_width', 'blog_author_page_height',
            'blog_author_list_image_width', 'blog_author_list_image_height',
            'blog_article_image_width', 'blog_article_image_height',
            'blog_category_image_width', 'blog_category_image_height'
        );
        
        foreach ($image_settings as $setting) {
            if (isset($this->request->post[$setting])) {
                $data[$setting] = $this->request->post[$setting];
            } else {
                $data[$setting] = $this->config->get($setting);
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/blog_category', $data));
    }

    public function install() {
        $this->load->model('extension/module/blog_category');
        $this->model_extension_module_blog_category->install();
    }

    public function uninstall() {
        $this->load->model('extension/module/blog_category');
        $this->model_extension_module_blog_category->uninstall();
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/blog_category')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
    
    private function saveBlogMainSeoUrl($keyword) {
        // Удаляем старые записи
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information/blog_category'");
        
        // Добавляем новые записи для всех языков
        $languages = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE status = '1'");
        
        foreach ($languages->rows as $language) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET 
                store_id = 0, 
                language_id = '" . (int)$language['language_id'] . "', 
                query = 'information/blog_category', 
                keyword = '" . $this->db->escape($keyword) . "'");
        }
    }
    
    private function getBlogMainKeyword() {
        $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'information/blog_category' AND store_id = 0 AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row['keyword'];
        }
        
        return 'blog'; // значение по умолчанию
    }
    
    private function saveBlogHomeData($data) {
        foreach ($data as $language_id => $values) {
            $this->db->query("REPLACE INTO " . DB_PREFIX . "blog_home_description SET 
                language_id = '" . (int)$language_id . "',
                name = '" . $this->db->escape($values['name']) . "',
                h1 = '" . $this->db->escape($values['h1']) . "',
                meta_title = '" . $this->db->escape($values['meta_title']) . "',
                meta_description = '" . $this->db->escape($values['meta_description']) . "',
                meta_keyword = '" . $this->db->escape($values['meta_keyword']) . "',
                description = '" . $this->db->escape($values['description']) . "'");
        }
    }
    
    private function saveAuthorListData($data) {
        foreach ($data as $language_id => $values) {
            $this->db->query("REPLACE INTO " . DB_PREFIX . "author_list_description SET 
                language_id = '" . (int)$language_id . "',
                name = '" . $this->db->escape($values['name']) . "',
                h1 = '" . $this->db->escape($values['h1']) . "',
                meta_title = '" . $this->db->escape($values['meta_title']) . "',
                meta_description = '" . $this->db->escape($values['meta_description']) . "',
                meta_keyword = '" . $this->db->escape($values['meta_keyword']) . "',
                description = '" . $this->db->escape($values['description']) . "'");
        }
    }
    
    private function getBlogHomeData($language_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_home_description WHERE language_id = '" . (int)$language_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        }
        
        return array(
            'name' => 'Блог',
            'h1' => 'Блог',
            'meta_title' => 'Блог',
            'meta_description' => '',
            'meta_keyword' => '',
            'description' => ''
        );
    }
    
    private function getAuthorListData($language_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "author_list_description WHERE language_id = '" . (int)$language_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        }
        
        return array(
            'name' => 'Авторы',
            'h1' => 'Наши авторы',
            'meta_title' => 'Авторы',
            'meta_description' => '',
            'meta_keyword' => '',
            'description' => ''
        );
    }
}
?>