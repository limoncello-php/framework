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
final class Filter extends BaseRule
{
    /**
     * Property key.
     */
    const PROPERTY_FILTER_ID = self::PROPERTY_LAST + 1;

    /**
     * Property key.
     */
    const PROPERTY_FILTER_OPTIONS = self::PROPERTY_FILTER_ID + 1;

    /**
     * Property key.
     */
    const PROPERTY_FILTER_ERROR_CODE = self::PROPERTY_FILTER_OPTIONS + 1;

    /**
     * @var int
     */
    private $filterId;

    /**
     * @var mixed
     */
    private $filterOptions;

    /**
     * @var int
     */
    private $errorCode;

    /**
     * For filter ID and options see @link http://php.net/manual/en/filter.filters.php
     *
     * @param int   $filterId
     * @param mixed $options
     * @param int   $errorCode
     */
    public function __construct(int $filterId, $options = null, int $errorCode = ErrorCodes::INVALID_VALUE)
    {
        $this->filterId      = $filterId;
        $this->filterOptions = $options;
        $this->errorCode     = $errorCode;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        $properties = $this->getStandardProperties() + [
                static::PROPERTY_FILTER_ID         => $this->getFilterId(),
                static::PROPERTY_FILTER_OPTIONS    => $this->getFilterOptions(),
                static::PROPERTY_FILTER_ERROR_CODE => $this->getErrorCode(),
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

        $filterId      = $context->getProperties()->getProperty(static::PROPERTY_FILTER_ID);
        $filterOptions = $context->getProperties()->getProperty(static::PROPERTY_FILTER_OPTIONS);
        $errorCode     = $context->getProperties()->getProperty(static::PROPERTY_FILTER_ERROR_CODE);

        $output = filter_var($value, $filterId, $filterOptions);

        return $output !== false ?
            BlockReplies::createSuccessReply($output) :
            BlockReplies::createErrorReply($context, $value, $errorCode, [$filterId, $filterOptions]);
    }

    /**
     * @return int
     */
    private function getFilterId(): int
    {
        return $this->filterId;
    }

    /**
     * @return mixed
     */
    private function getFilterOptions()
    {
        return $this->filterOptions;
    }

    /**
     * @return int
     */
    private function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
