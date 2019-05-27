local function addCacheKey(cacheKey, cacheValue, jsonTags, prefixForKey, prefixForTag, cacheTtl)
    local tags = cjson.decode(jsonTags)
    if not tags then
        -- invalid tags
        return -1
    end

    local ttlNumber = tonumber(cacheTtl)
    local prefixedCacheKey = prefixForKey .. cacheKey
    for _, tag in ipairs(tags) do
        local prefixedTag = prefixForTag .. tag
        redis.call("SADD", prefixedCacheKey, tag)
        redis.call("SADD", prefixedTag, cacheKey)
        if ttlNumber > 0 then
            redis.call("EXPIRE", prefixedCacheKey, cacheTtl)
            redis.call("EXPIRE", prefixedTag, cacheTtl)
        end
    end

    redis.call("SET", cacheKey, cacheValue)
    if ttlNumber > 0 then
        redis.call("EXPIRE", cacheKey, cacheTtl)
    end

    return 0
end

return addCacheKey(KEYS[1], ARGV[1], ARGV[2], ARGV[3], ARGV[4], ARGV[5])
