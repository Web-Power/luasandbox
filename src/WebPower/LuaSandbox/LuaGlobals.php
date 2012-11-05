<?php
namespace WebPower\LuaSandbox;

use \Lua;

class LuaGlobals
{
    public static function getGlobals()
    {
        if (version_compare(self::luaVersion(), '5.2.0') == -1) {
            return array (
                '_G',
                '_VERSION',
                'assert',
                'collectgarbage',
                'coroutine',
                'debug',
                'dofile',
                'error',
                'gcinfo',
                'getfenv',
                'getmetatable',
                'io',
                'ipairs',
                'load',
                'loadfile',
                'loadstring',
                'math',
                'module',
                'newproxy',
                'next',
                'os',
                'package',
                'pairs',
                'pcall',
                'print',
                'rawequal',
                'rawget',
                'rawset',
                'require',
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
            );
        } else {
            return array (
                '_G',
                '_VERSION',
                'assert',
                'bit32',
                'collectgarbage',
                'coroutine',
                'debug',
                'dofile',
                'error',
                'getmetatable',
                'io',
                'ipairs',
                'load',
                'loadfile',
                'loadstring',
                'math',
                'module',
                'next',
                'os',
                'package',
                'pairs',
                'pcall',
                'print',
                'rawequal',
                'rawget',
                'rawlen',
                'rawset',
                'require',
                'select',
                'setmetatable',
                'string',
                'table',
                'tonumber',
                'tostring',
                'type',
                'unpack',
                'xpcall',
            );
        }
    }

    private static function luaVersion()
    {
        return str_replace('Lua ', '', Lua::LUA_VERSION);
    }
}
