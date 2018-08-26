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

use Limoncello\Application\Contracts\Authorization\AuthorizationRulesInterface;
use Limoncello\Application\Contracts\Authorization\ResourceAuthorizationRulesInterface;
use Limoncello\Auth\Authorization\PolicyAdministration\AllOf;
use Limoncello\Auth\Authorization\PolicyAdministration\AnyOf;
use Limoncello\Auth\Authorization\PolicyAdministration\Logical;
use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyAdministration\PolicySet;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyAdministration\Target;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyAlgorithm;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyDecisionPoint;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Common\Reflection\ClassIsTrait;
use ReflectionClass;
use ReflectionException;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuthorizationRulesLoader
{
    use ClassIsTrait;

    /**
     * @var array
     */
    private $rulesData;

    /**
     * @param string $path
     * @param string $name
     *
     * @throws ReflectionException
     */
    public function __construct(string $path, string $name)
    {
        $this->rulesData = $this->loadData($path, $name);
    }

    /**
     * @param string $path
     * @param string $name
     *
     * @return array
     *
     * @throws ReflectionException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function loadData(string $path, string $name): array
    {
        $policies = [];
        foreach ($this->getAuthorizationRulesClasses($path) as $class) {
            $methodNames   = $this->getActions($class);
            $resourcesType = $this->getResourcesType($class);
            $policies[]    = $this->createClassPolicy($name, $class, $methodNames, $resourcesType);
        }
        $policySet    = (new PolicySet($policies, PolicyAlgorithm::firstApplicable()))->setName($name);
        $policiesData = (new PolicyDecisionPoint($policySet))->getEncodePolicySet();

        return $policiesData;
    }

    /**
     * @param string $policiesPath
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function getAuthorizationRulesClasses(string $policiesPath): array
    {
        /** @noinspection PhpParamsInspection */
        return iterator_to_array($this->selectClasses($policiesPath, AuthorizationRulesInterface::class));
    }

    /**
     * @param string $policyClass
     *
     * @return string[]
     * @throws ReflectionException
     */
    private function getActions(string $policyClass): array
    {
        $reflectionClass = new ReflectionClass($policyClass);

        $actions = [];
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPublic() === true &&
                $reflectionMethod->isStatic() === true &&
                $reflectionMethod->hasReturnType() === true &&
                (string)$reflectionMethod->getReturnType() === 'bool' &&
                $reflectionMethod->getNumberOfParameters() === 1 &&
                $reflectionMethod->getParameters()[0]->getClass()->implementsInterface(ContextInterface::class) === true
            ) {
                $actions[] = $reflectionMethod->getName();
            }
        }

        return $actions;
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    private function getResourcesType(string $className): ?string
    {
        $resourceType = null;
        if (array_key_exists(ResourceAuthorizationRulesInterface::class, class_implements($className)) === true) {
            /** @var ResourceAuthorizationRulesInterface $className */
            $resourceType = $className::getResourcesType();
        }

        return $resourceType;
    }

    /**
     * @param string      $policiesName
     * @param string      $class
     * @param array       $methods
     * @param string|null $resourcesType
     *
     * @return PolicyInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function createClassPolicy(
        string $policiesName,
        string $class,
        array $methods,
        string $resourcesType = null
    ): PolicyInterface {
        $rules = [];
        foreach ($methods as $method) {
            $rules[] = $this->createMethodRule($class, $method);
        }

        $policy = (new Policy($rules, RuleAlgorithm::firstApplicable()))
            ->setName($policiesName . ' -> ' . RequestProperties::REQ_RESOURCE_TYPE . "=`$resourcesType`")
            ->setTarget($this->target(RequestProperties::REQ_RESOURCE_TYPE, $resourcesType));

        return $policy;
    }

    /**
     * @param string $class
     * @param string $method
     *
     * @return RuleInterface
     */
    private function createMethodRule(string $class, string $method): RuleInterface
    {
        $rule = (new Rule())
            ->setName($method)
            ->setTarget($this->target(RequestProperties::REQ_ACTION, $method))
            ->setEffect(new Logical([$class, $method]))
            ->setName("$class::$method");

        return $rule;
    }

    /**
     * @param string|int      $key
     * @param string|int|null $value
     *
     * @return TargetInterface
     */
    private function target($key, $value): TargetInterface
    {
        assert(is_string($key) || is_int($key));
        assert($value === null || is_string($value) || is_int($value));

        $allOfs = [new AllOf([$key => $value])];
        $anyOff = new AnyOf($allOfs);
        $target = (new Target($anyOff))->setName("$key=`$value`");

        return $target;
    }

    /**
     * @return array
     */
    public function getRulesData(): array
    {
        return $this->rulesData;
    }
}
