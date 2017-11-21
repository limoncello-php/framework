<?php namespace Limoncello\Flute\Validation\Form\Execution;

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

/**
 * @package Limoncello\Flute
 */
class AttributeRulesSerializer
{
    /** Serialized indexes key */
    protected const SERIALIZED_RULE_SETS = 0;

    /** Serialized rules key */
    protected const SERIALIZED_BLOCKS = self::SERIALIZED_RULE_SETS + 1;

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

    /**
     * @param string          $name
     * @param RuleInterface[] $attributeRules
     *
     * @return self
     */
    public function addResourceRules(string $name, array $attributeRules): self
    {
        assert(!empty($name), 'Rule set name cannot be empty.');
        assert(!array_key_exists($name, $this->ruleSets), "A rule set with name `$name` has been added already.");

        $this->ruleSets[$name] = $this->serializeRulesArray($attributeRules);

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
    public static function getAttributeRules(string $name, array $data): array
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
