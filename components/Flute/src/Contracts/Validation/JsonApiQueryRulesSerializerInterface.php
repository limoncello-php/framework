<?php namespace Limoncello\Flute\Contracts\Validation;

/**
 * Copyright 2015-2018 info@neomerx.com
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
interface JsonApiQueryRulesSerializerInterface
{
    /**
     * Add rules from query rules class.
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
     * @param RuleInterface[]|null $filterRules
     * @param RuleInterface[]|null $fieldSetRules
     * @param RuleInterface|null   $sortsRule
     * @param RuleInterface|null   $includesRule
     * @param RuleInterface|null   $pageOffsetRule
     * @param RuleInterface|null   $pageLimitRule
     *
     * @return self
     */
    public function addQueryRules(
        string $name,
        ?array $filterRules,
        ?array $fieldSetRules,
        ?RuleInterface $sortsRule,
        ?RuleInterface $includesRule,
        ?RuleInterface $pageOffsetRule,
        ?RuleInterface $pageLimitRule
    ): JsonApiQueryRulesSerializerInterface;

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
     * @param string $name
     * @param array  $serializedData
     *
     * @return array
     */
    public static function readRules(string $name, array $serializedData): array;

    /**
     * Reads serialized filter validation rule indexes from rules.
     *
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readFilterRulesIndexes(array $serializedRules): ?array;

    /**
     * Reads serialized field set validation rule indexes from rules.
     *
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readFieldSetRulesIndexes(array $serializedRules): ?array;

    /**
     * Reads serialized sorts validation rule indexes from rules.
     *
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readSortsRuleIndexes(array $serializedRules): ?array;

    /**
     * Reads serialized includes validation rule indexes from rules.
     *
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readIncludesRuleIndexes(array $serializedRules): ?array;

    /**
     * Reads serialized page offset validation rule indexes from rules.
     *
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readPageOffsetRuleIndexes(array $serializedRules): ?array;

    /**
     * Reads serialized page limit validation rule indexes from rules.
     *
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readPageLimitRuleIndexes(array $serializedRules): ?array;

    /**
     * Read rule indexes (key, index pairs) from serialized rules indexes.
     *
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleMainIndexes(array $ruleIndexes): ?array;

    /**
     * Read first rule index from serialized rules indexes.
     *
     * @param array $ruleIndexes
     *
     * @return int
     */
    public static function readRuleMainIndex(array $ruleIndexes): ?int;

    /**
     * Read rule start indexes (key, index pairs) from serialized rules indexes.
     *
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleStartIndexes(array $ruleIndexes): array;

    /**
     * Read rule end indexes (key, index pairs) from serialized rules indexes.
     *
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleEndIndexes(array $ruleIndexes): array;
}
