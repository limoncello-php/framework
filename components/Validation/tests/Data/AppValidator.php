<?php namespace Limoncello\Tests\Validation\Data;

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

use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Validator;

/**
 * @package Limoncello\Tests\Validation
 */
class AppValidator extends Validator
{
    /**
     * @param callable      $condition
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    public static function requiredIf(callable $condition, RuleInterface $next)
    {
        return static::ifX($condition, static::required($next), self::success());
    }

    /**
     * @param array         $input
     * @param array         $keys
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    public static function requiredWithAll(array $input, array $keys, RuleInterface $next)
    {
        return static::requiredIf(function () use ($input, $keys) {
            $intersect = array_intersect_key(array_flip($keys), $input);
            return count($intersect) === count($keys);
        }, $next);
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param CaptureAggregatorInterface $aggregator
     * @param string                     $expectedType
     * @param RuleInterface              $indexRule
     * @param array                      $attributeRules
     * @param array                      $toOneRelationRules
     * @param array                      $toManyRelationRules
     * @param RuleInterface|null         $unlistedAttributeRule
     * @param RuleInterface|null         $unlistedRelationRule
     *
     * @return RuleInterface
     */
    public static function isJsonApi(
        CaptureAggregatorInterface $aggregator,
        $expectedType,
        RuleInterface $indexRule,
        array $attributeRules,
        array $toOneRelationRules = [],
        array $toManyRelationRules = [],
        RuleInterface $unlistedAttributeRule = null,
        RuleInterface $unlistedRelationRule = null
    ) {
        $typeRule = static::required(static::equals($expectedType));
        $idRule   = static::required($indexRule);

        $attributeCaptures = [];
        foreach ($attributeRules as $name => $rule) {
            $attributeCaptures[$name] = static::singleCapture($name, $rule, $aggregator);
        }

        $relationshipCaptures = [];
        foreach ($toOneRelationRules as $name => list($expectedType, $rule)) {
            $relationshipCaptures[$name] = static::arrayX([
                'data' => self::arrayX([
                    'type' => static::required(static::equals($expectedType)),
                    'id'   => static::singleCapture($name, $rule, $aggregator),
                ]),
            ]);
        }
        foreach ($toManyRelationRules as $name => list($expectedType, $rule)) {
            $relationshipCaptures[$name] = static::arrayX([
                'data' => static::eachX(self::arrayX([
                    'type' => static::required(static::equals($expectedType)),
                    'id'   => static::multiCapture($name, $rule, $aggregator),
                ])),
            ]);
        }

        return static::arrayX([
            'data' => static::required(static::arrayX([
                'type'          => $typeRule,
                'id'            => static::singleCapture('id', $idRule, $aggregator),
                'attributes'    => static::arrayX($attributeCaptures, $unlistedAttributeRule),
                'relationships' => static::arrayX($relationshipCaptures, $unlistedRelationRule),
            ])),
        ]);
    }
}
