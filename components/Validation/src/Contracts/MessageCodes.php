<?php namespace Limoncello\Validation\Contracts;

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
 * @package Limoncello\Validation
 */
interface MessageCodes
{
    /** Message code */
    const INVALID_VALUE = 0;

    /** Message code */
    const IS_STRING = 1;

    /** Message code */
    const IS_BOOL = 2;

    /** Message code */
    const IS_INT = 3;

    /** Message code */
    const IS_FLOAT = 4;

    /** Message code */
    const IS_NUMERIC = 5;

    /** Message code */
    const IS_DATE_TIME = 6;

    /** Message code */
    const IS_DATE_TIME_FORMAT = 7;

    /** Message code */
    const IS_ARRAY = 8;

    /** Message code */
    const IS_NULL = 9;

    /** Message code */
    const NOT_NULL = 10;

    /** Message code */
    const REQUIRED = 11;

    /** Message code */
    const STRING_LENGTH = 12;

    /** Message code */
    const STRING_LENGTH_MIN = 13;

    /** Message code */
    const STRING_LENGTH_MAX = 14;

    /** Message code */
    const BETWEEN = 15;

    /** Message code */
    const BETWEEN_MIN = 16;

    /** Message code */
    const BETWEEN_MAX = 17;

    /** Message code */
    const IN_VALUES = 18;

    /** Message code */
    const REG_EXP = 19;

    /** Message code */
    const EQUALS = 20;

    /** Message code */
    const NOT_EQUALS = 21;

    /** Message code */
    const LESS_THAN = 22;

    /** Message code */
    const LESS_OR_EQUALS = 23;

    /** Message code */
    const MORE_THAN = 24;

    /** Message code */
    const MORE_OR_EQUALS = 25;
}
