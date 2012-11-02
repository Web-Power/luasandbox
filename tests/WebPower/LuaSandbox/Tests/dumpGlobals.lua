local seen = {}
function dumpGlobals(t, prefix)
    seen[t] = true
    local names = {}
    for name in pairs(t) do
        table.insert(names, prefix .. name)
        local v = t[name]
        if type(v)=="table" and not seen[v] then
            local sub = dumpGlobals(v, prefix .. name .. '.')
            for sub_k,sub_v in pairs(sub) do
                table.insert(names, sub_v)
            end
        end
    end
    return names
end
return dumpGlobals(_G, '')