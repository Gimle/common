<?php
/**
 * Library of commonly used functions.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package common_functions
 */

namespace gimle\common;

/**
 * Removes all files and folders within a directory.
 *
 * @param string $path Path to root directory to clear.
 * @param bool $deleteRoot Also delete the root directory (Default: false)
 * @return void
 */
function clear_dir ($path, $deleteRoot = false)
{
	$files = glob(rtrim(str_replace('\\', '\\\\', $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*');
	foreach ($files as $file) {
		if ((is_dir($file)) && (!is_link($file))) {
			clear_dir($file, true);
		} else {
			unlink($file);
		}
	}
	if ($deleteRoot === true) {
		rmdir($path);
	}
}

/**
 * Find a value in an array by sending in a string with the location path.
 *
 * @param array $input
 * @param string $search
 * @param string $separator (Default: ".")
 * @return mixed
 */
function locate_in_array_by_string ($input, $search, $separator = '.')
{
	$search = explode($separator, $search);
	$result = $input;
	foreach ($search as $key) {
		if (isset($result[$key])) {
			$result = $result[$key];
		} else {
			return false;
		}
	}
	return $result;
}

/**
 * Parse php code in a string.
 *
 * @param string $value
 * @return string
 */
function parse_php ($value)
{
	ob_start();
	$value = preg_replace('/\<\?([^php|^=])/', 'gimle-hopefully-safe-replace-string$1', $value);
	eval('?>' . $value);
	$return = ob_get_contents();
	ob_end_clean();
	$return = str_replace('gimle-hopefully-safe-replace-string', '<?', $return);
	return $return;
}

/**
 * Converts a config file formatted filesize string to bytes.
 *
 * @param string $size
 * @return int Number of bytes.
 */
function string_to_bytes ($size)
{
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
function get_upload_limit ()
{
	return (int)min(string_to_bytes(ini_get('post_max_size')), string_to_bytes(ini_get('upload_max_filesize')));
}

/**
 * Dumps a varialble from the global scope.
 *
 * @param mixed $var The variable to dump.
 * @param bool $return Return output? (Default: false)
 * @param mixed $title string|bool|array Alternate title for the dump, or to backtrace.
 * @param mixed $background Override the default background.
 * @param string $mode Default "auto", can be: "cli" or "web".
 * @return mixed void|string
 */
function var_dump ($var, $return = false, $title = false, $background = false, $mode = 'auto')
{
	if ($background === false) {
		if (!isset(System::$config['common']['background'])) {
			$background = 'white';
		} else {
			$background = System::$config['common']['background'];
		}
	}

	if ($mode === 'auto') {
		$webmode = (ENV_LEVEL & ENV_WEB ? true : false);
	} elseif ($mode === 'web') {
		$webmode = true;
	} elseif ($mode === 'cli') {
		$webmode = false;
	} else {
		trigger_error('Invalid mode.', E_USER_WARNING);
	}

	$fixDumpString = function ($name, $value, $htmlspecial = true) use (&$background, &$mode) {
		if (in_array($name, array('[\'pass\']', '[\'password\']', '[\'PHP_AUTH_PW\']'))) {
			$value = '********';
		} else {
			$fix = array(
				"\r\n" => colorize('¤¶', 'gray', $background, $mode) . "\n", // Windows linefeed.
				"\n\r" => colorize('¶¤', 'gray', $background, $mode) . "\n\n", // Erronumous (might be interpeted as double) linefeed.
				"\n"   => colorize('¶', 'gray', $background, $mode) . "\n", // UNIX linefeed.
				"\r"   => colorize('¤', 'gray', $background, $mode) . "\n" // Old mac linefeed.
			);
			$value = strtr(($htmlspecial ? htmlspecialchars($value) : $value), $fix);
		}
		return $value;
	};

	$recursionClasses = array();

	$dodump = function ($var, $var_name = null, $indent = 0, $params = array()) use (&$dodump, &$fixDumpString, &$background, &$webmode, &$mode, &$recursionClasses) {
		if (is_object($var)) {
			if (!empty($recursionClasses)) {
				$add = true;
				foreach ($recursionClasses as $class) {
					if ($var === $class) {
						$add = false;
					}
				}
				if ($add === true) {
					$recursionClasses[] = $var;
				}
			} else {
				$recursionClasses[] = $var;
			}
		}

		$doDump_indent = colorize('|', 'lightgray', $background, $mode) . '   ';
		echo str_repeat($doDump_indent, $indent) . colorize(($webmode === true ? htmlentities($var_name) : $var_name), 'varname', $background, $mode);

		if (is_array($var)) {
			echo ' ' . colorize('=>', 'black', $background, $mode) . ' ' . colorize('Array (' . count($var) . ')', 'gray', $background, $mode) . "\n" . str_repeat($doDump_indent, $indent) . colorize('(', 'lightgray', $background, $mode) . "\n";
			foreach ($var as $key => $value) {
				if (strpos(print_r($var[$key], true), '*RECURSION*') !== false) {
					$doDump_indent = colorize('|', 'lightgray', $background, $mode) . '   ';
					echo str_repeat($doDump_indent, $indent + 1) . colorize('[\'' . ($webmode === true ? htmlentities($key) : $key) . '\']', 'varname', $background, $mode);
					echo ' ' . colorize('=', 'black', $background, $mode) . ' ';
					echo colorize('*RECURSION*', 'recursion', $background, $mode);
					echo "\n";
				} elseif (is_object($value)) {
					$same = false;
					foreach ($recursionClasses as $class) {
						if ($class === $value) {
							$same = true;
						}
					}
					if ($same === true) {
						$doDump_indent = colorize('|', 'lightgray', $background, $mode) . '   ';
						echo str_repeat($doDump_indent, $indent + 1) . colorize('[\'' . ($webmode === true ? htmlentities($key) : $key) . '\']', 'varname', $background, $mode);
						echo ' ' . colorize('=', 'black', $background, $mode) . ' ';
						echo colorize(get_class($value) . '()', 'recursion', $background, $mode);
						echo "\n";
					} elseif (get_class($value) === 'Closure') {
						$doDump_indent = colorize('|', 'lightgray', $background, $mode) . '   ';
						echo str_repeat($doDump_indent, $indent + 1) . colorize('[\'' . ($webmode === true ? htmlentities($key) : $key) . '\']', 'varname', $background, $mode);
						echo ' ' . colorize('=', 'black', $background, $mode) . ' ';
						echo colorize(get_class($value) . '()', 'recursion', $background, $mode);
						echo "\n";
					} else {
						$dodump($value, '[\'' . $key . '\']', $indent + 1);
					}
				} else {
					$dodump($value, '[\'' . $key . '\']', $indent + 1);
				}
			}
			echo str_repeat($doDump_indent, $indent) . colorize(')', 'lightgray', $background, $mode);
		} elseif (is_string($var)) {
			if ((isset($params['error'])) && ($params['error'] === true)) {
				echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('Error: ' . $fixDumpString($var_name, $var, $webmode), 'error', $background, $mode);
			} else {
				echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('String(' . strlen($var) . ')', 'gray', $background, $mode) . ' ' . colorize('\'' . $fixDumpString($var_name, $var, $webmode) . '\'', 'string', $background, $mode);
			}
		} elseif (is_int($var)) {
			echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('Integer(' . strlen($var) . ')', 'gray', $background, $mode) . ' ' . colorize($var, 'int', $background, $mode);
		} elseif (is_bool($var)) {
			echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('Boolean', 'gray', $background, $mode) . ' ' . colorize(($var === true ? 'true' : 'false'), 'bool', $background, $mode);
		} elseif (is_object($var)) {
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
				echo ' ' . colorize('=>', 'black', $background, $mode) . ' ' . colorize($class->getName() . ' Object (Iterator)' . $parents, 'gray', $background, $mode) . "\n" . str_repeat($doDump_indent, $indent) . colorize('(', 'lightgray', $background, $mode) . "\n";
				var_dump($var);
			} else {
				echo ' ' . colorize('=>', 'black', $background, $mode) . ' ' . colorize($class->getName() . ' Object' . $parents, 'gray', $background, $mode) . "\n" . str_repeat($doDump_indent, $indent) . colorize('(', 'lightgray', $background, $mode) . "\n";

				$dblcheck = array();
				foreach ((array)$var as $key => $value) {
					if (!property_exists($var, $key)) {
						$key = ltrim($key, "\x0*");
						if (substr($key, 0, strlen($class->getName())) === $class->getName()) {
							$key = substr($key, (strlen($class->getName()) + 1));
						} else {
							$parents = class_parents($var);
							if (!empty($parents)) {
								foreach ($parents as $parent) {
									if (substr($key, 0, strlen($parent)) === $parent) {
										$key = $parent . '->' . substr($key, (strlen($parent) + 1));
									}
								}
							}
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
						} elseif ($prop->isProtected()) {
							$append .= ' protected';
						}
						$prop->setAccessible(true);
						if ($prop->isStatic()) {
							$value = $prop->getValue();
							$append .= ' static';
						} else {
							set_error_handler(function ($errno, $errstr) {
								throw new \Exception($errstr);
							});
							try {
								$value = $prop->getValue($var);
							} catch (\Exception $e) {
								$value = $e->getMessage();
								$append .= ' error';
								$error = true;
							}
							restore_error_handler();
						}
						if (array_key_exists($prop->name, $dblcheck)) {
							unset($dblcheck[$prop->name]);
						}
						if (is_object($value)) {
							$same = false;
							foreach ($recursionClasses as $class) {
								if ($class === $value) {
									$same = true;
								}
							}
							if ($same === true) {
								$doDump_indent = colorize('|', 'lightgray', $background, $mode) . '   ';
								echo str_repeat($doDump_indent, $indent + 1) . colorize('[\'' . ($webmode === true ? htmlentities($prop->name . '\'' . $append) : $prop->name . '\'' . $append) . ']', 'varname', $background, $mode);
								echo ' ' . colorize('=', 'black', $background, $mode) . ' ';
								echo colorize(get_class($value) . '()', 'recursion', $background, $mode);
								echo "\n";
							} else {
								$dodump($value, '[\'' . $prop->name . '\'' . $append . ']', $indent + 1, array('error' => $error));
							}
						} else {
							$dodump($value, '[\'' . $prop->name . '\'' . $append . ']', $indent + 1, array('error' => $error));
						}
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
			echo str_repeat($doDump_indent, $indent) . colorize(')', 'lightgray', $background, $mode);
		} elseif (is_null($var)) {
			echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('null', 'black', $background, $mode);
		} elseif (is_float($var)) {
			echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('Float(' . strlen($var) . ')', 'gray', $background) . ' ' . colorize($var, 'float', $background, $mode);
		} elseif (is_resource($var)) {
			echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('Resource', 'gray', $background, $mode) . ' ' . $var;
		} else {
			echo ' ' . colorize('=', 'black', $background, $mode) . ' ' . colorize('Unknown', 'gray', $background, $mode) . ' ' . $var;
		}
		echo "\n";
	};

	$prefix = 'unique';
	$suffix = 'value';

	if ($return === true) {
		ob_start();
	}
	if ($webmode) {
		echo '<pre class="vardump">';
	}

	if (($title === false) || (is_array($title))) {
		$backtrace = debug_backtrace();
		if ((is_array($title)) && (isset($title['steps'])) && (isset($backtrace[$title['steps']]))) {
			$backtrace = $backtrace[$title['steps']];
		} else {
			$backtrace = $backtrace[0];
		}
		if (substr($backtrace['file'], -13) == 'eval()\'d code') {
			$title = 'eval()';
		} else {
			$con = explode("\n", file_get_contents($backtrace['file']));
			$callee = $con[$backtrace['line'] - 1];
			if ((is_array($title)) && (isset($title['match']))) {
				preg_match($title['match'], $callee, $matches);
			} else {
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
			} else {
				$title = 'Unknown dump string';
			}
		}
	}
	$dodump($var, $title);
	if ($webmode) {
		echo "</pre>\n";
	}
	if ($return === true) {
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
function array_key (array $arr = array(), $which = 0)
{
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
function array_value (array $arr = array (), $which = 0)
{
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
function generate_password ($length = 8)
{
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
function seconds_to_array ($time, $weeks = false)
{
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
function run_time ()
{
	$microtime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
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
function bytes_to_array ($filesize = 0, $decimals = 2)
{
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
function run ($exec)
{
	$filename = tempnam(TEMP_DIR, 'tmp_');
	touch($filename);
	exec($exec . ' 2> ' . $filename, $stout, $return);
	$sterr = explode("\n", trim(file_get_contents($filename)));
	unlink($filename);
	return array('stout' => $stout, 'sterr' => $sterr, 'return' => $return);
}

/**
 * Execute the given command in the background.
 *
 * <div style="background-color: #ffc; color: #333; border: 1px solid #333; padding: 20px 30px;">
 * <h1 style="text-align: center;">Caution</h1>
 * <p>This functionality relies on system pids combined with command to retrieve status.
 * Since pids can be reused by the system, you should make sure your <em>$command</em> call is unique.</p>
 * </div>
 *
 * @param string $command The command that will be executed.
 *
 * @return array Array containing info about the job.
 * <p style="font-family: monospace;">
 * array (<br>
 * <span style="white-space: pre; font-size: 50%;">&Tab;</span>'store' // The location of the command temp storage.<br>
 * <span style="white-space: pre; font-size: 50%;">&Tab;</span>'pid' // The process id.<br>
 * );
 * </pre>
 */
function run_bg ($command)
{
	$tmpdir = make_temp_file(false, 'php_run_bg' . DIRECTORY_SEPARATOR, false, true);
	file_put_contents($tmpdir . 'exec', $command);
	exec(sprintf("%s > %s 2> %s & echo $! > %s", $command, $tmpdir . 'stout', $tmpdir . 'sterr', $tmpdir . 'pid'));
	$pid = (int)trim(file_get_contents($tmpdir . 'pid'));
	return array('store' => $tmpdir, 'pid' => $pid);
}

/**
 * Get all the jobs.
 *
 * @return array
 */
function run_bg_jobs ()
{
	$return = array();
	if (is_readable(TEMP_DIR . 'php_run_bg')) {
		foreach (new \DirectoryIterator(TEMP_DIR . 'php_run_bg') as $fileInfo) {
			$fileName = $fileInfo->getFilename();
			if (substr($fileName, 0, 1) === '.') {
				continue;
			}
			$return[] = TEMP_DIR . 'php_run_bg/' . $fileName . '/';
		}
	}
	return $return;
}

/**
 * Get the status of a background process.
 *
 * @param string $store
 * @return mixed
 */
function run_bg_status ($store)
{
	if (file_exists($store)) {
		$pid = (int)trim(file_get_contents($store . 'pid'));
		$exec = str_replace(array('é', 'æ', 'ø', 'å', 'Æ', 'Ø', 'Å'), '??', str_replace(array('"', '\\'), '', file_get_contents($store . 'exec')));

		exec('ps ' . $pid, $ps);
		$running = false;
		if (count($ps) === 2) {
			if (strpos($ps[1], $exec) !== false) {
				$running = true;
			}
		}

		$stout = file_get_contents($store . 'stout');
		$sterr = file_get_contents($store . 'sterr');

		return array('stout' => $stout, 'sterr' => $sterr, 'pid' => $pid, 'running' => $running);
	}
	return false;
}

/**
 * Cleanup the background run temp data.
 *
 * @param mixed $store false to clean all, or a specific store to clean.
 * @return mixed
 */
function run_bg_clean ($store)
{
	$dir = TEMP_DIR . 'php_run_bg' . DIRECTORY_SEPARATOR;
	if (file_exists($dir)) {
		if ($store === false) {
			$cleaned = 0;
			$running = 0;
			foreach (new \DirectoryIterator($dir) as $fileinfo) {
				if (substr($fileinfo->getFilename(), 0, 1) !== '.') {
					$status = run_bg_status($dir . $fileinfo->getFilename() . DIRECTORY_SEPARATOR);
					if ((isset($status['running'])) && ($status['running'] === false)) {
						$cleaned++;
						exec('rm -rf ' . $dir . $fileinfo->getFilename() . DIRECTORY_SEPARATOR);
					} else {
						$running++;
					}
				}
			}
			return array('cleaned' => $cleaned, 'running' => $running);
		} elseif (file_exists($store)) {
			exec('rm -rf ' . $store);
			return true;
		}
	}
	return false;
}

/**
 * Calculates the greatest common divisor of $a and $b
 *
 * @param int $a Non-zero integer.
 * @param int $b Non-zero integer.
 * @return int
 */
function gcd ($a, $b)
{
	$b = (($a == 0) ? 0 : $b);
	return (($a % $b) ? gcd($b, abs($a - $b)) : $b);
}

/**
 * Convert integer to roman number.
 *
 * @param int $num Number.
 * @return string Roman number.
 */
function number_to_roman ($num)
{
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
function cut_string ($string, $limit, $byword = true, $ending = '…')
{
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
 * @param string $content The content to colorize.
 * @param string $color The color to use.
 * @param string $background The background for color overrides to maintain visibility.
 * @param string $mode Default "auto", can be: "cli" or "web".
 * @param bool $getStyle return the style only (Default false).
 * @return string
 */
function colorize ($content, $color, $background = false, $mode = 'auto', $getStyle = false)
{
	if ((isset(System::$config['common']['colorize'])) && (System::$config['common']['colorize'] === false)) {
		return $content;
	}

	if ($background === false) {
		if (!isset(System::$config['common']['background'])) {
			$background = 'white';
		} else {
			$background = System::$config['common']['background'];
		}
	}

	if ($mode === 'auto') {
		$climode = (ENV_LEVEL & ENV_CLI ? true : false);
	} elseif ($mode === 'web') {
		$climode = false;
	} elseif ($mode === 'cli') {
		$climode = true;
	} else {
		trigger_error('Invalid mode.', E_USER_WARNING);
	}

	if ($climode) {
		$template = "\033[%sm%s\033[0m";
	} elseif ($getStyle === false) {
		$template = '<span style="color: %s;">%s</span>';
	} else {
		$template = 'color: %s;';
	}
	if (substr($color, 0, 6) === 'range:') {
		$config = json_decode(substr($color, 6), true);
		if ($config['type'] === 'alert') {
			$state = ($config['value'] / $config['max']);
			if ($state >= 1) {
				if ($climode) {
					return sprintf($template, '38;5;9', $content);
				}
				return sprintf($template, '#ff0000', $content);
			} elseif ($climode) {
				if ($state < 0.1) {
					return sprintf($template, '38;5;2', $state);
				} elseif ($state < 0.25) {
					return sprintf($template, '38;5;118', $state);
				} elseif ($state < 0.4) {
					return sprintf($template, '38;5;148', $state);
				} elseif ($state < 0.5) {
					return sprintf($template, '38;5;220', $state);
				} elseif ($state < 0.6) {
					return sprintf($template, '38;5;220', $state);
				} elseif ($state < 0.8) {
					return sprintf($template, '38;5;178', $state);
				}
				return sprintf($template, '38;5;166', $state);
			} elseif ($state === 0.5) {
				if ($climode) {
					return sprintf($template, '38;5;11', $content);
				}
				return sprintf($template, '#ffff00', $content);
			} elseif ($state < 0.5) {
				return sprintf($template, '#' . str_pad(dechex(round($state * 511)), 2, '0', STR_PAD_LEFT) . 'ff00', $content);
			} else {
				$state = (0.5 - ($state - 0.5));
				return sprintf($template, '#ff' . str_pad(dechex(round(($state) * 511)), 2, '0', STR_PAD_LEFT) . '00', $content);
			}
		}
	} elseif ($color === 'gray') {
		if ($climode) {
			return sprintf($template, '38;5;240', $content);
		}
		return sprintf($template, 'gray', $content);
	} elseif ($color === 'string') {
		if ($climode) {
			return sprintf($template, '38;5;46', $content);
		}
		return sprintf($template, 'green', $content);
	} elseif ($color === 'int') {
		if ($climode) {
			return sprintf($template, '38;5;196', $content);
		}
		return sprintf($template, 'red', $content);
	} elseif ($color === 'lightgray') {
		if ($background === 'black') {
			if ($climode) {
				return sprintf($template, '38;5;240', $content);
			}
			return sprintf($template, 'darkgray', $content);
		}
		if ($climode) {
			return sprintf($template, '38;5;251', $content);
		}
		return sprintf($template, 'lightgray', $content);
	} elseif ($color === 'bool') {
		if ($climode) {
			return sprintf($template, '38;5;57', $content);
		}
		return sprintf($template, 'purple', $content);
	} elseif ($color === 'float') {
		if ($climode) {
			return sprintf($template, '38;5;39', $content);
		}
		return sprintf($template, 'dodgerblue', $content);
	} elseif ($color === 'error') {
		if ($climode) {
			return sprintf($template, '38;5;198', $content);
		}
		return sprintf($template, 'deeppink', $content);
	} elseif ($color === 'recursion') {
		if ($climode) {
			return sprintf($template, '38;5;208', $content);
		}
		return sprintf($template, 'darkorange', $content);
	} elseif ($background === 'black') {
		if ($climode) {
			return sprintf($template, '38;5;256', $content);
		}
		return sprintf($template, 'white', $content);
	} else {
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
 * @return array
 */
function request_url ($url, $post = false, $headers = false, $timeout = 1, $connecttimeout = 1)
{
	$return = array();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if (($headers !== false) && (is_array($headers))) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
	curl_setopt($ch, CURLOPT_HEADER, 1);
	if ($post !== false) {
		curl_setopt($ch, CURLOPT_POST, 1);
		if (is_array($post)) {
			$post = http_build_query($post);
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
	$result = curl_exec($ch);

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

	$return['reply'] = substr($result, $header_size);
	$return['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$return['header'] = substr($result, 0, $header_size);
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
function load_xml ($xmlstring)
{
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
 * @param mixed $ttl int Time to live or false to never renew.
 * @param string $xpath Xpath to a unix timestamp for expire. $ttl will then represent minimum time.
 * @param mixed $post false|array Optional post fields to send. (Default false).
 * @param mixed $headers false|array Optional headers to send. (Default: false).
 * @param int $timeout How many seconds to wait for responce. (Default 1).
 * @param int $connecttimeout How many seconds to wait for connection. (Default 1).
 * @param mixed $validationCallback false or a callback function for validating before storing the cache.
 * @return array with false on failure or object SimpleXMLElement on success.
 */
function get_xml ($url, $ttl = 600, $xpath = false, $post = false, $headers = false, $timeout = 1, $connecttimeout = 1, $validationCallback = false)
{
	$filename = preg_replace('/[\/?*:;{}\\\\]/', '★', $url);
	$cache = new Cache('gimle/common/get_xml/' . $filename);

	$return = array();
	$validation = null;
	if (!$cache->exists()) {
		$cacheHit = null;
		$return = request_url($url, $post, $headers, $timeout, $connecttimeout);
		if ($return['reply'] !== false) {
			$cacheStr = $return['reply'];
			$return['reply'] = load_xml($return['reply']);
		}
		if ($return['reply'] !== false) {
			if ($validationCallback !== false) {
				$res = $validationCallback($return);
				$validation = false;
				if ($res === true) {
					$validation = true;
					$cache->put($cacheStr);
				} else {
					$return['reply'] = $res;
					if ($res !== false) {
						$validation = true;
						$cache->put($res);
					}
				}
			} else {
				$cache->put($cacheStr);
			}
		}
	} else {
		$cacheHit = false;
		$reload = false;
		if (($xpath === false) && ($ttl !== false) && ($cache->age() > $ttl)) {
			/* No xpath, and time ran out, so setting flag to get new cache. */
			$reload = true;
		}
		if ($xpath !== false) {
			$return['reply'] = simplexml_load_string($cache->get());
			$expire = $return['reply']->xpath($xpath);
			if ((is_array($expire)) && (!empty($expire))) {
				$expire = (string)$expire[0];
				if (ctype_digit($expire)) {
					$expire = (int)$expire;
				} else {
					$expire = strtotime($expire);
				}
				if ($expire < time()) {
					if (($ttl !== false) && ($cache->age() > $ttl)) {
						/* Time exceeded ttl, and expire flag in xml. Setting flag to get new cache. */
						$reload = true;
					}
					/* else: Time exceeded xpath, but expire flag told to keep. */
				}
				/* else: keep cached version. */
			}
		}
		if ($reload === true) {
			$return = request_url($url, $post, $headers, $timeout, $connecttimeout);
			if ($return['reply'] !== false) {
				$simplexml = load_xml($return['reply']);
			}
			$validation = false;
			if ((isset($simplexml)) && ($simplexml !== false)) {
				$validation = true;
				if ($validationCallback !== false) {
					$callback = $return;
					$callback['reply'] = $simplexml;
					$res = $validationCallback($callback);
					$validation = false;
					if ($res === true) {
						$validation = true;
						$cache->put($return['reply']);
					} else {
						$return['reply'] = $res;
						if ($res !== false) {
							$validation = true;
							$cache->put($res);
						}
					}
				} else {
					$cache->put($return['reply']);
				}
				$return['reply'] = $simplexml;
			} elseif ($xpath === false) {
				$cacheHit = true;
				$return['reply'] = simplexml_load_string($cache->get());
			}
		} else {
			$cacheHit = true;
			$return['reply'] = simplexml_load_string($cache->get());
		}
	}
	$return['validation'] = $validation;
	$return['cacheHit'] = $cacheHit;
	return $return;
}

/**
 * Fetch a file from a url and cache it for a specified period of time.
 *
 * @param string $url The url.
 * @param mixed $ttl int Time to live or false to never renew.
 * @param mixed $post false|array Optional post fields to send. (Default false).
 * @param mixed $headers false|array Optional headers to send. (Default: false).
 * @param int $timeout How many seconds to wait for responce. (Default 1).
 * @param int $connecttimeout How many seconds to wait for connection. (Default 1).
 * @param mixed $validationCallback false or a callback function for validating before storing the cache.
 * @return array
 */
function get_file ($url, $ttl = 600, $post = false, $headers = false, $timeout = 1, $connecttimeout = 1, $validationCallback = false, $cacheName = false)
{
	if ($cacheName === false) {
		$cacheName = 'gimle/common/get_file/' . preg_replace('/[\/?*:;{}\\\\]/', '★', $url);
		if ($post !== false) {
			$cacheName .= '[' . preg_replace('/[\/?*:;{}\\\\]/', '★', json_encode($post)) . ']';
		}
	} elseif ($ttl === null) {
		return request_url($url, $post, $headers, $timeout, $connecttimeout);
	}
	$cache = new Cache($cacheName);

	$return = array();
	$validation = null;
	if ((!$cache->exists()) || (($ttl !== false) && ($cache->age() > $ttl))) {
		$return = request_url($url, $post, $headers, $timeout, $connecttimeout);
		if (!$cache->exists()) {
			$cacheHit = null;
		} else {
			$cacheHit = false;
		}
		if ($return['reply'] !== false) {
			if ($validationCallback !== false) {
				$res = $validationCallback($return);
				$validation = false;
				if ($res === true) {
					$validation = true;
					$cache->put($return['reply']);
				} elseif (is_string($res)) {
					$validation = true;
					$return['reply'] = $res;
					$cache->put($res);
				}
			} else {
				$cache->put($return['reply']);
			}
		} elseif ($cache->exists()) {
			$return['reply'] = $cache->get();
			$cacheHit = true;
		}
	} else {
		$return['reply'] = $cache->get();
		$cacheHit = true;
	}
	$return['validation'] = $validation;
	$return['cacheHit'] = $cacheHit;
	return $return;
}

/**
 * Get full translation table.
 *
 * @return array
 */
function get_html_translation_table ($append = array())
{
	$table = array();

	/* Load the full php 5.4 translation table for all php versions */
	if (version_compare(PHP_VERSION, '5.3.4') >= 0) {
		foreach (\get_html_translation_table(HTML_ENTITIES, null, mb_internal_encoding()) as $key => $value) {
			$table[$value] = $key;
		}
	} else {
		foreach (\get_html_translation_table(HTML_ENTITIES) as $key => $value) {
			$table[$value] = utf8_encode($key);
		}
	}
	if (version_compare(PHP_VERSION, '5.4.6') >= 0) {
		foreach (\get_html_translation_table(HTML_ENTITIES, ENT_HTML5 | ENT_QUOTES, mb_internal_encoding()) as $key => $value) {
			$table[$value] = $key;
		}
	} else {
		include System::$config['extensions']['common'] . 'inc' . DIRECTORY_SEPARATOR . 'ent.php';
		$table = array_merge($table, $html5);
	}

	/* Additional entities */
	$table['&ap;']     = '≈';
	$table['&there;']  = '∴';
	$table['&lsquor;'] = '‚';
	$table['&rdquor;'] = '„';
	$table['&dash;']   = '‐';
	$table['&lsqb;']   = '[';
	$table['&verbar;'] = '|';

	/* Add custom entities if provided */
	if (!empty($append)) {
		$table = array_merge($table, $append);
	}

	return $table;
}

/**
 * Convert code to utf-8.
 *
 * @param int $num
 * @return string
 */
function code2utf8 ($num)
{
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
function ent2utf8 ($string, $exclude = array('&', ';'), $append = array())
{
	$html_translation_table = array();
	foreach (get_html_translation_table($append) as $key => $value) {
		if (!in_array($value, $exclude)) {
			$html_translation_table[$key] = $value;
		}
	}
	$string = strtr($string, $html_translation_table);

	$string = preg_replace_callback('/&#x([0-9a-f]+);/i', function ($param) {
		return code2utf8(hexdec($param[1]));
	}, $string);
	$string = preg_replace_callback('/&#([0-9]+);/', function ($param) {
		return code2utf8($param[1]);
	}, $string);

	return $string;
}

/**
 * Convert utf8 to entities.
 *
 * @param string $string
 * @param array $exclude
 * @return string
 */
function utf82ent ($string, $exclude = array('.', ',', '-'), $append = array())
{
	$html_translation_table = array();
	foreach (get_html_translation_table($append) as $key => $value) {
		if (!in_array($value, $exclude)) {
			$html_translation_table[$value] = $key;
		}
	}
	$string = strtr($string, $html_translation_table);

	return $string;
}

/**
 * Convert a binary string to html escaped hex.
 *
 * @param string $string Input string
 * @param string|false $exclude Regular expression for excluded characters.
 * @return string
 */
function bin2htmlhex ($string, $exclude = '[\pL\pM\pS\pN\pP ]')
{
	$return = '';
	$block = false;
	for ($i = 0; $i < mb_strlen($string); $i++) {
		$subStr = mb_substr(mb_substr($string, $i), 0, 1);
		if ($subStr === '&') {
			$block = true;
		}
		if ($block === true) {
			$return .= $subStr;
			if ($subStr === ';') {
				$block = false;
			}
			continue;
		}

		if ($exclude !== false) {
			if (preg_match('/' . $exclude . '/', $subStr)) {
				$return .= $subStr;
				continue;
			}
		}

		$append = bin2hex(mb_convert_encoding($subStr, 'UTF-32', mb_internal_encoding()));
		if (substr($append, 0, 6) === '000000') {
			$append = substr($append, 6);
		} elseif (substr($append, 0, 4) === '0000') {
			$append = substr($append, 4);
		} elseif (substr($append, 0, 2) === '00') {
			$append = substr($append, 2);
		}

		$return .= '&#x' . $append . ';';
	}
	return $return;
}

/**
 * Get the users preferred language, or false if not found.
 *
 * @param array $avail A list of the available languages.
 * @return mixed false|string
 */
function get_preferred_language (array $avail, array $synonyms = array())
{
	if (!empty($synonyms)) {
		$mergedAvail = array_merge($avail, array_keys($synonyms));
	} else {
		$mergedAvail = $avail;
	}
	$return = false;
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$client = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
		$hitrate = 0;
		if (!empty($client)) {
			foreach ($client as $langstr) {
				if (preg_match('/(.*);q=([0-1]{0,1}\.\d{0,4})/', $langstr, $matches)) {
					if (($hitrate < (float)$matches[2]) && (in_array($matches[1], $mergedAvail))) {
						$return = $matches[1];
						$hitrate = (float)$matches[2];
					}
				} elseif (in_array($langstr, $mergedAvail)) {
					if (isset($synonyms[$langstr])) {
						return $synonyms[$langstr];
					}
					return $langstr;
				}
			}
		}
	}
	if ($return === false) {
		return false;
	}
	if (isset($synonyms[$return])) {
		return $synonyms[$return];
	}
	return $return;
}

/**
 * Create a empty temp file with unique name.
 *
 * @param string $dir optional
 * @param string $prefix optional
 * @param string $suffix optional
 * @param string $as_dir optional Create a directory instead of file.
 * @return string Full path and name of the new temp file.
 */
function make_temp_file ($dir = false, $prefix = false, $suffix = false, $as_dir = false)
{
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
		if ($as_dir === false) {
			touch($dir . $name);
		} else {
			$name .= DIRECTORY_SEPARATOR;
			mkdir($dir . $name, 0777, true);
		}
		return $dir . $name;
	}
	return make_temp_file($dir, $prefix, $suffix, $as_dir);
}

/**
 * Returns information about a path.
 * This function is almost the same as the built in one, with two exceptions:
 * 1) The dirname will always end with a trailing slash.
 * 2) Return leading directory dot as specified in the request parameter.
 *
 * @param $path
 * @param $options (Default: false)
 * @return mixed
 */
function pathinfo ($path, $options = false)
{
	$pathinfo = \pathinfo($path);
	if (($pathinfo['dirname'] === '.') && (substr($path, 0, 1) !== '.')) {
		$pathinfo['dirname'] = '';
	} else {
		$pathinfo['dirname'] .= DIRECTORY_SEPARATOR;
	}
	$pathinfo['complete'] = $path;

	if ($options !== false) {
		$return = '';
		if ($options & PATHINFO_DIRNAME) {
			$return .= $pathinfo['dirname'];
		}
		if ($options & PATHINFO_BASENAME) {
			$return .= $pathinfo['basename'];
		} else {
			if ($options & PATHINFO_FILENAME) {
				$return .= $pathinfo['filename'];
			}
			if ($options & PATHINFO_EXTENSION) {
				$return .= $pathinfo['extension'];
			}
		}
		return $return;
	}

	return $pathinfo;
}

/**
 * Check if a file exists on a remote server.
 *
 * @param $server Servername
 * @param $filename
 * @return mixed true if ok, array with details if fail.
 */
function file_exists_ssh ($server, $filename)
{
	$exec = 'if ssh ' . $server . ' stat ' . $filename . ' \> /dev/null 2\>\&1; then echo true; else echo false; fi';
	$res = run($exec);
	if (($res['return'] === 0) && (isset($res['stout'][0]))) {
		if ($res['stout'][0] === 'true') {
			return true;
		} elseif ($res['stout'][0] === 'false') {
			return false;
		}
	}
	return $res;
}

/**
 * Make a directory on a remote server.
 *
 * @param $server Servername
 * @param $pathname
 * @param $mode (Default: 0777)
 * @param $recursive Also create parent directories if they do not exist. (Default: false)
 * @return mixed true if ok, array with details if fail.
 */
function mkdir_ssh ($server, $pathname, $mode = 0777, $recursive = false)
{
	$exec = 'ssh ' . $server . ' mkdir -m ' . decoct($mode) . ' ';
	if ($recursive === true) {
		$exec .= '-p ';
	}
	$exec .= '"' . $pathname . '"';
	$res = run($exec);
	if (($res['return'] === 0) && (empty($res['stout'])) && (isset($res['sterr'][0])) && (!isset($res['sterr'][1])) && ($res['sterr'][0] === '')) {
		return true;
	}
	return $res;
}

/**
 * Change the user owner of a file exists on a remote server.
 *
 * @param $server Servername
 * @param $filename
 * @param $user
 * @return mixed true if ok, array with details if fail.
 */
function chown_ssh ($server, $filename, $user)
{
	$exec = 'ssh ' . $server . ' chown ' . $user . ' ' . $filename;
	$res = run($exec);
	if (($res['return'] === 0) && (empty($res['stout'])) && (isset($res['sterr'][0])) && (!isset($res['sterr'][1])) && ($res['sterr'][0] === '')) {
		return true;
	}
	return $res;
}

/**
 * Change the group owner of a file exists on a remote server.
 *
 * @param $server Servername
 * @param $filename
 * @param $user
 * @return mixed true if ok, array with details if fail.
 */
function chgrp_ssh ($server, $filename, $group)
{
	$exec = 'ssh ' . $server . ' chgrp ' . $group . ' ' . $filename;
	$res = run($exec);
	if (($res['return'] === 0) && (empty($res['stout'])) && (isset($res['sterr'][0])) && (!isset($res['sterr'][1])) && ($res['sterr'][0] === '')) {
		return true;
	}
	return $res;
}
