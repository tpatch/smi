<?php

class Sites extends Koken_Controller {

	function __construct()
    {
        parent::__construct();
    }

    function _clean_value($type, $value)
    {
    	if ($type === 'boolean' && is_string($value) && ($value === 'true' || $value === 'false'))
    	{
    		$value = $value === 'true';
    	}
    	else if ($type === 'color' && preg_match('/#[a-z0-9]{3}$/i', $value))
    	{
    		$value = $value . substr($value, 1);
    	}
    	return $value;
    }

	function _prep_options($options, $data = array(), $style_vars = array(), $default_style_vars = array(), $scope = false)
	{

		$_options = $flat = array();

		if (isset($options))
		{
			foreach($options as $group => $opts)
			{
				$tmp = array( 'group' => $group, 'settings' => array() );

				if (isset($opts['icon']))
				{
					$tmp['icon'] = $opts['icon'];
					$loop = $opts['settings'];
					if (isset($opts['dependencies']))
					{
						$tmp['dependencies'] = $opts['dependencies'];
					}
					if (isset($opts['scope']))
					{
						$scope = $opts['scope'];
					}
					else
					{
						$scope = false;
					}
				}
				else
				{
					$loop = $opts;
				}

				foreach($loop as $key => $arr)
				{
					if (isset($arr['label']))
					{
						$_t = array();
						$_t['key'] = $key;

						foreach($arr as $name => $val)
						{
							if ($name === 'settings' && is_string($val[0]))
							{
								$_o = array();
								foreach($val as $v)
								{
									$_o[] = array('label' => $v, 'value' => $v);
								}
								$val = $_o;
							}
							if ($name === 'type') {
								$val = strtolower($val);
							}
							$_t[$name] = $val;
						}

						if (isset($_t['value']))
						{
							$_t['default'] = $_t['value'];
						}
						else if (isset($default_style_vars[$key]))
						{
							$_t['default'] = $default_style_vars[$key];
						}

						if (isset($data[$key]))
						{
							$_t['value'] = $data[$key];
						}
						else if (isset($style_vars[$key]))
						{
							$_t['value'] = $style_vars[$key];
						}

						if (!isset($_t['scope']) && $scope)
						{
							$_t['scope'] = $scope;
						}

						$_t['value'] = $this->_clean_value($_t['type'], $_t['value']);

						if (isset($_t['default']))
						{
							$_t['default'] = $this->_clean_value($_t['type'], $_t['default']);
						}

						$tmp['settings'][] = $_t;
						unset($_t['key']);
						$flat[$key] = $_t;
					}
					else
					{
						list($sub, $_flat) = $this->_prep_options(array($key => $arr), $data, $style_vars, $default_style_vars, $scope);
						$tmp['settings'][] = array_pop( $sub );
						$flat = array_merge($flat, $_flat);
					}
				}

				$_options[] = $tmp;

			}

		}

		return array( $_options, $flat );

	}

