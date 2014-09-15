<?php
namespace gimle\common\traits;

trait Singelton
{
	private static $instance = false;

	public static function getInstance ()
	{
		if (self::$instance === false) {
			$me = get_called_class();
			$args = func_get_args();
			if (!empty($args)) {
				// @todo: Would be nice to pass the calling arguments as called, but that does not seem to work.
				// self::$instance = call_user_func_array(array($me, '__construct'), $args);
				self::$instance = new $me($args);
			} else {
				self::$instance = new $me();
			}
		}

		return self::$instance;
	}

	private function __construct ()
	{
	}
}
