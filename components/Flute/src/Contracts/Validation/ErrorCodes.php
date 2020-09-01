<?php declare (strict_types = 1);

namespace Limoncello\Flute\Contracts\Validation;

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

use Limoncello\Validation\Contracts\Errors\ErrorCodes as BaseErrorCodes;

/**
 * @package Limoncello\Flute
 */
interface ErrorCodes extends BaseErrorCodes
{
    /** Message code */
    const INVALID_ATTRIBUTES = self::LAST + 1;

    /** Message code */
    const TYPE_MISSING = self::INVALID_ATTRIBUTES + 1;

    /** Message code */
    const UNKNOWN_ATTRIBUTE = self::TYPE_MISSING + 1;

    /** Message code */
    const INVALID_RELATIONSHIP_TYPE = self::UNKNOWN_ATTRIBUTE + 1;

    /** Message code */
    const INVALID_RELATIONSHIP = self::INVALID_RELATIONSHIP_TYPE + 1;

    /** Message code */
    const UNKNOWN_RELATIONSHIP = self::INVALID_RELATIONSHIP + 1;

    /** Message code */
    const EXIST_IN_DATABASE_SINGLE = self::UNKNOWN_RELATIONSHIP + 1;

    /** Message code */
    const EXIST_IN_DATABASE_MULTIPLE = self::EXIST_IN_DATABASE_SINGLE + 1;

    /** Message code */
    const UNIQUE_IN_DATABASE_SINGLE = self::EXIST_IN_DATABASE_MULTIPLE + 1;

    /** Message code */
    const INVALID_OPERATION_ARGUMENTS = self::UNIQUE_IN_DATABASE_SINGLE + 1;

    /** Message code */
    const INVALID_UUID = self::INVALID_OPERATION_ARGUMENTS + 1;

    // Special code for those who extend this enum

    /** Message code */
    const FLUTE_LAST = self::INVALID_UUID;
}
