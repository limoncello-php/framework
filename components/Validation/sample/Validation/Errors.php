<?php declare(strict_types=1);

namespace Sample\Validation;

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

use Limoncello\Validation\Contracts\Errors\ErrorCodes;

/**
 * @package Sample
 */
interface Errors extends ErrorCodes
{
    /** Custom error code */
    const IS_EMAIL = self::LAST + 1;

    /** Custom error code */
    const IS_VALID_SKU = self::IS_EMAIL + 1;

    /** Custom error code */
    const IS_DELIVERY_DATE = self::IS_VALID_SKU + 1;
}
