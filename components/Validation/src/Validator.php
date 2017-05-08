<?php namespace Limoncello\Validation;

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

use Generator;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Contracts\ValidatorInterface;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Validator\Captures as CapturesX;
use Limoncello\Validation\Validator\Compares;
use Limoncello\Validation\Validator\Converters as ConvertersX;
use Limoncello\Validation\Validator\ExpressionsX;
use Limoncello\Validation\Validator\Generics;
use Limoncello\Validation\Validator\Types;
use Limoncello\Validation\Validator\ValidatorTrait;
use Limoncello\Validation\Validator\Values;
use Limoncello\Validation\Validator\Wrappers;

/**
 * @package Limoncello\Validation
 */
class Validator implements ValidatorInterface
{
    use ExpressionsX {
        andX as public;
        orX as public;
        ifX as public;
        arrayX as public;
        eachX as public;
        objectX as public;
        callableX as public;
    }

    use Generics {
        success as public;
        fail as public;
    }

    use Types {
        isString as public;
        isBool as public;
        isInt as public;
        isFloat as public;
        isNumeric as public;
        isDateTime as public;
        isDateTimeFormat as public;
        isArray as public;
        inValues as public;
    }

    use Values {
        isRequired as public;
        isNull as public;
        notNull as public;
        regExp as public;
        between as public;
        stringLength as public;
    }

    use Compares {
        equals as public;
        notEquals as public;
        lessThan as public;
        lessOrEquals as public;
        moreThan as public;
        moreOrEquals as public;
    }

    use CapturesX {
        singleCapture as public;
        multiCapture as public;
    }

    use ConvertersX {
        toBool as public;
        toDateTime as public;
        toFloat as public;
        toInt as public;
        toString as public;
    }

    use Wrappers {
        nullable as public;
        required as public;
    }

    use ValidatorTrait;

    /**
     * @var RuleInterface
     */
    private $rule;

    /**
     * @param RuleInterface $rule
     */
    public function __construct(RuleInterface $rule)
    {
        $this->rule = $rule;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        foreach (static::validateData($this->rule, $input, $this->createErrorAggregator()) as $error) {
            yield $error;
        }
    }

    /**
     * @param RuleInterface $rule
     *
     * @return ValidatorInterface
     */
    public static function validator(RuleInterface $rule): ValidatorInterface
    {
        return new static ($rule);
    }

    /**
     * @return ErrorAggregatorInterface
     */
    protected function createErrorAggregator(): ErrorAggregatorInterface
    {
        return new ErrorAggregator();
    }
}
