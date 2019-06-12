<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\JsonApi\Execution;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesSerializerInterface;
use Limoncello\Flute\Validation\Serialize\RulesSerializer;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use function array_key_exists;
use function assert;
use function is_array;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JsonApiDataRulesSerializer extends RulesSerializer implements JsonApiDataRulesSerializerInterface
{
    use ClassIsTrait;

    /** Serialized indexes key */
    protected const SERIALIZED_RULES = 0;

    /** Serialized rules key */
    protected const SERIALIZED_BLOCKS = self::SERIALIZED_RULES + 1;

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

    /**
     * @var array
     */
    private $serializedRules = [];

    /**
     * @inheritdoc
     */
    public function addRulesFromClass(string $rulesClass): JsonApiDataRulesSerializerInterface
    {
        assert(static::classImplements($rulesClass, JsonApiDataRulesInterface::class));

        $name = $rulesClass;

        /** @var JsonApiDataRulesInterface $rulesClass */

        return $this->addDataRules(
            $name,
            $rulesClass::getIdRule(),
            $rulesClass::getTypeRule(),
            $rulesClass::getAttributeRules(),
            $rulesClass::getToOneRelationshipRules(),
            $rulesClass::getToManyRelationshipRules()
        );
    }

    /**
     * @inheritdoc
     */
    public function addDataRules(
        string $name,
        RuleInterface $idRule,
        RuleInterface $typeRule,
        array $attributeRules,
        array $toOneRules,
        array $toManyRules
    ): JsonApiDataRulesSerializerInterface {
        $idRule->setName(DocumentInterface::KEYWORD_ID)->enableCapture();
        $typeRule->setName(DocumentInterface::KEYWORD_TYPE)->enableCapture();

        $ruleSet = [
            static::ID_SERIALIZED         => $this->addRule($idRule),
            static::TYPE_SERIALIZED       => $this->addRule($typeRule),
            static::ATTRIBUTES_SERIALIZED => $this->addRules($attributeRules),
            static::TO_ONE_SERIALIZED     => $this->addRules($toOneRules),
            static::TO_MANY_SERIALIZED    => $this->addRules($toManyRules),
        ];

        $this->serializedRules[$name] = $ruleSet;

        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getData(): array
    {
        return [
            static::SERIALIZED_RULES  => $this->serializedRules,
            static::SERIALIZED_BLOCKS => static::getBlocks(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function readRules(string $rulesClass, array $serializedData): array
    {
        assert(static::hasRules($rulesClass, $serializedData));

        $indexes = $serializedData[static::SERIALIZED_RULES][$rulesClass];

        return $indexes;
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
    public static function readBlocks(array $serializedData): array
    {
        assert(array_key_exists(static::SERIALIZED_BLOCKS, $serializedData));
        $serializedRules = $serializedData[static::SERIALIZED_BLOCKS];
        assert(is_array($serializedRules));

        return $serializedRules;
    }

    /**
     * @inheritdoc
     */
    public static function readIdRuleIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::ID_SERIALIZED, $serializedRules));
        $rule = $serializedRules[static::ID_SERIALIZED];
        assert(is_array($rule));

        return $rule;
    }

    /**
     * @inheritdoc
     */
    public static function readTypeRuleIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::TYPE_SERIALIZED, $serializedRules));
        $rule = $serializedRules[static::TYPE_SERIALIZED];
        assert(is_array($rule));

        return $rule;
    }

    /**
     * @inheritdoc
     */
    public static function readAttributeRulesIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::ATTRIBUTES_SERIALIZED, $serializedRules));
        $rules = $serializedRules[static::ATTRIBUTES_SERIALIZED];
        assert(is_array($rules));

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public static function readToOneRulesIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::TO_ONE_SERIALIZED, $serializedRules));
        $rules = $serializedRules[static::TO_ONE_SERIALIZED];
        assert(is_array($rules));

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public static function readToManyRulesIndexes(array $serializedRules): array
    {
        assert(array_key_exists(static::TO_MANY_SERIALIZED, $serializedRules));
        $rules = $serializedRules[static::TO_MANY_SERIALIZED];
        assert(is_array($rules));

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public static function readRuleIndex(array $ruleIndexes): int
    {
        return parent::getRuleIndex($ruleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRuleStartIndexes(array $ruleIndexes): array
    {
        return parent::getRuleStartIndexes($ruleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRuleEndIndexes(array $ruleIndexes): array
    {
        return parent::getRuleEndIndexes($ruleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRulesIndexes(array $arrayRuleIndexes): array
    {
        return parent::getRulesIndexes($arrayRuleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRulesStartIndexes(array $arrayRuleIndexes): array
    {
        return parent::getRulesStartIndexes($arrayRuleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readRulesEndIndexes(array $arrayRuleIndexes): array
    {
        return parent::getRulesEndIndexes($arrayRuleIndexes);
    }

    /**
     * @inheritdoc
     */
    public static function readSingleRuleIndexes(array $arrayRuleIndexes, string $name): array
    {
        return parent::geSingleRuleIndexes($arrayRuleIndexes, $name);
    }

    /**
     * @inheritdoc
     */
    public static function hasRule(int $index, array $blocks): bool
    {
        $result = array_key_exists($index, $blocks);

        return $result;
    }
}
