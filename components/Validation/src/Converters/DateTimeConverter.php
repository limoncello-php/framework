<?php namespace Limoncello\Validation\Converters;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @package Limoncello\Validation
 */
class DateTimeConverter extends BaseConverter
{
    use SimpleConverterTrait;

    /**
     * Context key.
     */
    const CONTEXT_FORMAT = 0;

    /**
     * @var string
     */
    private $format;

    /**
     * @param RuleInterface $next
     * @param string        $format
     */
    public function __construct(RuleInterface $next, string $format)
    {
        parent::__construct($next);

        $this->setFormat($format);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function convert($input): bool
    {
        if (is_string($input) === true) {
            $value  = DateTimeImmutable::createFromFormat($this->getFormat(), (string)$input);
            $result = $value !== false;
            if ($result === true) {
                $this->setConverted($value);
            }
        } elseif ($input instanceof DateTimeInterface) {
            $result = true;
            $this->setConverted($input);
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getErrorCode(): int
    {
        return MessageCodes::IS_DATE_TIME;
    }

    /**
     * @inheritdoc
     */
    protected function getErrorContext()
    {
        return [static::CONTEXT_FORMAT => $this->getFormat()];
    }

    /**
     * @return string
     */
    protected function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     *
     * @return self
     */
    protected function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }
}
