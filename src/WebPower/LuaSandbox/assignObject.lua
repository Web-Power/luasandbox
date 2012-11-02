-- This function is used by the LuaObjectProxy
function assignObject_(name, methods, getter, setter)
    local obj = {}
    for name, global_name in pairs(methods) do
        obj[name] = _G[global_name]
        _G[global_name] = nil
    end

    -- Magic property access using metatable
    local mt = {}
    mt.__index = _G[getter]
    _G[getter] = nil
    mt.__newindex = _G[setter]
    _G[setter] = nil
    setmetatable(obj, mt)

    _G[name] = obj
end
