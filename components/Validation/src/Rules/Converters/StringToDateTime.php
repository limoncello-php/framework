<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Converters;

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

use DateTimeImmutable;
use DateTimeInterface;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\I18n\Messages;
use Limoncello\Validation\Rules\ExecuteRule;
use function assert;
use function is_string;

/**
 * @package Limoncello\Validation
 */
final class StringToDateTime extends ExecuteRule
{
    /**
     * Property key.
     */
    const PROPERTY_FORMAT = self::PROPERTY_LAST + 1;

    /**
     * @param string $format
     */
    public function __construct(string $format)
    {
        assert(!empty($format));

        parent::__construct([
            self::PROPERTY_FORMAT => $format,
        ]);
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
        $format = $context->getProperties()->getProperty(self::PROPERTY_FORMAT);
        if (is_string($value) === true && ($parsed = static::parseFromFormat($value, $format)) !== null) {
            return static::createSuccessReply($parsed);
        } elseif ($value instanceof DateTimeInterface) {
            return static::createSuccessReply($value);
        }

        return static::createErrorReply(
            $context,
            $value,
            ErrorCodes::IS_DATE_TIME,
            Messages::IS_DATE_TIME,
            [$format]
        );
    }

    /**
     * @param string $input
     * @param string $format
     *
     * @return DateTimeInterface|null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function parseFromFormat(string $input, string $format)
    {
        $parsedOrNull = null;

        if (($value = DateTimeImmutable::createFromFormat($format, $input)) !== false) {
            $parsedOrNull = $value;
        }

        return $parsedOrNull;
    }
}
