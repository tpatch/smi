<?php

class Koken {
	public static $site;
	public static $settings = array();
	public static $profile;
	public static $location = array();
	public static $rss_feeds = array();
	public static $current_token;
	public static $rss;
	public static $categories;
	public static $page_class = false;
	public static $template_path;
	public static $fallback_path;
	public static $navigation_home_path = false;
	public static $original_url;
	public static $cache_path;
	public static $routed_variables;
	public static $draft;
	public static $preview;
	public static $rewrite;
	public static $pjax;
	public static $source;
	public static $template_variable_keys = array();
	public static $template_variables = array();
	public static $root_path;
	public static $protocol;
	public static $main_load_token = false;
	public static $custom_page_title = false;
	public static $tokens = array();
	public static $max_neighbors = array(2);
	public static $the_title_separator = false;
	public static $load_history = array();
	public static $timers = array();
	public static $curl_handle = false;

	private static $start_time = false;
	private static $last;
	private static $_parent = false;
	private static $level = 0;
	private static $depth;

	public static function title_from_archive($archive, $format = false)
	{
		if (!$archive['month'])
		{
			return $archive['year'];
		}

		$str = $archive['year'] . '-' . $archive['month'] . '-01 12:00:00';

		if (isset($archive['day']) && $archive['day'])
		{
			if (!$format)
			{
				$format = 'F j, Y';
			}
			$str = str_replace('-01 ', '-' . $archive['day'] . ' ', $str);
			$archive = date($format, strtotime($str));
		}
		else
		{
			if (!$format)
			{
				$format = 'F Y';
			}
			$archive = date($format, strtotime($str));
		}

		return $archive;
	}

	public static function breadcrumbs($options = array())
	{
		$front_path = '/';

		foreach(self::$site['navigation']['items'] as $item)
		{
			if ($item['front'])
			{
				$front_path = $item['path'];
				break;
			}
		}

		function to_month($m)
		{
			return date('F', strtotime("2012-$m-01"));
		}

		$defaults = array('separator' => '>', 'show_if_single' => 'true', 'show_home' => 'true', 'link_current' => 'true');

		$options = array_merge($defaults, $options);

		$options['show_if_single'] = $options['show_if_single'] === 'true';
		$options['show_home'] = $options['show_home'] === 'true';
		$options['link_current'] = $options['link_current'] === 'true';

		$options['separator'] = ' ' . $options['separator'] . ' ';

		$base = Koken::$location['root'];
		$link_tail = Koken::$preview ? '&amp;preview=' . Koken::$preview : '';

		$crumbs = array();

		if ($options['show_home'])
		{
			$crumbs[] =	array('link' => '/', 'label' => self::$site['url_data']['home']);
		}

		if (isset(self::$current_token['__koken__']) && self::$current_token['__koken__'] !== 'category')
		{
			$single = self::$current_token;
		}
		else if (isset(self::$current_token['album']))
		{
			$single = self::$current_token['album'];
		}
		else if (isset(self::$current_token['event']) && isset(self::$current_token['event']['counts']))
		{
			$single = self::$current_token['event'];
		}
		else
		{
			$single = false;
		}

		$source = self::$source['type'];

		if (isset($single['context']['type']))
		{
			if (isset(self::$site['urls'][$single['context']['type'] === 'category' ? 'categories' : $single['context']['type'] . 's']))
			{
				$section = self::$site['urls'][$single['context']['type'] === 'category' ? 'categories' : $single['context']['type'] . 's'];
				$crumbs[] = array('link' => $section, 'label' => self::$site['url_data'][ $single['context']['type'] ]['plural']);
			}
			if ($single['context']['type'] === 'album')
			{
				$section = str_replace(':slug', $single['context']['album']['slug'], self::$site['urls'][$single['context']['type']]);
				$crumbs[] = array('link' => $single['context']['album']['__koken_url'], 'label' => $single['context']['title']);
			}
			else if ($single['context']['type'] !== 'favorite' && isset(self::$site['urls'][$single['context']['type']]))
			{
				$section = str_replace(':slug', $single['context']['slug'], self::$site['urls'][$single['context']['type']]);
				$crumbs[] = array('link' => $section, 'label' => $single['context']['title']);
			}
			if (!in_array($single['context']['type'], array('favorite', 'album')))
			{
				$crumbs[] = array('link' => $single['context']['__koken_url'], 'label' => self::$site['url_data'][$single['__koken__']]['plural']);
			}
			$crumbs[] = array('link' => $single['__koken_url'], 'label' => empty($single['title']) ? $single['filename'] : $single['title']);
			$single = false;
		}
		else if (isset($single['context']['album']))
		{
			$content = $single;
			$single = $single['context']['album'];
		}
		else
		{
			$content = false;
		}

		if ($single && $source === 'event')
		{
			$data = self::$site['url_data']['timeline'];
			$url = self::$site['urls']['timeline'];
			$single['month'] = str_pad($single['month'], 2, '0', STR_PAD_LEFT);
			$single['day'] = str_pad($single['day'], 2, '0', STR_PAD_LEFT);

			$crumbs[] = array('link' => $url, 'label' => $data['plural']);
			$url .= $single['year'] . '/';
			$crumbs[] = array('link' => $url, 'label' => $single['year']);
			$url .= $single['month'] . '/';
			$crumbs[] = array('link' => $url, 'label' => to_month($single['month']));
			$url .= $single['day'] . '/';
			$crumbs[] = array('link' => $url, 'label' => $single['day']);
		}
		else if ($single)
		{
			$set = $single['__koken__'] === 'album' && $single['album_type'] === 'set';
			$ident = $set ? 'set' : $single['__koken__'];
			$data = self::$site['url_data'][$ident];
			$plural = $ident === 'category' ? 'categories' : $ident . 's';
			if ($set)
			{
				$data['url'] = self::$site['url_data']['album']['url'];
			}

			if (isset(self::$site['urls'][$plural]))
			{
				$section = self::$site['urls'][$plural];
				$crumbs[] = array('link' => $section, 'label' => self::$site['url_data'][ $set ? 'set' : $single['__koken__'] ]['plural']);
			}

			if (isset($data['url']) && strpos($data['url'], 'date') !== false && isset(self::$site['urls'][ 'archive_' . $single['__koken__'] . 's' ]))
			{
				$date = isset($single['published_on']) ? $single['published_on'] : ( $single['captured_on'] ? $single['captured_on'] : $single['created_on'] );
				$year = date('Y', $date['timestamp']);
				$month = date('m', $date['timestamp']);

				$crumbs[] = array('link' => $section . $year, 'label' => $year);
				$crumbs[] = array('link' => $section . $year . '/' . $month, 'label' => to_month($month));
			}

			$crumbs[] = array('link' => $single['__koken_url'], 'label' => empty($single['title']) ? $single['filename'] : $single['title']);

			if ($content)
			{
				$crumbs[] = array('link' => $content['__koken_url'], 'label' => empty($content['title']) ? $content['filename'] : $content['title']);
			}
		}
		else if (substr(strrev($source), 0, 1) === 's')
		{
			$data_type = $source === 'categories' ? 'category' : rtrim($source, 's');
			$data_type = $data_type === 'event' ? 'timeline' : $data_type;
			$data = self::$site['url_data'][$data_type];
			$url = self::$site['urls'][ $data_type === 'timeline' ? 'timeline' : $source ];
			$crumbs[] = array('link' => $url, 'label' => $data['plural']);

			if ($data_type === 'timeline' && isset(self::$routed_variables['year']))
			{
				$url .= self::$routed_variables['year'] . '/';
				$crumbs[] = array('link' => $url, 'label' => self::$routed_variables['year']);

				if (isset(self::$routed_variables['month']))
				{
					$crumbs[] = array('link' => $url . self::$routed_variables['month'] . '/', 'label' => to_month(self::$routed_variables['month']));
				}
			}
		}
		else if ($source === 'tag' || $source === 'category' || $source === 'archive')
		{
			$type = str_replace('members=', '', self::$source['filters'][0]);
			$section = empty($type) ? false : self::$site['urls'][ $type ];

			if ($source === 'archive')
			{
				$crumbs[] = array('link' => '/' . $section, 'label' => self::$site['url_data'][ rtrim($type, 's') ]['plural']);

				$year = self::$current_token['archive']['year'];
				$url = $section . $year . '/';
				$crumbs[] = array('link' => $url, 'label' => $year);

				if (isset(self::$current_token['archive']['month']) && self::$current_token['archive']['month'])
				{
					$month = self::$current_token['archive']['month'];
					$url .= $month . '/';
					$crumbs[] = array('link' => $url, 'label' => to_month($month));

					if (isset(self::$current_token['archive']['day']) && self::$current_token['archive']['day'])
					{
						$day = self::$current_token['archive']['day'];
						$url .= $day . '/';
						$crumbs[] = array('link' => $url, 'label' => $day);
					}
				}
			}
			else
			{
				$section = false;
				if (isset(self::$site['urls'][$source === 'category' ? 'categories' : $source . 's']))
				{
					$section = self::$site['urls'][$source === 'category' ? 'categories' : $source . 's'];
					$crumbs[] = array('link' => $section, 'label' => self::$site['url_data'][ $source ]['plural']);
				}
				if (isset(self::$site['urls'][$source]))
				{
					$obj = isset(self::$current_token['__koken__']) ? self::$current_token[self::$current_token['__koken__']] : self::$current_token['archive'];
					$section = str_replace(':slug', isset($obj['slug']) ? $obj['slug']: $obj['title'], self::$site['urls'][$source]);
					$crumbs[] = array('link' => $section, 'label' => $obj['title']);
				}
				if (!empty($type))
				{
					$data = self::$site['url_data'][ rtrim($type, 's') ];
					$obj = isset(self::$current_token['__koken__']) ? self::$current_token[self::$current_token['__koken__']] : self::$current_token['archive'];
					if ($section)
					{
						$crumbs[] = array('link' => self::$location['here'], 'label' => $data['plural']);
					}
					else
					{
						$crumbs[] = array('link' => '/' . strtolower($data['plural']) . '/', 'label' => $data['plural']);
						$crumbs[] = array('link' => self::$location['here'], 'label' => $obj['title']);
					}
				}

			}
		}
		else if (self::$custom_page_title)
		{
			$crumbs[] = array('link' => self::$location['here'], 'label' => self::$custom_page_title);
		}

		if (count(self::$location['parameters']['__overrides_display']))
		{
			foreach(self::$location['parameters']['__overrides_display'] as $filter)
			{
				$crumbs[] = array('link' => self::$location['here'], 'label' => self::case_convert($filter['title'], 'sentence') . ': ' . ( $filter['title'] === 'tags' ? $filter['value'] : self::case_convert($filter['value'], 'sentence')));
			}
		}

		if (!$options['show_if_single'] && count($crumbs) < 2)
		{
			return '';
		}
		else
		{
			$crumb_links = array();
			foreach($crumbs as $index => $c)
			{
				$path = $c['link'] === '/' ? '/' : '/' . trim($c['link'], '/') . '/';
				if ($path === $front_path)
				{
					$path = '/';
				}
				if ($index + 1 === count($crumbs) && !$options['link_current'])
				{
					$crumb_links[] = $c['label'];
				}
				else
				{
					$crumb_links[] = '<a title="' . $c['label'] . '" href="' . $base . $path . $link_tail . '">' . $c['label'] . '</a>';
				}

			}
			return '<span class="k-nav-breadcrumbs">' . join($options['separator'], $crumb_links) . '</span>';
		}
	}

