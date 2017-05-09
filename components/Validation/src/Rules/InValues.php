<?php namespace Limoncello\Validation\Rules;

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

use Generator;
use Limoncello\Validation\Contracts\MessageCodes;

/**
 * @package Limoncello\Validation
 */
class InValues extends BaseRule
{
    /** Error context key */
    const CONTEXT_VALUES = 0;

    /**
     * @var array
     */
    private $values;

    /**
     * @var bool
     */
    private $isStrict;

    /**
     * @param array $values
     * @param bool  $isStrict
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(array $values, bool $isStrict = true)
    {
        $this->values   = $values;
        $this->isStrict = $isStrict;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        if (in_array($input, $this->values, $this->isStrict) === false) {
            $context = [static::CONTEXT_VALUES => $this->values];
            yield $this->createError($this->getParameterName(), $input, MessageCodes::IN_VALUES, $context);
        }
    }
}
