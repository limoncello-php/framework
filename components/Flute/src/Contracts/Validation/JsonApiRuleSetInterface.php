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
 * @package Limoncello\Flute
 */
interface JsonApiRuleSetInterface
{
    /**
     * @return RuleInterface
     */
    public static function getTypeRule(): RuleInterface;

    /**
     * @return RuleInterface
     */
    public static function getIdRule(): RuleInterface;

    /**
     * @return RuleInterface[]
     */
    public static function getAttributeRules(): array;

    /**
     * @return RuleInterface[]
     */
    public static function getToOneRelationshipRules(): array;

    /**
     * @return RuleInterface[]
     */
    public static function getToManyRelationshipRules(): array;
}
