<?php namespace gimle\common;
/**
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package cache
 */

/**
 * Cache class.
 */
class Cache {
	/**
	 * The instance filename.
	 *
	 * @var bool|string
	 */
	private $filename = false;

	/**
	 * The prepended base path for caching.
	 *
	 * @var bool|string
	 */
	private static $prepend = false;

	/**
	 * Create a new caching instance with the specified filename.
	 *
	 * @param string $filename
	 * @return object
	 */
	public function __construct ($filename) {
		if (self::$prepend === false) {
			self::prepend();
		}
		$this->filename = self::$prepend . $filename;
	}

	/**
	 * Delete all files in the cache directory.
	 *
	 * @param boolean $deleteRoot Also delete the cache directory itself. (Default: false)
	 * @return void
	 */
	public static function clear ($deleteRoot = false) {
		if (self::$prepend === false) {
			self::prepend();
		}
		clear_dir(self::$prepend, $deleteRoot);
	}

	/**
	 * Get the age of the cache in seconds, or false on failure.
	 *
	 * @return int|bool
	 */
	public function age () {
		if (file_exists($this->filename)) {
			return (time() - filemtime($this->filename));
		}
		return false;
	}

	/**
	 * Check if a cache file exist.
	 *
	 * @return boolean
	 */
	public function exists () {
		if (file_exists($this->filename)) {
			return true;
		}
		return false;
	}

	/**
	 * Get the complete path and filename for the cache file.
	 *
	 * @return string
	 */
	public function getFilename () {
		return $this->filename;
	}

	/**
	 * Store cache string.
	 *
	 * @param string $content The content to cache.
	 * @return boolean true on success, otherwise false.
	 */
	public function put ($content) {
		$return = true;

		if (isset(System::$config['cache']['umask'])) {
			$oldumask = umask(System::$config['cache']['umask']);
		}

		if (isset(System::$config['cache']['chmod']['dir'])) {
			$chmod = System::$config['cache']['chmod']['dir'];
		}
		else {
			$chmod = 0777;
		}
		$dirname = dirname($this->filename) . DIRECTORY_SEPARATOR;
		if (!file_exists($dirname)) {
			mkdir($dirname, $chmod, true);
		}
		if ((!is_writable($dirname)) || (!is_readable($dirname))) {
			$return = false;
			trigger_error('Access to cache dir "' . $dirname . '" fail.', E_USER_WARNING);
		}
		else {
			file_put_contents($this->filename, $content);
			if (isset(System::$config['cache']['chmod']['file'])) {
				chmod($this->filename, System::$config['cache']['chmod']['file']);
			}
		}

		if (isset(System::$config['cache']['umask'])) {
			umask($oldumask);
		}
		return $return;
	}

	/**
	 * Get the cache content.
	 *
	 * @return string
	 */
	public function get () {
		if (file_exists($this->filename)) {
			return file_get_contents($this->filename);
		}
		return false;
	}

	/**
	 * Figure out the prepend path for cache files.
	 *
	 * @return void
	 */
	private static function prepend () {
		if (!isset(System::$config['cache']['dir'])) {
			self::$prepend = TEMP_DIR . substr(SITE_DIR, strrpos(rtrim(SITE_DIR, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR) + 1);
		}
		else {
			self::$prepend = System::$config['cache']['dir'];
		}
		if ((!is_writable(self::$prepend)) || (!is_readable(self::$prepend))) {
			trigger_error('Access to cache dir "' . self::$prepend . '" fail.', E_USER_WARNING);
		}
	}
}
