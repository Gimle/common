<?php
/**
 * Library of commonly used functions.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package functions
 */

namespace gimle\common;

/**
 * Removes all files and folders within a directory.
 *
 * @param string $path Path to root directory to clear.
 * @param bool $deleteRoot Also delete the root directory (Default: false)
 * @return void
 */
function clear_dir ($path, $deleteRoot = false) {
	$files = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*');
	foreach ($files as $file) {
		if ((is_dir($file)) && (!is_link($file))) {
			clear_dir($file, true);
		}
		else {
			unlink($file);
		}
	}
	if ($deleteRoot === true) {
		rmdir($path);
	}
}

/**
 * Parse php code in a string.
 *
 * @param string $value
 * @return string
 */
function parse_php ($value) {
	ob_start();
	$value = preg_replace('/\<\?[^php|^=]/', 'gimle-hopefully-safe-replace-string', $value);
	eval('?>' . $value);
	$return = ob_get_contents();
	ob_end_clean();
	str_replace('gimle-hopefully-safe-replace-string', '<?', $value);
	return $return;
}

/**
 * Converts a config file formatted filesize string to bytes.
 *
 * @param string $size
 * @return int Number of bytes.
 */
function string_to_bytes ($size) {
	$size = trim($size);
	$last = strtolower(substr($size, -1));
	$size = (int)$size;
	switch ($last) {
		case 'g':
			$size *= 1024;
		case 'm':
			$size *= 1024;
		case 'k':
			$size *= 1024;
	}
	return $size;
}

/**
 * Checks for the maximum size uploads.
 *
 * @return int Maximum number of bytes.
 */
function get_upload_limit () {
	return (int)min(string_to_bytes(ini_get('memory_limit')), string_to_bytes(ini_get('post_max_size')), string_to_bytes(ini_get('upload_max_filesize')));
}

/**
 * Dumps a varialble from the global scope.
 *
 * @todo Implement mode parameter.
 *
 * @param mixed $var The variable to dump.
 * @param bool $return Return output? (Default: false)
 * @param mixed $title string|bool|array Alternate title for the dump, or to backtrace.
 * @param string $mode Not implemented yet.
 * @return mixed void|string
 */
