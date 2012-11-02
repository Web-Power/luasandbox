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

    public function assignObject($name, $object)
    {
        $this->verifyVariableName($name);
        $refl = new \ReflectionObject($object);
        $methods = array();
        foreach ($refl->getMethods() as $method) {
            if ($method->isPublic() && !$method->isStatic() &&
                    !$method->isConstructor() && !$method->isDestructor()) {
                $methods[] = $method->name;
            }
        }

        $methods = array_flip($methods);
        $this->assignVar('_assignObject_',
            array(
                'name' => $name,
                'methods' => $methods
            )
        );

        foreach ($methods as $method => $_) {
            $this->assignCallable(
                '_assignObject__'.$name.'_'.$method, array($object, $method)
            );
        }

        $this->run(<<<CODE
local name = _assignObject_.name
local obj = {}
for method in pairs(_assignObject_.methods) do
     obj[method] = _G["_assignObject__" .. name .. "_".. method]
     _G["_assignObject__" .. name .. "_".. method] = nil
end
_assignObject_ = nil
_G[name] = obj
CODE
);
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
