<?php
/**
 * Handle special urls.
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package common
 */

namespace gimle\common;

if ((\gimle\core\page(1) !== false) && (\gimle\core\page(0) === 'css') || (\gimle\core\page(0) === 'js')) {
	$file = new Cache('gimle' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . \gimle\core\page(0) . DIRECTORY_SEPARATOR . \gimle\core\page(1));

	$cacheJS = false;
	$cacheCSS = false;
	if ((\gimle\core\page(0) === 'js') && (isset(System::$config['cache']['regenerate']['javascript'])) && (System::$config['cache']['regenerate']['javascript'] === true)) {
		$cacheJS = true;
	}
	elseif ((\gimle\core\page(0) === 'css') && (isset(System::$config['cache']['regenerate']['css'])) && (System::$config['cache']['regenerate']['css'] === true)) {
		$cacheCSS = true;
	}

	if ($file->exists() === false) {
		Canvas::createCaches(true, true, \gimle\core\page(1));
	}
	elseif (($cacheJS) || ($cacheCSS)) {
		Canvas::createCaches($cacheJS, $cacheCSS, \gimle\core\page(1));
	}

	if ($file->exists() === true) {
		header_remove('Expires');
		header_remove('Pragma');
		header_remove('Cache-Control');
		header_remove('X-Powered-By');
		header_remove('Set-Cookie');
		header_remove('Content-Language');
		header('Accept-Ranges: bytes');
		header('Server: ' . $_SERVER['SERVER_SOFTWARE']);
		header('ETag: "' . md5_file($file->getFilename()) . '"');
		header('Content-Length: ' . filesize($file->getFilename()));
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file->getFilename())) . ' GMT');
		if (\gimle\core\page(0) === 'js') {
			header('Content-Type: application/javascript');
		}
		elseif (\gimle\core\page(0) === 'css') {
			header('Content-Type: text/css');
		}
		readfile($file->getFilename());
	}
	else {
		header('HTTP/1.0 404 Not Found');
	}
	die();
}