function var_dump ($var, $return = false, $title = false, $mode = 'auto') {
	if (!isset(System::$config['common']['background'])) {
		$background = 'white';
	}
	else {
		$background = System::$config['common']['background'];
	}

	$fixDumpString = function ($name, $value, $htmlspecial = true) use (&$background) {
		if (in_array($name, array('[\'pass\']', '[\'password\']', '[\'PHP_AUTH_PW\']'))) {
			$value = '********';
		}
		else {
			$fix = array(
				"\r\n" => colorize('¤¶', 'gray', $background) . "\n", // Windows linefeed.
				"\n\r" => colorize('¶¤', 'gray', $background) . "\n\n", // Erronumous (might be interpeted as double) linefeed.
				"\n"   => colorize('¶', 'gray', $background) . "\n", // UNIX linefeed.
				"\r"   => colorize('¤', 'gray', $background) . "\n" // Old mac linefeed.
			);
			$value = strtr(($htmlspecial ? htmlspecialchars($value) : $value), $fix);
		}
		return $value;
	};

	$dodump = function ($var, $var_name = null, $indent = 0, $params = array()) use (&$dodump, &$fixDumpString, &$background) {
		if (strstr(print_r($var, true), '*RECURSION*') == true) {
			echo colorize('Recursion detected, performing normal var_dump:', 'recursion', $background) . ' ';
			echo colorize($var_name, 'varname', $background) . ' ' . colorize('=>', 'black', $background) . ' ';
			var_dump($var);
			return;
		}
		$doDump_indent = colorize('|', 'lightgray', $background) . '   ';
		echo str_repeat($doDump_indent, $indent) . colorize(htmlentities($var_name), 'varname', $background);

		if (is_array($var)) {
			echo ' ' . colorize('=>', 'black', $background) . ' ' . colorize('Array (' . count($var) . ')', 'gray', $background) . "\n" . str_repeat($doDump_indent, $indent) . colorize('(', 'lightgray', $background) . "\n";
			foreach ($var as $key => $value) {
				$dodump($value, '[\'' . $key . '\']', $indent + 1);
			}
			echo str_repeat($doDump_indent, $indent) . colorize(')', 'lightgray', $background);
		}
		elseif (is_string($var)) {
			if ((isset($params['error'])) && ($params['error'] === true)) {
				echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('Error: ' . $fixDumpString($var_name, $var, ENV_WEB), 'error', $background);
			}
			else {
				echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('String(' . strlen($var) . ')', 'gray', $background) . ' ' . colorize('\'' . $fixDumpString($var_name, $var, ENV_WEB) . '\'', 'string', $background);
			}
		}
		elseif (is_int($var)) {
			echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('Integer(' . strlen($var) . ')', 'gray', $background) . ' ' . colorize($var, 'int', $background);
		}
		elseif (is_bool($var)) {
			echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('Boolean', 'gray', $background) . ' ' . colorize(($var === true ? 'true' : 'false'), 'bool', $background);
		}
		elseif (is_object($var)) {
			$class = new \ReflectionObject($var);
			$parents = '';
			if ($parent = $class->getParentClass()) {
				$parents .= ' extends ' . $class->getParentClass()->name;
			}
			unset($parent);
			$interfaces = $class->getInterfaces();
			if (!empty($interfaces)) {
				$parents .= ' implements ' . implode(', ', array_keys($interfaces));
			}
			unset($interfaces);

			if ($var instanceof Iterator) {
				echo ' ' . colorize('=>', 'black', $background) . ' ' . colorize($class->getName() . ' Object (Iterator)' . $parents, 'gray', $background) . "\n" . str_repeat($doDump_indent, $indent) . colorize('(', 'lightgray', $background) . "\n";
				var_dump($var);
			}
			else {
				echo ' ' . colorize('=>', 'black', $background) . ' ' . colorize($class->getName() . ' Object' . $parents , 'gray', $background) . "\n" . str_repeat($doDump_indent, $indent) . colorize('(', 'lightgray', $background) . "\n";

				$dblcheck = array();
				foreach ((array)$var as $key => $value) {
					if (!property_exists($var, $key)) {
						$key = ltrim($key, "\x0*");
						if (substr($key, 0, strlen($class->getName())) == $class->getName()) {
							$key = substr($key, (strlen($class->getName()) + 1));
						}
					}
					$dblcheck[$key] = $value;
				}

				$reflect = new \ReflectionClass($var);

				$constants = $reflect->getConstants();
				if (!empty($constants)) {
					foreach ($constants as $key => $value) {
						$dodump($value, $key, $indent + 1);
					}
				}
				unset($constants);

				$props = $reflect->getProperties();
				if (!empty($props)) {
					foreach ($props as $prop) {
						$append = '';
						$error = false;
						if ($prop->isPrivate()) {
							$append .= ' private';
						}
						elseif ($prop->isProtected()) {
							$append .= ' protected';
						}
						$prop->setAccessible(true);
						if ($prop->isStatic()) {
							$value = $prop->getValue();
							$append .= ' static';
						}
						else {
							set_error_handler(function ($errno, $errstr) { throw new \Exception($errstr); });
							try {
								$value = $prop->getValue($var);
							}
							catch (\Exception $e) {
								$value = $e->getMessage();
								$append .= ' error';
								$error = true;
							}
							restore_error_handler();
						}
						if (array_key_exists($prop->name, $dblcheck)) {
							unset($dblcheck[$prop->name]);
						}
						$dodump($value, '[\'' . $prop->name . '\'' . $append . ']', $indent + 1, array('error' => $error));
					}
				}
				unset($props, $reflect);
				if (!empty($dblcheck)) {
					foreach ($dblcheck as $key => $value) {
						$dodump($value, '[\'' . $key . '\' magic]', $indent + 1);
					}
				}
			}
			unset($class);
			echo str_repeat($doDump_indent, $indent) . colorize(')', 'lightgray', $background);
		}
		elseif (is_null($var)) {
			echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('null', 'black', $background);
		}
		elseif (is_float($var)) {
			echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('Float(' . strlen($var) . ')', 'gray', $background) . ' ' . colorize($var, 'float', $background);
		}
		elseif (is_resource($var)) {
			echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('Resource', 'gray', $background) . ' ' . $var;
		}
		else {
			echo ' ' . colorize('=', 'black', $background) . ' ' . colorize('Unknown', 'gray', $background) . ' ' . $var;
		}
		echo "\n";
	};

	$prefix = 'unique';
	$suffix = 'value';

	if ($return == true) {
		ob_start();
	}
	if (!ENV_CLI) {
		echo '<pre class="vardump">';
	}

	if (($title === false) || (is_array($title))) {
		$backtrace = debug_backtrace();
		if ((is_array($title)) && (isset($title['steps'])) && (isset($backtrace[$title['steps']]))) {
			$backtrace = $backtrace[$title['steps']];
		}
		else {
			$backtrace = $backtrace[0];
		}
		if (substr($backtrace['file'], -13) == 'eval()\'d code') {
			$title = 'eval()';
		}
		else {
			$con = explode("\n", file_get_contents($backtrace['file']));
			$callee = $con[$backtrace['line'] - 1];
			if ((is_array($title)) && (isset($title['match']))) {
				preg_match($title['match'], $callee, $matches);
			}
			else {
				preg_match('/([a-zA-Z\\\\]+|)var_dump\((.*)/', $callee, $matches);
			}
			if (!empty($matches)) {
				$i = 0;
				$title = '';
				foreach (str_split($matches[0], 1) as $value) {
					if ($value === '(') {
						$i++;
					}
					if (($i === 0) && ($value === ',')) {
						break;
					}
					if ($value === ')') {
						$i--;
					}
					if (($i === 0) && ($value === ')')) {
						$title .= $value;
						break;
					}
					$title .= $value;
				}
			}
			else {
				$title = 'Unknown dump string';
			}
		}
	}
	$dodump($var, $title);
	if (!ENV_CLI) {
		echo "</pre>\n";
	}
	if ($return == true) {
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
}

/**
 * Find a key in array.
 *
 * Search from the end of an arrey by searching for a negative key.
 *
 * @param array $arr The array to find a key in.
 * @param int $which Which key to look for.
 * @return mixed bool|string key as string, or false if fail.
 */
function array_key (array $arr = array(), $which = 0) {
	$keys = array_keys($arr);
	if ($which < 0) {
		$keys = array_reverse($keys);
		$which = ~$which;
	}
	if (isset($keys[$which])) {
		return $keys[$which];
	}
	return false;
}

/**
 * Find a value in a array.
 *
 * Search from the end of an arrey by searching for a negative key.
 * The return value will be false both if the value of the key is the boolean value false, or if the lookup failed.
 *
 * @param array $arr The array to find a value in.
 * @param int $which Which key to look for.
 * @return mixed Value, or false if fail.
 */
function array_value (array $arr = array (), $which = 0) {
	if ($key = array_key($arr, $which)) {
		return $arr[$key];
	}
	return false;
}

/**
 * Generate a human readable random password string.
 *
 * @param int $length Number of characters.
 * @return string
 */
function generate_password ($length = 8) {
	$var = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$len = strlen($var);
	$return = '';
	for ($i = 0; $i < $length; $i++) {
		$return .= $var[rand(0, $len - 1)];
	}
	return $return;
}

/**
 * Convert seconds to grouped array.
 *
 * @param int $time Number of seconds.
 * @param bool $weeks Telling if weeks should be included. (Default is false)
 * @return array
 */
function seconds_to_array ($time, $weeks = false) {
	$time = str_replace(',', '.', $time);
	$value['years'] = 0;
	if ($weeks === true) {
		$value['weeks'] = 0;
	}
	$value = array_merge($value, array('days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0));
	if ($time >= 31556926) {
		$value['years'] = (int)floor($time / 31556926);
		$time = ($time % 31556926);
	}
	if (($time >= 604800) && ($weeks === true)) {
		$value['weeks'] = (int)floor($time / 604800);
		$time = ($time % 604800);
	}
	if ($time >= 86400) {
		$value['days'] = (int)floor($time / 86400);
		$time = ($time % 86400);
	}
	if ($time >= 3600) {
		$value['hours'] = (int)floor($time / 3600);
		$time = ($time % 3600);
	}
	if ($time >= 60) {
		$value['minutes'] = (int)floor($time / 60);
		$time = ($time % 60);
	}
	$value['seconds'] = (int)floor($time);
	return $value;
}

/**
 * Get the time since the script was started.
 *
 * @return string Human readable time string.
 */
function run_time () {
	$microtime = microtime(true) - TIME_START;
	$ttr = seconds_to_array($microtime);
	$microtime = str_replace(',', '.', $microtime);
	$time = '';
	if ($ttr['years'] != 0) {
		$time .= $ttr['years'] . ' year' . (($ttr['years'] > 1) ? 's' : '') . ' ';
		$decimals = 0;
	}
	if ($ttr['days'] != 0) {
		$time .= $ttr['days'] . ' day' . (($ttr['days'] > 1) ? 's' : '') . ' ';
		$decimals = 0;
	}
	if ($ttr['hours'] != 0) {
		$time .= $ttr['hours'] . ' hour' . (($ttr['hours'] > 1) ? 's' : '') . ' ';
		$decimals = 0;
	}
	if ($ttr['minutes'] != 0) {
		$time .= $ttr['minutes'] . ' minute' . (($ttr['minutes'] > 1) ? 's' : '') . ' ';
		$decimals = 2;
	}
	if (!isset($decimals)) {
		$decimals = 6;
	}
	$time .= $ttr['seconds'];
	$time .= (($decimals > 0) ? ',' . substr($microtime, strpos($microtime, '.') + 1, $decimals) : '') . ' second' . (($ttr['seconds'] != 1) ? 's' : '');
	return $time;
}

/**
 * Convert bytes to readable number.
 *
 * @param int $filesize Number of bytes.
 * @param int $decimals optional Number of decimals to include in string.
 * @return array containing prefix, float value and readable string.
 */
function bytes_to_array ($filesize = 0, $decimals = 2) {
	$return = array();
	$count = 0;
	$units = array('', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
	while ((($filesize / 1024) >= 1) && ($count < (count($units) - 1))) {
		$filesize = $filesize / 1024;
		$count++;
	}
	if (round($filesize, $decimals) === (float)1024) {
		$filesize = $filesize / 1024;
		$count++;
	}
	$return['units']  = $units[$count];
	$return['value']  = (float)$filesize;
	$return['string'] = round($filesize, $decimals) . (($count > 0) ? ' ' . $units[$count] : '');
	return $return;
}

/**
 * Execute an external program.
 *
 * @param string $exec Command
 * @return array
 */
function run ($exec) {
	$filename = tempnam(TEMP_DIR, 'tmp_');
	touch($filename);
	exec($exec . ' 2> ' . $filename, $stout, $return);
	$sterr = explode("\n", trim(file_get_contents($filename)));
	unlink($filename);
	return array('command' => $exec, 'stout' => $stout, 'sterr' => $sterr, 'return' => $return);
}

/**
 * Calculates the greatest common divisor of $a and $b
 *
 * @param int $a Non-zero integer.
 * @param int $b Non-zero integer.
 * @return int
 */
function gcd ($a, $b) {
	$b = (($a == 0) ? 0 : $b);
	return (($a % $b) ? gcd($b, abs($a - $b)) : $b);
}

/**
 * Convert integer to roman number.
 *
 * @param int $num Number.
 * @return string Roman number.
 */
function number_to_roman ($num) {
	$numbers = array(
		'M'  => 1000,
		'CM' => 900,
		'D'  => 500,
		'CD' => 400,
		'C'  => 100,
		'XC' => 90,
		'L'  => 50,
		'XL' => 40,
		'X'  => 10,
		'IX' => 9,
		'V'  => 5,
		'IV' => 4,
		'I' => 1
	);

	$return = '';
	foreach ($numbers as $key => $value) {
		$matches = (int)$num / $value;
		$return .= str_repeat($key, $matches);
		$num = $num % $value;
	}
	return $return;
}

/**
 * Cut a string to desired length.
 *
 * @param string $string Input string.
 * @param int $limit Max length.
 * @param bool $byword Cut by word, will never overflow desired length (Default: true).
 * @param string $ending Cutted string ending (Default: …).
 * @return string Result.
 */
function cut_string ($string, $limit, $byword = true, $ending = '…') {
	if (mb_strlen($string) > $limit + 1) {
		$string = mb_substr($string, 0, $limit - 1);
		$string = rtrim($string);
		if ($byword) {
			$len = mb_strrchr($string, ' ');
			if ($len) {
				$len = mb_strlen($len);
				$string = mb_substr($string, 0, -$len);
			}
		}
		$string .= $ending;
	}
	return $string;
}

/**
 * Colorize a string according to the envoriment settings.
 *
 * @todo Make background default to a color.
 *
 * @param string $content The content to colorize.
 * @param string $color The color to use.
 * @param string $background The background for color overrides to maintain visibility.
 * @param bool $getStyle return the style only (Default false).
 * @return string
 */
function colorize ($content, $color, $background, $getStyle = false) {
	if ((isset(System::$config['common']['colorize'])) && (System::$config['common']['colorize'] === false)) {
		return $content;
	}
	if (ENV_CLI) {
		$template = "\033[%sm%s\033[0m";
	}
	elseif ($getStyle === false) {
		$template = '<span style="color: %s;">%s</span>';
	}
	else {
		$template = 'color: %s;';
	}
	if (substr($color, 0, 6) === 'range:') {
		$config = json_decode(substr($color, 6), true);
		if ($config['type'] === 'alert') {
			$state = ($config['value'] / $config['max']);
			if ($state >= 1) {
				if (ENV_CLI) {
					return sprintf($template, '38;5;9', $content);
				}
				return sprintf($template, '#ff0000', $content);
			}
			elseif (ENV_CLI) {
				if ($state < 0.1) {
					return sprintf($template, '38;5;2', $state);
				}
				elseif ($state < 0.25) {
					return sprintf($template, '38;5;118', $state);
				}
				elseif ($state < 0.4) {
					return sprintf($template, '38;5;148', $state);
				}
				elseif ($state < 0.5) {
					return sprintf($template, '38;5;220', $state);
				}
				elseif ($state < 0.6) {
					return sprintf($template, '38;5;220', $state);
				}
				elseif ($state < 0.8) {
					return sprintf($template, '38;5;178', $state);
				}
				return sprintf($template, '38;5;166', $state);
			}
			elseif ($state === 0.5) {
				if (ENV_CLI) {
					return sprintf($template, '38;5;11', $content);
				}
				return sprintf($template, '#ffff00', $content);
			}
			elseif ($state < 0.5) {
				return sprintf($template, '#' . str_pad(dechex(round($state * 511)), 2, '0', STR_PAD_LEFT) . 'ff00', $content);
			}
			else {
				$state = (0.5 - ($state - 0.5));
				return sprintf($template, '#ff' . str_pad(dechex(round(($state) * 511)), 2, '0', STR_PAD_LEFT) . '00', $content);
			}
		}
	}
	elseif ($color === 'gray') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;240', $content);
		}
		return sprintf($template, 'gray', $content);
	}
	elseif ($color === 'string') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;46', $content);
		}
		return sprintf($template, 'green', $content);
	}
	elseif ($color === 'int') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;196', $content);
		}
		return sprintf($template, 'red', $content);
	}
	elseif ($color === 'lightgray') {
		if ($background === 'black') {
			if (ENV_CLI) {
				return sprintf($template, '38;5;240', $content);
			}
			return sprintf($template, 'darkgray', $content);
		}
		if (ENV_CLI) {
			return sprintf($template, '38;5;251', $content);
		}
		return sprintf($template, 'lightgray', $content);
	}
	elseif ($color === 'bool') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;57', $content);
		}
		return sprintf($template, 'purple', $content);
	}
	elseif ($color === 'float') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;39', $content);
		}
		return sprintf($template, 'dodgerblue', $content);
	}
	elseif ($color === 'error') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;198', $content);
		}
		return sprintf($template, 'deeppink', $content);
	}
	elseif ($color === 'recursion') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;208', $content);
		}
		return sprintf($template, 'darkorange', $content);
	}
	elseif ($background === 'black') {
		if (ENV_CLI) {
			return sprintf($template, '38;5;256', $content);
		}
		return sprintf($template, 'white', $content);
	}
	else {
		return $content;
	}
}

