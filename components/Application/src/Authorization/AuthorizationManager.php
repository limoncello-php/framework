<?php namespace Limoncello\Application\Authorization;

use Limoncello\Application\Exceptions\AuthorizationException;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyDecisionPoint;
use Limoncello\Auth\Authorization\PolicyEnforcement\PolicyEnforcementPoint;
use Limoncello\Auth\Authorization\PolicyEnforcement\Request;
use Limoncello\Auth\Authorization\PolicyInformation\PolicyInformationPoint;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\PolicyEnforcementPointInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\RequestInterface;
use Limoncello\Contracts\Authorization\AuthorizationManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @package Limoncello\Application
 */
class AuthorizationManager implements AuthorizationManagerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var array
     */
    private $policiesData;

    /**
     * @param ContainerInterface $container
     * @param array              $policiesData
     */
    public function __construct(ContainerInterface $container, array $policiesData)
    {
        $this->setContainer($container)->setPoliciesData($policiesData);
    }

    /**
     * @param string          $action
     * @param string|null     $resourceType
     * @param string|int|null $resourceIdentity
     *
     * @return bool
     */
    public function isAllowed(string $action, string $resourceType = null, $resourceIdentity = null): bool
    {
        $request = $this->createRequest($action, $resourceType, $resourceIdentity);
        $result  = $this->createPolicyEnforcementPoint($this->getContainer())->authorize($request);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function authorize(string $action, string $resourceType = null, $resourceIdentity = null)
    {
        if ($this->isAllowed($action, $resourceType, $resourceIdentity) !== true) {
            throw new AuthorizationException($action, $resourceType, $resourceIdentity);
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return PolicyEnforcementPointInterface
     */
    protected function createPolicyEnforcementPoint(ContainerInterface $container): PolicyEnforcementPointInterface
    {
        $contextDefinitions = [
            ContextProperties::CTX_CONTAINER => $container,
        ];

        $pip = new PolicyInformationPoint($contextDefinitions);
        $pdp = new PolicyDecisionPoint($this->getPoliciesData());
        $pep = new PolicyEnforcementPoint($pip, $pdp);

        if ($this->logger !== null) {
            $pip->setLogger($this->logger);
            $pdp->setLogger($this->logger);
            $pep->setLogger($this->logger);
        }

        return $pep;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return self
     */
    private function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return array
     */
    protected function getPoliciesData(): array
    {
        return $this->policiesData;
    }

    /**
     * @param array $policiesData
     *
     * @return self
     */
    private function setPoliciesData(array $policiesData): self
    {
        $this->policiesData = $policiesData;

        return $this;
    }

    /**
     * @param string          $action
     * @param string|null     $type
     * @param string|int|null $identity
     *
     * @return RequestInterface
     */
    private function createRequest(
        string $action,
        string $type = null,
        $identity = null
    ): RequestInterface {
        assert($identity === null || is_string($identity) || is_int($identity));
        return new Request([
            RequestProperties::REQ_ACTION            => $action,
            RequestProperties::REQ_RESOURCE_TYPE     => $type,
            RequestProperties::REQ_RESOURCE_IDENTITY => $identity,
        ]);
    }
}