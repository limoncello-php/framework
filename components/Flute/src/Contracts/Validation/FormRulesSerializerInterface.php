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
interface FormRulesSerializerInterface
{
    /**
     * Add rules from form rules class.
     *
     * @param string $rulesClass
     *
     * @return self
     */
    public function addRulesFromClass(string $rulesClass): self;

    /**
     * Add rules manually.
     *
     * @param string               $name
     * @param RuleInterface[]|null $attributeRules
     *
     * @return self
     */
    public function addFormRules(string $name, ?array $attributeRules): FormRulesSerializerInterface;

    /**
     * Get serialized data.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Reads validation blocks from serialized data.
     *
     * @param array $serializedData
     *
     * @return array
     */
    public static function readBlocks(array $serializedData): array;

    /**
     * Reads serialized validation rules from serialized data.
     *
     * @param string $name
     * @param array  $serializedData
     *
     * @return bool
     */
    public static function hasRules(string $name, array $serializedData): bool;

    /**
     * Reads serialized validation rules from serialized data.
     *
     * @param string $rulesClass
     * @param array  $serializedData
     *
     * @return array
     */
    public static function readRules(string $rulesClass, array $serializedData): array;

    /**
     * Read rule indexes from serialized rule indexes.
     *
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleMainIndexes(array $ruleIndexes): ?array;

    /**
     * Read rule start indexes from serialized rule indexes.
     *
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleStartIndexes(array $ruleIndexes): array;

    /**
     * Read rule end indexes from serialized rule indexes.
     *
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleEndIndexes(array $ruleIndexes): array;
}
