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
final class Fail extends BaseRule
{
    /**
     * Property key.
     */
    const PROPERTY_ERROR_CODE = self::PROPERTY_LAST + 1;

    /**
     * Property key.
     */
    const PROPERTY_ERROR_CONTEXT = self::PROPERTY_ERROR_CODE + 1;

    /**
     * @var int
     */
    private $errorCode;

    /**
     * @var mixed
     */
    private $errorContext;

    /**
     * @param int   $errorCode
     * @param mixed $errorContext
     */
    public function __construct(int $errorCode = ErrorCodes::INVALID_VALUE, $errorContext = null)
    {
        $this->errorCode    = $errorCode;
        $this->errorContext = $errorContext;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        return (new ProcedureBlock([self::class, 'execute']))
            ->setProperties($this->getStandardProperties() + [
                    self::PROPERTY_ERROR_CODE    => $this->getErrorCode(),
                    self::PROPERTY_ERROR_CONTEXT => $this->getErrorContext(),
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
        $properties = $context->getProperties();

        return BlockReplies::createErrorReply(
            $context,
            $value,
            $properties->getProperty(self::PROPERTY_ERROR_CODE),
            $properties->getProperty(self::PROPERTY_ERROR_CONTEXT)
        );
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return mixed
     */
    public function getErrorContext()
    {
        return $this->errorContext;
    }
}
