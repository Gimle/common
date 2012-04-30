<?php
/**
 * Canvas Utilities.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package canvas
 */

namespace gimle\common;

/**
 * Canvas class.
 */
class Canvas {
	/**
	 * The title of the page.
	 *
	 * @var string
	 */
	private static $title = '';

	/**
	 * The template to use.
	 *
	 * @var string
	 */
	private static $template = '';

	/**
	 * Magically generated variables for the canvas.
	 *
	 * @var array
	 */
	private static $magic = array();

	/**
	 * Setup a canvas temple, and enable late variable bindings.
	 *
	 * @param string $template
	 * @return void
	 */
	public static function start ($template) {
		self::$template = $template;
		ob_start();
		return;
	}

	/**
	 * Set or get title.
	 *
	 * @param mixed $title false|string false to get, or string to set.
	 * @param boolean $append
	 * @return mixed string|void
	 */
	public static function title ($title = false, $append = false) {
		if ($title === false) {
			return self::$title;
		}
		elseif ($append === true) {
			self::$title .= $title;
		}
		else {
			self::$title = $title;
		}
		return;
	}

	/**
	 * Create the canvas from template.
	 *
	 * @return void
	 */
	public static function create () {
		$content = ob_get_contents();
		ob_end_clean();

		$template = self::$template;
		$replaces = array('%title%', '%content%');
		$withs = array(self::$title, $content);
		if (!empty(self::$magic)) {
			foreach (self::$magic as $replace => $with) {
				if (is_array($with)) {
					$with = implode("\n", $with);
				}
				$replaces[] = '%' . $replace . '%';
				$withs[] = $with;
			}
		}
		preg_match_all('/%[a-z]+%/', $template, $matches);
		if (!empty($matches)) {
			foreach ($matches[0] as $match) {
				if (!in_array($match, $replaces)) {
					$template = str_replace($match, '', $template);
				}
			}
		}
		$template = str_replace($replaces, $withs, $template);

		echo $template;
	}

	/**
	 * Create CSS and JavaScript caches.
	 *
	 * @param boolean $cacheJS
	 * @param boolean $cacheCSS
	 * @param string $filename
	 * @return void
	 */
	public static function createCaches ($cacheJS = true, $cacheCSS = true, $filename = false) {
		// @todo Look at $filename, and php files.

		if ($cacheCSS) {
			$filenames = array();

			if (file_exists(SITE_DIR . 'css/')) {
				foreach (new \DirectoryIterator(SITE_DIR . 'css/') as $file) {
					if (($file->isDir()) && (substr($file->getFilename(), 0, 1) != '.')) {
						foreach (new \DirectoryIterator(SITE_DIR . 'css/' . $file->getFilename() . '/') as $subfile) {
							if ((!$subfile->isDir()) && (substr($subfile->getFilename(), 0, 1) != '.')) {
								$filenames['css'][$file->getFilename()][$subfile->getFilename()] = SITE_DIR . 'css/' . $file->getFilename() . '/' . $subfile->getFilename();
							}
						}
					}
				}
			}
			if ((isset($filenames['css'])) && (!empty($filenames['css']))) {
				foreach ($filenames['css'] as $key => $value) {
					if (!empty($filenames['css'][$key])) {
						sort($filenames['css'][$key]);
					}
				}
				foreach ($filenames['css'] as $key => $value) {
					$current = '';
					if (!empty($value)) {
						foreach ($value as $file) {
							if (substr($file, -4, 4) === '.php') {
								ob_start();
								include $file;
								$current .= ob_get_contents();
								ob_end_clean();
							}
							else {
								$current .= file_get_contents($file);
							}
						}
					}
					if ((!$filename) || ($filename == $key . '.css')) {
						$cache = new Cache('gimle' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . $key . '.css');
						$cache->put(parse_php($current));
						unset($cache);
					}
				}
			}
		}

		if ($cacheJS) {
			$filenames = array();

			if (file_exists(SITE_DIR . 'js/')) {
				foreach (new \DirectoryIterator(SITE_DIR . 'js/') as $file) {
					if (($file->isDir()) && (substr($file->getFilename(), 0, 1) != '.')) {
						foreach (new \DirectoryIterator(SITE_DIR . 'js/' . $file->getFilename() . '/') as $subfile) {
							if ((!$subfile->isDir()) && (substr($subfile->getFilename(), 0, 1) != '.')) {
								$filenames['javascript'][$file->getFilename()][$subfile->getFilename()] = SITE_DIR . 'js/' . $file->getFilename() . '/' . $subfile->getFilename();
							}
						}
					}
				}
			}
			if ((isset($filenames['javascript'])) && (!empty($filenames['javascript']))) {
				foreach ($filenames['javascript'] as $key => $value) {
					if (!empty($filenames['javascript'][$key])) {
						sort($filenames['javascript'][$key]);
					}
				}
				foreach ($filenames['javascript'] as $key => $value) {
					$current = '';
					if (!empty($value)) {
						foreach ($value as $file) {
							if (substr($file, -4, 4) === '.php') {
								ob_start();
								include $file;
								$current .= ob_get_contents();
								ob_end_clean();
							}
							else {
								$current .= file_get_contents($file);
							}
						}
					}
					if ((!$filename) || ($filename == $key . '.js')) {
						$cache = new Cache('gimle' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . $key . '.js');
						$cache->put(parse_php($current));
						unset($cache);
					}
				}
			}
		}
	}

	/**
	 * Set or get custom variables.
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public static function __callstatic ($method, $params) {
		if (empty($params)) {
			if (isset(self::$magic[$method])) {
				return self::$magic[$method];
			}
			return false;
		}
		if (!isset($params[1])) {
			self::$magic[$method][] = $params[0];
		}
		else {
			self::$magic[$method][$params[1]] = $params[0];
		}
		return;
	}
}