	private static function out_callback($matches)
	{
		return '<?php echo Koken::out(\'' . trim(str_replace("'", "\\'", $matches[1])) . '\'); ?>';
	}

	public static function parse($template)
	{
		$output = preg_replace_callback('/(<|\s)(\/)?koken\:([a-z_\-]+)([\=|\s][^\/].+?")?(\s+\/)?>/', array('Koken', 'callback'), $template);
		$output = preg_replace('/\{\{\s*discussion\s*\}\}/', '<?php Shutter::hook(\'discussion\', Koken::$current_token); ?>', $output);
		$output = preg_replace('/\{\{\s*discussion_count\s*\}\}/', '<?php Shutter::hook(\'discussion_count\', Koken::$current_token); ?>', $output);
		$output = preg_replace_callback('/\{\{\s*([^\}]+)\s*\}\}/', array('Koken', 'out_callback'), $output);
		return $output;
	}

	private static function attr_callback($matches)
	{
		$name = $matches[1];

		$value = preg_replace_callback("/{([a-z._\(\)\,\|\s\'\/\[\]0-9]+)([\s\*\-\+0-9]+)?}/", array('Koken', 'attr_replace'), $matches[2]);
		$value = trim($value, '" . ');
		$value = str_replace('"str_replace(', 'str_replace(', $value);

		if (!preg_match('/^(\((Koken::)?\$|str_replace\(|\(?empty\()/', $value))
		{
			$value = '"' . $value;
		}
		if (substr_count($value, '"') % 2 !== 0)
		{
			$value .= '"';
		}
		$value = str_replace('. "" .', '.', $value);
		if ($name === 'href')
		{
			$value = "<?php echo ( strpos($value, '/') === 0 ? Koken::\$location['root'] . $value : $value ) . ( Koken::\$preview ? '&amp;preview=' . Koken::\$preview : '' ); ?>";
		}
		else
		{
			$value = "<?php echo $value; ?>";
		}
		return "$name=\"$value\"";
	}

	private function attr_replace($matches)
	{
		$t = new Tag();

		if (strpos($matches[1], '.replace(') !== false)
		{
			preg_match('/(.*)\.replace\((.*)\)/', $matches[1], $r_matches);
			$data = $t->field_to_keys($r_matches[1]);
			return 'str_replace(' . $r_matches[2] . ', ' . $data . ')';
		}

		$modifier = isset($matches[2]) ? $matches[2] : '';
		return '" . (' . $t->field_to_keys($matches[1]) . $modifier . ') . "';
	}

	private static function callback($matches)
	{
		$out = '';
		list($full, $start, $closing, $action) = $matches;
		$closing = $closing === '/';
		$attr = $start !== '<';

		if (isset($matches[4]))
		{
			preg_match_all('/([:a-z_\-]+)="([^"]+?)?"/', $matches[4], $param_matches);
			$parameters = array();
			$parameters['api'] = array();

			foreach($param_matches[1] as $index => $key)
			{
				if (strpos($key, 'api:') === 0)
				{
					$key = str_replace('api:', '', $key);
					$parameters['api'][$key] = $param_matches[2][$index];
				}
				else
				{
					$parameters[$key] = $param_matches[2][$index];
				}
			}

			if (empty($parameters['api']))
			{
				unset($parameters['api']);
			}
		}
		else
		{
			$parameters = array();
		}

		if (isset($matches[5]))
		{
			$self_closing = trim($matches[5]) === '/';
		}
		else
		{
			$self_closing = false;
		}

		if ($attr)
		{
			$out = preg_replace_callback('/koken:([a-z\-]+)="([^"]+?)"/', array('Koken', 'attr_callback'), $full);
		}
		else if ($action === 'else')
		{
			$else_tag = self::$last[self::$level-1];
			$out .= $else_tag->do_else();
			if ($else_tag->untokenize_on_else)
			{
				array_shift(self::$tokens);
				$else_tag->tokenize = false;
			}
		}
		else
		{
			$action = preg_replace_callback(
				'/_([a-zA-Z])/',
				create_function(
					'$matches',
					'return strtoupper($matches[1]);'
				),
				$action
			);

			if ($action === 'not')
			{
				$action = 'if';
				$parameters['_not'] = true;
			}
			else if ($action === 'pop')
			{
				$action = 'shift';
				$parameters['_pop'] = true;
			}
			else if ($action === 'permalink')
			{
				$action = 'link';
				$parameters['echo'] = true;
			}
			else if ($action === 'previous')
			{
				$action = 'next';
				$parameters['_prev'] = true;
			}
			else if (in_array($action, array('content', 'essays', 'albums')))
			{
				$parameters['_obj'] = $action;
				$action = 'loader';
			}

			$klass = 'Tag' . ucwords($action);
			$t = new $klass($parameters);

			if (!$closing)
			{
				if (!$self_closing)
				{
					self::$last[self::$level] = $t;
				}

				if ($t->tokenize)
				{
					$token = md5(uniqid());
					array_unshift(self::$tokens, $token);
				}

				$out .= trim($t->generate());

				if ($t->tokenize)
				{
					$token = self::$tokens[0];
					$out .= "<?php Koken::\$current_token = \$value$token; ?>";
					if (isset(self::$tokens[1]))
					{
						$parent = self::$tokens[1];
						$out .= "<?php Koken::\$_parent = \$value$parent;  ?>";
					}
				}
			}

			if ($self_closing || $closing)
			{
				if (!$self_closing && isset(self::$last[self::$level-1]) && method_exists(self::$last[self::$level-1], 'close'))
				{
					$close_tag = self::$last[self::$level-1];
					$out .= $close_tag->close();

					if ($close_tag->tokenize)
					{
						array_shift(self::$tokens);
						if (count(self::$tokens))
						{
							$out .= '<?php Koken::$current_token = $value' . self::$tokens[0] . '; ?>';
						}
						if (isset(self::$tokens[1]))
						{
							$parent = self::$tokens[1];
							$out .= "<?php Koken::\$_parent = \$value$parent;  ?>";
						}
					}
				}
				else if (method_exists($t, 'close'))
				{
					$out .= $t->close();

					if ($t->tokenize && !$t->untokenize_on_else)
					{
						array_shift(self::$tokens);
						if (count(self::$tokens))
						{
							$out .= '<?php Koken::$current_token = $value' . self::$tokens[0] . '; ?>';
						}
						if (isset(self::$tokens[1]))
						{
							$parent = self::$tokens[1];
							$out .= "<?php Koken::\$_parent = \$value$parent;  ?>";
						}
					}
				}

				if ($closing)
				{
					self::$level--;
				}
			}
			else
			{
				self::$level++;
			}
		}

		return $out;
	}

