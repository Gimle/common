<?php
/**
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package common
 *
 * This file loads some functions that is not included in older / any php versions.
 */

if (!function_exists('mb_ucfirst')) {
	/**
	 * Make a string's first character uppercase.
	 *
	 * @param string $string The input string.
	 * @return string The resulting string.
	 */
	function mb_ucfirst ($string) {
		$return = '';
		$fc = mb_strtoupper(mb_substr($string, 0, 1));
		$return .= $fc . mb_substr($string, 1, mb_strlen($string));
		return $return;
	}
}

if (!function_exists('mb_str_pad')) {
	/**
	 * Pad a string to a certain length with another string.
	 *
	 * If the value of $pad_length is negative, less than, or equal to the length of the input string, no padding takes place.
	 * The $pad_string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
	 *
	 * @param string $input The input string.
	 * @param int $pad_length Pad length.
	 * @param string $pad_string Pad string.
	 * @param constant $pad_type Can be STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH.
	 * @param string $encoding The character encoding to use.
	 * @return string The padded string.
	 */
	function mb_str_pad ($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = null) {
		$diff = strlen($input) - mb_strlen($input, $encoding);
		return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
	}
}

if (!function_exists('is_binary')) {
	/**
	 * Checks if the input is binary.
	 *
	 * @param string $value The input string.
	 * @return bool True if binary, otherwise false.
	 */
	function is_binary ($value) {
		$filename = tempnam(TEMP_DIR, 'tmp_');
		file_put_contents($filename, $value);
		exec('file -i ' . $filename, $match);
		unlink($filename);
		$len = strlen($filename . ': ');
		$desc = substr($match[0], $len);
		if (substr($desc, 0, 4) == 'text') {
			return false;
		}
		return true;
	}
}
