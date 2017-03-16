<?php namespace Limoncello\Core\Routing\Traits;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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
trait CallableTrait
{
    /**
     * If callable can be cached.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isCallableToCache($value)
    {
        $result = is_callable($value) && (
                // string `Class::method`
                is_string($value) ||
                // array of strings [`Class`, `method`]
                (is_array($value) && count($value) === 2 && is_string($value[0]) && is_string($value[1]))
            );

        return $result;
    }

    /**
     * If array of callable values can be cached.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function isCallableToCacheArray(array $values)
    {
        foreach ($values as $value) {
            if ($this->isCallableToCache($value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getCallableToCacheMessage()
    {
        return 'Value either not callable or cannot be cached. ' .
            'Use callable in form of \'ClassName::methodName\' or [ClassName::class, \'methodName\'].';
    }
}