/**
 * Send a get and/or post request and return the result.
 *
 * @param string $url The url.
 * @param mixed $post false|array Optional post fields to send. (Default false).
 * @param mixed $headers false|array Optional headers to send. (Default: false).
 * @param int $timeout How many seconds to wait for responce. (Default 1).
 * @param int $connecttimeout How many seconds to wait for connection. (Default 1).
 * @return mixed false|array
 */
function request_url ($url, $post = false, $headers = false, $timeout = 1, $connecttimeout = 1) {
	$return = array();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if (($headers !== false) && (is_array($headers))) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	if (($post !== false) && (is_array($post))) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
	$result = curl_exec($ch);

	if ($result === false) {
		return false;
	}

	if (substr($result, 0, 25) === "HTTP/1.1 100 Continue\r\n\r\n") {
		$tmp = explode("\r\n\r\n", $result, 3);
		$result = array();
		$result[0] = '';
		$result[1] = $tmp[2];
		unset($tmp[2]);
		$result[0] = implode("\n\n", $tmp);
		unset($tmp);
	}
	else {
		$result = explode("\r\n\r\n", $result, 2);
	}

	$return['reply'] = $result[1];
	$return['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$return['header'] = str_replace("\r", '', $result[0]);
	$return['info'] = curl_getinfo($ch);
	$return['error'] = curl_errno($ch);

	curl_close($ch);

	return $return;
}

/**
 * Load a xml string into a simplexml object with libxml internal errors.
 *
 * @param string $xmlstring
 * @return mixed false|object SimpleXMLElement
 */
function load_xml ($xmlstring) {
	$cond = libxml_use_internal_errors(true);
	$xml = simplexml_load_string((string)$xmlstring);
	if ($cond !== true) {
		libxml_use_internal_errors($cond);
	}
	return $xml;
}

/**
 * Fetch a xml file from a url and cache it for a specified period of time.
 *
 * @param string $url The url.
 * @param int $ttl Time to live.
 * @return mixed false|object SimpleXMLElement
 */
function get_xml ($url, $ttl = 600) {
	$filename = preg_replace("#[^\pL _\-'\.,0-9]#iu", '_', $url);
	$cache = new Cache('gimle' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'get_xml' . DIRECTORY_SEPARATOR . $filename);

	$return = false;
	if (!$cache->exists()) {
		$xml = request_url($url);
		if ($xml !== false) {
			$return = load_xml($xml['reply']);
		}
		if ($return !== false) {
			$cache->put($xml['reply']);
		}
	}
	else {
		if (($ttl !== false) && ($cache->age() > $ttl)) {
			$xml = request_url($url);
			if ($xml !== false) {
				$simplexml = load_xml($xml['reply']);
			}
			if ((isset($simplexml)) && ($simplexml !== false)) {
				$cache->put($xml['reply']);
				$return = $simplexml;
			}
			else {
				$return = simplexml_load_string($cache->get());
			}
		}
		else {
			$return = simplexml_load_string($cache->get());
		}
	}
	return $return;
}

/**
 * Get full translation table.
 *
 * @return array
 */
function get_html_translation_table () {
	$table = array();
	if ((PHP_MAJOR_VERSION >= 5) && (PHP_MINOR_VERSION >= 3) && (PHP_RELEASE_VERSION >= 3)) {
		foreach (\get_html_translation_table(HTML_ENTITIES) as $key => $value) {
			$table[$value] = utf8_encode($key);
		}
	}
	else {
		foreach (\get_html_translation_table(HTML_ENTITIES, null, mb_internal_encoding()) as $key => $value) {
			$table[$value] = $key;
		}
	}
	if ((PHP_MAJOR_VERSION >= 5) && (PHP_MINOR_VERSION >= 4)) {
		foreach (\get_html_translation_table(HTML_ENTITIES, ENT_HTML5 | ENT_QUOTES, mb_internal_encoding()) as $key => $value) {
			$table[$value] = $key;
		}
	}
	else {
		require System::$config['extensions']['common'] . 'inc' . DIRECTORY_SEPARATOR . 'ent.php';
		$table = array_merge($table, $html5);
	}
	$table['&ap;'] = '≈';
	$table['&dash;'] = '‐';
	$table['&lsqb;'] = '[';
	$table['&lsquor;'] = '‚';
	$table['&rdquor;'] = '„';
	$table['&there;'] = '∴';
	$table['&verbar;'] = '|';
	$table['&rsquo;'] = '’';
	$table['&Omega;'] = 'Ω';
	$table['&omega;'] = 'ω';

	return $table;
}

/**
 * Convert code to utf-8.
 *
 * @param int $num
 * @return string
 */
function code2utf8 ($num) {
	if ($num < 128) {
		return chr($num);
	}
	if ($num < 2048) {
		return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
	}
	if ($num < 65536) {
		return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	}
	if ($num < 2097152) {
		return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	}
	return '';
}

/**
 * Convert entities to utf8.
 *
 * @param string $string
 * @param array $exclude
 * @return string
 */
function ent2utf8 ($string, $exclude = array('&', ';')) {
	$html_translation_table = array();
	foreach (get_html_translation_table() as $key => $value) {
		if (!in_array($value, $exclude)) {
			$html_translation_table[$key] = $value;
		}
	}
	$string = strtr($string, $html_translation_table);

	$string = preg_replace('/&#x([0-9a-f]+);/ei', '\\' . __NAMESPACE__ . '\\code2utf8(hexdec("\\1"))', $string);
	$string = preg_replace('/&#([0-9]+);/e', '\\' . __NAMESPACE__ . '\\code2utf8(\\1)', $string);

	return $string;
}

/**
 * Get the users preferred language, or false if not found.
 *
 * @param array $avail A list of the available languages.
 * @return mixed false|string
 */
function get_preferred_language (array $avail) {
	$return = false;
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$client = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
		$hitrate = 0;
		if (!empty($client)) {
			foreach ($client as $langstr) {
				if (preg_match('/(.*);q=([0-1]{0,1}\.\d{0,4})/', $langstr, $matches)) {
					if (($hitrate < (float)$matches[2]) && (in_array($matches[1], $avail))) {
						$return = $matches[1];
						$hitrate = (float)$matches[2];
					}
				}
				elseif (in_array($langstr, $avail)) {
					return $langstr;
				}
			}
		}
	}
	return $return;
}

/**
 * Create a empty temp file with unique name.
 *
 * @param string $dir optional
 * @param string $prefix optional
 * @param string $suffix optional
 * @return string Full path and name of the new temp file.
 */
function make_temp_file ($dir = false, $prefix = false, $suffix = false) {
	$name = generate_password();
	if ($dir === false) {
		$dir = TEMP_DIR;
	}
	if ($prefix !== false) {
		$name = $prefix . $name;
	}
	if ($suffix !== false) {
		$name = $name . $suffix;
	}
	if (!file_exists($dir . $name)) {
		touch($dir . $name);
		return $dir . $name;
	}
	return tempfile($dir, $prefix, $suffix);
}
