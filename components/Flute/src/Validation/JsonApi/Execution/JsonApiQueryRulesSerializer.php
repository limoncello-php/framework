<?php namespace Limoncello\Flute\Validation\JsonApi\Execution;

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

use Limoncello\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesSerializerInterface;
use Limoncello\Flute\Validation\Serialize\RulesSerializer;
use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JsonApiQueryRulesSerializer extends RulesSerializer implements JsonApiQueryRulesSerializerInterface
{
    /**
     * @var array
     */
    private $serializedRules = [];

    /** Index key */
    protected const IDENTITY_RULE= 0;

    /** Index key */
    protected const FILTER_RULES = self::IDENTITY_RULE + 1;

    /** Index key */
    protected const FIELD_SET_RULES = self::FILTER_RULES + 1;

    /** Index key */
    protected const SORTS_RULE = self::FIELD_SET_RULES + 1;

    /** Index key */
    protected const INCLUDES_RULE = self::SORTS_RULE + 1;

    /** Index key */
    protected const PAGE_OFFSET_RULE = self::INCLUDES_RULE + 1;

    /** Index key */
    protected const PAGE_LIMIT_RULE = self::PAGE_OFFSET_RULE + 1;

    /** Index key */
    protected const SINGLE_RULE_INDEX = 0;

    /** Serialized indexes key */
    protected const SERIALIZED_RULES = 0;

    /** Serialized rules key */
    protected const SERIALIZED_BLOCKS = self::SERIALIZED_RULES + 1;

    /**
     * @inheritdoc
     */
    public function addRulesFromClass(string $rulesClass): JsonApiQueryRulesSerializerInterface
    {
        assert(static::isRulesClass($rulesClass));

        $name = $rulesClass;

        /** @var JsonApiQueryRulesInterface $rulesClass */

        return $this->addQueryRules(
            $name,
            $rulesClass::getIdentityRule(),
            $rulesClass::getFilterRules(),
            $rulesClass::getFieldSetRules(),
            $rulesClass::getSortsRule(),
            $rulesClass::getIncludesRule(),
            $rulesClass::getPageOffsetRule(),
            $rulesClass::getPageLimitRule()
        );
    }

    /** @noinspection PhpTooManyParametersInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addQueryRules(
        string $name,
        ?RuleInterface $identityRule,
        ?array $filterRules,
        ?array $fieldSetRules,
        ?RuleInterface $sortsRule,
        ?RuleInterface $includesRule,
        ?RuleInterface $pageOffsetRule,
        ?RuleInterface $pageLimitRule
    ): JsonApiQueryRulesSerializerInterface {
        assert(!empty($name));
        assert(static::hasRules($name, $this->serializedRules) === false);

        $identityRule === null ?: $identityRule->setName(JsonApiQueryParserInterface::PARAM_IDENTITY);
        $sortsRule === null ?: $sortsRule->setName(JsonApiQueryParserInterface::PARAM_SORT);
        $includesRule === null ?: $includesRule->setName(JsonApiQueryParserInterface::PARAM_INCLUDE);
        $pageOffsetRule === null ?: $pageOffsetRule->setName(JsonApiQueryParserInterface::PARAM_PAGE);
        $pageLimitRule === null ?: $pageLimitRule->setName(JsonApiQueryParserInterface::PARAM_PAGE);

        $this->serializedRules[$name] = [
            static::IDENTITY_RULE     =>
                $identityRule === null ? null : $this->addRules([static::SINGLE_RULE_INDEX => $identityRule]),
            static::FILTER_RULES     =>
                $filterRules === null ? null : $this->addRules($filterRules),
            static::FIELD_SET_RULES  =>
                $fieldSetRules === null ? null : $this->addRules($fieldSetRules),
            static::SORTS_RULE       =>
                $sortsRule === null ? null : $this->addRules([static::SINGLE_RULE_INDEX => $sortsRule]),
            static::INCLUDES_RULE    =>
                $includesRule === null ? null : $this->addRules([static::SINGLE_RULE_INDEX => $includesRule]),
            static::PAGE_OFFSET_RULE =>
                $pageOffsetRule === null ? null : $this->addRules([static::SINGLE_RULE_INDEX => $pageOffsetRule]),
            static::PAGE_LIMIT_RULE  =>
                $pageLimitRule === null ? null : $this->addRules([static::SINGLE_RULE_INDEX => $pageLimitRule]),
        ];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return [
            static::SERIALIZED_RULES  => $this->serializedRules,
            static::SERIALIZED_BLOCKS => $this->getBlocks(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function readBlocks(array $serializedData): array
    {
        return $serializedData[static::SERIALIZED_BLOCKS];
    }

    /**
     * @inheritdoc
     */
    public static function hasRules(string $name, array $serializedData): bool
    {
        // the value could be null so we have to check by key existence.
        return
            array_key_exists(static::SERIALIZED_RULES, $serializedData) === true &&
            array_key_exists($name, $serializedData[static::SERIALIZED_RULES]);
    }

    /**
     * @inheritdoc
     */
    public static function readRules(string $name, array $serializedData): array
    {
        assert(static::hasRules($name, $serializedData) === true);

        return $serializedData[static::SERIALIZED_RULES][$name];
    }

    /**
     * @inheritdoc
     */
    public static function readIdentityRuleIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::IDENTITY_RULE];
    }

    /**
     * @inheritdoc
     */
    public static function readFilterRulesIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::FILTER_RULES];
    }

    /**
     * @inheritdoc
     */
    public static function readFieldSetRulesIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::FIELD_SET_RULES];
    }

    /**
     * @inheritdoc
     */
    public static function readSortsRuleIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::SORTS_RULE];
    }

    /**
     * @inheritdoc
     */
    public static function readIncludesRuleIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::INCLUDES_RULE];
    }

    /**
     * @inheritdoc
     */
    public static function readPageOffsetRuleIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::PAGE_OFFSET_RULE];
    }

    /**
     * @inheritdoc
     */
    public static function readPageLimitRuleIndexes(array $serializedRules): ?array
    {
        return $serializedRules[static::PAGE_LIMIT_RULE];
    }

    /**
     * @inheritdoc
     */
    public static function readRuleMainIndexes(array $ruleIndexes): ?array
    {
        return parent::getRulesIndexes($ruleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRuleMainIndex(array $ruleIndexes): ?int
    {
        // if you read main/first/only rule and blocks actually have more then something must be wrong
        assert(count($ruleIndexes[static::RULES_ARRAY_INDEXES]) === 1);

        return $ruleIndexes[static::RULES_ARRAY_INDEXES][static::SINGLE_RULE_INDEX];
    }

    /**
     * @inheritdoc
     */
    public static function readRuleStartIndexes(array $ruleIndexes): array
    {
        return parent::getRulesStartIndexes($ruleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRuleEndIndexes(array $ruleIndexes): array
    {
        return parent::getRulesEndIndexes($ruleIndexes);
    }

    /**
     * @param string $rulesClass
     *
     * @return bool
     */
    private static function isRulesClass(string $rulesClass): bool
    {
        return
            class_exists($rulesClass) === true &&
            in_array(JsonApiQueryRulesInterface::class, class_implements($rulesClass)) === true;
    }
}
