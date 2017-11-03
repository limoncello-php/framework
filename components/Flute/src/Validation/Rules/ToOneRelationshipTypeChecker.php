<?php namespace Limoncello\Flute\Validation\Rules;

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

use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Flute
 */
final class ToOneRelationshipTypeChecker extends BaseRule
{
    /**
     * Property key.
     */
    const PROPERTY_RESOURCE_TYPE = self::PROPERTY_LAST + 1;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        return (new ProcedureBlock([self::class, 'execute']))
            ->setProperties($this->getStandardProperties() + [self::PROPERTY_RESOURCE_TYPE => $this->getType()]);
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
        // parser guarantees that input will be either null or an [$type => $id] where type and id are scalars

        if ($value === null) {
            return BlockReplies::createSuccessReply($value);
        }

        assert(is_array($value) === true && count($value) === 1);
        $index = reset($value);
        $type  = key($value);
        assert(is_scalar($index) === true && is_scalar($type) === true);
        $expectedType = $context->getProperties()->getProperty(self::PROPERTY_RESOURCE_TYPE);
        $reply = $type === $expectedType ?
            BlockReplies::createSuccessReply($index) :
            BlockReplies::createErrorReply($context, $type, ErrorCodes::INVALID_RELATIONSHIP_TYPE);

        return $reply;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
