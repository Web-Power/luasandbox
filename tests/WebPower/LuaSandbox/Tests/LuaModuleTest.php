<?php
namespace WebPower\LuaSandbox\Tests;

use \Lua;
use WebPower\LuaSandbox\LuaGlobals;

class LuaModuleTest extends \PHPUnit_Framework_TestCase
{
    private $callbackArg;

    protected function setUp()
    {
        $this->callbackArg = null;
    }

    function testCallbackUsage()
    {
        $sandbox = new Lua();
        $this->assertEquals(Lua::LUA_VERSION, $sandbox->getVersion());

        $res = $sandbox->registerCallback('phpCallback', array($this, 'luaCallback'));
        $this->assertSame($sandbox, $res);

        $res = $sandbox->assign('phpValue', 1337);
        $this->assertSame($sandbox, $res);

        $res = $sandbox->eval(<<<CODE
return phpCallback(phpValue);
CODE
        );
        $this->assertEquals(7331, $res);
        $this->assertEquals(1337, $this->callbackArg);
    }

    function luaCallback($arg)
    {
        $this->callbackArg = $arg;
        return 7331;
    }

    function testRegisterCallbackGeneratesError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $sandbox = new Lua();
        $sandbox->registerCallback('phpCallback');
    }

    function testFaultyLuaGeneratesError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'Lua::eval(): lua error: [string "line"]:1: attempt to call global \'nonexitingFUnction\' (a nil value)');
        $sandbox = new Lua();
        $sandbox->eval(<<<CODE
nonexitingFUnction();
CODE
        );
    }

    function testListOfGlobalVars()
    {
        $sandbox = new Lua();
        $globals = array();
        $sandbox->registerCallback('phpCallback', function($key) use(&$globals) {
                $globals[] = $key;
            });

        $sandbox->eval(<<<CODE
    local cb = phpCallback
    _G.phpCallback = nil
	for n,v in pairs(_G) do
        cb(n)
	end
CODE
        );

        sort($globals);

        $this->assertEquals(LuaGlobals::getGlobals(), $globals);
    }

    function testUnsettingGlobalVars()
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'Lua::eval(): lua error: [string "line"]:2: attempt to call field \'exit\' (a nil value)');
        $sandbox = new Lua();
        $sandbox->eval(<<<CODE
os.exit = nil;
os.exit(1);
CODE
        );
    }

    function testUnsettingWholeOsTable()
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'Lua::eval(): lua error: [string "line"]:2: attempt to index global \'os\' (a nil value)');
        $sandbox = new Lua();
        $sandbox->eval(<<<CODE
_G.os = nil;
os.getenv('pwd');
CODE
        );
    }

    function testLuaClosure()
    {
        $sandbox = new Lua();
        $closure = $sandbox->eval(<<<CODE
return function(arg)
	return arg * 2;
end
CODE
        );
        $this->assertInstanceOf('LuaClosure', $closure);
        $this->assertEquals(2674, $sandbox->call($closure, array(1337)));

        unset($sandbox);
        $this->assertEquals(20, $closure(10));
    }

    function testEvalHasOwnLocalVars()
    {
        $sandbox = new Lua();
        $sandbox->eval(<<<CODE
local piet = 1337
CODE
        );
        $res = $sandbox->eval(<<<CODE
return piet
CODE
        );
        $this->assertNull($res);
    }
}
