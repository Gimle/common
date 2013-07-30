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
	 * Wrapper function to add deprecated messages.
	 */
	public static function start ($template) {
		$bt = debug_backtrace();
		trigger_error('Use _start method instead. Caller: <b>' . $bt[0]['file'] . '</b> on line <b>' . $bt[0]['line'] . '</b>', E_USER_DEPRECATED);
		return self::_start($template);
	}

	/**
	 * Wrapper function to add deprecated messages.
	 */
	public static function create ($return = false) {
		$bt = debug_backtrace();
		trigger_error('Use _create method instead. Caller: <b>' . $bt[0]['file'] . '</b> on line <b>' . $bt[0]['line'] . '</b>', E_USER_DEPRECATED);
		return self::_create($return);
	}

	/**
	 * Wrapper function to add deprecated messages.
	 */
	public static function createCaches ($cacheJS = true, $cacheCSS = true, $filename = false) {
		$bt = debug_backtrace();
		trigger_error('Use _createCaches method instead. Caller: <b>' . $bt[0]['file'] . '</b> on line <b>' . $bt[0]['line'] . '</b>', E_USER_DEPRECATED);
		return self::_createCaches($cacheJS, $cacheCSS, $filename);
	}

	/**
	 * Load a canvas.
	 *
	 * @param string $filename
	 * @return void
	 */
	public static function _load ($filename) {
		ob_start();
		require $filename;
		$canvas = ob_get_contents();
		ob_end_clean();
		Canvas::_start(parse_php($canvas));
	}

	/**
	 * Setup a canvas temple, and enable late variable bindings.
	 *
	 * @param string $template
	 * @return void
	 */
	public static function _start ($template) {
		self::$template = $template;
		ob_start();
		return;
	}

	/**
	 * Check if a custom set variable is set, and has a value.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public static function _blankCheck ($name) {
		$check = self::$name();
		if (($check !== false) && (!empty($check)) && (implode('', $check) !== '')) {
			return false;
		}
		return true;
	}

	/**
	 * Create the canvas from template.
	 *
	 * @return void
	 */
	public static function _create ($return = false) {
		$content = ob_get_contents();
		ob_end_clean();

		$template = self::$template;
		$replaces = array('%content%');
		$withs = array($content);

		if (!empty(self::$magic)) {
			foreach (self::$magic as $replace => $with) {
				if (is_array($with)) {
					$withTmp = array();
					foreach ($with as $value) {
						if (!is_array($value)) {
							$withTmp[] = $value;
						}
					}
					$with = implode("\n", $withTmp);
					unset($withTmp);
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

		if ($return === false) {
			echo $template;
			return;
		}
		return $template;
	}

	/**
	 * Create CSS and JavaScript caches.
	 *
	 * @param boolean $cacheJS
	 * @param boolean $cacheCSS
	 * @param string $filename
	 * @return void
	 */
	public static function _createCaches ($cacheJS = true, $cacheCSS = true, $filename = false) {
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
	 * This method will overwrite previous value by default.
	 * To append instead of overwrite, set second parameter to true.
	 * To unset a value, set the value to null.
	 *
	 * <p>Example setting a value.</p>
	 * <code>Canvas::title('My page');</code>
	 *
	 * <p>Example appending a value.</p>
	 * <code>Canvas::title('My page', true);</code>
	 *
	 * <p>Example setting a value at a position (You can also use named positions).</p>
	 * <code>Canvas::title('My page', $pos);</code>
	 *
	 * <p>Example removing a variable.</p>
	 * <code>Canvas::title(null);</code>
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public static function __callStatic ($method, $params) {
		if (substr($method, 0, 1) === '_') {
			trigger_error('Methods starting with underscore is reserved for functionality, and should not be used for variables.', E_USER_ERROR);
		}

		if (empty($params)) {
			if (isset(self::$magic[$method])) {
				return self::$magic[$method];
			}
			return false;
		}
		if (!isset($params[1])) {
			if (($params[0] !== null) && (!is_bool($params[0]))) {
				self::$magic[$method] = array($params[0]);
			}
			elseif ($params[0] === null) {
				unset(self::$magic[$method]);
			}
		}
		else {
			if (($params[1] !== null) && (!is_bool($params[1]))) {
				self::$magic[$method][$params[1]] = $params[0];
			}
			elseif ($params[1] === true) {
				self::$magic[$method][] = $params[0];
			}
		}

		return;
	}
}
