<?php namespace Limoncello\Validation\Validator;

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
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Rules\Fail;
use Limoncello\Validation\Rules\Success;

/**
 * @package Limoncello\Validation
 */
trait Generics
{
    /**
     * @return RuleInterface
     */
    protected static function success(): RuleInterface
    {
        return new Success();
    }

    /**
     * @param int $messageCode
     *
     * @return RuleInterface
     */
    protected static function fail(int $messageCode = MessageCodes::INVALID_VALUE): RuleInterface
    {
        return new Fail($messageCode);
    }
}
