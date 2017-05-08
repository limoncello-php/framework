<?php namespace Limoncello\Application\Traits;

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
 * @package Limoncello\Application
 */
trait ParseCallableTrait
{
    /**
     * @param mixed $mightBeCallable
     *
     * @return array
     */
    protected function parseCacheCallable($mightBeCallable): array
    {
        if (is_string($mightBeCallable) === true &&
            count($nsClassMethod = explode('::', $mightBeCallable, 2)) === 2 &&
            count($nsClass = explode('\\', $nsClassMethod[0])) > 1
        ) {
            $canBeClass     = array_pop($nsClass);
            $canBeNamespace = array_filter($nsClass);
            $canBeMethod    = $nsClassMethod[1];
        } elseif (is_array($mightBeCallable) === true &&
            count($mightBeCallable) === 2 &&
            count($nsClass = explode('\\', $mightBeCallable[0])) > 1
        ) {
            $canBeClass     = array_pop($nsClass);
            $canBeNamespace = array_filter($nsClass);
            $canBeMethod    = $mightBeCallable[1];
        } else {
            return [null, null, null];
        }

        foreach (array_merge($canBeNamespace, [$canBeClass, $canBeMethod]) as $value) {
            // is string might have a-z, A-Z, _, numbers but has at least one a-z or A-Z.
            if (is_string($value) === false ||
                preg_match('/^\\w+$/i', $value) !== 1 ||
                preg_match('/^[a-z]+$/i', $value) !== 1
            ) {
                return [null, null, null];
            }
        }

        $namespace = implode('\\', $canBeNamespace);
        $class     = $canBeClass;
        $method    = $canBeMethod;

        return [$namespace, $class, $method];
    }
}
