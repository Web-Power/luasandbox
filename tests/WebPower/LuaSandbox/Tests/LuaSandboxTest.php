<?php
namespace WebPower\LuaSandbox\Tests;

use WebPower\LuaSandbox\LuaSandbox;

class LuaSandboxTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LuaSandbox
     */
    public $obj;

    protected function setUp()
    {
        $this->obj = new LuaSandbox();
    }

    /**
     * @expectedException WebPower\LuaSandbox\LuaErrorException
     */
    function testInvalidLuaThrowsException()
    {
        $lua = <<<CODE
callUnExistingFunction();
CODE;
        $this->obj->run($lua);
    }

    function testValidLua()
    {
        $res = $this->obj->run(<<<CODE
return 10 * 2;
CODE
        );
        $this->assertEquals(20, $res);
    }

    function testUnset()
    {
        $this->obj->unsetVar(
            array(
                'dofile',
                'loadfile',
                'module',
                'require',
                'coroutine',
                'debug',
                'file',
                'io',
                'os',
                'package',
            )
        );

        $globals = $this->obj->run(<<<CODE
local names = {}
for name, val in pairs(_G) do
    table.insert(names, name)
end
table.sort(names)
return names
CODE
        );
        $this->assertEquals(
            array(
                1 => '_G',
                '_VERSION',
                'assert',
                'collectgarbage',
                'error',
                'gcinfo',
                'getfenv',
                'getmetatable',
                'ipairs',
                'load',
                'loadstring',
                'math',
                'newproxy',
                'next',
                'pairs',
                'pcall',
                'print',
                'rawequal',
                'rawget',
                'rawset',
                'select',
                'setfenv',
                'setmetatable',
                'string',
                'table',
                'tonumber',
                'tostring',
                'type',
                'unpack',
                'xpcall',
            ),
            $globals
        );
    }

    /**
     * @expectedException WebPower\LuaSandbox\LuaErrorException
     */
    function testAssertThrowsException()
    {
        $this->setExpectedException('\WebPower\LuaSandbox\LuaErrorException');
        $this->obj->run(<<<CODE
assert(false, 'Assertion failed')
CODE
        );
    }

    /**
     * @expectedException WebPower\LuaSandbox\LuaErrorException
     */
    function testErrorThrowsException()
    {
        $this->obj->run(<<<CODE
error('Some error')
CODE
        );
    }

    function testPhpCallbackFunction()
    {
        $args = false;
        $this->obj->assignCallable('doSomething', function() use(&$args) {
                $args = func_get_args();
            });
        $this->obj->run(<<<CODE
doSomething(1, 3, 3, 7, {1, 3, 3, 7})
CODE
        );
        $this->assertEquals(
            array(
                1, 3, 3, 7,
                array(1=> 1, 3, 3, 7)
            ),
            $args
        );
    }

    /**
     * @expectedException WebPower\LuaSandbox\Exception
     */
    function testInvalidCallbackName()
    {
        $this->obj->assignCallable('0abc', function() {});
    }

    function testVariables()
    {
        $this->obj->assignVar('valueFromPhp', 1337);
        $val = $this->obj->run(<<<CODE
return valueFromPhp
CODE
        );
        $this->assertEquals(1337, $val);
    }

    /**
     * @expectedException WebPower\LuaSandbox\InvalidVariableNameException
     */
    function testInvalidVariableName()
    {
        $this->setExpectedException('WebPower\LuaSandbox\Exception');
        $this->obj->assignVar('0abc', 1337);
    }

    /**
     * @expectedException WebPower\LuaSandbox\Exception
     */
    function testInvalidVariableValue()
    {
        $this->obj->assignVar('testFunc', function($a, $b) { return $a + $b; });
    }

    /**
     * @expectedException WebPower\LuaSandbox\InvalidVariableNameException
     */
    function testReservedKeywordVariable()
    {
        $this->obj->assignVar('break', 'test');
    }

    function testAssigningObject()
    {
        $obj = new \ArrayObject(array());
        $this->obj->assignObject('myArray', $obj);
        $this->obj->run('myArray.append(10)');
        $this->assertEquals(1, count($obj));
        $this->assertEquals(10, $obj[0]);

        $obj->testProperty = 'hoi';
        $res = $this->obj->run('return myArray.testProperty');
        $this->assertEquals('hoi', $res);
    }
}
