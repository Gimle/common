<?php
/**
 * Cache Utilities.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package cache
 */

namespace gimle\common;

/**
 * Cache class.
 */
class Cache {
	/**
	 * The instance filename.
	 *
	 * @var mixed $filename bool|string
	 */
	private $filename = false;

	/**
	 * The prepended base path for caching.
	 *
	 * @var mixed $prepend bool|string
	 */
	private $prepend = false;

	/**
	 * The name of the config block to retrieve config from.
	 *
	 * Can be set only in the constructor as the second parameter.
	 *
	 * @var string $configBlock Default value: cache
	 */
	private $configBlock = 'cache';

	/**
	 * Create a new caching instance with the specified filename.
	 *
	 * @param string $filename
	 * @param string $location Optional override for the cache config block key.
	 * @return object
	 */
	public function __construct ($filename, $config = false) {
		if ($config !== false) {
			$this->configBlock = $config;
		}
		$this->prepend();
		$this->filename = $this->prepend . $filename;
	}

	/**
	 * Delete all files in the cache directory.
	 *
	 * @param boolean $deleteRoot Also delete the cache directory itself. (Default: false)
	 * @return void
	 */
	public static function clear ($deleteRoot = false) {
		clear_dir($this->prepend, $deleteRoot);
	}

	/**
	 * Get the age of the cache in seconds, or false on failure.
	 *
	 * @return mixed int|bool
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

		if (isset(System::$config[$this->configBlock]['umask'])) {
			$oldumask = umask(System::$config[$this->configBlock]['umask']);
		}

		if (isset(System::$config[$this->configBlock]['chmod']['dir'])) {
			$chmod = System::$config[$this->configBlock]['chmod']['dir'];
		} else {
			$chmod = 0777;
		}
		$dirname = dirname($this->filename) . DIRECTORY_SEPARATOR;
		if (!file_exists($dirname)) {
			mkdir($dirname, $chmod, true);
		}
		if ((!is_writable($dirname)) || (!is_readable($dirname))) {
			$return = false;
			trigger_error('Access to cache dir "' . $dirname . '" fail.', E_USER_WARNING);
		} else {
			file_put_contents($this->filename, $content);
			if (isset(System::$config[$this->configBlock]['chmod']['file'])) {
				chmod($this->filename, System::$config[$this->configBlock]['chmod']['file']);
			}
		}

		if (isset(System::$config[$this->configBlock]['umask'])) {
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
	private function prepend () {
		if (isset(System::$config[$this->configBlock]['base_dir'])) {
			$basePath = System::$config[$this->configBlock]['base_dir'];
		}

		if (!isset(System::$config[$this->configBlock]['dir'])) {
			if (!isset($basePath)) {
				$basePath = TEMP_DIR;
			}
			$this->prepend = $basePath . substr(SITE_DIR, strrpos(rtrim(SITE_DIR, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR) + 1);
		} else {
			if (!isset($basePath)) {
				$basePath = '';
			}
			$this->prepend = $basePath . System::$config[$this->configBlock]['dir'];
		}

		if (isset(System::$config[$this->configBlock]['umask'])) {
			$oldumask = umask(System::$config[$this->configBlock]['umask']);
		}

		if (isset(System::$config[$this->configBlock]['chmod']['dir'])) {
			$chmod = System::$config[$this->configBlock]['chmod']['dir'];
		} else {
			$chmod = 0777;
		}
		if (!file_exists($this->prepend)) {
			mkdir($this->prepend, $chmod, true);
		}
		if ((!is_writable($this->prepend)) || (!is_readable($this->prepend))) {
			trigger_error('Access to cache dir "' . $this->prepend . '" fail.', E_USER_WARNING);
		}
	}
}
