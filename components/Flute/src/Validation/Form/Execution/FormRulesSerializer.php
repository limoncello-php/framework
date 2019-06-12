<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\Form\Execution;

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
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;
use Limoncello\Flute\Contracts\Validation\FormRulesSerializerInterface;
use Limoncello\Flute\Validation\Serialize\RulesSerializer;
use function array_key_exists;
use function assert;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormRulesSerializer extends RulesSerializer implements FormRulesSerializerInterface
{
    use ClassIsTrait;

    /**
     * @var array
     */
    private $serializedRules = [];

    /** Serialized indexes key */
    protected const SERIALIZED_RULES = 0;

    /** Serialized rules key */
    protected const SERIALIZED_BLOCKS = self::SERIALIZED_RULES + 1;

    /**
     * @inheritdoc
     */
    public function addRulesFromClass(string $rulesClass): FormRulesSerializerInterface
    {
        assert(static::classImplements($rulesClass, FormRulesInterface::class));

        $name = $rulesClass;

        /** @var FormRulesInterface $rulesClass */

        return $this->addFormRules($name, $rulesClass::getAttributeRules());
    }

    /**
     * @inheritdoc
     */
    public function addFormRules(string $name, ?array $attributeRules): FormRulesSerializerInterface
    {
        assert(!empty($name));
        assert(static::hasRules($name, $this->serializedRules) === false);

        $this->serializedRules[$name] = $attributeRules === null ? null : $this->addRules($attributeRules);

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
    public static function readRules(string $rulesClass, array $serializedData): array
    {
        assert(static::hasRules($rulesClass, $serializedData) === true);

        return $serializedData[static::SERIALIZED_RULES][$rulesClass];
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
}
