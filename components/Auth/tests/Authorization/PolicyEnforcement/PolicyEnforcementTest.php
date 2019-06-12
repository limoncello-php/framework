<?php declare(strict_types=1);

namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement;

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

use Exception;
use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\BasePolicyOrSetAlgorithm;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\Encoder;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyDecisionPoint;
use Limoncello\Auth\Authorization\PolicyEnforcement\PolicyEnforcementPoint;
use Limoncello\Auth\Authorization\PolicyEnforcement\Request;
use Limoncello\Auth\Authorization\PolicyInformation\Context;
use Limoncello\Auth\Authorization\PolicyInformation\PolicyInformationPoint;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetMatchEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\PolicyEnforcementPointInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\PolicyInformationPointInterface;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\ContextProperties;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies\Application;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies\Comments;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies\Messaging;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\RequestProperties;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\TestRuleAlgorithm;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Tests\Auth
 */
class PolicyEnforcementTest extends TestCase
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
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]);
        $this->assertTrue($policyEnforcement->authorize($authRequest));
    }

    /**
     * Test authorization of operation without any additional parameters (e.g. 'send message').
     */
    public function testAuthorizeOperationFail()
    {
        // set up environment variables (current user is not signed in and currently is not work time)
        $this->currentUserId   = null;
        $this->currentUserRole = null;
        $this->isWorkTime      = false;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]);
        $this->assertFalse($policyEnforcement->authorize($authRequest));
    }

    /**
     * Test authorization of operation on resource type (e.g. 'create comment').
     */
    public function testAuthorizeOperationOnResourceTypeSuccess()
    {
        // set up environment variables (current user is signed as member)
        $this->currentUserRole = 'member';
        $this->currentUserId   = 123;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION     => Comments::OPERATION_CREATE,
            RequestProperties::REQUEST_RESOURCE_TYPE => Comments::RESOURCE_TYPE,
        ]);
        $this->assertTrue($policyEnforcement->authorize($authRequest));
        $this->assertTrue(static::$obligationCalled);
        $this->assertTrue(static::$adviceCalled);
    }

    /**
     * Test authorization of operation on resource type (e.g. 'create comment').
     */
    public function testAuthorizeOperationOnResourceTypeFail()
    {
        // set up environment variables (current user is not signed in)
        // check that non signed in user cannot create comments
        $this->currentUserRole = null;
        $this->currentUserId   = null;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION     => Comments::OPERATION_CREATE,
            RequestProperties::REQUEST_RESOURCE_TYPE => Comments::RESOURCE_TYPE,
        ]);
        $this->assertFalse($policyEnforcement->authorize($authRequest));
    }

    /**
     * Test authorization of operation on specific resource (e.g. 'update comment with ID 123').
     */
    public function testAuthorizeOperationOnSpecificResourceAsMember()
    {
        // set up environment variables (current user is signed as member)
        $this->currentUserRole = 'member';
        $this->currentUserId   = 456;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION         => Comments::OPERATION_UPDATE,
            RequestProperties::REQUEST_RESOURCE_TYPE     => Comments::RESOURCE_TYPE,
            RequestProperties::REQUEST_RESOURCE_IDENTITY => 123, // <- ID 123 is owned
        ]);
        $this->assertTrue($policyEnforcement->authorize($authRequest));

        // send authorization request
        $authRequest = new Request([
            RequestProperties::REQUEST_OPERATION         => Comments::OPERATION_UPDATE,
            RequestProperties::REQUEST_RESOURCE_TYPE     => Comments::RESOURCE_TYPE,
            RequestProperties::REQUEST_RESOURCE_IDENTITY => 1234, // <- ID 1234 is not owned
        ]);
        $this->assertFalse($policyEnforcement->authorize($authRequest));
    }

    /**
     * Test authorization of operation on specific resource (e.g. 'update comment with ID 123').
     */
    public function testAuthorizeOperationOnSpecificResourceAsAdmin()
    {
        // admin can edit any resource
        $this->currentUserRole = 'admin';
        $this->currentUserId   = 789;
        $policyEnforcement     = $this->createPolicyEnforcementPoint();
        $authRequest           = new Request([
            RequestProperties::REQUEST_OPERATION         => Comments::OPERATION_UPDATE,
            RequestProperties::REQUEST_RESOURCE_TYPE     => Comments::RESOURCE_TYPE,
            RequestProperties::REQUEST_RESOURCE_IDENTITY => 1234, // <- ID 1234 is not owned
        ]);
        $this->assertTrue($policyEnforcement->authorize($authRequest));
    }

    /**
     * Test evaluate policy.
     */
    public function testEvaluatePolicyWithIntermediateAndPermit()
    {
        TestRuleAlgorithm::$result = EvaluationEnum::PERMIT;
        $policy                    = new Policy([(new Rule())], new TestRuleAlgorithm());
        $encodedPolicy             = Encoder::encodePolicy($policy);
        $context                   = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $result = BasePolicyOrSetAlgorithm::evaluatePolicy(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $this->logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::INDETERMINATE_PERMIT,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy.
     */
    public function testEvaluatePolicyWithIntermediateAndDeny()
    {
        TestRuleAlgorithm::$result = EvaluationEnum::DENY;
        $policy                    = new Policy([(new Rule())], new TestRuleAlgorithm());
        $encodedPolicy             = Encoder::encodePolicy($policy);
        $context                   = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $result = BasePolicyOrSetAlgorithm::evaluatePolicy(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $this->logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::INDETERMINATE_DENY,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy.
     */
    public function testEvaluatePolicyWithIntermediateAndIntermediate()
    {
        TestRuleAlgorithm::$result = EvaluationEnum::INDETERMINATE;
        $policy                    = new Policy([(new Rule())], new TestRuleAlgorithm());
        $encodedPolicy             = Encoder::encodePolicy($policy);
        $context                   = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $result = BasePolicyOrSetAlgorithm::evaluatePolicy(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $this->logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy.
     */
    public function testEvaluatePolicyWithIntermediateAndIntermediateDenyOrPermit()
    {
        TestRuleAlgorithm::$result = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
        $policy                    = new Policy([(new Rule())], new TestRuleAlgorithm());
        $encodedPolicy             = Encoder::encodePolicy($policy);
        $context                   = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $result = BasePolicyOrSetAlgorithm::evaluatePolicy(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $this->logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy.
     */
    public function testEvaluatePolicyWithIntermediateAndIntermediatePermit()
    {
        TestRuleAlgorithm::$result = EvaluationEnum::INDETERMINATE_PERMIT;
        $policy                    = new Policy([(new Rule())], new TestRuleAlgorithm());
        $encodedPolicy             = Encoder::encodePolicy($policy);
        $context                   = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $result = BasePolicyOrSetAlgorithm::evaluatePolicy(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $this->logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::INDETERMINATE_PERMIT,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy.
     */
    public function testEvaluatePolicyWithIntermediateAndIntermediateDeny()
    {
        TestRuleAlgorithm::$result = EvaluationEnum::INDETERMINATE_DENY;
        $policy                    = new Policy([(new Rule())], new TestRuleAlgorithm());
        $encodedPolicy             = Encoder::encodePolicy($policy);
        $context                   = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $result = BasePolicyOrSetAlgorithm::evaluatePolicy(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $this->logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::INDETERMINATE_DENY,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy set.
     */
    public function testEvaluatePolicySetWithIntermediate()
    {
        $set           = Application::getApplicationPolicy();
        $encodedPolicy = Encoder::encodePolicySet($set);
        $context       = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $logger = null;

        $result = BasePolicyOrSetAlgorithm::evaluatePolicySet(
            $context,
            TargetMatchEnum::INDETERMINATE,
            $encodedPolicy,
            $logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::NOT_APPLICABLE,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
    }

    /**
     * Test evaluate policy set.
     */
    public function testEvaluatePolicySetWithNotMatch()
    {
        $set           = Application::getApplicationPolicy();
        $encodedPolicy = Encoder::encodePolicySet($set);
        $context       = new Context(new Request([
            RequestProperties::REQUEST_OPERATION => Messaging::OPERATION_SEND,
        ]), $this->getContextDefinitions());

        $logger = null;

        $result = BasePolicyOrSetAlgorithm::evaluatePolicySet(
            $context,
            TargetMatchEnum::NOT_MATCH,
            $encodedPolicy,
            $logger
        );
        $this->assertEquals([
            BasePolicyOrSetAlgorithm::EVALUATION_VALUE       => EvaluationEnum::NOT_APPLICABLE,
            BasePolicyOrSetAlgorithm::EVALUATION_OBLIGATIONS => [],
            BasePolicyOrSetAlgorithm::EVALUATION_ADVICE      => [],
        ], $result);
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

        $pdp = new PolicyDecisionPoint(Application::getApplicationPolicy());
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
