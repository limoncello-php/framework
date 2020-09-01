<?php declare(strict_types=1);

namespace Limoncello\Flute\Validation\JsonApi\Rules;

/**
 * Copyright 2020 info@lolltec.com
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
use Ramsey\Uuid\Validator\ValidatorInterface as UuidValidatorInterface;

/**
 * @package Limoncello\Flute
 */
final class IsValidUuidRule extends ExecuteRule
{
    /**
     * @inheritDoc
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        /** @var UuidValidatorInterface $uuidValidator */
        $uuidValidator = $context->getContainer()->get(UuidValidatorInterface::class);

        return $uuidValidator->validate($value) === true ?
            static::createSuccessReply($value) :
            static::createErrorReply(
                $context,
                $value,
                ErrorCodes::INVALID_UUID,
                Messages::INVALID_UUID,
                []
            );
    }
}
