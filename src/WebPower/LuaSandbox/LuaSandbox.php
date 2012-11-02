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
    }

    /**
     * @param string $lua
     * @return int|float|string|array|callable|void
     * @throws LuaErrorException
     */
    public function run($lua)
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

    public function assignCallable($name, $callback)
    {
        $this->verifyVariableName($name);
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Callback should be a callable');
        }
        $this->sandbox->registerCallback($name, $callback);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws Exception when lua didn't register the var
     */
    public function assignVar($name, $value)
    {
        $this->verifyVariableName($name);
        $this->sandbox->assign($name, $value);
        $ret = $this->run('return _G["'.$name.'"]');
        if ($ret != $value) {
            $this->unsetVar($name);
            throw new Exception(sprintf('Assigning Var with name: %s failed', $name));
        }
    }

    public function unsetVar($name)
    {
        foreach ((array) $name as $global) {
            $this->sandbox->assign($global, null);
        }
    }

    private function verifyVariableName($name)
    {
        $reserved = array(
            'and', 'break', 'do', 'else', 'elseif', 'end', 'false', 'for',
            'function', 'if', 'in', 'local', 'nil', 'not', 'or', 'repeat',
            'return', 'then', 'true', 'until', 'while'
        );

        $isReserved = in_array($name, $reserved);
        $isInvalid = !preg_match(
            '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',
            $name
        );
        if ($isReserved || $isInvalid) {
            throw new InvalidVariableNameException($name);
        }
    }

    private function throwLuaError($error)
    {
        $error = new \ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        );
        throw new LuaErrorException('Error in executed Lua', 0, $error);
    }
}
