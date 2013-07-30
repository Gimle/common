<?php
namespace gimle\common\traits;

trait Singelton
{
	private static $instance = false;

	public static function getInstance ()
	{
		if (self::$instance === false) {
			$me = get_class();
			self::$instance = new $me();
		}

		return self::$instance;
	}

	private function __construct ()
	{
	}
}
