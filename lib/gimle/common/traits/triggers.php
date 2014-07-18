<?php
namespace gimle\common\traits;

trait Triggers
{
	private $triggers = array();

	protected function on ($trigger, $callback)
	{
		$this->triggers[$trigger] = $callback;
	}

	protected function trigger ($trigger, $params = array())
	{
		if ((isset($this->triggers[$trigger])) && (method_exists($this, $trigger))) {
			return call_user_func_array($this->triggers[$trigger], $params);
		}
	}
}
