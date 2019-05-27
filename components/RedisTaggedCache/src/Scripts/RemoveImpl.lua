local function removeCacheKey(cacheKey, prefixForKey, prefixForTag)
    -- read tags related to the key
    local prefixedCacheKey = prefixForKey .. cacheKey
    local tags = redis.call("SMEMBERS", prefixedCacheKey)

    -- for every tag remove backward link to the key
    for _, tag in ipairs(tags) do
        redis.call("SREM", prefixForTag .. tag, cacheKey)
    end

    -- now we can drop the related tags and the key itself
    redis.call("DEL", prefixedCacheKey, cacheKey)

    return 0
end
