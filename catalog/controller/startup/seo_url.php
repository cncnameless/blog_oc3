<?php
class ControllerStartupSeoUrl extends Controller {
	// Окнчание для SeoUrl
	private $postfix = 'html';
	private $postfix_route = ['product/product', 'information/information'];
	private $enable_postfix = false;
	private $enable_slash = false;
	private $mode = 0;

	// Новые настройки слэша
	private $enable_slash_category = false;
	private $enable_slash_product = false;

	/* Префикс ЧПУ для определённых роутов.

	Пример - Приставка /brand/ для всех производителей :
	private $prefix_by_route = [
		'product/manufacturer/info' => 'brand',
	];

	Пример - Приставка /category/ для категорий и /product/ для товаров :
	private $prefix_by_route = [
		'product/category'          => 'category',
		'product/product'           => 'product',
	];
	*/
	private $prefix_by_route = [];

	// Кеши
	private $category_keywords = [];
	private $category_path = [];
	private $keyword = [];
	private $blog_category_keywords = [];
	private $blog_category_path = [];

	public function __construct($registry) {
		parent::__construct($registry);
		
		$this->enable_postfix = $this->config->get('config_seo_url_postfix');
		$this->enable_slash = $this->config->get('config_seo_url_slash');
		$this->mode = (int)$this->config->get('config_seo_url_mode');
		
		// Новые настройки слэша
		$this->enable_slash_category = $this->config->get('config_seo_url_slash_category');
		$this->enable_slash_product = $this->config->get('config_seo_url_slash_product');
	}

	public function index() {
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// ДОБАВЛЕНО: Обработка прямого доступа к /blog и /blog/authors
		if (isset($this->request->get['_route_'])) {
			$route = $this->request->get['_route_'];
			
			// ИСПРАВЛЕНИЕ: Более точная проверка для главной блога
			if ($route == 'blog' || $route == 'blog/') {
				$this->request->get['route'] = 'information/blog_category';
				unset($this->request->get['_route_']);
				// Убедимся, что нет редиректа
				$this->request->get['disable_redirect'] = true;
			}
			
			if ($route == 'blog/authors' || $route == 'blog/authors/') {
				$this->request->get['route'] = 'information/author';
				unset($this->request->get['_route_']);
				// Убедимся, что нет редиректа
				$this->request->get['disable_redirect'] = true;
			}
		}

		if (isset($this->request->get['_route_'])) {
			// Приводим к нижнему регистру и делим на части
			$this->request->get['_route_'] = utf8_strtolower($this->request->get['_route_']);
			$parts = array_filter(explode('/', $this->request->get['_route_']), 'strlen');

			// Проверяем, начинается ли URL с /blog
			$is_blog_route = false;
			$is_authors_page = false;
			$is_author_page = false;
			
			if (count($parts) > 0 && $parts[0] == 'blog') {
				$is_blog_route = true;
				array_shift($parts); // Убираем 'blog'
				
				// Проверяем, является ли это страницей авторов
				if (count($parts) > 0 && $parts[0] == 'authors') {
					$is_authors_page = true;
					array_shift($parts); // Убираем 'authors'
					
					// Если после authors есть еще части - это конкретный автор
					if (count($parts) > 0) {
						$is_author_page = true;
						$is_authors_page = false;
					}
				}
			}

			if (count($parts) > 1 && in_array($parts[0], $this->prefix_by_route)) {
				array_shift($parts);
			}
			
			// Убираем постфикс .html и т.п.
			if ($this->postfix && count($parts) > 0) {
				$last = array_pop($parts);
				$point_parts = explode('.', $last);
				if (count($point_parts) > 1 && end($point_parts) == $this->postfix) {
					array_pop($point_parts);
					$last = implode('.', $point_parts);
				}
				$parts[] = $last;
			}

			// Обрабатываем страницу списка авторов
			if ($is_authors_page && empty($parts)) {
				$this->request->get['route'] = 'information/author';
			}
			// Обрабатываем конкретного автора
			elseif ($is_author_page && !empty($parts)) {
				$author_keyword = end($parts);
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($author_keyword) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND query LIKE 'author_id=%'");
				
				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);
					if ($url[0] == 'author_id') {
						$this->request->get['author_id'] = $url[1];
						$this->request->get['route'] = 'information/author';
					}
				} else {
					$this->request->get['route'] = 'error/not_found';
				}
			}
			// Обрабатываем категории блога и статьи
			elseif ($is_blog_route && !empty($parts)) {
				$this->processBlogRoutes($parts);
			}
			// Обрабатываем стандартные маршруты
			else {
				$this->processStandardRoutes($parts);
			}

