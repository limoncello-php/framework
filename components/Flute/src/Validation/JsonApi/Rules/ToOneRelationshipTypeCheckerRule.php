<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\JsonApi\Rules;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use Limoncello\Flute\L10n\Messages;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use function assert;
use function count;
use function is_array;
use function is_scalar;
use function key;
use function reset;

/**
 * @package Limoncello\Flute
 */
final class ToOneRelationshipTypeCheckerRule extends ExecuteRule
{
    /**
     * Property key.
     */
    const PROPERTY_RESOURCE_TYPE = self::PROPERTY_LAST + 1;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        parent::__construct([
            static::PROPERTY_RESOURCE_TYPE => $type,
        ]);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     * @param null             $primaryKeyValue
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        // parser guarantees that input will be either null or an [$type => $id] where type and id are scalars

        if ($value === null) {
            return static::createSuccessReply($value);
        }

        assert(is_array($value) === true && count($value) === 1);
        $index = reset($value);
        $type  = key($value);
        assert(is_scalar($index) === true && is_scalar($type) === true);
        $expectedType = $context->getProperties()->getProperty(static::PROPERTY_RESOURCE_TYPE);
        $reply        = $type === $expectedType ?
            static::createSuccessReply($index) :
            static::createErrorReply(
                $context,
                $type,
                ErrorCodes::INVALID_RELATIONSHIP_TYPE,
                Messages::INVALID_RELATIONSHIP_TYPE,
                []
            );

        return $reply;
    }
}
