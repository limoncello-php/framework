<?php namespace Limoncello\Application\Authorization;

use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Contracts\Authentication\AccountInterface;
use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
trait AuthorizationRulesTrait
{
    /**
     * @param ContextInterface $context
     *
     * @return string
     */
    protected static function ctxGetAction(ContextInterface $context): string
    {
        assert($context->has(RequestProperties::REQ_ACTION));

        $value = $context->get(RequestProperties::REQ_ACTION);

        return $value;
    }

    /**
     * @param ContextInterface $context
     *
     * @return string|null
     */
    protected static function ctxGetResourceType(ContextInterface $context)
    {
        assert($context->has(RequestProperties::REQ_RESOURCE_TYPE));

        $value = $context->get(RequestProperties::REQ_RESOURCE_TYPE);

        assert($value === null || is_string($value));

        return $value;
    }

    /**
     * @param ContextInterface $context
     *
     * @return string|int|null
     */
    protected static function ctxGetResourceIdentity(ContextInterface $context)
    {
        assert($context->has(RequestProperties::REQ_RESOURCE_IDENTITY));

        $value = $context->get(RequestProperties::REQ_RESOURCE_IDENTITY);

        assert($value === null || is_string($value) || is_int($value));

        return $value;
    }

    /**
     * @param ContextInterface $context
     *
     * @return AccountInterface
     */
    protected static function ctxGetCurrentAccount(ContextInterface $context): AccountInterface
    {
        $container = static::ctxGetContainer($context);

        assert($container->has(AccountManagerInterface::class));

        /** @var AccountManagerInterface $manager */
        $manager = $container->get(AccountManagerInterface::class);
        $account = $manager->getAccount();

        return $account;
    }

    /**
     * @param ContextInterface $context
     *
     * @return ContainerInterface
     */
    protected static function ctxGetContainer(ContextInterface $context): ContainerInterface
    {
        assert($context->has(ContextProperties::CTX_CONTAINER));

        return $context->get(ContextProperties::CTX_CONTAINER);
    }
}