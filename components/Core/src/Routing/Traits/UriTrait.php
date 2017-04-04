<?php namespace Limoncello\Core\Routing\Traits;

/**
 * Copyright 2015-2017 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @package Limoncello\Core
 */
trait UriTrait
{
    /**
     * @param string $uri
     * @param bool   $trailingSlash
     *
     * @return string
     */
    protected function normalizeUri(string $uri, bool $trailingSlash)
    {
        // add starting '/' and cut ending '/' if necessary
        $uri = strlen($uri) > 0 && $uri[0] === '/' ? $uri : '/' . $uri;
        $prefixLen = strlen($uri);
        $uri = $prefixLen > 1 && substr($uri, -1) === '/' ? substr($uri, 0, $prefixLen - 1) : $uri;

        // feature: trailing slashes are possible when asked
        $uri = $trailingSlash === true && substr($uri, -1) !== '/' ? $uri . '/' : $uri;

        return $uri;
    }

    /**
     * @param string $uri1
     * @param string $uri2
     *
     * @return string
     */
    protected function concatUri(string $uri1, string $uri2): string
    {
        $fEndsWithSlash   = strlen($uri1) > 0 && substr($uri1, -1) === '/';
        $sStartsWithSlash = strlen($uri2) > 0 && $uri2[0] === '/';

        // only one has '/'
        if ($fEndsWithSlash xor $sStartsWithSlash) {
            return $uri1 . $uri2;
        }

        // either both have '/' nor both don't have

        $result = $fEndsWithSlash === true ? $uri1 . substr($uri2, 1) : $uri1 . '/' . $uri2;

        return $result;
    }
}
