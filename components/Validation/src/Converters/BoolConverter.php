<?php namespace Limoncello\Validation\Converters;

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

use Limoncello\Validation\Contracts\MessageCodes;

/**
 * @package Limoncello\Validation
 */
class BoolConverter extends BaseConverter
{
    use SimpleConverterTrait;

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function convert($input): bool
    {
        $result = is_scalar($input) === true;
        if ($result === true) {
            if (is_string($input) === true) {
                $value = (
                    strcasecmp($input, 'true') === 0 ?
                        true :
                        (strcasecmp($input, 'false') === 0 ? false : (bool)$input)
                );
            } else {
                $value = (bool)$input;
            }

            $this->setConverted($value);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getErrorCode(): int
    {
        return MessageCodes::IS_BOOL;
    }
}
