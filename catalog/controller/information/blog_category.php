<?php
class ControllerInformationBlogCategory extends Controller {
    public function index() {
        $this->load->language('information/blog_category');
        $this->load->model('catalog/blog_category');
        $this->load->model('catalog/information');
        $this->load->model('tool/image');
        
        $author_model_loaded = false;
        if (is_file(DIR_APPLICATION . 'model/catalog/author.php')) {
            $this->load->model('catalog/author');
            $author_model_loaded = true;
        }

        if (isset($this->request->get['blog_category_id'])) {
            $blog_category_id = (int)$this->request->get['blog_category_id'];
        } else {
            $blog_category_id = 0;
        }

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = 12;

        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', '', true)
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

                $data['breadcrumbs'][] = [
                    'text' => $this->language->get('text_blog'),
                    'href' => $this->url->link('information/blog_category', '', true)
                ];

                $category_paths = $this->model_catalog_blog_category->getBlogCategoryPaths($blog_category_id);
                
                foreach ($category_paths as $path) {
                    if ($path['blog_category_id'] != $blog_category_id) {
                        $parent_info = $this->model_catalog_blog_category->getBlogCategory($path['blog_category_id']);
                        
                        if ($parent_info) {
                            $data['breadcrumbs'][] = [
                                'text' => $parent_info['name'],
                                'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $path['blog_category_id'], true)
                            ];
                        }
                    }
                }

                $data['breadcrumbs'][] = [
                    'text' => $category_info['name'],
                    'href' => ''
                ];

                $data['subcategories'] = [];
                $subcategories = $this->model_catalog_blog_category->getChildBlogCategories($blog_category_id);
                
                foreach ($subcategories as $subcategory) {
                    $data['subcategories'][] = [
                        'blog_category_id' => $subcategory['blog_category_id'],
                        'name' => $subcategory['name'],
                        'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $subcategory['blog_category_id'], true)
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
                    
                    $author_names = [];
                    $authors_data = [];
                    
                    if ($author_model_loaded) {
                        $schema_type = $result['schema_type'] ?? 'BlogPosting';
                        $should_have_authors = in_array($schema_type, ['BlogPosting', 'NewsArticle', 'Review']);
                        
                        if ($should_have_authors) {
                            $authors = $this->model_catalog_author->getAuthorsByInformationId($result['information_id']);
                            
                            foreach ($authors as $author) {
                                $author_names[] = $author['name'];
                                
                                $author_image = '';
                                if (!empty($author['image']) && file_exists(DIR_IMAGE . $author['image'])) {
                                    $author_image = $this->model_tool_image->resize($author['image'], 80, 80);
                                } else {
                                    $author_image = $this->model_tool_image->resize('placeholder.png', 80, 80);
                                }
                                
                                $authors_data[] = [
                                    'name' => $author['name'],
                                    'job_title' => $author['job_title'] ?? '',
                                    'image' => $author_image,
                                    'href' => $this->url->link('information/author', 'author_id=' . $author['author_id'], true)
                                ];
                            }
                        }
                    }
                    
                    $data['articles'][] = [
                        'information_id' => $result['information_id'],
                        'title' => $result['title'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                        'full_description' => strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')),
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'date_added_iso' => date('c', strtotime($result['date_added'])),
                        'date_modified_iso' => date('c', strtotime($result['date_modified'])),
                        'viewed' => $result['viewed'],
                        'reading_time' => $result['reading_time'],
                        'image' => $image,
                        'schema_type' => $result['schema_type'] ?? 'BlogPosting',
                        'rating_value' => $result['rating_value'],
                        'authors' => $author_names,
                        'authors_data' => $authors_data,
                        'href' => $this->url->link('information/information', 'information_id=' . $result['information_id'], true)
                    ];
                }

                $data['json_ld'] = $this->generateSchemaMarkup($data, $category_info, $blog_category_id);

                $pagination = new Pagination();
                $pagination->total = $article_total;
                $pagination->page = $page;
                $pagination->limit = $limit;
                $pagination->url = $this->url->link('information/blog_category', 'blog_category_id=' . $blog_category_id . '&page={page}', true);

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
            
            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_blog'),
                'href' => ''
            ];

            $data['root_categories'] = [];
            $root_categories = $this->model_catalog_blog_category->getChildBlogCategories(0);
            
            foreach ($root_categories as $category) {
                $data['root_categories'][] = [
                    'blog_category_id' => $category['blog_category_id'],
                    'name' => $category['name'],
                    'href' => $this->url->link('information/blog_category', 'blog_category_id=' . $category['blog_category_id'], true)
                ];
            }
            
            $data['authors_link'] = $this->url->link('information/author', '', true);
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
                
                $author_names = [];
                $authors_data = [];
                
                if ($author_model_loaded) {
                    $schema_type = $result['schema_type'] ?? 'BlogPosting';
                    $should_have_authors = in_array($schema_type, ['BlogPosting', 'NewsArticle', 'Review']);
                    
                    if ($should_have_authors) {
                        $authors = $this->model_catalog_author->getAuthorsByInformationId($result['information_id']);
                        
                        foreach ($authors as $author) {
                            $author_names[] = $author['name'];
                            
                            $author_image = '';
                            if (!empty($author['image']) && file_exists(DIR_IMAGE . $author['image'])) {
                                $author_image = $this->model_tool_image->resize($author['image'], 80, 80);
                            } else {
                                $author_image = $this->model_tool_image->resize('placeholder.png', 80, 80);
                            }
                            
                            $authors_data[] = [
                                'name' => $author['name'],
                                'job_title' => $author['job_title'] ?? '',
                                'image' => $author_image,
                                'href' => $this->url->link('information/author', 'author_id=' . $author['author_id'], true)
                            ];
                        }
                    }
                }
                
                $data['articles'][] = [
                    'information_id' => $result['information_id'],
                    'title' => $result['title'],
                    'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                    'full_description' => strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')),
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'date_added_iso' => date('c', strtotime($result['date_added'])),
                    'date_modified_iso' => date('c', strtotime($result['date_modified'])),
                    'viewed' => $result['viewed'],
                    'reading_time' => $result['reading_time'],
                    'image' => $image,
                    'schema_type' => $result['schema_type'] ?? 'BlogPosting',
                    'rating_value' => $result['rating_value'],
                    'authors' => $author_names,
                    'authors_data' => $authors_data,
                    'href' => $this->url->link('information/information', 'information_id=' . $result['information_id'], true)
                ];
            }

            $data['json_ld'] = $this->generateSchemaMarkup($data, null, 0);

            $pagination = new Pagination();
            $pagination->total = $article_total;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = $this->url->link('information/blog_category', 'page={page}', true);

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

    private function generateSchemaMarkup($data, $category_info = null, $blog_category_id = 0) {
        $json_ld = [];
        $base_url = HTTP_SERVER;
        $current_url = $this->getCurrentUrl($blog_category_id);

        // BreadcrumbList
        $breadcrumb_list = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        $position = 1;
        foreach ($data['breadcrumbs'] as $breadcrumb) {
            $name = strip_tags(html_entity_decode($breadcrumb['text'], ENT_QUOTES, 'UTF-8'));
            
            if ($position === 1 && empty(trim($name))) {
                $name = $this->language->get('text_home');
            }
            
            $name = preg_replace('/\s+/', ' ', trim($name));
            $item_url = $breadcrumb['href'] ? $this->getAbsoluteUrl($breadcrumb['href']) : $current_url;
            
            $breadcrumb_list['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
                'item' => $item_url
            ];
            $position++;
        }

        $json_ld['breadcrumb'] = $breadcrumb_list;

        // CollectionPage
        $collection_page = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $data['heading_title'],
            'url' => $current_url,
            'mainContent' => [
                '@type' => 'ItemList',
                'itemListElement' => []
            ]
        ];

        if ($category_info && !empty($category_info['description'])) {
            $clean_description = $this->cleanTextForSchema(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'));
            $collection_page['description'] = $clean_description;
        }

        // ItemList
        foreach ($data['articles'] as $key => $article) {
            $list_item = [
                '@type' => 'ListItem',
                'position' => $key + 1,
                'item' => []
            ];

            $article_type = $article['schema_type'];
            $clean_description = $this->cleanTextForSchema($article['full_description']);
            
            $article_data = [
                '@type' => $article_type,
                'headline' => $article['title'],
                'description' => $clean_description,
                'datePublished' => $article['date_added_iso'],
                'dateModified' => $article['date_modified_iso'],
                'url' => $article['href'],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $article['href']
                ]
            ];

            if ($article['image'] && $article['image'] != $this->model_tool_image->resize('placeholder.png', 400, 300)) {
                $article_data['image'] = $article['image'];
            }

            // Авторы
            if (!empty($article['authors_data'])) {
                $authors = [];
                foreach ($article['authors_data'] as $author) {
                    $author_data = [
                        '@type' => 'Person',
                        'name' => $author['name']
                    ];
                    
                    if (!empty($author['job_title'])) {
                        $author_data['jobTitle'] = $author['job_title'];
                    }
                    
                    if (!empty($author['href'])) {
                        $author_data['url'] = $author['href'];
                    }
                    
                    if (!empty($author['image']) && $author['image'] != $this->model_tool_image->resize('placeholder.png', 80, 80)) {
                        $author_data['image'] = $author['image'];
                    }
                    
                    $authors[] = $author_data;
                }
                
                $article_data['author'] = count($authors) === 1 ? $authors[0] : $authors;
            }

            // Publisher для всех типов
            $article_data['publisher'] = [
                '@type' => 'Organization',
                'name' => $this->config->get('config_name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $base_url . 'image/' . $this->config->get('config_logo')
                ]
            ];

            // Review rating
            if ($article_type === 'Review' && $article['rating_value'] !== null) {
                $article_data['reviewRating'] = [
                    '@type' => 'Rating',
                    'ratingValue' => $article['rating_value'],
                    'bestRating' => '5'
                ];
            }

            $list_item['item'] = $article_data;
            $collection_page['mainContent']['itemListElement'][] = $list_item;
        }

        $json_ld['collection_page'] = $collection_page;

        return $json_ld;
    }

    private function cleanTextForSchema($text) {
        $clean = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);
        return $clean;
    }

    private function getCurrentUrl($blog_category_id = 0) {
        if ($blog_category_id > 0) {
            return $this->url->link('information/blog_category', 'blog_category_id=' . $blog_category_id, true);
        } else {
            return $this->url->link('information/blog_category', '', true);
        }
    }

    private function getAbsoluteUrl($relative_url) {
        if (strpos($relative_url, HTTP_SERVER) === 0) {
            return $relative_url;
        }
        
        if (strpos($relative_url, 'http') === 0) {
            return $relative_url;
        }
        
        if (strpos($relative_url, 'route=') !== false) {
            return $this->url->link($relative_url, '', true);
        }
        
        return HTTP_SERVER . ltrim($relative_url, '/');
    }
}
?>