-- $ wrk -t8 -c130 -d30s -s ./wrk.lua "http://localhost:8090"

wrk.method = "POST"
wrk.headers["Content-Type"] = "application/x-www-form-urlencoded"
wrk.body = "title=post_title&text=post_text&created-at=2100-01-01"

request = function()
    local index = math.random(0, 59)
    local path = "/posts" .. index .. "/create"

    return wrk.format(nil, path)
end