	function index()
	{
		list($params, $id) = $this->parse_params(func_get_args());
		$site = new Setting;
		$site->like('name', 'site_%')->or_like('name', 'image_%')->get_iterated();

		$draft = new Draft;
		$data = array();
		$ds = DIRECTORY_SEPARATOR;
		$template_path = FCPATH . 'storage' . $ds . 'themes' . $ds;
		$defaults = json_decode( file_get_contents( FCPATH . 'app' . $ds . 'site' . $ds . 'defaults.json' ), true );
		$default_template_path = FCPATH . 'app' . $ds . 'site' . $ds . 'themes' . $ds;
		$pulse_base = FCPATH . 'app' . $ds . 'site' . $ds . 'themes' . $ds . 'common' . $ds . 'js' . $ds . 'pulse.json';

		$user = new User;
		$user->get();

		if (isset($params['preview']))
		{
			$theme_root = $template_path . $params['preview'] . $ds;
			$template_info = json_decode( file_get_contents( $theme_root . 'info.json'), true );
			if (!$template_info)
			{
				$this->set_response_data( array( 'error' => 'Unable to parse the info.json file for this theme.') );
				return;
			}

			$p = new Draft;
			$p->path = $params['preview'];
			$p->init_draft_nav();
			$draft->data = json_decode( $p->data, true );
		}
		else
		{
			if (isset($params['draft']))
			{
				$draft->where('draft', 1);
			}
			else
			{
				$draft->where('current', 1);
			}

			$draft->get();

			if ($draft->exists())
			{
				$theme_root = $template_path . $draft->path . $ds;
				$template_info = json_decode( file_get_contents( $theme_root . 'info.json'), true );

				if (!$template_info)
				{
					$this->set_response_data( array( 'error' => 'Unable to parse the info.json file for this theme.') );
					return;
				}

				$is_live = $draft->current && $draft->data === $draft->live_data;

				$template_info['published'] = $is_live;

				$draft->data = json_decode( isset($params['draft']) ? $draft->data : $draft->live_data, true);
			}
			else
			{
				$this->error('404', 'Draft not found.');
			}
		}

		foreach($defaults['templates'] as $path => $info)
		{
			if ( !file_exists($theme_root . $path . '.lens') && !file_exists($default_template_path . $path . '.lens') )
			{
				unset($defaults['templates'][$path]);
			}
		}

		foreach($defaults['routes'] as $url => $info)
		{
			if (!isset($defaults['templates'][$info['template']]))
			{
				unset($defaults['routes'][$url]);
			}
		}

		if (isset($template_info['routes']))
		{
			$template_info['routes'] = array_merge_custom( $defaults['routes'], $template_info['routes'] );
		}
		else
		{
			$template_info['routes'] = $defaults['routes'];
		}

		if (isset($template_info['templates']))
		{
			$template_info['templates'] = array_merge_custom( $defaults['templates'], $template_info['templates'] );
		}
		else
		{
			$template_info['templates'] = $defaults['templates'];
		}

		$files = scandir($theme_root);

		foreach($files as $file)
		{
			$info = pathinfo($file);
			if (isset($info['extension']) && $info['extension'] === 'lens' && $info['filename'] !== 'error' && !isset($template_info['templates'][$info['filename']]))
			{
				$template_info['templates'][$info['filename']] = array(
					'name' => ucfirst( preg_replace('/[^a-z0-9]/', ' ', strtolower($info['filename'])) )
				);
			}
		}
		if (isset($template_info['styles']))
		{
			if (isset($draft->data['settings']['__style']) && isset($template_info['styles'][$draft->data['settings']['__style']]))
			{
				$key = $draft->data['settings']['__style'];
			}
			else
			{
				$key = $draft->data['settings']['__style'] = array_shift( array_keys($template_info['styles']) );
			}

			$template_info['style'] = array_merge( array('key' => $key), $template_info['styles'][$key]);

			$styles = array();

			foreach($template_info['styles'] as $key => $opts)
			{
				$styles[] = array_merge( array('key' => $key), $opts );
			}

			$template_info['styles'] = $styles;
		}
		else
		{
			$template_info['styles'] = array();
		}

		if ($this->method == 'get')
		{
			list($data['urls'], $data['url_data'], $routes) = $draft->setup_urls($theme_root);

			if (isset($params['draft']))
			{
				$options_file = FCPATH . $ds . 'storage' . $ds . 'themes' . $ds . $draft->path . $ds . 'css' . $ds . 'settings.css.lens';

				if (file_exists($options_file))
				{

					$functions = array();

					// Strip comments so they don't confuse the parser
					$contents = preg_replace('/\/\*.*?\*\//si', '', file_get_contents($options_file));

					preg_match_all('/@import\surl\(.*\[?\$([a-z_0-9]+)\]?.*\);/', $contents, $imports);

					foreach($imports[1] as $setting)
					{
						if (!isset($functions[$setting]))
						{
							$functions[$setting] = 'reload';
						}
					}

					$contents = preg_replace('/@import\surl\(.*\);/', '', $contents);

					preg_match_all('/([^\{]+)\s*\{([^\}]+)\}/s', $contents, $matches);

					foreach($matches[2] as $index => $block)
					{
						$selector = $matches[1][$index];
						preg_match_all('/([a-z\-]+):([^;]+)( !important)?;/', $block, $rules);

						foreach($rules[2] as $j => $rule)
						{
							$property = $rules[1][$j];

							preg_match_all('/\[?\$([a-z_0-9]+)\]?/', $rule, $options);

							if (count($options))
							{
								foreach($options[1] as $option)
								{
									if (!isset($functions[$option]))
									{
										$functions[$option] = array();
									}
									else if ($functions[$option] === 'reload')
									{
										continue;
									}
									$functions[$option][] = array(
										'selector' => trim(str_replace("\n", '', $selector)),
										'property' => trim($property),
										'template' => trim(str_replace('url(', "url(storage/themes/{$draft->path}/", $rule)),
									);
								}
							}
						}
					}

					$template_info['live_updates'] = $functions;
				}
			}

			$pulse_settings = json_decode(file_get_contents($pulse_base), true);

			list( $template_info['pulse'], $template_info['pulse_flat'] ) = $this->_prep_options( $pulse_settings );

			if (isset($draft->data['pulse_groups']))
			{
				$template_info['pulse_groups'] = $draft->data['pulse_groups'];
			}
			else
			{
				$template_info['pulse_groups'] = array();
			}

			if (!isset($template_info['templates']))
			{
				$template_info['templates'] = array();
			}

			if (!isset($template_info['routes']))
			{
				$template_info['routes'] = array();
			}

			if (isset($draft->data['routes']))
			{
				$template_info['routes'] = array_merge_custom($template_info['routes'], $draft->data['routes']);
			}

			$template_info['navigation'] = $draft->data['navigation'];

			unset($template_info['navigation_groups']);

			$albums_flat = new Album;
			$albums_flat
				->select('id,level,left_id')
				->where('deleted', 0)
				->order_by('left_id ASC')
				->get_iterated();

			$albums_indexed = array();
			$ceiling = 1;
			foreach($albums_flat as $a)
			{
				$albums_indexed[$a->id] = array('level' => (int) $a->level);
				$ceiling = max($a->level, $ceiling);
			}

			$album_keys = array_keys($albums_indexed);

			function nest($nav, $routes, $albums_indexed, $album_keys, $ceiling)
			{
				$l = 1;

				$nested = array();

				while($l <= $ceiling)
				{
					foreach($nav as $index => $item)
					{
						if (preg_match('/^(mailto|https?)/', $item['path']) || (!isset($item['auto']) && !isset($routes[$item['path']])))
						{
							if ($l === 1)
							{
								$nested[] = $item;
							}
							continue;
						}
						if (isset($routes[$item['path']]))
						{
							$r = $routes[$item['path']];
						}
						else
						{
							$r = false;
						}
						if ((isset($item['auto']) && in_array($item['auto'], array('set', 'album'))) || ($r && isset($r['source']) && in_array($r['source'], array('set', 'album'))))
						{
							if (isset($item['auto']))
							{
								$id = $item['id'];

								if ($item['auto'] === 'set')
								{
									$item['set'] = true;
								}
							}
							else
							{
								foreach($r['filters'] as $f)
								{
									if (strpos($f, 'id=') === 0)
									{
										$id = array_pop( explode('=', $f) );
										break;
									}
								}

								if ($r['source'] === 'set')
								{
									$item['set'] = true;
								}
							}

							if (isset($albums_indexed[$id]))
							{
								$level = $albums_indexed[$id]['level'];

								if ($level === $l && $l === 1)
								{
									$nested[] = $item;
									$albums_indexed[$id]['nav'] =& $nested[ count($nested) - 1];
									unset($nav[$index]);
								}
								else if ($level === $l)
								{
									while ($level > 0)
									{
										$level--;
										$done = false;
										$start = array_search($id, $album_keys);
										while ($start > 0)
										{
											$start--;
											$_id = $album_keys[$start];

											if (array_key_exists($_id, $albums_indexed) && $albums_indexed[$_id]['level'] === $level && isset($albums_indexed[$_id]['nav']))
											{
												$albums_indexed[$_id]['nav']['items'][] = $item;
												$albums_indexed[$id]['nav'] =& $albums_indexed[$_id]['nav']['items'][ count($albums_indexed[$_id]['nav']['items']) - 1];
												unset($nav[$index]);
												$done = true;
												break;
											}
										}
										if ($done)
										{
											break;
										}
									}
								}
							}
						}
						else if ($l === 1)
						{
							$nested[] = $item;
							unset($nav[$index]);
						}
					}

					$l++;
				}

				return $nested;
			}

			function build_autos($items, $data, $user)
			{
				foreach($items as $index => &$item)
				{
					if (isset($item['auto']))
					{
						if (isset($data['urls'][$item['auto']]))
						{
							$item['path'] = $data['urls'][$item['auto']];
						}
						else if ($item['auto'] === 'set')
						{
							$item['path'] = '';
						}

						if ($item['auto'] === 'profile')
						{
							switch($item['id'])
							{
								case 'twitter':
									$item['path'] = 'https://twitter.com/' . $user->twitter;
									break;

								default:
									$item['path'] = $user->{$item['id']};
									break;
							}

							if (!isset($item['label']) || empty($item['label']))
							{
								$item['label'] = ucwords($item['id']) . ($item['id'] === 'google' ? '+' : '');
							}
						}
						else if ($item['auto'] === 'rss')
						{
							$item['path'] = '/feed/' . $item['id'] . ( $item['id'] === 'essay' ? 's' : '' ) . '/recent.rss';
							if (!isset($item['label']))
							{
								$item['label'] = $data['url_data'][$item['id']]['plural'] . ' RSS';
							}
						}
						else if (preg_match('/s$/', $item['auto']) || $item['auto'] === 'timeline')
						{
							if ($item['auto'] === 'timeline' && isset($item['year']))
							{
								$item['path'] .= $item['year'] . '/';
								if (isset($item['month']) && $item['month'] !== false && $item['month'] !== 'any')
								{
									$m = str_pad($item['month'], 2, '0', STR_PAD_LEFT);
									$item['path'] .= $m . '/';
								}
							}

							if (strpos($item['auto'], '_') !== false) {
								foreach(array('id', 'slug', 'month', 'year', 'day') as $id)
								{
									if ($id === 'month')
									{
										if (!isset($item['month']) || $item['month'] === 'any' || $item['month'] === false)
										{
											$item['month'] = '';
										}
										else
										{
											$item['month'] = str_pad($item['month'], 2, '0', STR_PAD_LEFT);
										}
									}
									if ($id === 'day' && !isset($item['day']))
									{
										$item['day'] = '';
									}
									if ($id === 'slug' && !isset($item['slug']) && isset($item['id']))
									{
										if (strpos($item['auto'], 'tag_') === 0)
										{
											$item['slug'] = $item['id'];
										}
										else
										{
											$c = new Category;
											if (is_numeric($item['id']))
											{
												$c->select('slug')->get_by_id($item['id']);
												$item['slug'] = $c->slug;
											}
											else
											{
												$item['slug'] = $item['id'];
											}
										}
									}
									if (isset($item[$id]))
									{
										$item['path'] = str_replace(":$id", $item[$id], $item['path']);
									}
								}
							}
							else if (!isset($item['label']))
							{
								$item['label'] = $data['url_data'][$item['auto'] === 'categories' ? 'category' : rtrim($item['auto'], 's')]['plural'];
							}
						}
						else
						{
							if ($item['auto'] === 'home')
							{
								if (!isset($item['label']))
								{
									$item['label'] = $data['url_data']['home'];
								}
								$item['path'] = '/home/';
							}
							else if ($item['auto'] === 'album' || $item['auto'] === 'set')
							{
								$a = new Album;
								$a->select('id,slug,created_on,title');

								if (is_numeric($item['id']))
								{
									$a->where('id', $item['id']);
								}
								else
								{
									$a->where('slug', $item['id'])->or_where('internal_id', $item['id']);
								}

								$a->get();
								$item['path'] = str_replace(':id', $a->id, $item['path']);
								$item['path'] = str_replace(':slug', $a->slug, $item['path']);
								$item['path'] = str_replace(':year', date('Y', $a->created_on), $item['path']);
								$item['path'] = str_replace(':month', date('m', $a->created_on), $item['path']);
								$item['path'] = str_replace(':day', date('d', $a->created_on), $item['path']);
								if (!isset($item['label']))
								{
									$item['label'] = $a->title;
								}
							}
							else if ($item['auto'] === 'page' || $item['auto'] === 'essay')
							{
								$t = new Text;
								$t->select('id,slug,published_on,title');

								if (is_numeric($item['id']))
								{
									$t->where('id', $item['id']);
								}
								else
								{
									$t->where('slug', $item['id']);
								}

								$t->get();

								if (!$t->exists())
								{
									unset($items[$index]);
									continue;
								}

								$item['path'] = str_replace(':id', $t->id, $item['path']);
								$item['path'] = str_replace(':slug', $t->slug, $item['path']);
								$item['path'] = str_replace(':year', date('Y', $t->published_on), $item['path']);
								$item['path'] = str_replace(':month', date('m', $t->published_on), $item['path']);
								$item['path'] = str_replace(':day', date('d', $t->published_on), $item['path']);
								if (!isset($item['label']))
								{
									$item['label'] = $t->title;
								}
							}
							else if ($item['auto'] === 'content')
							{

								if (isset($item['album_id']))
								{
									$item['path'] = preg_replace('/:(id|slug)/', ':album_$1', $data['urls']['album']) . substr(str_replace(':year/:month/', '', $data['urls']['content']), 1);

									$a = new Album;
									$a->select('id,slug,created_on,title');

									if (is_numeric($item['album_id']))
									{
										$a->where('id', $item['album_id']);
									}
									else
									{
										$a->where('slug', $item['album_id'])->or_where('internal_id', $item['album_id']);
									}

									$a->get();

									if (!$a->exists())
									{
										unset($items[$index]);
										continue;
									}

									$item['path'] = str_replace(':album_id', $a->id, $item['path']);
									$item['path'] = str_replace(':album_slug', $a->slug, $item['path']);
									$date = $a->created_on;
								}
								else
								{
									$date = $c->captured_on;
								}

								$c = new Content;
								$c->select('id,slug,captured_on,title');

								if (is_numeric($item['id']))
								{
									$c->where('id', $item['id']);
								}
								else
								{
									$c->where('slug', $item['id'])->or_where('internal_id', $item['id']);
								}

								$c->get();

								if (!$c->exists())
								{
									unset($items[$index]);
									continue;
								}

								$item['path'] = str_replace(':id', $c->id, $item['path']);
								$item['path'] = str_replace(':slug', $c->slug, $item['path']);
								$item['path'] = str_replace(':year', date('Y', $date), $item['path']);
								$item['path'] = str_replace(':month', date('m', $date), $item['path']);
								$item['path'] = str_replace(':day', date('d', $date), $item['path']);
								if (!isset($item['label']))
								{
									$item['label'] = $c->title;
								}

								if (isset($item['lightbox']) && $item['lightbox'])
								{
									$item['path'] .= 'lightbox/';
								}
							}
						}

						if ($item['auto'] !== 'profile')
						{
							$item['path'] = str_replace(array(':year', ':month'), '', $item['path']);
							$item['path'] = preg_replace('/[\(\)\?\:]/', '', $item['path']);
							$item['path'] = preg_replace('~[/]+~', '/', $item['path']);
						}
					}
				}

				return $items;
			}

			$template_info['navigation']['items'] = build_autos($template_info['navigation']['items'], $data, $user);

			$template_info['navigation']['items_nested'] = nest($template_info['navigation']['items'], $template_info['routes'], $albums_indexed, $album_keys, $ceiling);

			foreach($template_info['navigation']['groups'] as &$group)
			{
				$group['items'] = build_autos($group['items'], $data, $user);
				$group['items_nested'] = nest($group['items'], $template_info['routes'], $albums_indexed, $album_keys, $ceiling);
			}
			$pages = array();
			$paths = array();

			foreach ($template_info['routes'] as $path => $arr) {
				$pages[] = array_merge( array('path' => (string) $path), $arr );
				$paths[] = $path;
			}

			$template_info['routes'] = $pages;

			if (isset($template_info['settings']))
			{
				$default_style_vars = array();
				if (isset($template_info['styles']) && count($template_info['styles']))
				{
					$tmp = array_reverse($template_info['styles']);
					foreach($tmp as $style)
					{
						if (isset($style['variables']))
						{
							$default_style_vars = array_merge($default_style_vars, $style['variables']);
						}
					}
				}

				list( $template_info['settings'], $template_info['settings_flat'] ) = $this->_prep_options(
					$template_info['settings'],
					isset($draft->data['settings']) ? $draft->data['settings'] : array(),
					isset($template_info['style']) && isset($template_info['style']['variables']) ? $template_info['style']['variables'] : array(),
					$default_style_vars
				);

				if (isset($draft->data['settings']) && isset($draft->data['settings']['__style']))
				{
					$template_info['settings_flat']['__style'] = array('value' => $draft->data['settings']['__style']);
				}
			}
			else
			{
				$template_info['settings'] = $template_info['settings_flat'] = array();
			}

			if (isset($template_info['style']) && isset($template_info['style']['variables']))
			{
				foreach($template_info['style']['variables'] as $key => &$varval)
				{

					if (preg_match('/#[a-z0-9]{3}$/i', $varval))
					{
						$varval = $varval . substr($varval, 1);
					}

					if (!isset($template_info['settings_flat'][$key]))
					{
						$template_info['settings_flat'][$key] = array( 'value' => $varval );
					}

				}
			}

			$types = array();
			$names = array();

			$templates_indexed = $template_info['templates'];

			foreach($template_info['templates'] as $key => $val)
			{
				if (isset($val['source']) && $val['source'] === 'date')
				{
					$val['source'] = 'archives';
				}

				$types[] = array(
					'path' => $key,
					'info' => $val
				);

				$names[] = $val['name'];
			}

			natcasesort($names);

			$final = array();

			foreach($names as $index => $name)
			{
				$final[] = $types[$index];
			}

			$template_info['templates'] = $final;
			$bools = array('site_hidpi');
			foreach($site as $s)
			{
				$val = $s->value;
				if (in_array($s->name, $bools))
				{
					$val = $val == 'true';
				}
				$data[ preg_replace('/^site_/', '', $s->name) ] = $val;
			}
			$data['draft_id'] = $draft->id;
			$data['theme'] = array(
				'path' => isset($params['preview']) ? $params['preview'] : $draft->path
			);

			unset($data['id']);

			foreach($template_info as $key => $val) {
				if (in_array($key, array('name', 'version', 'description', 'demo')))
				{
					$data['theme'][$key] = $val;
				}
				else
				{
					$data[$key] = $val;
				}
			}

			$data['routes'] = array_merge($data['routes'], $routes);

			// templates always need to be after routes
			$templates_tmp = $data['templates'];
			$routes_tmp = $data['routes'];
			unset($data['templates']);
			unset($data['routes']);
			$data['routes'] = $routes_tmp;
			$data['templates'] = $templates_tmp;

			$data['profile'] = array(
				'name' => $user->public_display === 'both' ? $user->public_first_name . ' ' . $user->public_last_name : $user->{"public_{$user->public_display}_name"},
				'first' => $user->public_first_name,
				'last' => $user->public_last_name,
				'email' => $user->public_email,
				'twitter' => str_replace('@', '', $user->twitter),
				'facebook' => $user->facebook,
				'google_plus' => $user->google
			);

			if (isset($draft->data['custom_css']))
			{
				$data['custom_css'] = $draft->data['custom_css'];
			}
			else
			{
				$data['custom_css'] = '';
			}

			$this->set_response_data($data);

		}
		else
		{
			switch($this->method)
			{
				case 'put':

					$data = json_decode($_POST['data'], true);

					if (isset($data['revert']))
					{
						if ($data['revert'] === 'all')
						{
							$draft->data = $draft->live_data;
						}
						else
						{
							unset($draft->data['settings']);
							$draft->data = json_encode($draft->data);
						}
					}
					else
					{

						if (isset($data['custom_css']))
						{
							$draft->data['custom_css'] = $data['custom_css'];
						}

						if (isset($data['navigation']))
						{
							unset($data['navigation']['active']);
							$draft->data['navigation'] = $data['navigation'];
						}

						if (isset($data['routes']))
						{
							$pages = array();
							foreach($data['routes'] as $p)
							{
								if (isset($p['section'])) continue;

								$key = $p['path'];
								unset($p['path']);
								if (!in_array($p, $template_info['routes']))
								{
									$pages[$key] = $p;
								}

							}
							$draft->data['routes'] = $pages;
						}

						if (isset($data['settings_send']))
						{
							foreach($data['settings_send'] as $key => $val)
							{
								$draft->data['settings'][$key] = $val;
							}
						}

						if (isset($data['url_data_send']))
						{
							$source = $data['url_data_send']['source'] === 'categories' ? 'category' : rtrim($data['url_data_send']['source'], 's');
							$u = new Url;
							$u->order_by('id DESC')->get();
							$new_data = unserialize($u->data);
							foreach($new_data as &$url_data)
							{
								if ($url_data['type'] === $source)
								{
									$url_data['data'][$data['url_data_send']['order']] = $data['url_data_send']['value'];
									break;
								}
							}
							$u->data = serialize($new_data);
							$u->save();
						}

						if (isset($data['pulse_settings_send']) && !empty($data['pulse_settings_send']))
						{
							if (!isset($draft->data['pulse_groups'][$data['pulse_settings_group']]))
							{
								$draft->data['pulse_groups'][$data['pulse_settings_group']] = array();
							}

							foreach($data['pulse_settings_send'] as $key => $val)
							{
								$draft->data['pulse_groups'][$data['pulse_settings_group']][$key] = $val;
							}
						}

					 	$draft->data = json_encode($draft->data);
				 	}

				 	$draft->save();

				 	$this->redirect("/site/draft:true");
					break;
			}
		}
	}

	function publish($draft_id = false)
	{
		if (!$draft_id)
		{
			$this->error('400', 'Draft ID parameter not present.');
		}

		if ($this->method === 'post')
		{
			$draft = new Draft;
			$draft->where('id', $draft_id)->get();

			if ($draft->exists())
			{
				$draft->where('current', 1)->update('current', 0);
				$draft->live_data = $draft->data;
				$draft->current = 1;
				$draft->save();
				exit;
			}
			else
			{
				$this->error('404', "Draft not found.");
			}
		}
		else{
			$this->error('400', 'This endpoint only accepts tokenized POST requests.');
		}
	}

}

/* End of file site.php */
/* Location: ./system/application/controllers/site.php */