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
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Package\FluteSettings;

/**
 * @package Limoncello\Tests\Flute
 */
class Flute extends FluteSettings
{
    /**
     * @var string[]
     */
    private $modelToSchemaMap;

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
    private $apiFolder;

    /**
     * @var string
     */
    private $valRulesFolder;

    /**
     * @var string
     */
    private $jsonCtrlFolder;

    /**
     * @var string
     */
    private $schemasPath;

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
     * @param string[] $modelToSchemaMap
     * @param string[] $jsonRuleSets
     * @param string[] $formRuleSets
     * @param string[] $queryRuleSets
     */
    public function __construct(
        array $modelToSchemaMap,
        array $jsonRuleSets,
        array $formRuleSets,
        array $queryRuleSets
    ) {
        $this->apiFolder      = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Api']);
        $this->jsonCtrlFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Http']);
        $this->schemasPath    = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Schemas']);
        $this->valRulesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation']);
        $this->formValPath    = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'Forms', '**']);
        $this->jsonValPath    = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'JsonData', '**']);
        $this->queryValPath   = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'JsonQueries', '**']);

        $this->modelToSchemaMap = $modelToSchemaMap;
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
                static::KEY_API_FOLDER                   => $this->apiFolder,
                static::KEY_JSON_CONTROLLERS_FOLDER      => $this->jsonCtrlFolder,
                static::KEY_SCHEMAS_FOLDER               => $this->schemasPath,
                static::KEY_JSON_VALIDATION_RULES_FOLDER => $this->valRulesFolder,
                static::KEY_JSON_VALIDATORS_FOLDER       => $this->jsonValPath,
                static::KEY_FORM_VALIDATORS_FOLDER       => $this->formValPath,
                static::KEY_QUERY_VALIDATORS_FOLDER      => $this->queryValPath,
            ];
    }

    /**
     * @inheritdoc
     */
    protected function selectClasses(string $path, string $implementClassName): Generator
    {
        $settings      = parent::getSettings();
        $schemasPath   = $this->schemasPath . DIRECTORY_SEPARATOR . $settings[static::KEY_SCHEMAS_FILE_MASK];
        $jsonFilePath  = $this->jsonValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_JSON_VALIDATORS_FILE_MASK];
        $formsFilePath = $this->formValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_FORM_VALIDATORS_FILE_MASK];
        $queryFilePath = $this->queryValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_QUERY_VALIDATORS_FILE_MASK];

        switch ($path) {
            case $schemasPath:
                foreach ($this->getModelToSchemaMap() as $schemaClass) {
                    yield $schemaClass;
                }
                break;
            case $jsonFilePath:
                assert($implementClassName === JsonApiDataRulesInterface::class);
                foreach ($this->getJsonValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            case $formsFilePath:
                assert($implementClassName === FormRulesInterface::class);
                foreach ($this->getFormValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            case $queryFilePath:
                assert($implementClassName === JsonApiQueryRulesInterface::class);
                foreach ($this->getQueryValidationRuleSets() as $ruleSet) {
                    if (in_array($implementClassName, class_implements($ruleSet)) === true) {
                        yield $ruleSet;
                    }
                }
                break;
            default:
                assert("Unknown path `$path`.");
        }
    }

    /**
     * @inheritdoc
     */
    private function getModelToSchemaMap(): array
    {
        return $this->modelToSchemaMap;
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
