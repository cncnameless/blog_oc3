<?php
class ControllerInformationInformation extends Controller {
    public function index() {
        $this->load->language('information/information');
        $this->load->model('catalog/information');
        $this->load->model('catalog/blog_category');
        $this->load->model('catalog/author');
        $this->load->model('tool/image');

        $data['breadcrumbs'] = array();

        // ИСПРАВЛЕНО #3: Иконка домой
        $data['breadcrumbs'][] = array(
            'text' => '<i class="fa fa-home"></i>',
            'href' => $this->url->link('common/home')
        );

        if (isset($this->request->get['information_id'])) {
            $information_id = (int)$this->request->get['information_id'];
        } else {
            $information_id = 0;
        }

        $information_info = $this->model_catalog_information->getInformation($information_id);

        if ($information_info) {
            $this->model_catalog_information->updateViewed($information_id);

            $this->document->setTitle($information_info['meta_title']);
            $this->document->setDescription($information_info['meta_description']);
            $this->document->setKeywords($information_info['meta_keyword']);

            $blog_categories = $this->model_catalog_information->getInformationBlogCategories($information_id);

            if ($blog_categories && isset($this->request->get['blog_path'])) {
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category')
                );

                $blog_path = $this->request->get['blog_path'];
                $parts = explode('_', $blog_path);
                $current_path = '';

                foreach ($parts as $blog_category_id) {
                    $category_info = $this->model_catalog_blog_category->getBlogCategory($blog_category_id);
                    
                    if ($category_info) {
                        $current_path .= $current_path ? '_' . $blog_category_id : $blog_category_id;
                        
                        $data['breadcrumbs'][] = array(
                            'text' => $category_info['name'],
                            'href' => $this->url->link('information/blog_category', 'blog_path=' . $current_path)
                        );
                    }
                }
            } else if ($blog_categories) {
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category')
                );

                $first_category_id = $blog_categories[0];
                $blog_category_info = $this->model_catalog_blog_category->getBlogCategory($first_category_id);

                if ($blog_category_info) {
                    $data['breadcrumbs'][] = array(
                        'text' => $blog_category_info['name'],
                        'href' => $this->url->link('information/blog_category', 'blog_path=' . $first_category_id)
                    );
                }
            }

            $data['breadcrumbs'][] = array(
                'text' => $information_info['title'],
                'href' => ''
            );

            $data['heading_title'] = $information_info['title'];
            $data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

            // УДАЛЕНО #4: Блок blog_categories для избежания дубликата ссылок
            $data['blog_categories'] = array();

            $data['date_added'] = date($this->language->get('date_format_short'), strtotime($information_info['date_added']));
            $data['viewed'] = $information_info['viewed'];
            $data['reading_time'] = $information_info['reading_time'];

            $data['authors'] = array();
            $article_authors = $this->model_catalog_information->getInformationAuthors($information_id);
            
            foreach ($article_authors as $author_data) {
                $author_info = $this->model_catalog_author->getAuthor($author_data['author_id']);
                if ($author_info) {
                    if ($author_info['image']) {
                        $image = $this->model_tool_image->resize($author_info['image'], 100, 100);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', 100, 100);
                    }
                    
                    $data['authors'][] = array(
                        'author_id' => $author_info['author_id'],
                        'name' => $author_info['name'],
                        'job_title' => $author_info['job_title'],
                        'bio' => utf8_substr(strip_tags(html_entity_decode($author_info['bio'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                        'image' => $image,
                        'is_primary' => $author_data['is_primary'],
                        'href' => $this->url->link('information/author', 'author_id=' . $author_info['author_id'])
                    );
                }
            }

            $data['microdata'] = $this->getArticleMicrodata($information_info, $data['authors']);

            $data['text_views'] = $this->language->get('text_views');
            $data['text_reading_time'] = $this->language->get('text_reading_time');
            $data['continue'] = $this->url->link('common/home');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('information/information', $data));
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_error'),
                'href' => ''
            );

            $this->document->setTitle($this->language->get('text_error'));
            $data['heading_title'] = $this->language->get('text_error');
            $data['text_error'] = $this->language->get('text_error');
            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    private function getArticleMicrodata($article_info, $authors) {
        $microdata = array(
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $article_info['title'],
            'datePublished' => $article_info['date_added'],
            'dateModified' => $article_info['date_modified'] ? $article_info['date_modified'] : $article_info['date_added'],
            'author' => array(),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => $this->config->get('config_name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->config->get('config_url') . 'image/' . $this->config->get('config_logo')
                )
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => $this->url->link('information/information', 'information_id=' . $article_info['information_id'])
            )
        );

        foreach ($authors as $author) {
            $microdata['author'][] = array(
                '@type' => 'Person',
                'name' => $author['name'],
                'jobTitle' => $author['job_title'],
                'url' => $author['href']
            );
        }

        return $microdata;
    }

    public function agree() {
        $this->load->model('catalog/information');

        if (isset($this->request->get['information_id'])) {
            $information_id = (int)$this->request->get['information_id'];
        } else {
            $information_id = 0;
        }

        $output = '';
        $information_info = $this->model_catalog_information->getInformation($information_id);

        if ($information_info) {
            $output = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
        }

        $this->response->setOutput($output);
    }
}
?>