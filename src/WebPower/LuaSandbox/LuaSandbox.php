<?php
namespace WebPower\LuaSandbox;

use \Lua;

class LuaSandbox
{
    /** @var Lua */
    private $sandbox;

    function __construct()
    {
        if (!extension_loaded('lua')) {
            throw new Exception('Lua PHP module not installed. See http://pecl.php.net/package/lua');
        }
        $this->sandbox = new Lua();
    }

    /**
     * @param string $lua
     * @return int|float|string|array|\Closure|void
     * @throws LuaErrorException
     */
    public function run($lua)
    {
        return $this->tryRunStringOrFile($lua, false);
    }

    /**
     * @param string $file
     * @return int|float|string|array|\Closure|void
     * @throws \InvalidArgumentException
     * @throws LuaErrorException
     */
    public function runFile($file)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException('File not found');
        }
        return $this->tryRunStringOrFile($file, true);
    }

    public function call($name, array $args = array())
    {
        $res = $this->sandbox->call($name, $args);

        return $res;
    }

    public function assignCallable($name, $callback)
    {
        $this->assertValidIdentifier($name);
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
        $this->assertValidIdentifier($name);
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
            $this->assertValidIdentifier($global);
            $this->sandbox->assign($global, null);
        }
    }

    public function assignObject($name, $object)
    {
        $proxy = new LuaObjectProxy($object);
        $proxy->assignInSandbox($this, $name);
    }

    public function isValidIdentifier($name)
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

        return $isReserved || $isInvalid;
    }

    private function tryRunStringOrFile($str, $file = false)
    {
        $level = error_reporting(0);

        if (!$file)
            $retval = $this->sandbox->eval($str);
        else
            $retval = $this->sandbox->include($str);

        error_reporting($level);

        if ($retval === false)
            $this->throwLuaError(error_get_last());

        return $retval;
    }

    public function assertValidIdentifier($name)
    {
        if ($this->isValidIdentifier($name)) {
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
