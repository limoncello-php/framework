<?php declare(strict_types=1);

namespace Sample\Validation;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use function is_int;

/**
 * @package Sample
 */
class IsSkuRule extends ExecuteRule
{
    /** @var string Message Template */
    const MESSAGE_TEMPLATE = 'The value should be a valid SKU.';

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function execute($value, ContextInterface $context): array
    {
        $idExists = is_int($value) === true && $value < 3;

        return $idExists === true ?
            static::createSuccessReply($value) :
            static::createErrorReply(
                $context,
                $value,
                Errors::IS_VALID_SKU,
                static::MESSAGE_TEMPLATE,
                []
            );
    }
}
