<?php namespace Limoncello\Auth\Contracts\Authorization\PolicyAdministration;

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
 * @package Limoncello\Auth
 */
abstract class EvaluationEnum
{
    /** @see http://docs.oasis-open.org/xacml/3.0/xacml-3.0-core-spec-os-en.html#_Toc325047187 */

    /** Combine result */
    const PERMIT = (1 << 0);

    /** Combine result */
    const DENY =  (1 << 1);

    /** Combine result */
    const INDETERMINATE = (1 << 2);

    /** Combine result */
    const NOT_APPLICABLE = (1 << 3);

    /** Combine result */
    const INDETERMINATE_PERMIT = self::INDETERMINATE | self::PERMIT;

    /** Combine result */
    const INDETERMINATE_DENY = self::INDETERMINATE | self::DENY;

    /** Combine result */
    const INDETERMINATE_DENY_OR_PERMIT = self::INDETERMINATE | self::DENY | self::PERMIT;

    /**
     * @param int $value
     *
     * @return string
     */
    public static function toString($value)
    {
        assert(is_int($value) === true);

        switch ($value) {
            case static::PERMIT:
                $result = 'PERMIT';
                break;
            case static::DENY:
                $result = 'DENY';
                break;
            case static::INDETERMINATE:
                $result = 'INDETERMINATE';
                break;
            case static::NOT_APPLICABLE:
                $result = 'NOT APPLICABLE';
                break;
            case static::INDETERMINATE_PERMIT:
                $result = 'INDETERMINATE PERMIT';
                break;
            case static::INDETERMINATE_DENY:
                $result = 'INDETERMINATE DENY';
                break;
            case static::INDETERMINATE_DENY_OR_PERMIT:
                $result = 'INDETERMINATE DENY OR PERMIT';
                break;
            default:
                $result = 'UNKNOWN';
                break;
        }

        return $result;
    }
}
