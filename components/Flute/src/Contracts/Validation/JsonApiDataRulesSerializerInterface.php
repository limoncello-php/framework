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
interface JsonApiDataRulesSerializerInterface
{
    /**
     * Add rules from data rules class.
     *
     * @param string $rulesClass
     *
     * @return self
     */
    public function addRulesFromClass(string $rulesClass): self;

    /**
     * Add rules manually.
     *
     * @param string          $name
     * @param RuleInterface   $idRule
     * @param RuleInterface   $typeRule
     * @param RuleInterface[] $attributeRules
     * @param RuleInterface[] $toOneRules
     * @param RuleInterface[] $toManyRules
     *
     * @return self
     */
    public function addDataRules(
        string $name,
        RuleInterface $idRule,
        RuleInterface $typeRule,
        array $attributeRules,
        array $toOneRules,
        array $toManyRules
    ): JsonApiDataRulesSerializerInterface;

    /**
     * Get serialized data.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * @param array $serializedData
     *
     * @return array
     */
    public static function readBlocks(array $serializedData): array;

    /**
     * @param string $name
     * @param array  $serializedData
     *
     * @return bool
     */
    public static function hasRules(string $name, array $serializedData): bool;

    /**
     * @param string $rulesClass
     * @param array  $serializedData
     *
     * @return array
     */
    public static function readRules(string $rulesClass, array $serializedData): array;

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readIdRuleIndexes(array $serializedRules): array;

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readTypeRuleIndexes(array $serializedRules): array;

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readAttributeRulesIndexes(array $serializedRules): array;

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readToOneRulesIndexes(array $serializedRules): array;

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function readToManyRulesIndexes(array $serializedRules): array;

    /**
     * @param array $ruleIndexes
     *
     * @return int
     */
    public static function readRuleIndex(array $ruleIndexes): int;

    /**
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleStartIndexes(array $ruleIndexes): array;

    /**
     * @param array $ruleIndexes
     *
     * @return array
     */
    public static function readRuleEndIndexes(array $ruleIndexes): array;

    /**
     * @param array $arrayRuleIndexes
     *
     * @return array
     */
    public static function readRulesIndexes(array $arrayRuleIndexes): array;

    /**
     * @param array $arrayRuleIndexes
     *
     * @return array
     */
    public static function readRulesStartIndexes(array $arrayRuleIndexes): array;

    /**
     * @param array $arrayRuleIndexes
     *
     * @return array
     */
    public static function readRulesEndIndexes(array $arrayRuleIndexes): array;

    /**
     * @param int   $index
     * @param array $blocks
     *
     * @return bool
     */
    public static function hasRule(int $index, array $blocks): bool;
}
