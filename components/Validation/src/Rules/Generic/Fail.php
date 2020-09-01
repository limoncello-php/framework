<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Generic;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\I18n\Messages;
use Limoncello\Validation\Rules\ExecuteRule;
use function assert;

/**
 * @package Limoncello\Validation
 */
final class Fail extends ExecuteRule
{
    /**
     * Property key.
     */
    const PROPERTY_ERROR_CODE = self::PROPERTY_LAST + 1;

    /**
     * Property key.
     */
    const PROPERTY_ERROR_MESSAGE_TEMPLATE = self::PROPERTY_ERROR_CODE + 1;

    /**
     * Property key.
     */
    const PROPERTY_ERROR_MESSAGE_PARAMETERS = self::PROPERTY_ERROR_MESSAGE_TEMPLATE + 1;

    /**
     * @param int    $errorCode
     * @param string $messageTemplate
     * @param array  $messageParams
     */
    public function __construct(
        int $errorCode = ErrorCodes::INVALID_VALUE,
        string $messageTemplate = Messages::INVALID_VALUE,
        array $messageParams = []
    ) {
        assert($this->checkEachValueConvertibleToString($messageParams));

        parent::__construct([
            self::PROPERTY_ERROR_CODE               => $errorCode,
            self::PROPERTY_ERROR_MESSAGE_TEMPLATE   => $messageTemplate,
            self::PROPERTY_ERROR_MESSAGE_PARAMETERS => $messageParams,
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
        $properties = $context->getProperties();

        return static::createErrorReply(
            $context,
            $value,
            $properties->getProperty(self::PROPERTY_ERROR_CODE),
            $properties->getProperty(self::PROPERTY_ERROR_MESSAGE_TEMPLATE),
            $properties->getProperty(self::PROPERTY_ERROR_MESSAGE_PARAMETERS)
        );
    }
}
