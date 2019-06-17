<?php declare(strict_types=1);

namespace Limoncello\Passport\Adaptors\PostgreSql;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use function assert;
use function explode;
use function strlen;
use function substr;

/**
 * @package Limoncello\Passport
 */
trait ArrayParserTrait
{
    /**
     * @param string $values
     *
     * @return string[]
     */
    protected function parseArray(string $values): array
    {
        // PostgreSql arrays represented as strings
        // '{}' - empty array
        // '{scope1}' or '{scope1,scope2}' - non-empty array
        // so it should always start with '{' and end with '}'.
        assert(substr($values, 0, 1) === '{' && substr($values, -1) === '}');

        $parsed = strlen($values) === 2 ? [] : explode(',', substr($values, 1, -1));

        return $parsed;
    }
}
