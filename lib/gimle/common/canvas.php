<?php namespace gimle\common;
/**
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @link http://gimlé.org/extensions/common/
 * @package canvas
 */

/**
 * Canvas class.
 */
class Canvas {
	/**
	 * Create CSS and JavaScript caches.
	 *
	 * @param boolean $cacheJS
	 * @param boolean $cacheCSS
	 * @param string $filename
	 * @return void
	 */
	public static function createCaches ($cacheJS = true, $cacheCSS = true, $filename = false) {
		// @todo Look at $filename, and php files.

		if ($cacheCSS) {
			$filenames = array();

			if (file_exists(SITE_DIR . 'css/')) {
				foreach (new \DirectoryIterator(SITE_DIR . 'css/') as $file) {
					if (($file->isDir()) && (substr($file->getFilename(), 0, 1) != '.')) {
						foreach (new \DirectoryIterator(SITE_DIR . 'css/' . $file->getFilename() . '/') as $subfile) {
							if ((!$subfile->isDir()) && (substr($subfile->getFilename(), 0, 1) != '.')) {
								$filenames['css'][$file->getFilename()][$subfile->getFilename()] = SITE_DIR . 'css/' . $file->getFilename() . '/' . $subfile->getFilename();
							}
						}
					}
				}
			}
			if ((isset($filenames['css'])) && (!empty($filenames['css']))) {
				foreach ($filenames['css'] as $key => $value) {
					if (!empty($filenames['css'][$key])) {
						sort($filenames['css'][$key]);
					}
				}
				foreach ($filenames['css'] as $key => $value) {
					$current = '';
					if (!empty($value)) {
						foreach ($value as $file) {
							if (substr($file, -4, 4) === '.php') {
								ob_start();
								include $file;
								$current .= ob_get_contents();
								ob_end_clean();
							}
							else {
								$current .= file_get_contents($file);
							}
						}
					}
					if ((!$filename) || ($filename == $key . '.css')) {
						$cache = new Cache('gimle' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . $key . '.css');
						$cache->put(parse_php($current));
						unset($cache);
					}
				}
			}
		}

		if ($cacheJS) {
			$filenames = array();

			if (file_exists(SITE_DIR . 'js/')) {
				foreach (new \DirectoryIterator(SITE_DIR . 'js/') as $file) {
					if (($file->isDir()) && (substr($file->getFilename(), 0, 1) != '.')) {
						foreach (new \DirectoryIterator(SITE_DIR . 'js/' . $file->getFilename() . '/') as $subfile) {
							if ((!$subfile->isDir()) && (substr($subfile->getFilename(), 0, 1) != '.')) {
								$filenames['javascript'][$file->getFilename()][$subfile->getFilename()] = SITE_DIR . 'js/' . $file->getFilename() . '/' . $subfile->getFilename();
							}
						}
					}
				}
			}
			if ((isset($filenames['javascript'])) && (!empty($filenames['javascript']))) {
				foreach ($filenames['javascript'] as $key => $value) {
					if (!empty($filenames['javascript'][$key])) {
						sort($filenames['javascript'][$key]);
					}
				}
				foreach ($filenames['javascript'] as $key => $value) {
					$current = '';
					if (!empty($value)) {
						foreach ($value as $file) {
							if (substr($file, -4, 4) === '.php') {
								ob_start();
								include $file;
								$current .= ob_get_contents();
								ob_end_clean();
							}
							else {
								$current .= file_get_contents($file);
							}
						}
					}
					if ((!$filename) || ($filename == $key . '.js')) {
						$cache = new Cache('gimle' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . $key . '.js');
						$cache->put(parse_php($current));
						unset($cache);
					}
				}
			}
		}
	}
}
