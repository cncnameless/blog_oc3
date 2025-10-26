<?php
class ControllerExtensionModuleBlogCategory extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/blog_category');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_blog_category', $this->request->post);
            
            // Сохраняем SEO URL для главной блога
            if (isset($this->request->post['module_blog_category_main_keyword'])) {
                $this->saveBlogMainSeoUrl($this->request->post['module_blog_category_main_keyword']);
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
        
        // Статус модуля
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

        // Языковые переменные
        $data['entry_main_keyword'] = $this->language->get('entry_main_keyword');
        $data['help_main_keyword'] = $this->language->get('help_main_keyword');

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
}
?>