<?php namespace Limoncello\Flute\Contracts\Validation;

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

use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Limoncello\Application
 */
interface JsonApiQueryRulesInterface
{
    /**
     * @return RuleInterface[]|null
     */
    public static function getFilterRules(): ?array;

    /**
     * @return RuleInterface[]|null
     */
    public static function getFieldSetRules(): ?array;

    /**
     * @return RuleInterface|null
     */
    public static function getSortsRule(): ?RuleInterface;

    /**
     * @return RuleInterface|null
     */
    public static function getIncludesRule(): ?RuleInterface;

    /**
     * @return RuleInterface|null
     */
    public static function getPageOffsetRule(): ?RuleInterface;

    /**
     * @return RuleInterface|null
     */
    public static function getPageLimitRule(): ?RuleInterface;
}
