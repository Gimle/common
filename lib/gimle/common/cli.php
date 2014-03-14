<?php
namespace gimle\common;

class Cli {
	public static $argv = array();

	public static $foreground_colors = array(
			'black'        => '0;30',
			'dark_gray'    => '1;30',
			'blue'         => '0;34',
			'light_blue'   => '1;34',
			'green'        => '0;32',
			'light_green'  => '1;32',
			'cyan'         => '0;36',
			'light_cyan'   => '1;36',
			'red'          => '0;31',
			'light_red'    => '1;31',
			'purple'       => '0;35',
			'light_purple' => '1;35',
			'brown'        => '0;33',
			'yellow'       => '1;33',
			'light_gray'   => '0;37',
			'white'        => '1;37'
	);

	public static $foreground_html = array(
			'black'        => '000',
			'blue'         => '2659A2',
			'red'          => 'AE0707',
			'brown'        => 'B56110',
			'yellow'       => 'FCE93F',
			'green'        => '1B481A',
	);

	public static $background_colors = array(
			'black'      => '40',
			'red'        => '41',
			'green'      => '42',
			'yellow'     => '43',
			'blue'       => '44',
			'magenta'    => '45',
			'cyan'       => '46',
			'light_gray' => '47'
	);

	public static $returnColors = true;
	public static $returnType = 'terminal';

	public static $fg = 'white';

	public static function start ($description = false, $options = array()) {
		$_SERVER['options'] = $_SERVER['params'] = array();
		foreach ($_SERVER['argv'] as $key => $value) {
			if ($key > 0) {
				if (substr($value, 0, 1) == '-') {
					$nextMightBeValue = $value;
				}

				if (($nextMightBeValue) && (substr($value, 0, 1) !== '-')) {
					array_pop($_SERVER['options']);
					if (substr($nextMightBeValue, 0, 2) === '--') {
						$_SERVER['options'][trim($nextMightBeValue, '-')] = $value;
					} else {
						$keys = str_split(substr($nextMightBeValue, 1));

						$total = count($keys);
						for ($i = 0; $i < $total; $i++) {
							if ($i == $total - 1) {
								$_SERVER['options'][$keys[$i]] = $value;
							} else {
								$_SERVER['options'][$keys[$i]] = true;
							}
						}
					}
					$nextMightBeValue = false;
				} elseif ($nextMightBeValue) {
					if (substr($value, 0, 2) === '--') {
						$_SERVER['options'][trim($value, '-')] = true;
					} else {
						$keys = str_split(trim($value, '-'));
						foreach ($keys as $value2) {
							$_SERVER['options'][$value2] = true;
						}
					}
				} else {
					$_SERVER['params'][] = $value;
				}
			} else {
				$nextMightBeValue = false;
			}
		}

		self::$argv = $_SERVER['argv'];
		array_shift(self::$argv);
		foreach (self::$argv as $key => $value) {
			if (strstr($value, ' ')) {
				self::$argv[$key] = '"' . $value . '"';
			}
		}

		if (!empty($options)) {
			self::oraganizeOptions($options);
		}

		if (isset($_SERVER['options']['no-color'])) {
			self::$returnColors = false;
		}

		if (empty($_SERVER['options'])) {
			echo "\n";
			echo self::color('This script can not run without arguments.', 'red') . "\n";
			echo "\n - For a list of available commands, use --help\n\n";
			self::terminate();
		} elseif (isset($_SERVER['options']['help'])) {
			echo "\n";
			if ($description !== false) {
				echo $description;
			} else {
				echo 'No description.';
			}
			if (!empty($options)) {
				echo "\n\nOptions:\n";
				echo self::createHelp($options);
			} else {
				echo "\n";
			}
			exit("\n");
		}
	}

	public static function oraganizeOptions ($options) {
		foreach ($options as $key => $value) {
			if ((isset($value['short'])) && (isset($_SERVER['options'][$value['short']]))) {
				if (!isset($_SERVER['options'][$key])) {
					$_SERVER['options'][$key] = $_SERVER['options'][$value['short']];
				}
				unset($_SERVER['options'][$value['short']]);
			}
		}
	}

