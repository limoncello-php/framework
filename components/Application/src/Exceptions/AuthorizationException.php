<?php namespace Limoncello\Application\Exceptions;

use Limoncello\Contracts\Exceptions\AuthorizationExceptionInterface;
use RuntimeException;

/**
 * @package Limoncello\Application
 */
class AuthorizationException extends RuntimeException implements AuthorizationExceptionInterface
{
    /**
     * @var string
     */
    private $action;

    /**
     * @var string|null
     */
    private $resourceType;

    /**
     * @var string|int|null
     */
    private $resourceIdentity;

    /**
     * @param string          $action
     * @param null|string     $resourceType
     * @param int|null|string $resourceIdentity
     */
    public function __construct(string $action, string $resourceType = null, $resourceIdentity = null)
    {
        assert($resourceIdentity === null || is_string($resourceIdentity) || is_int($resourceIdentity));

        $this->action = $action;
        $this->resourceType = $resourceType;
        $this->resourceIdentity = $resourceIdentity;
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @inheritdoc
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @inheritdoc
     */
    public function getResourceIdentity()
    {
        return $this->resourceIdentity;
    }
}
