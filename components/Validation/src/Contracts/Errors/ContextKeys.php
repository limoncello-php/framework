<?php declare(strict_types=1);

namespace Limoncello\Validation\Contracts\Errors;

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

/**
 * @package Limoncello\Validation
 */
interface ContextKeys
{
    /** Message code */
    const DATE_TIME_VALUE = 0;

    /** Message code */
    const DATE_TIME_MIN = 0;

    /** Message code */
    const DATE_TIME_MAX = self::DATE_TIME_MIN + 1;

    /** Message code */
    const SCALAR_MIN = 0;

    /** Message code */
    const SCALAR_MAX = self::SCALAR_MIN + 1;

    /** Message code */
    const SCALAR_VALUE = 0;

    /** Message code */
    const SCALAR_VALUES = 0;

    /** Message code */
    const STRING_LENGTH_MIN = 0;

    /** Message code */
    const STRING_LENGTH_MAX = self::STRING_LENGTH_MIN + 1;
}
