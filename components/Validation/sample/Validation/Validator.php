<?php namespace Sample\Validation;

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

use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Validator as v;
use Limoncello\Validation\Validator as BaseValidator;

/**
 * @package Sample
 */
class Validator extends BaseValidator
{
    /**
     * @return RuleInterface
     */
    public static function isEmail()
    {
        $condition = function ($input) {
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
        };

        return v::andX(v::isString(), v::ifX($condition, v::success(), v::fail(Translator::IS_EMAIL)));
    }

    /**
     * @param int $maxLength
     *
     * @return RuleInterface
     */
    public static function isRequiredString($maxLength)
    {
        return v::andX(v::isString(), v::andX(v::isRequired(), v::stringLength(1, $maxLength)));
    }

    /**
     * @param int $maxLength
     *
     * @return RuleInterface
     */
    public static function isNullOrNonEmptyString($maxLength)
    {
        return v::ifX('is_null', v::success(), v::andX(v::isString(), v::stringLength(1, $maxLength)));
    }

    /**
     * @return RuleInterface
     */
    public static function isExistingPaymentPlan()
    {
        // emulate database request
        $existsInDatabase = function ($recordId) {
            return $recordId < 3;
        };

        return v::callableX($existsInDatabase, Translator::IS_EXISTING_PAYMENT_PLAN);
    }

    /**
     * @return RuleInterface
     */
    public static function isListOfStrings()
    {
        return v::eachX(v::andX(v::isString(), v::stringLength(1)));
    }
}
