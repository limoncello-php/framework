<?php namespace Limoncello\Flute\Validation\JsonApi\Execution;

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

use Limoncello\Validation\Contracts\Execution\BlockSerializerInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Execution\BlockSerializer;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * @package Limoncello\Flute
 */
class JsonApiRuleSerializer
{
    /** Serialized indexes key */
    protected const SERIALIZED_RULE_SETS = 0;

    /** Serialized rules key */
    protected const SERIALIZED_BLOCKS = self::SERIALIZED_RULE_SETS + 1;

    /** Index key */
    protected const ID_SERIALIZED = 0;

    /** Index key */
    protected const TYPE_SERIALIZED = self::ID_SERIALIZED + 1;

    /** Index key */
    protected const ATTRIBUTES_SERIALIZED = self::TYPE_SERIALIZED + 1;

    /** Index key */
    protected const TO_ONE_SERIALIZED = self::ATTRIBUTES_SERIALIZED + 1;

    /** Index key */
    protected const TO_MANY_SERIALIZED = self::TO_ONE_SERIALIZED + 1;

    // Single rule serialization keys

    /** Index key */
    protected const SINGLE_RULE_INDEX = 0;

    /** Index key */
    protected const SINGLE_RULE_START_INDEXES = self::SINGLE_RULE_INDEX + 1;

    /** Index key */
    protected const SINGLE_RULE_END_INDEXES = self::SINGLE_RULE_START_INDEXES + 1;

    // Rules array serialization keys

    /** Index key */
    protected const RULES_ARRAY_INDEXES = 0;

    /** Index key */
    protected const RULES_ARRAY_START_INDEXES = self::RULES_ARRAY_INDEXES + 1;

    /** Index key */
    protected const RULES_ARRAY_END_INDEXES = self::RULES_ARRAY_START_INDEXES + 1;

    /**
     * @var BlockSerializerInterface
     */
    private $blockSerializer;

