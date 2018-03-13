<?php namespace Limoncello\Flute\Validation\Serialize;

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
class RulesSerializer
{
    // Single rule serialization keys

    /** Index key */
    protected const RULES_SINGLE_INDEX = 0;

    /** Index key */
    protected const RULES_SINGLE_START_INDEXES = self::RULES_SINGLE_INDEX + 1;

    /** Index key */
    protected const RULES_SINGLE_END_INDEXES = self::RULES_SINGLE_START_INDEXES + 1;

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
     * @param RuleInterface $rule
     *
     * @return array
     */
    public function addRule(RuleInterface $rule): array
    {
        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        $result = [
            static::RULES_SINGLE_INDEX         => $this->getSerializer()->addBlock($rule->toBlock()),
            static::RULES_SINGLE_START_INDEXES => $this->getSerializer()->getBlocksWithStart(),
            static::RULES_SINGLE_END_INDEXES   => $this->getSerializer()->getBlocksWithEnd(),
        ];

        return $result;
    }

    /**
     * @param BlockSerializerInterface $blockSerializer
     */
    public function __construct(BlockSerializerInterface $blockSerializer)
    {
        $this->blockSerializer = $blockSerializer;
    }

    /**
     * @param RuleInterface[] $rules
     *
     * @return array
     */
    public function addRules(array $rules): array
    {
        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        $indexes = [];
        foreach ($rules as $name => $rule) {
            assert($rule instanceof RuleInterface);

            $ruleName = $rule->getName();
            if (empty($ruleName) === true) {
                $ruleName = $name;
            }

            $block          = $rule->setName($ruleName)->enableCapture()->toBlock();
            $indexes[$name] = $this->getSerializer()->addBlock($block);
        }

        $ruleIndexes = [
            static::RULES_ARRAY_INDEXES       => $indexes,
            static::RULES_ARRAY_START_INDEXES => $this->getSerializer()->getBlocksWithStart(),
            static::RULES_ARRAY_END_INDEXES   => $this->getSerializer()->getBlocksWithEnd(),
        ];

        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        return $ruleIndexes;
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getBlocks(): array
    {
        $blocks = BlockSerializer::unserializeBlocks($this->getSerializer()->get());

        return $blocks;
    }

    /**
     * @param array $singleRuleIndexes
     *
     * @return int
     */
    public static function getRuleIndex(array $singleRuleIndexes): int
    {
        assert(array_key_exists(static::RULES_SINGLE_INDEX, $singleRuleIndexes));
        $result = $singleRuleIndexes[static::RULES_SINGLE_INDEX];

        return $result;
    }

    /**
     * @param array $singleRuleIndexes
     *
     * @return array
     */
    public static function getRuleStartIndexes(array $singleRuleIndexes): array
    {
        assert(array_key_exists(static::RULES_SINGLE_START_INDEXES, $singleRuleIndexes));
        $result = $singleRuleIndexes[static::RULES_SINGLE_START_INDEXES];

        return $result;
    }

    /**
     * @param array $singleRuleIndexes
     *
     * @return array
     */
    public static function getRuleEndIndexes(array $singleRuleIndexes): array
    {
        assert(array_key_exists(static::RULES_SINGLE_END_INDEXES, $singleRuleIndexes));
        $result = $singleRuleIndexes[static::RULES_SINGLE_END_INDEXES];

        return $result;
    }

    /**
     * @param array $arrayRulesIndexes
     *
     * @return array
     */
    public static function getRulesIndexes(array $arrayRulesIndexes): array
    {
        assert(array_key_exists(static::RULES_ARRAY_INDEXES, $arrayRulesIndexes));
        $result = $arrayRulesIndexes[static::RULES_ARRAY_INDEXES];

        return $result;
    }

    /**
     * @param array $arrayRulesIndexes
     *
     * @return array
     */
    public static function getRulesStartIndexes(array $arrayRulesIndexes): array
    {
        assert(array_key_exists(static::RULES_ARRAY_START_INDEXES, $arrayRulesIndexes));
        $result = $arrayRulesIndexes[static::RULES_ARRAY_START_INDEXES];

        return $result;
    }

    /**
     * @param array $arrayRulesIndexes
     *
     * @return array
     */
    public static function getRulesEndIndexes(array $arrayRulesIndexes): array
    {
        assert(array_key_exists(static::RULES_ARRAY_END_INDEXES, $arrayRulesIndexes));
        $result = $arrayRulesIndexes[static::RULES_ARRAY_END_INDEXES];

        return $result;
    }

    /**
     * @return BlockSerializerInterface
     */
    protected function getSerializer(): BlockSerializerInterface
    {
        return $this->blockSerializer;
    }
}
