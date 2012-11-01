<?php
namespace WebPower\LuaSandbox\Tests;

use WebPower\LuaSandbox\LuaSandbox;

class LuaSandboxTest extends \PHPUnit_Framework_TestCase
{
	function testCreation()
	{
		new LuaSandbox();
	}

	function testInvalidLuaThrowsException()
	{
		$this->setExpectedException('\WebPower\LuaSandbox\LuaErrorException');
		$sandbox = new LuaSandbox();
		$lua = <<<CODE
callUnExistingFunction();
CODE;
		$sandbox->execute($lua);
	}

	function testValidLua()
	{
		$sandbox = new LuaSandbox();
		$res = $sandbox->execute(<<<CODE
return 10 * 2;
CODE
);
		$this->assertEquals(20, $res);
	}

	function testCleanGlobalScope()
	{
		$sandbox = new LuaSandbox();
		$globals = $sandbox->execute(<<<CODE
local seen = {}
function dumpGlobals(t, prefix)
	seen[t] = true
	local names = {}
	for name in pairs(t) do
		table.insert(names, prefix .. name)
		v = t[name]
		if type(v)=="table" and not seen[v] then
			local sub = dumpGlobals(v, name .. '.')
			for sub_k,sub_v in pairs(sub) do
				names[sub_k] = sub_v
			end
		end
	end
	return names
end
local globals = dumpGlobals(_G, '')
table.sort(globals)
return globals
CODE
);
		$this->assertEquals(
			array(
				1 => '_G',
				'_VERSION',
				'assert',
				// 'collectgarbage',
				// 'coroutine',
				// 'coroutine.create',
				// 'coroutine.running',
				// 'debug',
				// 'dofile',
				'dumpGlobals', // Our function to dump the globals
				'error',
				'string',
				'table'
			),
			$globals
		);
	}

	function testAssertThrowsException()
	{
		$this->setExpectedException('\WebPower\LuaSandbox\LuaErrorException');
		$sandbox = new LuaSandbox();
		$sandbox->execute(<<<CODE
assert(false, 'Assertion failed')
CODE
);
	}

	function testErrorThrowsException()
	{
		$this->setExpectedException('\WebPower\LuaSandbox\LuaErrorException');
		$sandbox = new LuaSandbox();
		$sandbox->execute(<<<CODE
error('Some error')
CODE
		);
	}
}
