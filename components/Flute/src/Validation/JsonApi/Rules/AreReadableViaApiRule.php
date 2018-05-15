<?php namespace Limoncello\Flute\Validation\JsonApi\Rules;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package Limoncello\Flute
 */
final class AreReadableViaApiRule extends ExecuteRule
{
    /**
     * Property key.
     */
    const PROPERTY_API_CLASS = self::PROPERTY_LAST + 1;

    /**
     * @param string $apiClass
     */
    public function __construct(string $apiClass)
    {
        assert(
            class_exists($apiClass) === true &&
            array_key_exists(CrudInterface::class, class_implements($apiClass)) === true
        );

        parent::__construct([
            static::PROPERTY_API_CLASS => $apiClass,
        ]);
    }

    /**
     * @param mixed            $values
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function execute($values, ContextInterface $context): array
    {
        assert(is_array($values));

        $apiClass = $context->getProperties()->getProperty(static::PROPERTY_API_CLASS);

        /** @var FactoryInterface $apiFactory */
        $apiFactory = $context->getContainer()->get(FactoryInterface::class);

        /** @var CrudInterface $api */
        $api = $apiFactory->createApi($apiClass);

        $readIndexes = $api->withIndexesFilter($values)->indexIdentities();

        $result = count($readIndexes) === count($values);

        return $result === true ?
            static::createSuccessReply($values) :
            static::createErrorReply($context, $values, ErrorCodes::EXIST_IN_DATABASE_MULTIPLE);
    }
}
