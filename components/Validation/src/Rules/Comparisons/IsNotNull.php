<?php namespace Limoncello\Validation\Rules\Comparisons;

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

use Limoncello\Validation\Contracts\Errors\ContextKeys;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;

/**
 * @package Limoncello\Validation
 */
final class IsNotNull extends BaseOneValueComparision
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(null, ErrorCodes::SCALAR_NOT_EQUALS, [ContextKeys::SCALAR_VALUE => null]);
    }

    /**
     * @inheritdoc
     */
    public static function compare($value, ContextInterface $context): bool
    {
        $result = $value !== null;

        return $result;
    }
}
