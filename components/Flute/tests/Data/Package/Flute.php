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
    private $validationRuleSets;

    /**
     * @param string[] $modelToSchemeMap
     * @param string[] $validationRuleSets
     */
    public function __construct(array $modelToSchemeMap = [], array $validationRuleSets = [])
    {
        $this->modelToSchemeMap   = $modelToSchemeMap;
        $this->validationRuleSets = $validationRuleSets;
    }

    /**
     * @inheritdoc
     */
    protected function getSchemesPath(): string
    {
        return 'whatever_schemes';
    }

    /**
     * @inheritdoc
     */
    protected function getRuleSetsPath(): string
    {
        return 'whatever_rule_sets';
    }

    /**
     * @inheritdoc
     */
    protected function selectClasses(string $path, string $implementClassName): Generator
    {
        if ($path === 'whatever_schemes') {
            foreach ($this->getModelToSchemeMap() as $schemeClass) {
                yield $schemeClass;
            }
        } else {
            assert($path === 'whatever_rule_sets');
            foreach ($this->getValidationRuleSets() as $ruleSet) {
                yield $ruleSet;
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
    private function getValidationRuleSets(): array
    {
        return $this->validationRuleSets;
    }
}
