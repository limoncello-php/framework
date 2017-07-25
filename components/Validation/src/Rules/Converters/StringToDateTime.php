<?php namespace Limoncello\Validation\Rules\Converters;

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

use DateTimeImmutable;
use DateTimeInterface;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Validation
 */
final class StringToDateTime extends BaseRule
{
    /**
     * Property key.
     */
    const PROPERTY_FORMAT = self::PROPERTY_LAST + 1;

    /**
     * @var string
     */
    private $format;

    /**
     * @param string $format
     */
    public function __construct(string $format)
    {
        assert(!empty($format));

        $this->format = $format;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        return (new ProcedureBlock([self::class, 'execute']))
            ->setProperties($this->getStandardProperties() + [self::PROPERTY_FORMAT => $this->getFormat()]);
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
            return BlockReplies::createSuccessReply($parsed);
        } elseif ($value instanceof DateTimeInterface) {
            return BlockReplies::createSuccessReply($value);
        }

        return BlockReplies::createErrorReply(
            $context,
            $value,
            ErrorCodes::IS_DATE_TIME,
            [self::PROPERTY_FORMAT => $format]
        );
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
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
