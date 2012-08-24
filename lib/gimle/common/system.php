<?php
/**
 * System Utilities.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package system
 */

namespace gimle\common;

/**
 * System class.
 */
class System {
	/**
	 * Array containing the search paths for autoloading.
	 *
	 * @var array
	 */
	public static $autoloadPrependPaths = array(SITE_DIR);

	/**
	 * Array containing values from the config files.
	 *
	 * This variable should not be modified. Use $settings instead.
	 *
	 * @var array
	 */
	public static $config = array();

	/**
	 * Array to store glabal settings.
	 *
	 * @var array
	 */
	public static $settings = array();

	/**
	 * Autoload.
	 *
	 * @param string $name
	 * @return void
	 */
	public static function autoload ($name) {
		foreach (static::$autoloadPrependPaths as $autoloadPrependPath) {
			$file = $autoloadPrependPath . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, strtolower($name)) . '.php';
			if (file_exists($file)) {
				include $file;
				if (method_exists($name, 'initialize')) {
					call_user_func(array($name, 'initialize'));
				}
				break;
			}
		}
	}
}
