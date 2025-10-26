<?php
class ControllerExtensionModuleBlogCategory extends Controller {
    public function index($setting) {
        $this->load->language('extension/module/blog_category');
        $this->load->model('catalog/blog_category');
        
        $data['categories'] = array();
        
        $categories = $this->model_catalog_blog_category->getBlogCategories();
        
        foreach ($categories as $category) {
            $data['categories'][] = array(
                'blog_category_id' => $category['blog_category_id'],
                'name' => $category['name'],
                'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $category['blog_category_id'])
            );
        }
        
        return $this->load->view('extension/module/blog_category', $data);
    }
}