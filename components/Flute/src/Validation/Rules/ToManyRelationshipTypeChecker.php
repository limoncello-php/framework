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

use Limoncello\Flute\Contracts\Validation\ContextInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Flute
 */
final class ToManyRelationshipTypeChecker extends BaseRule
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function execute($value, ContextInterface $context): array
    {
        // parser guarantees that input will be an array of [$type => $id] where type and id are scalars

        // we will check the type of every pair and send further identities only
        $indexes          = [];
        $foundInvalidType = null;
        $expectedType     = $context->getProperties()->getProperty(self::PROPERTY_RESOURCE_TYPE);
        foreach ($value as $typeAndId) {
            assert(is_array($typeAndId) === true && count($typeAndId) === 1);
            $index = reset($typeAndId);
            $type  = key($typeAndId);
            assert(is_scalar($index) === true && is_scalar($type) === true);
            if ($type === $expectedType) {
                $indexes[] = $index;
            } else {
                $foundInvalidType = $type;
                break;
            }
        }

        $reply = $foundInvalidType === null ?
            BlockReplies::createSuccessReply($indexes) :
            BlockReplies::createErrorReply($context, $foundInvalidType, ErrorCodes::INVALID_RELATIONSHIP_TYPE);

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
