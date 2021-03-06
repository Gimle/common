<?php
/**
 * Initialize the common extension.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package common
 */

namespace gimle\common;

if (isset($config['stateless'])) {
	if ($config['stateless'] === false) {
		session_start();
	} elseif (defined('BASE_PATH_KEY')) {
		if (is_array($config['stateless'])) {
			if (!in_array(BASE_PATH_KEY, $config['stateless'])) {
				session_start();
			}
		} elseif (is_string($config['stateless'])) {
			if ($config['stateless'] !== BASE_PATH_KEY) {
				session_start();
			}
		}
	}
} else {
	session_start();
}

require __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, __NAMESPACE__) . DIRECTORY_SEPARATOR . 'system.php';

spl_autoload_register(__NAMESPACE__ . '\System::autoload');

if (isset($config['extensions'])) {
	if (!empty($config['extensions'])) {
		foreach ($config['extensions'] as $extensionPath) {
			if ((is_string($extensionPath)) && (file_exists($extensionPath . 'lib' . DIRECTORY_SEPARATOR))) {
				System::$autoloadPrependPaths[] = $extensionPath;
			}
		}
		unset($extensionPath);
	}
}

System::$config = $config;
unset($config);

if (!file_exists(TEMP_DIR)) {
	mkdir(TEMP_DIR, 0777, true);
}
if ((!is_writable(TEMP_DIR)) || (!is_readable(TEMP_DIR))) {
	trigger_error('Access to temp dir "' . TEMP_DIR . '" fail.', E_USER_WARNING);
}

require __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'functions.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, __NAMESPACE__) . DIRECTORY_SEPARATOR . 'functions.php';

if (in_array(\gimle\core\page(0), array('css', 'js'))) {
	include __DIR__ . DIRECTORY_SEPARATOR . 'specialurls.php';
}

if (ENV_LEVEL | ENV_LIVE) {
	include __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'dev.php';
}
