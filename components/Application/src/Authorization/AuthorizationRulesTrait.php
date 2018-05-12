<?php namespace Limoncello\Application\Authorization;

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

use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Contracts\Authentication\AccountInterface;
use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package Limoncello\Application
 */
trait AuthorizationRulesTrait
{
    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected static function reqHasAction(ContextInterface $context): bool
    {
        return $context->has(RequestProperties::REQ_ACTION);
    }

    /**
     * @param ContextInterface $context
     *
     * @return string
     */
    protected static function reqGetAction(ContextInterface $context): string
    {
        assert(static::reqHasAction($context));

        $value = $context->get(RequestProperties::REQ_ACTION);

        return $value;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected static function reqHasResourceType(ContextInterface $context): bool
    {
        return $context->has(RequestProperties::REQ_RESOURCE_TYPE);
    }

    /**
     * @param ContextInterface $context
     *
     * @return string|null
     */
    protected static function reqGetResourceType(ContextInterface $context): ?string
    {
        assert(static::reqHasResourceType($context));

        $value = $context->get(RequestProperties::REQ_RESOURCE_TYPE);

        assert($value === null || is_string($value));

        return $value;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected static function reqHasResourceIdentity(ContextInterface $context): bool
    {
        return $context->has(RequestProperties::REQ_RESOURCE_IDENTITY);
    }

    /**
     * @param ContextInterface $context
     *
     * @return string|int|null
     */
    protected static function reqGetResourceIdentity(ContextInterface $context)
    {
        assert(static::reqHasResourceIdentity($context));

        $value = $context->get(RequestProperties::REQ_RESOURCE_IDENTITY);

        assert($value === null || is_string($value) || is_int($value));

        return $value;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function ctxHasCurrentAccount(ContextInterface $context): bool
    {
        /** @var AccountManagerInterface $manager */
        $container = static::ctxGetContainer($context);
        $manager   = $container->get(AccountManagerInterface::class);
        $account   = $manager->getAccount();

        return $account !== null;
    }

    /**
     * @param ContextInterface $context
     *
     * @return AccountInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function ctxGetCurrentAccount(ContextInterface $context): AccountInterface
    {
        assert(static::ctxHasCurrentAccount($context));

        /** @var AccountManagerInterface $manager */
        $container = static::ctxGetContainer($context);
        $manager   = $container->get(AccountManagerInterface::class);
        $account   = $manager->getAccount();

        return $account;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected static function ctxHasContainer(ContextInterface $context): bool
    {
        return $context->has(ContextProperties::CTX_CONTAINER);
    }

    /**
     * @param ContextInterface $context
     *
     * @return ContainerInterface
     */
    protected static function ctxGetContainer(ContextInterface $context): ContainerInterface
    {
        assert(static::ctxHasContainer($context));

        return $context->get(ContextProperties::CTX_CONTAINER);
    }
}
