<?php
namespace WebPower\LuaSandbox;

use \Lua;

class LuaSandbox
{
	/** @var Lua */
	private $sandbox;

	function __construct()
	{
		if (!class_exists('\\Lua')) {
			throw new Exception('Lua PHP module not installed. See http://pecl.php.net/package/lua');
		}
		$this->sandbox = new Lua();

		$this->unsetGlobals(array(
			'collectgarbage',
			'coroutine.create',
			'coroutine.running',
			'coroutine',
			'debug',
			'dofile',
			'gcinfo',
		));

	}

	private function unsetGlobals($globals)
	{
		foreach ($globals as $global) {
			$this->sandbox->eval('_G.'.$global.' = nil');
		}
	}

	/**
	 * @param string $lua
	 * @return int|float|string|array|callable|void
	 * @throws LuaErrorException
	 */
	public function execute($lua)
	{
		$level = error_reporting(0);

		$retval = $this->sandbox->eval($lua);

		error_reporting($level);

		if ($retval === false) {
			$error = error_get_last();
			$this->throwLuaError($error);
		}
		return $retval;
	}

	private function throwLuaError($error)
	{
		$error = new \ErrorException(
			$error['message'], 0, $error['type'], $error['file'], $error['line']
		);
		throw new LuaErrorException('Error in executed Lua', 0, $error);
	}
}
