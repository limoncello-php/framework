local function invalidateTag(tag, prefixForKey, prefixForTag)
    -- read all cache keys that are affected by the tag
    local prefixedTag = prefixForTag .. tag
    local cacheKeys = redis.call("SMEMBERS", prefixedTag)

    -- now remove all the affected cache keys
    for _, cacheKey in ipairs(cacheKeys) do
        local retCode = removeCacheKey(cacheKey, prefixForKey, prefixForTag)
        if retCode ~= 0 then
            return retCode
        end
    end

    return 0
end

return invalidateTag(KEYS[1], ARGV[1], ARGV[2])
