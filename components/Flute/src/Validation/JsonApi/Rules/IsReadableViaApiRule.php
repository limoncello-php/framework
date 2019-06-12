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

use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\L10n\Messages;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function assert;
use function is_int;
use function is_string;

/**
 * @package Limoncello\Flute
 */
final class IsReadableViaApiRule extends ExecuteRule
{
    use ClassIsTrait;

    /**
     * Property key.
     */
    const PROPERTY_API_CLASS = self::PROPERTY_LAST + 1;

    /**
     * @param string $apiClass
     */
    public function __construct(string $apiClass)
    {
        assert(static::classImplements($apiClass, CrudInterface::class));

        parent::__construct([
            static::PROPERTY_API_CLASS => $apiClass,
        ]);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function execute($value, ContextInterface $context): array
    {
        assert(is_int($value) || is_string($value));

        $apiClass = $context->getProperties()->getProperty(static::PROPERTY_API_CLASS);

        /** @var FactoryInterface $apiFactory */
        $apiFactory = $context->getContainer()->get(FactoryInterface::class);

        /** @var CrudInterface $api */
        $api = $apiFactory->createApi($apiClass);

        $data   = $api->withIndexFilter((string)$value)->indexIdentities();
        $result = !empty($data);

        return $result === true ?
            static::createSuccessReply($value) :
            static::createErrorReply(
                $context,
                $value,
                ErrorCodes::EXIST_IN_DATABASE_SINGLE,
                Messages::EXIST_IN_DATABASE_SINGLE,
                []
            );
    }
}