			// Если route не определён по ключевым словам — определяем по параметрам
			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				} elseif (isset($this->request->get['author_id'])) {
					$this->request->get['route'] = 'information/author';
				} elseif (isset($this->request->get['blog_category_id']) || isset($this->request->get['blog_path'])) {
					$this->request->get['route'] = 'information/blog_category';
				}
			}

			unset($this->request->get['_route_']);
		}

		// УДАЛЯЕМ ЛИШНИЕ ПАРАМЕТРЫ ДЛЯ БЛОГА
		if (isset($this->request->get['route'])) {
			if ($this->request->get['route'] == 'information/blog_category' && isset($this->request->get['blog_path'])) {
				unset($this->request->get['blog_path']);
			}
			if ($this->request->get['route'] == 'information/information' && isset($this->request->get['blog_category_id'])) {
				unset($this->request->get['blog_category_id']);
			}
			if ($this->request->get['route'] == 'information/author' && !isset($this->request->get['author_id']) && isset($this->request->get['authors_page'])) {
				unset($this->request->get['authors_page']);
			}
		}

		if (empty($this->request->get['route'])) {
			$this->request->get['route'] = 'common/home';
		}

		// ПРОВЕРКА И РЕДИРЕКТ ДЛЯ ЧИСТЫХ URL
		// ИСПРАВЛЕНИЕ: Отключаем редирект для главной блога и страницы авторов
		if (isset($this->request->get['disable_redirect']) || 
			$this->request->server['REQUEST_METHOD'] == 'POST' || 
			(isset($this->request->server['HTTP_X_REQUESTED_WITH']) && utf8_strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
			return;
		}

		$original_url = $this->config->get('config_secure') ? $this->config->get('config_ssl') : $this->config->get('config_url');
		$original_request = $this->request->server['REQUEST_URI'];

		$path = parse_url($original_url, PHP_URL_PATH);
		if ($path && strpos($original_request, $path) === 0) {
			$original_request = utf8_substr($original_request, utf8_strlen($path));
		}

		$original_url .= ltrim($original_request, '/');

		$params = array();
		foreach ($this->request->get as $key => $value) {
			if ($key != 'route' && $key != 'disable_redirect') {
				$params[$key] = is_array($value) ? $value : html_entity_decode($value, ENT_QUOTES, 'UTF-8');
			}
		}

		// ИСПРАВЛЕНИЕ: Отключаем редирект для главной блога и страницы авторов
		$current_route = isset($this->request->get['route']) ? $this->request->get['route'] : '';
		$is_blog_home = ($current_route == 'information/blog_category' && empty($params));
		$is_authors_page = ($current_route == 'information/author' && empty($params));
		
		if (!$is_blog_home && !$is_authors_page && $this->request->get['route'] != 'error/not_found') {
			$seo_url = $this->url->link($this->request->get['route'], http_build_query($params), $this->config->get('config_secure'));
			
			// УБИРАЕМ ПАРАМЕТРЫ ИЗ SEO_URL ДЛЯ ЧИСТОТЫ
			$clean_seo_url = $this->cleanUrlParameters($seo_url);
			
			if (rawurldecode($original_url) != rawurldecode($clean_seo_url)) {
				$this->response->redirect($clean_seo_url, 301);
			}
		}
	}

	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));
		$url = null;
		$has_postfix = false;
		$route = null;
		$data = array();

		parse_str($url_info['query'], $data);

		// УДАЛЯЕМ НЕНУЖНЫЕ ПАРАМЕТРЫ СРАЗУ
		unset($data['blog_path']);
		unset($data['authors_page']);

		if (isset($data['route'])) {
			$route = $data['route'];

			// ИСПРАВЛЕНО: Главная страница блога
			if ($route == 'information/blog_category' && !isset($data['blog_category_id'])) {
				$url = '/blog';
				unset($data['route']);
				return $this->buildFinalUrl($url_info, $url, $data, $route);
			}

			// ПЕРВОЕ: Обрабатываем страницу авторов (без author_id)
			if ($route == 'information/author' && !isset($data['author_id'])) {
				$url = '/blog/authors';
				unset($data['route']);
				return $this->buildFinalUrl($url_info, $url, $data, $route);
			}

			// Обрабатываем остальные маршруты блога
			if ($route == 'information/blog_category' || $route == 'information/author' || ($route == 'information/information' && $this->isBlogArticle($data))) {
				$url = $this->rewriteBlogUrl($route, $data);
			} else {
				// Стандартная обработка
				$keyword = $this->getKeyword($data['route']);
				if ($keyword !== false) {
					$url = '/' . $keyword;
					unset($data['route']);
				}

				if (!empty($this->prefix_by_route[$route])) {
					$url = '/' . $this->prefix_by_route[$route] . $url;
				}
			}
		}

		// Специфика product/product оставлена без изменений
		if (!empty($route) && $route == 'product/product' && !empty($data['product_id'])) {
			if ($this->mode == 0) {
				if (isset($data['path'])) {
					$path = $data['path'];
					unset($data['path']);
					$data = array_merge(['path' => $path], $data);
				}
			} else {
				unset($data['path']);
				unset($data['manufacturer_id']);
			}

			if ($this->mode == 2) {
				$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$data['product_id'] . "' AND main_category = '1' LIMIT 1");
				if ($query->row) {
					$data = array_merge(['path' => $query->row['category_id']], $data);
				}
			}

			if ($this->mode == 3) {
				$query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$data['product_id'] . "' LIMIT 1");
				if ($query->row && $query->row['manufacturer_id'] > 0) {
					$data = array_merge(['manufacturer_id' => $query->row['manufacturer_id']], $data);
				}
			}
		}

		// Формирование ЧПУ из параметров (если URL для блога не был сгенерирован)
		if ($url === null && isset($data['route'])) {
			foreach ($data as $key => $value) {
				if (isset($data['route'])) {
					if (($data['route'] == 'product/product' && $key == 'product_id') ||
						(($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') ||
						(($data['route'] == 'information/information') && $key == 'information_id') ||
						($data['route'] == 'information/author' && $key == 'author_id') ||
						($data['route'] == 'information/blog_category' && $key == 'blog_category_id')) {

						$keyword = $this->getKeyword($key . '=' . (int)$value);

						if ($keyword) {
							$url .= '/' . $keyword;
							unset($data[$key]);
						}
					} elseif ($key == 'path') {
						// Для product path (категории товаров) — собираем цепочку
						$categories = explode('_', $value);
						$category_id = array_pop($categories);

						if ($this->mode == 4) {
							$keyword = $this->getKeyword('category_id=' . $category_id);
							if ($keyword) $url .= '/' . $keyword;
						} else {
							foreach ($this->getKeywordsByCategory($category_id) as $keyword) {
								if ($keyword) {
									$url .= '/' . $keyword;
								} else {
									$url = null;
									break;
								}
							}
						}
						unset($data[$key]);
					} elseif ($key == 'blog_path') {
						// Для blog_path — собираем цепочку ключевых слов для блога
						$categories = explode('_', $value);
						$category_id = array_pop($categories);

						if ($this->mode == 4) {
							$keyword = $this->getKeyword('blog_category_id=' . $category_id);
							if ($keyword) $url .= '/' . $keyword;
						} else {
							foreach ($this->getKeywordsByBlogCategory($category_id) as $keyword) {
								if ($keyword) {
									$url .= '/' . $keyword;
								} else {
									$url = null;
									break;
								}
							}
						}
						unset($data[$key]);
					}
				}
			}
		}

		if (isset($url)) {
			return $this->buildFinalUrl($url_info, $url, $data, $route);
		}

		return $link;
	}

	/**
	 * Собирает финальный URL с улучшенной логикой слэшей
	 */
	private function buildFinalUrl($url_info, $url, $data, $route) {
		$has_postfix = false;
		
		if (!empty($route) && in_array($route, $this->postfix_route)) {
			$has_postfix = true;
			if ($this->enable_postfix && $this->postfix) {
				$url .= '.' . $this->postfix;
			}
		}

		// ОПТИМИЗИРОВАННАЯ ЛОГИКА ДОБАВЛЕНИЯ СЛЭША ДЛЯ БЛОГА И СТАНДАРТНЫХ СТРАНИЦ
		if ($this->enable_slash) {
			$add_slash = false;
			
			// СУПЕР-БЫСТРАЯ ПРОВЕРКА: если обе опции включены - добавляем ко всем ссылкам
			if ($this->enable_slash_category && $this->enable_slash_product) {
				$add_slash = true;
			} 
			// Если включена только одна опция - определяем тип страницы
			elseif ($this->enable_slash_category || $this->enable_slash_product) {
				$current_route = $route;
				
				// Быстрая проверка по паттернам (вместо массивов)
				$is_listing_page = strpos($current_route, 'category') !== false || 
								   strpos($current_route, 'manufacturer') !== false ||
								   strpos($current_route, 'special') !== false ||
								   strpos($current_route, 'search') !== false;
				
				// ДОБАВЛЕНО: для блога - категории блога и список авторов считаем списками
				if (!$is_listing_page) {
					$is_listing_page = ($current_route == 'information/blog_category') || 
									   ($current_route == 'information/author' && !isset($data['author_id']));
				}
				
				// Для категорий: добавляем только если это список И включена опция категорий
				if ($is_listing_page && $this->enable_slash_category) {
					$add_slash = true;
				}
				// Для товаров: добавляем если НЕ список И включена опция товаров
				elseif (!$is_listing_page && $this->enable_slash_product) {
					$add_slash = true;
				}
			}
			// Для совместимости: если новые настройки не заданы, используем старую логику
			elseif (!$this->config->has('config_seo_url_slash_category') && 
					!$this->config->has('config_seo_url_slash_product') && 
					!$has_postfix && $url != '/') {
				$add_slash = true;
			}
			
			if ($add_slash) {
				$url .= '/';
			}
		}

		unset($data['route']);
		
		// УДАЛЯЕМ ВСЕ ПАРАМЕТРЫ БЛОГА
		unset($data['blog_path']);
		unset($data['blog_category_id']);
		unset($data['authors_page']);

		if (isset($data['page']) && $data['page'] != '{page}' && $data['page'] <= 1) {
			unset($data['page']);
		}

		$query = '';
		if (!empty($data)) {
			$raw_query = str_replace('+', '%20', http_build_query($data));
			$parts = explode('&', $raw_query);
			foreach ($parts as $part) {
				$values = explode('=', $part);
				$key = str_replace(['%5B', '%5D'], ['[', ']'], $values[0]);
				$value = isset($values[1]) ? $values[1] : '';
				$query .= '&' . $key . '=' . $value;
			}
			if ($query) {
				$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
			}
		}

		return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
	}

	/**
	 * Очищает URL от ненужных параметров
	 */
	private function cleanUrlParameters($url) {
		$url = preg_replace('/\?(blog_path|blog_category_id|authors_page)=[^&]*&?/', '?', $url);
		$url = rtrim($url, '?');
		$url = str_replace('?&', '?', $url);
		return $url;
	}

	/**
	 * Обрабатывает маршруты блога при разборе URL
	 */
	private function processBlogRoutes($parts) {
		$current_path = array();
		
		foreach ($parts as $part) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
			
			if ($query->num_rows) {
				$url = explode('=', $query->row['query']);
				
				if ($url[0] == 'blog_category_id') {
					$current_path[] = $url[1];
					$this->request->get['blog_category_id'] = $url[1];
				}
				elseif ($url[0] == 'information_id') {
					$this->request->get['information_id'] = $url[1];
					// Если есть путь категорий, устанавливаем blog_category_id
					if (!empty($current_path)) {
						$this->request->get['blog_category_id'] = end($current_path);
					}
					break;
				}
				else {
					$this->request->get['route'] = 'error/not_found';
					break;
				}
			} else {
				$this->request->get['route'] = 'error/not_found';
				break;
			}
		}
		
		// Определяем маршрут
		if (!isset($this->request->get['route']) || $this->request->get['route'] != 'error/not_found') {
			if (isset($this->request->get['information_id'])) {
				$this->request->get['route'] = 'information/information';
			} elseif (isset($this->request->get['blog_category_id'])) {
				$this->request->get['route'] = 'information/blog_category';
				if (!empty($current_path)) {
					$this->request->get['blog_path'] = implode('_', $current_path);
				}
			}
		}
	}

	/**
	 * Обрабатывает стандартные маршруты при разборе URL
	 */
	private function processStandardRoutes($parts) {
		foreach ($parts as $part) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

			if ($query->num_rows) {
				$url = explode('=', $query->row['query']);

				if ($url[0] == 'product_id') {
					$this->request->get['product_id'] = $url[1];
				}

				if ($url[0] == 'category_id') {
					if ($this->mode == 4) {
						$this->request->get['path'] = $this->getPathByCategory($url[1]);
					} else {
						$this->request->get['path'] = isset($this->request->get['path']) ? $this->request->get['path'] . '_' . $url[1] : $url[1];
					}
				}

				if ($url[0] == 'manufacturer_id') {
					$this->request->get['manufacturer_id'] = $url[1];
				}

				if ($url[0] == 'information_id') {
					$this->request->get['information_id'] = $url[1];
				}

				if ($url[0] == 'author_id') {
					$this->request->get['author_id'] = $url[1];
				}

				if ($url[0] == 'blog_category_id') {
					if ($this->mode == 4) {
						$this->request->get['blog_path'] = $this->getPathByBlogCategory($url[1]);
					} else {
						$this->request->get['blog_path'] = isset($this->request->get['blog_path']) ? $this->request->get['blog_path'] . '_' . $url[1] : $url[1];
					}
					$this->request->get['blog_category_id'] = $url[1];
				}

				if ($query->row['query'] && !in_array($url[0], ['information_id', 'manufacturer_id', 'category_id', 'product_id', 'author_id', 'blog_category_id'])) {
					$this->request->get['route'] = $query->row['query'];
				}
			} else {
				$this->request->get['route'] = 'error/not_found';
				break;
			}
		}
	}

	/**
	 * Обрабатывает маршруты блога для генерации URL
	 */
	private function rewriteBlogUrl($route, &$data) {
		$url = '/blog';
		
		// ИСПРАВЛЕНО: Главная страница блога
		if ($route == 'information/blog_category' && !isset($data['blog_category_id'])) {
			unset($data['route']);
			return $url;
		}
		
		if ($route == 'information/author' && !isset($data['author_id'])) {
			// Страница списка авторов
			$url .= '/authors';
			unset($data['route']);
			return $url;
		}

		if ($route == 'information/author' && isset($data['author_id'])) {
			// Страница конкретного автора
			$keyword = $this->getKeyword('author_id=' . (int)$data['author_id']);
			if ($keyword) {
				$url .= '/authors/' . $keyword;
				unset($data['author_id']);
				unset($data['route']);
				return $url;
			}
		}
		elseif ($route == 'information/blog_category' && isset($data['blog_category_id'])) {
			// Категория блога
			$blog_category_id = (int)$data['blog_category_id'];
			
			if ($this->mode == 4) {
				$keyword = $this->getKeyword('blog_category_id=' . $blog_category_id);
				if ($keyword) {
					$url .= '/' . $keyword;
				}
			} else {
				foreach ($this->getKeywordsByBlogCategory($blog_category_id) as $keyword) {
					if ($keyword) {
						$url .= '/' . $keyword;
					} else {
						return null;
					}
				}
			}
			unset($data['blog_category_id']);
			unset($data['route']);
			return $url;
		}
		elseif ($route == 'information/information' && isset($data['information_id'])) {
			// Статья в блоге - получаем категорию статьи
			$blog_category_id = $this->getBlogCategoryIdByInformationId($data['information_id']);
			
			if ($blog_category_id) {
				// Строим путь категорий
				if ($this->mode == 4) {
					$keyword = $this->getKeyword('blog_category_id=' . $blog_category_id);
					if ($keyword) {
						$url .= '/' . $keyword;
					}
				} else {
					foreach ($this->getKeywordsByBlogCategory($blog_category_id) as $keyword) {
						if ($keyword) {
							$url .= '/' . $keyword;
						} else {
							return null;
						}
					}
				}
				
				// Добавляем keyword статьи
				$keyword = $this->getKeyword('information_id=' . (int)$data['information_id']);
				if ($keyword) {
					$url .= '/' . $keyword;
					unset($data['information_id']);
					unset($data['route']);
					return $url;
				}
			}
		}
		
		return null;
	}

	/**
	 * Проверяет, является ли статья частью блога
	 */
	private function isBlogArticle($data) {
		if (isset($data['information_id'])) {
			$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$data['information_id'] . "'");
			return $query->row['total'] > 0;
		}
		return false;
	}

	/**
	 * Получает ID категории блога для статьи
	 */
	private function getBlogCategoryIdByInformationId($information_id) {
		$query = $this->db->query("SELECT blog_category_id FROM " . DB_PREFIX . "information_to_blog_category WHERE information_id = '" . (int)$information_id . "' LIMIT 1");
		if ($query->num_rows) {
			return $query->row['blog_category_id'];
		}
		return false;
	}

	// Ключевые слова для product category (оригинальная логика)
	private function getKeywordsByCategory($category_id) {
		if (!isset($this->category_keywords[$category_id])) {
			$query = $this->db->query("SELECT su.keyword FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "seo_url su ON (su.query = CONCAT('category_id=', cp.path_id)) WHERE cp.category_id = '" . (int)$category_id . "' AND su.store_id = '" . (int)$this->config->get('config_store_id') . "' AND su.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cp.level");
			$this->category_keywords[$category_id] = array();
			foreach ($query->rows as $row) {
				$this->category_keywords[$category_id][] = $row['keyword'];
			}
		}
		return $this->category_keywords[$category_id];
	}

	private function getPathByCategory($category_id) {
		if (!isset($this->category_path[$category_id])) {
			$query = $this->db->query("SELECT GROUP_CONCAT(path_id ORDER BY level SEPARATOR '_') AS path FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");
			$this->category_path[$category_id] = $query->row['path'] ?? $category_id;
		}
		return $this->category_path[$category_id];
	}

	// Ключевые слова для blog category (аналогично product category)
	private function getKeywordsByBlogCategory($blog_category_id) {
		if (!isset($this->blog_category_keywords[$blog_category_id])) {
			$query = $this->db->query("SELECT su.keyword FROM " . DB_PREFIX . "blog_category_path bcp LEFT JOIN " . DB_PREFIX . "seo_url su ON (su.query = CONCAT('blog_category_id=', bcp.path_id)) WHERE bcp.blog_category_id = '" . (int)$blog_category_id . "' AND su.store_id = '" . (int)$this->config->get('config_store_id') . "' AND su.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bcp.level");
			$this->blog_category_keywords[$blog_category_id] = array();
			foreach ($query->rows as $row) {
				$this->blog_category_keywords[$blog_category_id][] = $row['keyword'];
			}
		}
		return $this->blog_category_keywords[$blog_category_id];
	}

	private function getPathByBlogCategory($blog_category_id) {
		if (!isset($this->blog_category_path[$blog_category_id])) {
			$query = $this->db->query("SELECT GROUP_CONCAT(path_id ORDER BY level SEPARATOR '_') AS path FROM " . DB_PREFIX . "blog_category_path WHERE blog_category_id = '" . (int)$blog_category_id . "'");
			$this->blog_category_path[$blog_category_id] = $query->row['path'] ?? $blog_category_id;
		}
		return $this->blog_category_path[$blog_category_id];
	}

	// Получение keyword по query (кеш)
	private function getKeyword($query_string) {
		if (!isset($this->keyword[$query_string])) {
			$query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($query_string) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
			$this->keyword[$query_string] = $query->num_rows ? $query->row['keyword'] : false;
		}
		return $this->keyword[$query_string];
	}
}
?>