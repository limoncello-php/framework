<?php declare(strict_types=1);

namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data;

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
 * @package Limoncello\Tests\Auth
 */
interface RequestProperties
{
    /** Request key */
    const REQUEST_OPERATION = 0;

    /** Request key */
    const REQUEST_RESOURCE_TYPE = self::REQUEST_OPERATION + 1;

    /** Request key */
    const REQUEST_RESOURCE_IDENTITY = self::REQUEST_RESOURCE_TYPE + 1;

    /** Request key */
    const REQUEST_RESOURCE_ATTRIBUTES = self::REQUEST_RESOURCE_IDENTITY + 1;

    /** Request key */
    const REQUEST_RESOURCE_RELATIONSHIPS = self::REQUEST_RESOURCE_ATTRIBUTES + 1;

    /** Request key */
    const REQUEST_LAST = self::REQUEST_RESOURCE_RELATIONSHIPS;
}
