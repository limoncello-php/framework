<?php namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement;

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

use Exception;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyDecisionPoint;
use Limoncello\Auth\Authorization\PolicyEnforcement\PolicyEnforcementPoint;
use Limoncello\Auth\Authorization\PolicyEnforcement\Request;
use Limoncello\Auth\Authorization\PolicyInformation\PolicyInformationPoint;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\PolicyEnforcementPointInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\PolicyInformationPointInterface;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\ContextProperties;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies\Application;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies\Posts;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\RequestProperties;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Tests\Auth
 */
class PolicySwitchOptimizationTest extends TestCase
{
    /**
     * @var bool
     */
    public static $obligationCalled = false;

    /**
     * @var bool
     */
    public static $adviceCalled = false;

    /**
     * @var resource
     */
    private $logStream;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int|null
     */
    private $currentUserId;

    /**
     * @var string|null
     */
    private $currentUserRole;

    /**
     * @var bool
     */
    private $isWorkTime;

    /**
     * Test authorization of operation without any additional parameters (e.g. 'send message').
     */
    public function testAuthorizeOperationSuccess()
    {
        // set up environment variables (current user is not signed in and currently is work time)
        $this->currentUserId   = null;
        $this->currentUserRole = null;
        $this->isWorkTime      = true;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION     => Posts::OPERATION_INDEX,
            RequestProperties::REQUEST_RESOURCE_TYPE => Posts::RESOURCE_TYPE,
        ]);
        $this->assertTrue($policyEnforcement->authorize($authRequest));

        // what is important for us in this test is how many execution steps were made.
        // due to optimization we expect it to be very few.
        // how we control it? well, we just count number of log entries as it corresponds to number of steps.
        // it's not ideal but solves the problem.
        //
        // so what should you do if the next assert fails (due to changes in logging or logic)?
        // you have to check the log messages and make sure it was executed as switch:
        // 1) First step was checking Post rules and no rules for other resources were checked.
        // 2) Second step was checking `index` and no other Post rules were checked.
        $loggedActions = explode(PHP_EOL, $this->getLogs());
        $this->assertCount(10, $loggedActions);
    }

    /**
     * Test authorization of operation on resource type (e.g. 'create comment').
     */
    public function testAuthorizeOperationOnResourceTypeFail()
    {
        // set up environment variables (current user is not signed in and currently is work time)
        $this->currentUserId   = null;
        $this->currentUserRole = null;
        $this->isWorkTime      = true;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION     => Posts::OPERATION_CREATE,
            RequestProperties::REQUEST_RESOURCE_TYPE => Posts::RESOURCE_TYPE,
        ]);

        // we expect it to fail because operation `create` is not defined in Policies.
        $this->assertFalse($policyEnforcement->authorize($authRequest));
        $this->assertFalse(static::$obligationCalled);
        $this->assertFalse(static::$adviceCalled);
    }

    /**
     * @param ContextInterface $context
     *
     * @return void
     */
    public static function markObligationAsCalled(ContextInterface $context)
    {
        // cant be null but to avoid 'not used variable' warning
        static::assertNotNull($context);

        static::$obligationCalled = true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return void
     */
    public static function markAdviceAsCalled(ContextInterface $context)
    {
        // cant be null but to avoid 'not used variable' warning
        static::assertNotNull($context);

        static::$adviceCalled = true;
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logStream = fopen('php://memory', 'rw');
        $this->logger    = new Logger('auth', [new StreamHandler($this->getLogStream())]);

        $this->currentUserId   = null;
        $this->currentUserRole = null;
        $this->isWorkTime      = false;

        static::$obligationCalled = false;
        static::$adviceCalled     = false;
    }

    /**
     * @return resource
     */
    protected function getLogStream()
    {
        return $this->logStream;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        fclose($this->getLogStream());
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    protected function getLogs()
    {
        rewind($this->getLogStream());
        $logs = stream_get_contents($this->getLogStream());

        return $logs;
    }

    /**
     * @return PolicyEnforcementPointInterface
     */
    private function createPolicyEnforcementPoint()
    {
        $pip = $this->createPolicyInformationPoint();

        $pdp = new PolicyDecisionPoint(Application::getApplicationPolicyThatCouldBeOptimizedAsSwitch());
        $pdp->setLogger($this->getLogger());

        $pep = new PolicyEnforcementPoint($pip, $pdp);
        $pep->setLogger($this->getLogger());

        $this->assertTrue($pep->isExecuteAdvice());
        $pep->disableExecuteAdvice();
        $this->assertFalse($pep->isExecuteAdvice());
        $pep->enableExecuteAdvice();
        $this->assertTrue($pep->isExecuteAdvice());

        return $pep;
    }

    /**
     * @return PolicyInformationPointInterface
     */
    private function createPolicyInformationPoint()
    {
        // Typically values are taken from application container, system environment and
        // external systems but not from constructor parameters. However it's fine for testing purposes.
        // Both scalar values and methods/closures are supported.
        $pip = new PolicyInformationPoint($this->getContextDefinitions());

        return $pip;
    }

    /**
     * @return array
     */
    private function getContextDefinitions()
    {
        return [
            LoggerInterface::class                       => $this->getLogger(),
            ContextProperties::CONTEXT_CURRENT_USER_ID   => $this->currentUserId,
            ContextProperties::CONTEXT_CURRENT_USER_ROLE => $this->currentUserRole,
            ContextProperties::CONTEXT_USER_IS_SIGNED_IN => $this->currentUserId !== null &&
                $this->currentUserRole !== null,
            ContextProperties::CONTEXT_IS_WORK_TIME      => function () {
                return $this->isWorkTime;
            },
        ];
    }
}
