<?php namespace Limoncello\Validation\Rules;

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

use DateTime;
use Generator;
use Limoncello\Validation\Contracts\MessageCodes;

/**
 * @package Limoncello\Validation
 */
class IsDateTimeFormat extends BaseRule
{
    /** Error context key */
    const CONTEXT_FORMAT = 0;

    /**
     * @var string
     */
    private $format;

    /**
     * @param string $format
     */
    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function validate($input): Generator
    {
        if (DateTime::createFromFormat($this->format, (string)$input) === false) {
            $context = [static::CONTEXT_FORMAT => $this->format];
            yield $this->createError($this->getParameterName(), $input, MessageCodes::IS_DATE_TIME_FORMAT, $context);
        }
    }
}
