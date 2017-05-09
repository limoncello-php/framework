<?php namespace Limoncello\Validation\Validator;

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

use Limoncello\Validation\Captures\MultipleCaptures;
use Limoncello\Validation\Captures\SingleCapture;
use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @package Limoncello\Validation
 */
trait Captures
{
    /**
     * @param string                     $name
     * @param RuleInterface              $rule
     * @param CaptureAggregatorInterface $aggregator
     *
     * @return RuleInterface
     */
    protected static function singleCapture(
        string $name,
        RuleInterface $rule,
        CaptureAggregatorInterface $aggregator
    ): RuleInterface {
        return new SingleCapture($name, $rule, $aggregator);
    }

    /**
     * @param string                     $name
     * @param RuleInterface              $rule
     * @param CaptureAggregatorInterface $aggregator
     *
     * @return RuleInterface
     */
    protected static function multiCapture(
        string $name,
        RuleInterface $rule,
        CaptureAggregatorInterface $aggregator
    ): RuleInterface {
        return new MultipleCaptures($name, $rule, $aggregator);
    }
}