	private static function prep_api($url)
	{
		if (strpos($url, 'api.php?') !== false)
		{
			$bits = explode('api.php?', $url);
			$url = $bits[1];
		}

		if (self::$draft && !is_null(self::$site['draft_id']))
		{
			$url .= '/draft_context:' . self::$site['draft_id'];
		}
		else if (self::$preview)
		{
			$url .= '/draft_context:' . self::$preview;
		}
		$api_cache_dir = self::$root_path .
							DIRECTORY_SEPARATOR . 'storage' .
							DIRECTORY_SEPARATOR . 'cache' .
							DIRECTORY_SEPARATOR . 'api';
		$stamp = $api_cache_dir . DIRECTORY_SEPARATOR . 'stamp';
		$cache_file = $api_cache_dir . DIRECTORY_SEPARATOR . md5($url) . '.cache';

		if (file_exists($cache_file) && filemtime($cache_file) >= filemtime($stamp))
		{
			return json_decode( file_get_contents($cache_file), true );
		}
		else
		{
			return $url;
		}
	}

	private static function curl_setup($url, $curl = false)
	{
		if (!$curl)
		{
			$curl = curl_init();
		}
		curl_setopt($curl, CURLOPT_URL, self::$protocol . '://' . $_SERVER['HTTP_HOST'] . self::$location['real_root_folder'] . '/api.php?' . $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		return $curl;
	}

	private static function is_really_callable($functions)
	{
		$disabled_functions = explode(',', str_replace(' ', '', ini_get('disable_functions')));

		if (ini_get('suhosin.executor.func.blacklist'))
		{
			$disabled_functions = array_merge($disabled_functions, explode(',', str_replace(' ', '', ini_get('suhosin.executor.func.blacklist'))));
		}

		if (!is_array($functions))
		{
			$functions = array($functions);
		}


		foreach($functions as $f)
		{
			if (in_array($f, $disabled_functions) || !is_callable($f))
			{
				return false;
			}
		}

		return true;
	}

	public static function api($url)
	{
		if (is_array($url))
		{
			// Shared hosts are crazy, so let's really make sure curl_multi works
			if (
				self::is_really_callable(
					array('curl_multi_init', 'curl_multi_add_handle', 'curl_multi_getcontent', 'curl_multi_remove_handle', 'curl_multi_close', 'curl_multi_exec')
				)
			)
			{
				$return = array();
				$curls = array();

				foreach($url as $index => $u)
				{
					if (is_array($u) && empty($u))
					{
						$return[$index] = array();
					}
					else
					{
						$data = self::prep_api($u);

						if (is_array($data))
						{
							$return[$index] = $data;
						}
						else
						{
							$curls[] = array(
								'index' => $index,
								'url' => $data
							);
						}
					}
				}

				if (!empty($curls))
				{
					if (count($curls) === 1)
					{
						$start = microtime(true);
						$return[$curls[0]['index']] = self::api($curls[0]['url']);
						$end = microtime(true);
						self::$timers[$curls[0]['url']] = $end - $start;
					}
					else
					{
						$start = microtime(true);

						$mh = curl_multi_init();

						foreach($curls as &$ch)
						{
							$ch['curl'] = self::curl_setup($ch['url']);
							curl_multi_add_handle($mh, $ch['curl']);
						}

						// kick off the requests
						do
						{
							$mrc = curl_multi_exec($mh, $active);
						} while ($active > 0);

						$timer_urls = array();

						foreach($curls as $c)
						{
							$timer_urls[] = $c['url'];
							$return[$c['index']] = json_decode( curl_multi_getcontent($c['curl']), true );
							curl_multi_remove_handle($mh, $c['curl']);
						}

						curl_multi_close($mh);

						$end = microtime(true);
						self::$timers[join("\n\t", $timer_urls)] = $end - $start;
					}
				}
			}
			else
			{
				foreach($url as $index => $u)
				{
					$return[] = self::api($u);
				}
			}

			return $return;
		}
		else
		{
			$data = self::prep_api($url);

			if (!is_array($data))
			{

				if (!self::$curl_handle)
				{
					self::$curl_handle = curl_init();
				}

				$curl = self::curl_setup($data, self::$curl_handle);

				$start = microtime(true);
				$data = json_decode( curl_exec($curl), true );
				$end = microtime(true);

				if ($curl !== self::$curl_handle)
				{
					curl_close($curl);
				}

				self::$timers[$url] = $end - $start;
			}

			return $data;
		}
	}

	public static function get_path($relative_path)
	{
		$primary = self::$template_path . DIRECTORY_SEPARATOR . $relative_path;
		$backup = self::$fallback_path . DIRECTORY_SEPARATOR . $relative_path;

		if (file_exists($primary))
		{
			return $primary;
		}
		else if (file_exists($backup))
		{
			return $backup;
		}
		return false;
	}

	public static function render($raw)
	{
		ob_start();
		eval('?>' . $raw);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	public static function cache($contents)
	{

		$buster = self::$root_path . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'no-site-cache';

		if (isset(self::$cache_path) && error_reporting() == 0 && !file_exists($buster))
		{
			function make_child_dir($path)
			{
				// No need to continue if the directory already exists
				if (is_dir($path)) return true;

				// Make sure parent exists
				$parent = dirname($path);
				if (!is_dir($parent))
				{
					make_child_dir($parent);
				}

				$created = false;
				$old = umask(0);

				// Try to create new directory with parent directory's permissions
				$permissions = substr(sprintf('%o', fileperms($parent)), -4);
				if (mkdir($path, octdec($permissions), true))
				{
					$created = true;
				}
				// If above doesn't work, chmod to 777 and try again
				else if (	$permissions == '0755' &&
							chmod($parent, 0777) &&
							mkdir($path, 0777, true)
						)
				{
					$created = true;
				}
				umask($old);
				return $created;
			}

			if (make_child_dir( dirname(self::$cache_path) ))
			{
				file_put_contents(self::$cache_path, $contents);
			}
		}
	}

	private static function case_convert($str, $case)
	{
		switch ($case) {
			case 'lower':
				$str = function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
				break;

			case 'upper':
				$str = function_exists('mb_strtoupper') ? mb_strtoupper($str) : strtoupper($str);
				break;

			case 'title':
				$str = function_exists('mb_convert_case') ? mb_convert_case($str, MB_CASE_TITLE) : ucwords($str);
				break;

			case 'sentence':
				$str = function_exists('mb_substr') ? mb_strtoupper(mb_substr($str, 0, 1)) . mb_strtolower(mb_substr($str, 1)) : ucfirst($str);
				break;
		}
		return $str;
	}
	public static function out($key)
	{
		$parameters = array( 'separator' => ' ' );
		preg_match_all('/([a-z_]+)=["\']([^"\']+)["|\']/', $key, $matches);
		foreach($matches[1] as $i => $name)
		{
			$key = str_replace($matches[0][$i], '', $key);
			$parameters[$name] = $matches[2][$i];
		}

		$is_archive = strpos($key, 'archive.type') !== false;

		$key = str_replace(' ', '', $key);
		$count = $plural = $singular = $math = $to_json = false;
		if (strpos($key, '|') === false)
		{
			$globals = array(
				'site', 'location', '_parent', 'rss', 'profile', 'source', 'settings', 'routed_variables', 'page_variables', 'pjax', 'labels'
			);

			if (strpos($key, '.length') !== false)
			{
				$key = str_replace('.length', '', $key);
				$count = true;
			}

			if (preg_match('/_on$/', $key))
			{
				$key .= '.timestamp';
				if (!isset($parameters['date_format']))
				{
					if (isset($parameters['date_only']))
					{
						$parameters['date_format'] = self::$site['date_format'];
					}
					else if (isset($parameters['time_only'])) {
						$parameters['date_format'] = self::$site['time_format'];
					}
					else
					{
						$parameters['date_format'] = self::$site['date_format'] . ' ' . self::$site['time_format'];
					}
				}
			}

			if (preg_match('~\s*([+\-/\*])\s*(([0-9]+)|([a-z_\.]+))\s*?~', $key, $maths))
			{
				$math = array('operator' => $maths[1], 'num' => is_numeric($maths[2]) ? $maths[2] : self::out($maths[2]));
				$key = str_replace($maths[0], '', $key);
			}

			$keys = explode('.', $key);

			if (in_array($keys[0], $globals))
			{
				$global_key = array_shift($keys);
				if ($global_key === 'labels')
				{
					$return = self::$site['url_data'];
				}
				else if ($global_key === 'rss')
				{
					$return = self::$rss_feeds;
				}
				else
				{
					$return = self::$$global_key;
				}
			}
			else if (in_array($key, self::$template_variable_keys))
			{
				return self::$template_variables[$key];
			}
			else
			{
				$return = self::$current_token;
			}

			if (count($keys) === 1 && $keys[0] === 'now')
			{
				$return = time();
			}
			else if (count($keys) === 1 && $keys[0] === 'year')
			{
				$return = date('Y');
			}
			else
			{
				while(count($keys))
				{
					$index = array_shift($keys);
					if ($index === 'index')
					{
						$index = '__loop_index';
					}
					else if ($index === 'first')
					{
						$index = 0;
					}

					if ((!is_array($return) || !isset($return[$index])) && $index === 'type' && isset($return['__koken__']))
					{
						if (count($keys))
						{
							$next = array_shift($keys);
						}
						else
						{
							$next = 'plural';
						}
						$return = self::$site['url_data'][$return['__koken__']][$next];

						if (!isset($parameters['case']))
						{
							$parameters['case'] = 'title';
						}
						$plural = $singular = false;
						$keys = array();
					}
					else if ((!is_array($return) || !isset($return[$index])) && $index === 'clean')
					{
						$parameters['clean'] = true;
					}
					else if (!isset($return[$index]) && $index === 'title' && isset($return['year']))
					{
						if (isset($return['month']))
						{
							$return = self::title_from_archive($return, isset($parameters['date_format']) ? $parameters['date_format'] : false);
						}
						else
						{
							$return = $return['year'];
						}
						unset($parameters['date_format']);
					}
					else
					{
						if (($index === 'plural' || $index === 'singular') && $is_archive && isset(self::$site['url_data'][$return]) && isset(self::$site['url_data'][$return][$index]))
						{
							$return = self::$site['url_data'][$return][$index];

							if (!isset($parameters['case']))
							{
								$parameters['case'] = 'lower';
							}
						}
						else
						{
							if ($index === 'to_json')
							{
								$to_json = true;
							}
							else if ($index === 'plural' && (!is_array($return) || !isset($return['plural'])))
							{
								$plural = true;
							}
							else if ($index === 'singular' && (!is_array($return) || !isset($return['singular'])))
							{
								$singular = true;
							}
							else
							{
								$return = isset($return[$index]) ? $return[$index] : '';
							}
						}

					}
				}
			}
		}
		else
		{
			$candidates = explode('|', $key);
			$return = '';
			while(empty($return) && count($candidates))
			{
				$return = self::out(array_shift($candidates));
			}
		}

		if ($count)
		{
			$return = count($return);

			if (isset($parameters['plural']) && isset($parameters['singular']) && is_numeric($return))
			{
				return $return === 1 ? $parameters['singular'] : $parameters['plural'];
			}
			else
			{
				return $return;
			}
		}
		else
		{

			if (isset($parameters['truncate']))
			{
				$return = self::truncate(strip_tags($return), $parameters['truncate'], isset($parameters['after_truncate']) ? $parameters['after_truncate'] : '…');
			}

			if (isset($parameters['case']))
			{
				$return = self::case_convert($return, $parameters['case']);
			}

			if (isset($parameters['paragraphs']))
			{
				$return = self::format_paragraphs($return);
			}

			if (isset($parameters['date_format']))
			{
				if (is_array($return) && isset($return['timestamp']))
				{
					$return = $return['timestamp'];
				}
				$return = date($parameters['date_format'], $return);
			}

			if (isset($parameters['strip_html']))
			{
				$return = str_replace('"', '&quot;', strip_tags($return));
			}

			if (isset($parameters['url_encode']))
			{
				$return = urlencode($return);
			}

			if (isset($parameters['if_true']))
			{
				$return = $return ? $parameters['if_true'] : '';
			}

			if (isset($parameters['clean']))
			{
				$return = preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', preg_replace('/\.[a-z]+$/', '', $return)));
			}

			if (isset($parameters['plural']) && isset($parameters['singular']) && is_numeric($return))
			{
				$return = $return === 1 ? $parameters['singular'] : $parameters['plural'];
			}

			if ($plural)
			{
				$return = self::plural($return);
			}

			if ($singular)
			{
				$return = self::singular($return);
			}

			if ($math)
			{
				switch($math['operator'])
				{
					case '+':
						$return += $math['num'];
						break;

					case '-':
						$return -= $math['num'];
						break;

					case '/':
						$return /= $math['num'];
						break;

					case '*':
						$return *= $math['num'];
						break;
				}
			}

			if (is_array($return) && !isset($parameters['debug']))
			{
				if ($to_json)
				{
					unset($return['counts']);

					$fields = false;
					if (isset($parameters['fields']))
					{
						$fields = explode(',', str_replace(' ', '', $parameters['fields']));
					}

					if (isset($return[0]))
					{
						$fresh = array();
						foreach($return as $r)
						{
							if ($fields)
							{
								$slim = array();
								foreach($fields as $f)
								{
									$slim[$f] = $r[$f];
								}
								$fresh[] = $slim;
							}
							else
							{
								$fresh[] = $r;
							}
						}
						$return = $fresh;
					}
					else if ($fields)
					{
						$slim = array();
						foreach($fields as $f)
						{
							$slim[$f] = $return[$f];
						}
						$return = $slim;
					}

					$return = json_encode($return);
				}
				else if (isset($return['clean']))
				{
					$return = $return['clean'];
				}
				else if (isset($return['raw']))
				{
					$return = $return['raw'];
				}
				else if (count($return))
				{
					if (is_array($return[0]))
					{
						if (isset($parameters['field']) && isset($return[0][$parameters['field']]))
						{
							$return = array_map(create_function('$arr', 'return $arr["' . $parameters['field'] . '"];'), $return);
						}
						else
						{
							$return = array();
						}
					}

					$return = join($parameters['separator'], $return);
				}
				else {
					$return = '';
				}
			}
			return isset($parameters['debug']) ? var_dump($return) : $return;
		}
	}

	private static function singular($str)
	{
		$result = strval($str);

		$singular_rules = array(
			'/(matr)ices$/'         => '\1ix',
			'/(vert|ind)ices$/'     => '\1ex',
			'/^(ox)en/'             => '\1',
			'/(alias)es$/'          => '\1',
			'/([octop|vir])i$/'     => '\1us',
			'/(cris|ax|test)es$/'   => '\1is',
			'/(shoe)s$/'            => '\1',
			'/(o)es$/'              => '\1',
			'/(bus|campus)es$/'     => '\1',
			'/([m|l])ice$/'         => '\1ouse',
			'/(x|ch|ss|sh)es$/'     => '\1',
			'/(m)ovies$/'           => '\1\2ovie',
			'/(s)eries$/'           => '\1\2eries',
			'/([^aeiouy]|qu)ies$/'  => '\1y',
			'/([lr])ves$/'          => '\1f',
			'/(tive)s$/'            => '\1',
			'/(hive)s$/'            => '\1',
			'/([^f])ves$/'          => '\1fe',
			'/(^analy)ses$/'        => '\1sis',
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
			'/([ti])a$/'            => '\1um',
			'/(p)eople$/'           => '\1\2erson',
			'/(m)en$/'              => '\1an',
			'/(s)tatuses$/'         => '\1\2tatus',
			'/(c)hildren$/'         => '\1\2hild',
			'/(n)ews$/'             => '\1\2ews',
			'/([^u])s$/'            => '\1',
		);

		foreach ($singular_rules as $rule => $replacement)
		{
			if (preg_match($rule, $result))
			{
				$result = preg_replace($rule, $replacement, $result);
				break;
			}
		}

		return $result;
	}

	private static function plural($str, $force = FALSE)
	{
		$result = strval($str);

		$plural_rules = array(
			'/^(ox)$/'                 => '\1\2en',     // ox
			'/([m|l])ouse$/'           => '\1ice',      // mouse, louse
			'/(matr|vert|ind)ix|ex$/'  => '\1ices',     // matrix, vertex, index
			'/(x|ch|ss|sh)$/'          => '\1es',       // search, switch, fix, box, process, address
			'/([^aeiouy]|qu)y$/'       => '\1ies',      // query, ability, agency
			'/(hive)$/'                => '\1s',        // archive, hive
			'/(?:([^f])fe|([lr])f)$/'  => '\1\2ves',    // half, safe, wife
			'/sis$/'                   => 'ses',        // basis, diagnosis
			'/([ti])um$/'              => '\1a',        // datum, medium
			'/(p)erson$/'              => '\1eople',    // person, salesperson
			'/(m)an$/'                 => '\1en',       // man, woman, spokesman
			'/(c)hild$/'               => '\1hildren',  // child
			'/(buffal|tomat)o$/'       => '\1\2oes',    // buffalo, tomato
			'/(bu|campu)s$/'           => '\1\2ses',    // bus, campus
			'/(alias|status|virus)/'   => '\1es',       // alias
			'/(octop)us$/'             => '\1i',        // octopus
			'/(ax|cris|test)is$/'      => '\1es',       // axis, crisis
			'/s$/'                     => 's',          // no change (compatibility)
			'/$/'                      => 's',
		);

		foreach ($plural_rules as $rule => $replacement)
		{
			if (preg_match($rule, $result))
			{
				$result = preg_replace($rule, $replacement, $result);
				break;
			}
		}

		return $result;
	}

	public static function get_default_link($name)
	{
		$name = rtrim($name, 's');
		$template = self::$location['urls'][$name];
		preg_match('~^/([^/]+)~', $template, $matches);
		return $matches[0];
	}

	public static function form_link($obj, $ctx, $lightbox)
	{
		if (isset($obj['link']))
		{
			return $obj['link'];
		}

		if (isset($obj['__koken_url']) && !isset($obj['__koken__override']))
		{
			$url = $obj['__koken_url'];
		}
		else
		{
			$defaults = self::$location['urls'];
			if (isset($obj['__koken__override']))
			{
				$type = $obj['__koken__override'];

				if (isset($obj['album']))
				{
					$obj = $obj['album'];
				}

				if ((!self::$source || strpos(self::$source['type'], 'event') !== 0) && $type === 'event_timeline' && in_array($obj['__koken__'], array('content', 'album', 'essay')) && isset($defaults['event_timeline']))
				{
					$type = 'event_timeline';
				}
			}
			else
			{
				if (isset($obj['album']))
				{
					$obj = $obj['album'];
				}
				$type = $obj['__koken__'];
			}

			$url = '';

			if ($type === 'album' && $obj['album_type'] === 'set')
			{
				$type = 'set';
			}

			if (isset($defaults[$type]))
			{
				if (!$defaults[$type] && $type === 'set')
				{
					$type = 'album';
				}

				if ($defaults[$type])
				{
					if (strpos($type, 'tag') === 0)
					{
						$obj['id'] = $obj['title'];
						if (is_numeric($obj['id']))
						{
							$obj['id'] = 'tag-' . $obj['id'];
						}
					}

					if (isset($obj['internal_id']))
					{
						$obj['id'] = $obj['slug'] = $obj['internal_id'];
					}

					$url = $defaults[$type];

					if ($obj['__koken__'] === 'content' && isset($ctx['id']) && is_numeric($ctx['id']))
					{
						$obj['content_slug'] = $obj['slug'];
						$obj['content_id'] = $obj['id'];
						if (isset($ctx['internal_id']))
						{
							$obj['id'] = $obj['slug'] = $ctx['internal_id'];
						}
						else
						{
							$obj['slug'] = $ctx['slug'];
							$obj['id'] = $ctx['id'];
						}
					}

					if (!isset($obj['slug']) && isset($obj['tag']))
					{
						$obj['slug'] = $obj['tag']['title'];
						if (is_numeric($obj['slug']))
						{
							$obj['slug'] = 'tag-' . $obj['slug'];
						}
					}

					if (isset($obj['date']))
					{
						if (isset($obj['__koken__override_date']) && isset($obj[$obj['__koken__override_date']]))
						{
							$date = $obj[$obj['__koken__override_date']];
						}
						else
						{
							$date = $obj['date'];
						}
						$obj['year'] = date('Y', $date['timestamp']);
						$obj['month'] = date('m', $date['timestamp']);
						// $obj['day'] = date('d', $obj['date']['timestamp']);
					}

					preg_match_all('/:([a-z_]+)/', $url, $matches);

					foreach($matches[1] as $magic)
					{
						if (!isset($obj[$magic]))
						{
							$obj[$magic] = '';
						}
						$url = str_replace(':' . $magic, urlencode($obj[$magic]), $url);
					}

					$url = str_replace('(?:', '', $url);
					$url = str_replace(')?', '', $url);
				}
			}
		}

		$url = rtrim($url, '/');

		if ($lightbox)
		{
			$url = preg_replace('~/?lightbox$~', '', $url) . '/lightbox';
		}

		return $url . '/';
	}

	public static function context_parameters($type = 'content')
	{
		if ($type === 'content')
		{
			$params = '/context:';
			if (isset(Koken::$routed_variables['album_id']) || isset(Koken::$routed_variables['album_slug']))
			{
				$params .= isset(Koken::$routed_variables['album_id']) ? Koken::$routed_variables['album_id'] : 'slug-' . Koken::$routed_variables['album_slug'];
				$order = explode(' ', self::$site['url_data']['content']['album_order']);
				$params .= '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
			else if (isset(Koken::$routed_variables['tag_slug']))
			{
				$params .= 'tag-' . urlencode(Koken::$routed_variables['tag_slug']);
				$order = explode(' ', self::$site['url_data']['content']['order']);
				$params .= '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
			else if (isset(Koken::$routed_variables['category_slug']))
			{
				$params .= 'category-' . urlencode(Koken::$routed_variables['category_slug']);
				$order = explode(' ', self::$site['url_data']['content']['order']);
				$params .= '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
			else
			{
				if (isset(self::$site['urls']['favorites']) && strpos(self::$location['here'], self::$site['urls']['favorites']) === 0)
				{
					$order = explode(' ', self::$site['url_data']['favorite']['order']);
					$params .= 'favorites';
				}
				else
				{
					$order = explode(' ', self::$site['url_data']['content']['order']);
					$params .= 'stream';
				}
				$params .= '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
		}
		else
		{
			if (isset(Koken::$routed_variables['tag_slug']))
			{
				$params = '/context:tag-' . urlencode(Koken::$routed_variables['tag_slug']);
				$order = explode(' ', self::$site['url_data'][$type]['order']);
				$params .= '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
			else if (isset(Koken::$routed_variables['category_slug']))
			{
				$params = '/context:category-' . urlencode(Koken::$routed_variables['category_slug']);
				$order = explode(' ', self::$site['url_data'][$type]['order']);
				$params .= '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
			else
			{
				$order = explode(' ', self::$site['url_data'][$type]['order']);
				$params = '/context_order:' . $order[0] . '/context_order_direction:' . strtolower($order[1]);
			}
		}
		return $params;
	}

	public static function render_nav($data, $list, $root = false, $klass = '')
	{
		$pre = $wrap_pre = $wrap_post = '';
		$post = '&nbsp;';
		if ($list)
		{
			$wrap_pre = '<ul class="k-nav-list' . ($root ? ' k-nav-root' : '') . ' ' . $klass . '">';
			$wrap_post = '</ul>';
			$pre = '<li>';
			$post = '</li>';
		}

		$current_match_len = 0;
		$o = $wrap_pre;
		foreach($data as $key => $value)
		{
			if (isset($value['hide'])) continue;

			if (strlen($value['path']) && $value['path'][0] === '/' && !preg_match('/\.rss$/', $value['path']))
			{
				$value['path'] = rtrim($value['path'], '/') . '/';
			}
			if ($value['path'] === self::$navigation_home_path)
			{
				$value['path'] = '/';
			}
			if ($value['path'] == '/')
			{
				$current = self::$location['here'] === '/';
			}
			else
			{
				$current = preg_match('~^' . $value['path'] . '(.*)?$~', self::$location['here']) && strlen($value['path']) > $current_match_len;

				if ($current)
				{
					$current_match_len = strlen($value['path']);
					$o = str_replace('class="k-nav-current"', '', $o);
					$o = str_replace('class="k-nav-current ', 'class="', $o);
				}
			}
			$o .= $pre . '<a';
			if (isset($value['target']))
			{
				$o .= ' target="' . $value['target'] . '"';
			}
			$classes = array();
			if ($current){
				$classes[] = 'k-nav-current';
			}
			if (isset($value['set']))
			{
				$classes[] = 'k-nav-set';
			}
			if (count($classes))
			{
				$o .= ' class="' . join(' ', $classes) . '"';
			}

			$root = $value['path'] === '/' ? preg_replace( '/\/index.php$/', '', self::$location['root'] ) : self::$location['root'];
			$is_native = !preg_match('/^(https?|mailto)/', $value['path']);
			$o .= ' title="' . $value['label'] . '" href="' . ( $is_native ? $root : '' ) . $value['path'] . ( self::$preview && $is_native ? '&amp;preview=' . self::$preview : '') . '">' . $value['label'] . '</a>';

			if (isset($value['items']) && !empty($value['items']))
			{
				$o .= self::render_nav($value['items'], $list);
			}
			$o .= $post;
		}
		return $o . $wrap_post;
	}

	static public function output_img($content, $options = array(), $params = '')
	{
		$defaults = array(
			'width' => 0,
			'height' => 0,
			'crop' => false,
			'hidpi' => self::$site['hidpi'] && !self::$rss
		);

		$options = array_merge($defaults, $options);

		$attr = array(
			$options['width'],
			$options['height']
		);

		$w = $options['width'];
		$h = $options['height'];

		if ($w ==0 && $h == 0)
		{
			// Responsive
			// return;
			// $w = '100%';
		}

		if (!isset($content['url']) || !isset($content['cropped']))
		{
			if (!$options['crop'])
			{
				if ($options['width'] == 0)
				{
					$w = round(($h*$content['width'])/$content['height']);
				}
				else if ($options['height'] == 0)
				{
					$h = round(($w*$content['height'])/$content['width']);
				}
				else
				{
					$original_aspect = $content['aspect_ratio'];
					$target_aspect = $w/$h;
					if ($original_aspect >= $target_aspect)
					{
						if ($w > $content['width'])
						{
							$w = $content['width'];
							$h = $content['height'];
						}
						else
						{
							$h = round(($w*$content['height'])/$content['width']);
						}
					}
					else
					{
						if ($h > $content['height'])
						{
							$w = $content['width'];
							$h = $content['height'];
						}
						else
						{
							$w = round(($h*$content['width'])/$content['height']);
						}
					}
				}
			}

			$longest = max($w, $h);

			$breakpoints = array(
				'tiny' => 60,
				'small' => 100,
				'medium' => 480,
				'medium_large' => 800,
				'large' => 1024,
				'xlarge' => 1600,
				'huge' => 2048
			);

			$preset_base = '';
			$last_len = false;
			foreach($breakpoints as $name => $len)
			{
				if ($longest <= $len)
				{
					$diff = $len - $longest;
					if (!$last_len || ($longest - $last_len > $diff))
					{
						$preset_base = $name;
					}
					break;
				}
				$preset_base = $name;
				$last_len = $len;
			}

			$attr[] = self::$site["image_{$preset_base}_quality"];
			$attr[] = self::$site["image_{$preset_base}_sharpening"]*100;

			$url = $content['cache_path']['prefix'] . join('.', $attr);
			if ($options['crop'])
			{
				$url .= '.crop';
			}
			if ($options['hidpi'])
			{
				$url .= '.2x';
			}
			$url .= '.' . $content['cache_path']['extension'];
		}
		else
		{
			if ($options['crop'])
			{
				$content = $content['cropped'];
			}
			else if ($w > 0 && $h > 0)
			{
				$original_aspect = $content['width'] / $content['height'];
				$target_aspect = $w/$h;
				if ($original_aspect >= $target_aspect)
				{
					$h = round(($w*$content['height'])/$content['width']);
				}
				else
				{
					$w = round(($h*$content['width'])/$content['height']);
				}
			}
			$url = $content['url'];
		}

		if ((isset($params['class']) && strpos($params['class'], 'k-lazy-load') !== false) || $options['hidpi'])
		{
			$params['data-src'] = $url;
			$noscript = true;
			$params['src'] = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
		}
		else
		{
			$noscript = false;
			$params['src'] = $url;
		}

		if ($w > 0)
		{
			$params['width'] = $w;
		}
		if ($h > 0)
		{
			$params['height'] = $h;
		}

		if ($noscript)
		{
			$noscript_params = $params;
			$noscript_params['src'] = $noscript_params['data-src'];
			unset($noscript_params['data-src']);
			$noscript = '<noscript><img ' . self::params_to_str($noscript_params) . ' /></noscript>';
		}
		else
		{
			$noscript = '';
		}

		return "$noscript<img " . self::params_to_str($params) . " />";
	}

	static private function params_to_str($params)
	{
		$arr = array();
		foreach($params as $key => $val)
		{
			$arr[] = "$key=\"$val\"";
		}
		return join(' ', $arr);
	}

	static public function truncate($str, $limit, $after = '…')
	{
		if (strlen($str) > $limit) {
			$str = trim(substr($str, 0, $limit)) . $after;
		}

		return $str;
	}

	static public function format_paragraphs($pee, $br = 1)
	{
		if ( trim($pee) === '' )
		return '';
		$pee = $pee . "\n"; // just to make things a little easier, pad the end
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		if ( strpos($pee, '<object') !== false ) {
			$pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
			$pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
		}
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		// make paragraphs, including one at the end
		$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
		$pee = '';
		foreach ( $pees as $tinkle )
			$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
		$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace

		return $pee;
	}

	static public function covers($data, $min, $max)
	{
		if (!isset($data['album']['covers']))
		{
			return array();
		}

		$covers = $data['album']['covers'];

		if ($min && count($covers) < $min)
		{
			if (isset($data['albums']))
			{
				$pool = array();
				foreach($data['albums'] as $album)
				{
					if (isset($album['covers']) && count($album['covers']))
					{
						$pool = array_merge($pool, $album['covers']);
					}
				}
			}
			else if (isset($data['content']))
			{
				$pool = $data['content'];
			}
			else if ($data['album_type'] === 'standard')
			{
				$diff = $min - count($covers);
				$content = self::api('/albums/' . $data['album']['id'] .
					'/content/with_topics:0/covers:0/with_covers:0/with_context:0/limit:' . $diff .
					(isset($data['album']['__cover_hint_before']) ? '/order_by:uploaded_on/before:' . $data['album']['__cover_hint_before'] : '')
				);
				$pool = $content['content'];
			}
			else
			{
				$pool = array();
			}

			if (count($pool) && $min - count($covers) > 0)
			{
				$ids = array();
				foreach($covers as $c)
				{
					$ids[] = $c['id'];
				}

				foreach($pool as $content)
				{
					if (isset($content['id']) && !in_array($content['id'], $ids))
					{
						$covers[] = $content;
					}

					if (count($covers) >= $min)
					{
						break;
					}
				}
			}
		}

		if ($max)
		{
			$covers = array_slice($covers, 0, $max);
		}

		return $covers;
	}

	static private function parse_source_aliases($source)
	{
		$aliases = array(
			'date' => 'events',
			'archives' => 'events',
			'timeline' => 'events'
		);

		if (isset($aliases[$source]))
		{
			return $aliases[$source];
		}

		return $source;
	}

	static public function load($params)
	{
		$defaults = array(
			'model' 			=> 'content',
			'list'				=> false,
			'filters'			=> array(),
			'id_from_url' 		=> false,
			'paginate_from_url' => false,
			'api'				=> array(),
			'load_content'		=> false,
			'id'				=> false,
			'id_prefix'			=> '',
			'tree' 				=> false,
			'type' 				=> false,
			'source'			=> false,
			'archive'			=> false
		);

		$featured = false;

		$params['filters'] = array();

		foreach($params as $key => $val)
		{
			if (strpos($key, 'filter:') === 0)
			{
				$left = str_replace('filter:', '', $key);
				if (strpos($left, ':not') === false)
				{
					$right = '';
				}
				else
				{
					$right = '!';
					$left = str_replace(':not', '', $left);
				}
				$params['filters'][] = $left . '=' . $right . $val;
			}
			else if (!isset($defaults[$key]))
			{
				$defaults['api'][$key] = $val;
			}
		}

		$custom = false;

		if (isset($params['source']))
		{
			$params['source'] = self::parse_source_aliases($params['source']);
			$source = array('type' => $params['source']);
			$defaults['list'] = substr( strrev($params['source']), 0, 1 ) === 's';
			$defaults['model'] = rtrim( $params['source'], 's' ) . 's';
			$defaults['filters'] = isset($params['filters']) ? $params['filters'] : array();
			$custom = true;
			if (isset($params['tree']))
			{
				$defaults['tree'] = true;
			}
		}
		else if (Koken::$source)
		{
			Koken::$source['type'] = self::parse_source_aliases(Koken::$source['type']);
			$source = Koken::$source;
			$defaults['list'] = substr( strrev(Koken::$source['type']), 0, 1 ) === 's';
			$defaults['model'] = rtrim( Koken::$source['type'], 's' ) . 's';
			$defaults['filters'] = is_array(Koken::$source['filters']) ? Koken::$source['filters'] : array();
		}

		if ($defaults['model'] === 'sets' && $defaults['list'])
		{
			$defaults['model'] = 'albums';
			$defaults['api']['types'] = 'set';
		}

		if (strpos($defaults['model'], 'featured_') === 0)
		{
			$bits = explode('_', $defaults['model']);
			$defaults['model'] = 'features';
			$defaults['id'] = rtrim($bits[1], 's');
			if ($defaults['id'] === 'essay')
			{
				$defaults['id'] = 'text';
			}
			$defaults['list'] = true;
		}

		if ($defaults['model'] === 'contents')
		{
			$defaults['model'] = 'content';
		}
		else if ($defaults['model'] === 'essays' || $defaults['model'] === 'pages')
		{
			if ($defaults['list'])
			{
				$defaults['api']['type'] = trim($defaults['model'], 's');
			}
			$defaults['model'] = 'text';
		}
		else if ($defaults['model'] === 'categorys')
		{
			$defaults['model'] = 'categories';
		}
		else if ($defaults['model'] === 'sets')
		{
			$defaults['model'] = 'albums';
		}

		if (is_array($defaults['filters']))
		{
			foreach($defaults['filters'] as $filter)
			{
				if (strpos($filter, '=') !== false)
				{
					$bits = explode('=', $filter);
					if ($bits[0] === 'id')
					{
						$__id = substr($bits[1], 0, 1) === '"' ? $bits[1] : urlencode($bits[1]);
					}
					else if ($bits[0] === 'members')
					{
						$params['type'] = $bits[1];
					}
					else
					{
						if (strpos($bits[1], '!') === 0 || strpos($bits[0], '!') !== false)
						{
							$bits[1] = str_replace('!', '', $bits[1]);
							$bits[0] = str_replace('!', '', $bits[0]) . '_not';
						}
						if (strpos($bits[0], 'category') === 0 && (!is_numeric($bits[1]) && strpos($bits[1], '" . Koken') !== 0))
						{
							$bits[1] = Koken::$categories[strtolower($bits[1])];
						}
						$defaults['api'][$bits[0]] = $bits[1];
					}
				}
				else
				{
					if (substr($filter, 0, 1) === '!')
					{
						$filter = str_replace('!', '', $filter);
						$val = 0;
					}
					else
					{
						$val = 1;
					}

					if ($filter === 'featured' && $val === 1)
					{
						$featured = true;
					}
					else
					{
						$defaults['api'][$filter] = $val;
					}

				}
			}
		}

		if ($source['type'] === 'tags' && isset($__id))
		{
			$defaults['id'] = $__id;
		}
		else if ($source['type'] === 'event' && isset(Koken::$routed_variables['year']))
		{
			$__id = join('-', array(Koken::$routed_variables['year'], Koken::$routed_variables['month'], Koken::$routed_variables['day']));
		}

		if ($source['type'] === 'events' && !$custom)
		{
			if (isset(Koken::$routed_variables['month']))
			{
				$defaults['api']['month'] = Koken::$routed_variables['month'];
			}
			else if (isset($defaults['api']['month']))
			{
				Koken::$routed_variables['month'] = $defaults['api']['month'];
			}
			if (isset(Koken::$routed_variables['year']))
			{
				$defaults['api']['year'] = Koken::$routed_variables['year'];
			}
			else if (isset($defaults['api']['year']))
			{
				Koken::$routed_variables['year'] = $defaults['api']['year'];
			}
			if (isset(Koken::$routed_variables['day']))
			{
				$defaults['api']['day'] = Koken::$routed_variables['day'];
			}
			else if (isset($defaults['api']['day']))
			{
				Koken::$routed_variables['day'] = $defaults['api']['day'];
			}
		}

		if ($source['type'] === 'archive' && !$custom)
		{
			if ($params['type'] === 'essays')
			{
				$defaults['api']['type'] = 'essay';
				$defaults['model'] = 'text';
			}
			else
			{
				$defaults['model'] = $params['type'] === 'contents' ? 'content' : $params['type'];
			}
			$defaults['list'] = true;
			if (isset(Koken::$routed_variables['month']))
			{
				$defaults['api']['month'] = Koken::$routed_variables['month'];
			}
			else if (isset($defaults['api']['month']))
			{
				Koken::$routed_variables['month'] = $defaults['api']['month'];
			}
			if (isset(Koken::$routed_variables['year']))
			{
				$defaults['api']['year'] = Koken::$routed_variables['year'];
			}
			else if (isset($defaults['api']['year']))
			{
				Koken::$routed_variables['year'] = $defaults['api']['year'];
			}
			if (isset(Koken::$routed_variables['day']))
			{
				$defaults['api']['day'] = Koken::$routed_variables['day'];
			}
			else if (isset($defaults['api']['day']))
			{
				Koken::$routed_variables['day'] = $defaults['api']['day'];
			}

			$defaults['archive'] = 'date';
		}

		if (!$defaults['list'])
		{
			if ($source['type'] === 'tag' && !$custom && isset($params['type']))
			{
				if ($params['type'] === 'essays')
				{
					$defaults['api']['type'] = 'essay';
					$defaults['model'] = 'text';
					$defaults['api'] = array_merge(array('order_by' => 'published_on', 'state' => 'published'), $defaults['api'] );
				}
				else
				{
					$defaults['model'] = $params['type'] === 'contents' ? 'content' : $params['type'];
				}
				$defaults['list'] = true;
				$defaults['id_prefix'] = 'tags:';
				$defaults['paginate_from_url'] = true;
				$defaults['archive'] = 'tag';
			}

			if (isset($__id))
			{
				$defaults['id'] = $__id;
				if (!$custom)
				{
					Koken::$routed_variables['id'] = $__id;
				}

				if ($source['type'] === 'tag')
				{
					$defaults['id_prefix'] = 'slug:';
				}
			}
			else
			{
				$defaults['id_from_url'] = true;
			}

			if (in_array($defaults['model'], array('albums', 'events', 'tags', 'categories')))
			{
				$defaults['list'] = true;
				$defaults['paginate_from_url'] = true;
			}
		}

		if ($defaults['list'])
		{

			if ($defaults['model'] === 'albums' || $defaults['model'] === 'categories')
			{
				$defaults['api'] = array_merge(array('include_empty' => '0'), $defaults['api'] );
			}
			else if ($defaults['model'] === 'text')
			{
				$defaults['api'] = array_merge(array('order_by' => 'published_on', 'state' => 'published'), $defaults['api'] );
			}

			$defaults['paginate_from_url'] = true;

		}

		$options = $defaults;

		$paginate = $options['paginate_from_url'] && $options['list'] && !$custom;

		$url = '/' . $options['model'] . ( $featured ? '/featured' : '' );

		if ($options['tree'] && $options['model'] === 'albums')
		{
			$url .= '/tree';
		}

		if ($options['model'] === 'text' && isset($_GET['preview_draft']))
		{
			$options['id'] = $_GET['preview_draft'];
			$options['id_from_url'] = false;
		}

		if ($options['id_from_url'] || $options['id'])
		{
			if (!isset($defaults['api']['custom']))
			{
				if ($options['id_from_url'])
				{
					if (empty($options['id_prefix']))
					{
						$slug_prefix = 'slug:';
					}
					else
					{
						$slug_prefix = '';
					}
					if ($options['id_prefix'] === 'tags:' && preg_match('/tag\-\d+/', self::$routed_variables['slug']))
					{
						self::$routed_variables['slug'] = str_replace('tag-', '', self::$routed_variables['slug']);
					}
					$url .= "/{$options['id_prefix']}" . ( isset(self::$routed_variables['id']) ? urlencode(self::$routed_variables['id']) : $slug_prefix . urlencode(self::$routed_variables['slug']) );
				}
				else if ($options['id'])
				{
					$url .= "/{$options['id_prefix']}" . urlencode($options['id']);
				}

				if (!isset($defaults['api']['context']))
				{
					if ($options['model'] === 'content')
					{
						$url .= self::context_parameters();
					}
					else if ($options['model'] === 'text')
					{
						$url .= self::context_parameters('essay');
					}
					else if ($options['model'] === 'albums' && $options['list'])
					{
						$url .= '/content' . Koken::context_parameters('album');
					}
				}
			}
		}

		if (isset($params['type']) && $options['model'] === 'categories')
		{
			$url .= '/' . ($params['type'] === 'contents' ? 'content' : $params['type']);
			$options['list'] = true;
			$paginate = !$custom;
			$options['archive'] = 'category';
			if ($params['type'] === 'essays')
			{
				$options['api'] = array_merge(array('order_by' => 'published_on', 'state' => 'published'), $defaults['api'] );
			}
		}

		if (in_array($options['model'], array('content', 'text', 'albums')))
		{
			if (!isset($options['api']['neighbors']))
			{
				$options['api']['neighbors'] = 2;
			}

			$max_n = 2;
			foreach(self::$max_neighbors as $max)
			{
				if (!is_numeric($max))
				{
					preg_match('/settings\.([a-z_]+)/', $max, $match);
					if ($match)
					{
						$max = self::$settings[$match[1]];
					}
				}
				$max_n = max($max_n, $max);
			}
			$options['api']['neighbors'] = max($options['api']['neighbors'], $max_n);
		}

		if (!$custom)
		{
			$overrides = Koken::$location['parameters']['__overrides'];
			if (isset($overrides['order_by']) && strpos($overrides['order_by'], '_on') !== false && !isset($overrides['order_direction']))
			{
				$overrides['order_direction'] = 'desc';
			}
			$options['api'] = array_merge($options['api'], $overrides);
		}

		foreach($options['api'] as $key => $value)
		{
			if (!is_numeric($value) && $value == 'true')
			{
				$value = 1;
			}
			else if (!is_numeric($value) && $value == 'false')
			{
				$value = 0;
			}
			else
			{
				$value = urlencode($value);
			}
			$url .= "/$key:$value";
		}

		$collection_name = $options['model'] === 'contents' || ($options['model'] === 'albums' && ($options['id_from_url'] || $options['id']) && $options['list']) ? 'content' : $options['model'];

		return array(
			$url, $options, $collection_name, $paginate
		);
	}

	static function time($token, $options = array(), $attr)
	{

		$options['relative'] = $options['relative'] === 'true' || $options['relative'] == '1';
		$has_class = $options['class'] !== 'false';
		$options['rss'] = $options['rss'] !== 'false';

		if (!$token)
		{
			$timestamp = time();
		}
		else if (isset($token['timestamp']))
		{
			$timestamp = $token['timestamp'];
		}
		else if (isset($token['album']))
		{
			$timestamp = $token['album']['date']['timestamp'];
		}
		else if (isset($token['date']) && isset($token['date']['timestamp']))
		{
			$timestamp = $token['date']['timestamp'];
		}
		else if (isset($token['event']) || (isset($token['__koken__']) && $token['__koken__'] === 'event') || isset($token['year']))
		{
			$obj = isset($token['event']) ? $token['event'] : $token;
			$str = $obj['year'] . '-';
			$format = 'Y';

			if (isset($obj['month']))
			{
				$str .= $obj['month'] . '-';
				$format = 'F ' . $format;

				if (isset($obj['day']))
				{
					$str .= $obj['day'];
					$format = 'date';
				}
				else
				{
					$str .= '01';
				}
			}
			else
			{
				$str .= '01-01';
			}
			$timestamp = strtotime($str);
			$options['show'] = $format;
		}
		else if (isset($token['year']))
		{
			$m = isset($token['month']) ? $token['month'] : 1;
			$d = isset($token['day']) ? $token['day'] : 1;
			$timestamp = strtotime($token['year'] . '-' . $m . '-' . $d);
			$options['show'] = 'F Y';
		}

		if ($options['rss'])
		{
			echo( date('D, d M Y H:i:s T', $timestamp) );
		}
		else
		{
			if ($has_class)
			{
				$klass = $options['class'];
			}
			else
			{
				$klass = '';

			}
			if ($options['relative'])
			{
				$klass .= ' k-relative-time';
			}

			if (!empty($klass))
			{
				$klass = ' class="' . $klass . '"';
			}

			switch($options['show'])
			{
				case 'both':
					$f = self::$site['date_format'] . ' ' . self::$site['time_format'];
					break;

				case 'date':
					$f = self::$site['date_format'];
					break;

				case 'time':
					$f = self::$site['time_format'];
					break;

				default:
					$f = $options['show'];
					break;
			}

			$dt = date('c', $timestamp);
			$text = date($f, $timestamp);

			$attr_clean = array();

			foreach($attr as $key => $val)
			{
				$attr_clean[] = "$key=\"$val\"";
			}
			$attr = join(' ', $attr_clean);
			echo <<<OUT
<time{$klass} datetime="$dt" $attr>
	$text
</time>
OUT;
		}
	}

	private static function get_event_date($obj)
	{
		if (isset($obj['published_on']))
		{
			return $obj['published_on']['timestamp'];
		}
		else if (isset($obj['created_on']))
		{
			return $obj['created_on']['timestamp'];
		}
		else if (isset($obj['uploaded_on']))
		{
			return $obj['uploaded_on']['timestamp'];
		}
	}

	static function event_sort($one, $two)
	{
		$one_d = self::get_event_date($one);
		$two_d = self::get_event_date($two);

		return $one_d < $two_d ? 1 : -1;
	}

	static function start()
	{
		self::$start_time = microtime(true);
	}

	static function cleanup()
	{

		$overall = round((microtime(true) - self::$start_time)*1000);

		if (self::$curl_handle)
		{
			curl_close(self::$curl_handle);
			self::$curl_handle = false;
		}

		if (error_reporting() !== 0)
		{
			arsort(self::$timers);

			$t = array();
			$total = 0;

			$total = array_sum(self::$timers);

			foreach(self::$timers as $url => $time)
			{
				$p = round(($time / $total) * 100);
				$time = round($time*1000);
				$t[] = "{$time}ms ($p%)\n\t----------------------------------\n\t$url";
			}

			$total = round($total*1000);
			return "<!--\n\n\tKOKEN DEBUGGING (Longest requests first)\n\n\t" . join("\n\n\t", $t) . "\n\n\tTotal API calls: " . count(self::$timers) . "\n\tTotal API time: {$total}ms\n\tTotal time: {$overall}ms\n-->";
		}
	}

	static private function fallback_load($base, $params)
	{
		foreach($params as $key => $val)
		{
			if ($key === 'limit_to' || $key === 'order_by' || strpos($key, 'filter:') === 0)
			{
				$base .= '/' . str_replace('filter:', '', $key) . ':' . $val;
			}
		}
		return self::api($base);
	}

	static function albums($params)
	{
		return self::fallback_load('/albums', $params);
	}

	static function content($params)
	{
		return self::fallback_load('/content', $params);
	}

	static function essays($params)
	{
		return self::fallback_load('/text/page_type:essay/published:1', $params);
	}

	static function tags($params)
	{
		return self::fallback_load('/tags', $params);
	}

	static function categories($params)
	{
		return self::fallback_load('/categories', $params);
	}

	static function dates($params)
	{
		return self::fallback_load('/events', $params);
	}
}