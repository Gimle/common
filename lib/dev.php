<?php
/**
 * This file loads some functions into global namespace for development.
 *
 * @copyright Copyright (c) 2013, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package dev_functions
 */

if (!function_exists('d')) {
	/**
	 * Dumps a varialble from the global scope.
	 *
	 * @param mixed $var The variable to dump.
	 * @param bool $return Return output? (Default: false)
	 * @param mixed $title string|false Alternate title for the dump.
	 * @return string
	 */
	function d ($var, $return = false, $title = false)
	{
		if ($title === false) {
			$title = array(
				'steps' => 1,
				'match' => '/d\((.*)/'
			);
		}
		return \gimle\common\var_dump($var, $return, $title);
	}
}
