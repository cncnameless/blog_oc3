<?php
class ModelExtensionModuleBlogCategory extends Model {
    public function getBlogCategories() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category c LEFT JOIN " . DB_PREFIX . "blog_category_description cd ON (c.blog_category_id = cd.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_to_store cs ON (c.blog_category_id = cs.blog_category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cs.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1' ORDER BY c.sort_order, cd.name ASC");
        return $query->rows;
    }
}