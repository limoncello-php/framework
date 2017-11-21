<?php namespace Limoncello\Tests\Flute\Data\Package;

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
use Limoncello\Flute\Contracts\Validation\AttributeRulesSetInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiRuleSetInterface;
use Limoncello\Flute\Package\FluteSettings;

/**
 * @package Limoncello\Tests\Flute
 */
class Flute extends FluteSettings
{
    /**
     * @var string[]
     */
    private $modelToSchemeMap;

    /**
     * @var string[]
     */
    private $jsonValRuleSets;

    /**
     * @var string[]
     */
    private $formValRuleSets;

    /**
     * @var string
     */
    private $schemesPath;

    /**
     * @var string
     */
    private $validatorsPath;

    /**
     * @param string[] $modelToSchemeMap
     * @param string[] $jsonRuleSets
     * @param string[] $formRuleSets
     */
    public function __construct(array $modelToSchemeMap, array $jsonRuleSets, array $formRuleSets)
    {
        $this->schemesPath    = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Schemes']);
        $this->validatorsPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', '**']);

        $this->modelToSchemeMap = $modelToSchemeMap;
        $this->jsonValRuleSets  = $jsonRuleSets;
        $this->formValRuleSets  = $formRuleSets;
    }

    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        return parent::getSettings() + [
                static::KEY_SCHEMES_FOLDER              => $this->schemesPath,
                static::KEY_JSON_VALIDATORS_FOLDER      => $this->validatorsPath,
                static::KEY_ATTRIBUTE_VALIDATORS_FOLDER => $this->validatorsPath,
            ];
    }

    /**
     * @inheritdoc
     */
    protected function selectClasses(string $path, string $implementClassName): Generator
    {
        $schemesFileMask    = parent::getSettings()[static::KEY_SCHEMES_FILE_MASK];
        $validatorsFileMask = parent::getSettings()[static::KEY_SCHEMES_FILE_MASK];

        if ($path === $this->schemesPath . DIRECTORY_SEPARATOR . $schemesFileMask) {
            foreach ($this->getModelToSchemeMap() as $schemeClass) {
                yield $schemeClass;
            }
        } else {
            assert($path === $this->validatorsPath . DIRECTORY_SEPARATOR . $validatorsFileMask);
            if ($implementClassName === JsonApiRuleSetInterface::class) {
                foreach ($this->getJsonValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
            } else {
                assert($implementClassName === AttributeRulesSetInterface::class);
                foreach ($this->getFormValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    private function getModelToSchemeMap(): array
    {
        return $this->modelToSchemeMap;
    }

    /**
     * @return string[]
     */
    private function getJsonValidationRuleSets(): array
    {
        return $this->jsonValRuleSets;
    }

    /**
     * @return string[]
     */
    private function getFormValidationRuleSets(): array
    {
        return $this->formValRuleSets;
    }
}