	public static function out ($message, $type = 'info') {
		$return = '';
		$exp = '/\\033\[(.*?)\m/';
		if ($type === 'info') {
			if (!isset($_SERVER['options']['quiet'])) {
				$return = self::color('Info:', 'light_blue') . ' ' . $message . "\n";
			}
		} elseif ($type === 'user') {
			$return = self::color('User:', 'purple') . ' ' . $message . "\n";
		} elseif ($type === 'debug') {
			if (isset($_SERVER['options']['debug'])) {
				$return = self::color('Debug:', 'brown') . ' ' . $message . "\n";
			}
		} elseif ($type === 'error') {
			$return = self::color('Error:', 'red') . ' ' . $message . "\n";
			$message = date('Y-m-d H:i:s') . ': ' . $message;
		}
		return $return;
	}

	public static function ttl () {
		$ttr = \gimle\common\seconds_to_array(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
		$time = '';
		if ($ttr['years'] !== 0) {
			$time .= $ttr['years'] . ' year' . (($ttr['years'] > 1) ? 's' : '') . ' ';
		}
		if ($ttr['days'] !== 0) {
			$time .= $ttr['days'] . ' day' . (($ttr['days'] > 1) ? 's' : '') . ' ';
		}
		if ($ttr['hours'] !== 0) {
			$time .= $ttr['hours'] . ' hour' . (($ttr['hours'] > 1) ? 's' : '') . ' ';
		}
		if ($ttr['minutes'] !== 0) {
			$time .= $ttr['minutes'] . ' minute' . (($ttr['minutes'] > 1) ? 's' : '') . ' ';
		}
		$time .= $ttr['seconds'] . ' second' . (($ttr['seconds'] != 1) ? 's' : '');
		return $time;
	}

	public static function createHelp ($options) {
		$len = 1;
		$return = '';
		foreach (array_keys($options) as $value) {
			if (strlen($value) > $len) {
				$len = strlen($value);
			}
		}
		foreach ($options as $key => $value) {
			$return .= '  ';
			if (isset($value['short'])) {
				$return .= '-' . $value['short'] . ', ';
			} else {
				$return .= '    ';
			}
			$return .= '--' . str_pad($key, $len + 2, ' ', STR_PAD_RIGHT);
			$return .= $value['description'] . "\n";
		}
		return $return;
	}

	public static function markWhiteSpace ($input, $default = "\033[0m") {
		$input = str_replace(' ', "\033[" . self::$foreground_colors['brown'] . "m·$default", $input);
		$input = str_replace(' ', "\033[" . self::$background_colors['red'] . "m $default", $input);
		return $input;
	}

	public static function color ($string, $foreground = false, $background = false) {
		if (is_array($string)) {
			$string = print_r($string, true);
		}

		$return = '';
		if (self::$returnColors) {

			if (($foreground) && (isset(self::$foreground_colors[$foreground]))) {
				if (self::$returnType === 'terminal') {
					$return .= "\033[" . self::$foreground_colors[$foreground] . 'm';
				} elseif (self::$returnType === 'html') {
					$return .= '<span style="display: inline-block; color: #' . self::$foreground_html[$foreground] . ';">';
				}
			}
			if (($background) && (isset(self::$background_colors[$foreground])) && (self::$returnType === 'terminal')) {
				$return .= "\033[" . self::$background_colors[$background] . 'm';
			}

			$return .= $string;
			if (self::$returnType == 'terminal') {
				$return .= "\033[0m";
			} elseif (self::$returnType == 'html') {
				$return .= '</span>';
			}
		} else {
			$return = $string;
		}

		return $return;
	}

	public static function terminate ($status = 0, $message = false) {
		if ($message) {
			echo $message;
		}
		exit($status);
	}

	/**
	 * Create a "yes/no" input from user in cli mode.
	 *
	 * @param string $question Question to display.
	 * @param string $trueValue True value.
	 * @return boolean
	 */
	public function get ($question, $trueValue) {
		echo Cli::color('User:', 'purple') . ' ' . $question;
		$handle = fopen('php://stdin', 'r');
		$line = fgets($handle);
		if (trim($line) === $trueValue) {
			return true;
		}
		return false;
	}
}
