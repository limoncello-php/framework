<?php namespace Limoncello\Validation\Rules\Generic;

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

use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Validation
 */
final class Enum extends BaseRule
{
    /**
     * Property key.
     */
    const PROPERTY_VALUES = self::PROPERTY_LAST + 1;

    /**
     * @var array
     */
    private $values;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        assert(!empty($values));

        $this->values = $values;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        $properties = $this->getStandardProperties() + [
            static::PROPERTY_VALUES => $this->getValues(),
        ];

        return (new ProcedureBlock([self::class, 'execute']))->setProperties($properties);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute($value, ContextInterface $context): array
    {
        assert($context);

        $values = $context->getProperties()->getProperty(static::PROPERTY_VALUES);
        $isOk   = in_array($value, $values);

        return $isOk === true ?
            BlockReplies::createSuccessReply($value) :
            BlockReplies::createErrorReply($context, $value, ErrorCodes::INVALID_VALUE);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
