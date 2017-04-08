<?php namespace Limoncello\Flute\Contracts\Validation;

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

use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @package Limoncello\Flute
 */
interface ValidatorInterface
{
    /** Rule description index */
    const RULE_INDEX = 0;

    /** Rule description index */
    const RULE_CAPTURE_NAME = 1;

    /** Rule description index */
    const RULE_EXPECTED_TYPE = 2;

    /**
     * @param SchemaInterface $schema
     * @param array           $jsonData
     * @param RuleInterface   $idRule
     * @param RuleInterface[] $attributeRules
     * @param RuleInterface[] $toOneRules
     * @param RuleInterface[] $toManyRules
     *
     * @return array Captures
     */
    public function assert(
        SchemaInterface $schema,
        array $jsonData,
        RuleInterface $idRule,
        array $attributeRules,
        array $toOneRules = [],
        array $toManyRules = []
    ): array;

    /**
     * @param SchemaInterface $schema
     * @param array           $jsonData
     * @param RuleInterface   $idRule
     * @param RuleInterface[] $attributeRules
     * @param RuleInterface[] $toOneRules
     * @param RuleInterface[] $toManyRules
     *
     * @return array ErrorCollection and CaptureAggregatorInterface[]
     */
    public function check(
        SchemaInterface $schema,
        array $jsonData,
        RuleInterface $idRule,
        array $attributeRules,
        array $toOneRules = [],
        array $toManyRules = []
    ): array;
}
