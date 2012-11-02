<?php
namespace WebPower\LuaSandbox;

class LuaObjectProxy
{
    private $object;
    /** @var LuaSandbox */
    private $sandbox;

    function __construct($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Object expected');
        }
        $this->object = $object;
    }

    /**
     * @param LuaSandbox $sandbox
     * @param string $name
     * @throws InvalidVariableNameException
     */
    public function assignInSandbox(LuaSandbox $sandbox, $name)
    {
        $sandbox->assertValidIdentifier($name);
        $this->sandbox = $sandbox;
        $refl = new \ReflectionObject($this->object);
        $methods = array();
        foreach ($refl->getMethods() as $method) {
            if ($method->isPublic() && !$method->isStatic() &&
                !$method->isConstructor() && !$method->isDestructor()) {
                $methods[] = $method->name;
            }
        }

        $lua_methods = $this->createLuaMethodsForObject($methods, $this->object);
        $getter = $this->createGetterForObject();
        $setter = $this->createSetterForObject();

        $this->sandbox->runFile(__DIR__.'/assignObject.lua');
        $this->sandbox->call(
            'assignObject_',
            array($name, $lua_methods, $getter, $setter)
        );
        $sandbox->unsetVar('assignObject_');
    }

    private function createLuaMethodsForObject($methods, $object)
    {
        $lua_methods = array();
        foreach ($methods as $method) {
            $global_name = '_assignObject__' . $method;
            $this->sandbox->assignCallable($global_name, array($object, $method));
            $lua_methods[$method] = $global_name;
        }

        return $lua_methods;
    }

    private function createGetterForObject()
    {
        $getter = '_assignObject_getter';
        $this->sandbox->assignCallable($getter, array($this, 'getObjectProperty'));
        return $getter;
    }

    public function getObjectProperty($t, $key)
    {
        return $this->object->{$key};
    }

    private function createSetterForObject()
    {
        $setter = '_assignObject_setter';
        $this->sandbox->assignCallable($setter, array($this, 'setObjectProperty'));
        return $setter;
    }

    public function setObjectProperty($t, $key, $value)
    {
        $this->object->{$key} = $value;
    }
}
