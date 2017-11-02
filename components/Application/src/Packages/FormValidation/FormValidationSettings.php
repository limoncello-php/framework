<?php namespace Limoncello\Application\Packages\FormValidation;

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

use Generator;
use Limoncello\Application\Contracts\Validation\FormRuleSetInterface;
use Limoncello\Application\FormValidation\Execution\FormRuleSerializer;
use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * @package Limoncello\Application
 */
abstract class FormValidationSettings implements SettingsInterface
{
    /**
     * @param string $path
     * @param string $implementClassName
     *
     * @return Generator
     */
    abstract protected function selectClasses(string $path, string $implementClassName): Generator;

    /** Settings key */
    const KEY_VALIDATORS_FOLDER = 0;

    /** Config key */
    const KEY_VALIDATORS_FILE_MASK = self::KEY_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_VALIDATION_RULE_SETS_DATA = self::KEY_VALIDATORS_FILE_MASK + 1;

    /** Settings key */
    protected const KEY_LAST = self::KEY_VALIDATION_RULE_SETS_DATA;

    /**
     * @inheritdoc
     */
    final public function get(): array
    {
        $defaults = $this->getSettings();

        $validatorsFolder   = $defaults[static::KEY_VALIDATORS_FOLDER] ?? null;
        $validatorsFileMask = $defaults[static::KEY_VALIDATORS_FILE_MASK] ?? null;
        $validatorsPath     = $validatorsFolder . DIRECTORY_SEPARATOR . $validatorsFileMask;

        assert(
            $validatorsFolder !== null && empty(glob($validatorsFolder)) === false,
            "Invalid Validators folder `$validatorsFolder`."
        );


        return $defaults + [
                static::KEY_VALIDATION_RULE_SETS_DATA => $this->createValidationRulesSetData($validatorsPath),
            ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_VALIDATORS_FILE_MASK => '*.php',
        ];
    }

    /**
     * @param string $validatorsPath
     *
     * @return array
     */
    private function createValidationRulesSetData(string $validatorsPath): array
    {
        $serializer = new FormRuleSerializer();
        foreach ($this->selectClasses($validatorsPath, FormRuleSetInterface::class) as $setClass) {
            /** @var string $setName */
            $setName = $setClass;
            assert(
                is_string($setClass) &&
                class_exists($setClass) &&
                array_key_exists(FormRuleSetInterface::class, class_implements($setClass))
            );
            /** @var FormRuleSetInterface $setClass */
            $serializer->addResourceRules($setName, $setClass::getAttributeRules());
        }

        $ruleSetsData = $serializer->getData();

        return $ruleSetsData;
    }
}