    /**
     * @var array
     */
    private $ruleSets;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->blockSerializer = $this->createBlockSerializer();
        $this->ruleSets        = [];
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string          $name
     * @param RuleInterface   $idRule
     * @param RuleInterface   $typeRule
     * @param RuleInterface[] $attributeRules
     * @param RuleInterface[] $toOneRules
     * @param RuleInterface[] $toManyRules
     *
     * @return self
     */
    public function addResourceRules(
        string $name,
        RuleInterface $idRule,
        RuleInterface $typeRule,
        array $attributeRules,
        array $toOneRules,
        array $toManyRules
    ): self {
        assert(!empty($name));
        assert(!array_key_exists($name, $this->ruleSets));

        $idRule->setName(DocumentInterface::KEYWORD_ID)->enableCapture();
        $typeRule->setName(DocumentInterface::KEYWORD_TYPE)->enableCapture();

        $ruleSet = [
            static::ID_SERIALIZED         => $this->serializeRule($idRule),
            static::TYPE_SERIALIZED       => $this->serializeRule($typeRule),
            static::ATTRIBUTES_SERIALIZED => $this->serializeRulesArray($attributeRules),
            static::TO_ONE_SERIALIZED     => $this->serializeRulesArray($toOneRules),
            static::TO_MANY_SERIALIZED    => $this->serializeRulesArray($toManyRules),
        ];

        $this->ruleSets[$name] = $ruleSet;

        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        return $this;
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getData(): array
    {
        return [
            static::SERIALIZED_RULE_SETS => $this->ruleSets,
            static::SERIALIZED_BLOCKS    => static::getBlocks($this->getSerializer()->get()),
        ];
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return array
     */
    public static function extractRuleSet(string $name, array $data): array
    {
        assert($data[static::SERIALIZED_RULE_SETS] ?? false);
        $indexes = $data[static::SERIALIZED_RULE_SETS][$name];
        assert(is_array($indexes));

        return $indexes;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function extractBlocks(array $data): array
    {
        assert(array_key_exists(static::SERIALIZED_BLOCKS, $data));
        $serializedRules = $data[static::SERIALIZED_BLOCKS];
        assert(is_array($serializedRules));

        return $serializedRules;
    }

    /**
     * @param array $ruleSet
     *
     * @return array
     */
    public static function getIdRule(array $ruleSet): array
    {
        assert(array_key_exists(static::ID_SERIALIZED, $ruleSet));
        $rule = $ruleSet[static::ID_SERIALIZED];
        assert(is_array($rule));

        return $rule;
    }

    /**
     * @param array $ruleSet
     *
     * @return array
     */
    public static function getTypeRule(array $ruleSet): array
    {
        assert(array_key_exists(static::TYPE_SERIALIZED, $ruleSet));
        $rule = $ruleSet[static::TYPE_SERIALIZED];
        assert(is_array($rule));

        return $rule;
    }

    /**
     * @param array $ruleSet
     *
     * @return array
     */
    public static function getAttributeRules(array $ruleSet): array
    {
        assert(array_key_exists(static::ATTRIBUTES_SERIALIZED, $ruleSet));
        $rules = $ruleSet[static::ATTRIBUTES_SERIALIZED];
        assert(is_array($rules));

        return $rules;
    }

    /**
     * @param array $ruleSet
     *
     * @return array
     */
    public static function getToOneRules(array $ruleSet): array
    {
        assert(array_key_exists(static::TO_ONE_SERIALIZED, $ruleSet));
        $rules = $ruleSet[static::TO_ONE_SERIALIZED];
        assert(is_array($rules));

        return $rules;
    }

    /**
     * @param array $ruleSet
     *
     * @return array
     */
    public static function getToManyRules(array $ruleSet): array
    {
        assert(array_key_exists(static::TO_MANY_SERIALIZED, $ruleSet));
        $rules = $ruleSet[static::TO_MANY_SERIALIZED];
        assert(is_array($rules));

        return $rules;
    }

    /**
     * @param array $serializedRule
     *
     * @return int
     */
    public static function getRuleIndex(array $serializedRule): int
    {
        assert(array_key_exists(static::SINGLE_RULE_INDEX, $serializedRule));
        $result = $serializedRule[static::SINGLE_RULE_INDEX];
        assert(is_int($result));

        return $result;
    }

    /**
     * @param array $serializedRule
     *
     * @return array
     */
    public static function getRuleStartIndexes(array $serializedRule): array
    {
        assert(array_key_exists(static::SINGLE_RULE_START_INDEXES, $serializedRule));
        $result = $serializedRule[static::SINGLE_RULE_START_INDEXES];
        assert(is_array($result));

        return $result;
    }

    /**
     * @param array $serializedRule
     *
     * @return array
     */
    public static function getRuleEndIndexes(array $serializedRule): array
    {
        assert(array_key_exists(static::SINGLE_RULE_END_INDEXES, $serializedRule));
        $result = $serializedRule[static::SINGLE_RULE_END_INDEXES];
        assert(is_array($result));

        return $result;
    }

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function getRulesIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::RULES_ARRAY_INDEXES, $serializedRules));
        $result = $serializedRules[static::RULES_ARRAY_INDEXES];
        assert(is_array($result));

        return $result;
    }

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function getRulesStartIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::RULES_ARRAY_START_INDEXES, $serializedRules));
        $result = $serializedRules[static::RULES_ARRAY_START_INDEXES];
        assert(is_array($result));

        return $result;
    }

    /**
     * @param array $serializedRules
     *
     * @return array
     */
    public static function getRulesEndIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::RULES_ARRAY_END_INDEXES, $serializedRules));
        $result = $serializedRules[static::RULES_ARRAY_END_INDEXES];
        assert(is_array($result));

        return $result;
    }

    /**
     * @param int   $index
     * @param array $blocks
     *
     * @return bool
     */
    public static function isRuleExist(int $index, array $blocks): bool
    {
        $result = array_key_exists($index, $blocks);

        return $result;
    }

    /**
     * @param array $serializedRules
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected static function getBlocks(array $serializedRules): array
    {
        $blocks = BlockSerializer::unserializeBlocks($serializedRules);

        return $blocks;
    }

    /**
     * @return BlockSerializerInterface
     */
    protected function getSerializer(): BlockSerializerInterface
    {
        return $this->blockSerializer;
    }

    /**
     * @return BlockSerializerInterface
     */
    protected function createBlockSerializer(): BlockSerializerInterface
    {
        return new BlockSerializer();
    }

    /**
     * @param RuleInterface $rule
     *
     * @return array
     */
    private function serializeRule(RuleInterface $rule): array
    {
        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        $result = [
            static::SINGLE_RULE_INDEX         => $this->getSerializer()->addBlock($rule->toBlock()),
            static::SINGLE_RULE_START_INDEXES => $this->getSerializer()->getBlocksWithStart(),
            static::SINGLE_RULE_END_INDEXES   => $this->getSerializer()->getBlocksWithEnd(),
        ];

        return $result;
    }

    /**
     * @param RuleInterface[] $rules
     *
     * @return array
     */
    private function serializeRulesArray(array $rules): array
    {
        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        $indexes = [];
        foreach ($rules as $name => $rule) {
            assert(is_string($name) === true && empty($name) === false);
            assert($rule instanceof RuleInterface);

            $block          = $rule->setName($name)->enableCapture()->toBlock();
            $indexes[$name] = $this->getSerializer()->addBlock($block);
        }

        return [
            static::RULES_ARRAY_INDEXES       => $indexes,
            static::RULES_ARRAY_START_INDEXES => $this->getSerializer()->getBlocksWithStart(),
            static::RULES_ARRAY_END_INDEXES   => $this->getSerializer()->getBlocksWithEnd(),
        ];
    }
}
