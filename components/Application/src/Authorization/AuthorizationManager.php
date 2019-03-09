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
     * @inheritdoc
     */
    public function isAllowed(
        string $action,
        string $resourceType = null,
        string $resourceIdentity = null,
        array $extraParams = []
    ): bool {
        $request = $this->createRequest($action, $resourceType, $resourceIdentity, $extraParams);
        $result  = $this->createPolicyEnforcementPoint($this->getContainer())->authorize($request);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function authorize(
        string $action,
        string $resourceType = null,
        string $resourceIdentity = null,
        array $extraParams = []
    ): void {
        if ($this->isAllowed($action, $resourceType, $resourceIdentity, $extraParams) !== true) {
            throw new AuthorizationException($action, $resourceType, $resourceIdentity, $extraParams);
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
     * @param string      $action
     * @param string|null $type
     * @param string|null $identity
     * @param array       $extraParams
     *
     * @return RequestInterface
     */
    private function createRequest(
        string $action,
        string $type = null,
        string $identity = null,
        array $extraParams = []
    ): RequestInterface {
        assert($identity === null || is_string($identity) || is_array($identity) || is_int($identity));
        return new Request([
            RequestProperties::REQ_ACTION            => $action,
            RequestProperties::REQ_RESOURCE_TYPE     => $type,
            RequestProperties::REQ_RESOURCE_IDENTITY => $identity,
        ] + $extraParams);
    }
}
