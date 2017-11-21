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
use Limoncello\Flute\Contracts\Validation\FormRuleSetInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiRuleSetInterface;
use Limoncello\Flute\Contracts\Validation\QueryRuleSetInterface;
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
     * @var string[]
     */
    private $queryValRuleSets;

    /**
     * @var string
     */
    private $schemesPath;

    /**
     * @var string
     */
    private $formValPath;

    /**
     * @var string
     */
    private $jsonValPath;

    /**
     * @var string
     */
    private $queryValPath;

    /**
     * @param string[] $modelToSchemeMap
     * @param string[] $jsonRuleSets
     * @param string[] $formRuleSets
     * @param string[] $queryRuleSets
     */
    public function __construct(
        array $modelToSchemeMap,
        array $jsonRuleSets,
        array $formRuleSets,
        array $queryRuleSets
    ) {
        $this->schemesPath  = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Schemes']);
        $this->formValPath  = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'FormRuleSets', '**']);
        $this->jsonValPath  = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'JsonRuleSets', '**']);
        $this->queryValPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'QueryRuleSets', '**']);

        $this->modelToSchemeMap = $modelToSchemeMap;
        $this->jsonValRuleSets  = $jsonRuleSets;
        $this->formValRuleSets  = $formRuleSets;
        $this->queryValRuleSets = $queryRuleSets;
    }

    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        return parent::getSettings() + [
                static::KEY_SCHEMES_FOLDER          => $this->schemesPath,
                static::KEY_JSON_VALIDATORS_FOLDER  => $this->jsonValPath,
                static::KEY_FORM_VALIDATORS_FOLDER  => $this->formValPath,
                static::KEY_QUERY_VALIDATORS_FOLDER => $this->queryValPath,
            ];
    }

    /**
     * @inheritdoc
     */
    protected function selectClasses(string $path, string $implementClassName): Generator
    {
        $settings      = parent::getSettings();
        $schemesPath   = $this->schemesPath . DIRECTORY_SEPARATOR . $settings[static::KEY_SCHEMES_FILE_MASK];
        $jsonFilePath  = $this->jsonValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_JSON_VALIDATORS_FILE_MASK];
        $formsFilePath = $this->formValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_FORM_VALIDATORS_FILE_MASK];
        $queryFilePath = $this->queryValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_QUERY_VALIDATORS_FILE_MASK];

        switch ($path) {
            case $schemesPath:
                foreach ($this->getModelToSchemeMap() as $schemeClass) {
                    yield $schemeClass;
                }
                break;
            case $jsonFilePath:
                assert($implementClassName === JsonApiRuleSetInterface::class);
                foreach ($this->getJsonValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            case $formsFilePath:
                assert($implementClassName === FormRuleSetInterface::class);
                foreach ($this->getFormValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            case $queryFilePath:
                assert($implementClassName === QueryRuleSetInterface::class);
                foreach ($this->getQueryValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            default:
                assert("Unknown path `$path`.");
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

    /**
     * @return string[]
     */
    private function getQueryValidationRuleSets(): array
    {
        return $this->queryValRuleSets;
    }
}
