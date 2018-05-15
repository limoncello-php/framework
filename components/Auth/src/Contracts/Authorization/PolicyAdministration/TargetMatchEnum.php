<?php namespace Limoncello\Auth\Contracts\Authorization\PolicyAdministration;

/**
 * Copyright 2015-2018 info@neomerx.com
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
 * @package Limoncello\Auth
 */
abstract class TargetMatchEnum
{
    /** @see http://docs.oasis-open.org/xacml/3.0/xacml-3.0-core-spec-os-en.html #7.11 (table 4) */

    /** Combine result */
    const MATCH = 0;

    /** Combine result */
    const NOT_MATCH =  self::MATCH + 1;

    /** Combine result */
    const NO_TARGET =  self::NOT_MATCH + 1;

    /** Combine result */
    const INDETERMINATE = self::NO_TARGET + 1;

    /**
     * @param int $value
     *
     * @return string
     */
    public static function toString(int $value): string
    {
        switch ($value) {
            case static::MATCH:
                $result = 'MATCH';
                break;
            case static::NOT_MATCH:
                $result = 'NOT MATCH';
                break;
            case static::NO_TARGET:
                $result = 'NO TARGET';
                break;
            case static::INDETERMINATE:
                $result = 'INDETERMINATE';
                break;
            default:
                $result = 'UNKNOWN';
                break;
        }

        return $result;
    }
}
