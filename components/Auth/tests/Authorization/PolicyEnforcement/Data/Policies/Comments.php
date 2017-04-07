<?php namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies;

/**
 * Copyright 2015-2017 info@neomerx.com
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

use Limoncello\Auth\Authorization\PolicyAdministration\Advice;
use Limoncello\Auth\Authorization\PolicyAdministration\Logical;
use Limoncello\Auth\Authorization\PolicyAdministration\Obligation;
use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\MethodInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\ContextProperties;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\PolicyEnforcementTest;

/**
 * @package Limoncello\Tests\Auth
 */
abstract class Comments extends General
{
    /** Operation identity */
    const RESOURCE_TYPE = 'comments';

    /**
     * @return PolicyInterface
     */
    public static function getPolicies()
    {
        return (new Policy([
            static::onIndex(),
            static::onRead(),
            static::onCreate(),
            static::onUpdate(),
            static::onDelete(),
        ], RuleAlgorithm::permitOverrides())
        )
            ->setTarget(static::target(ContextProperties::PARAM_RESOURCE_TYPE, static::RESOURCE_TYPE))
            ->setName('Comments');
    }

    /**
     * @return RuleInterface
     */
    protected static function onIndex()
    {
        return (new Rule())->setTarget(static::targetOperationIndex())->setName('index');
    }

    /**
     * @return RuleInterface
     */
    protected static function onRead()
    {
        return (new Rule())->setTarget(static::targetOperationRead())->setName('read');
    }

    /**
     * @return RuleInterface
     */
    protected static function onCreate()
    {
        $obligation = new Obligation(EvaluationEnum::PERMIT, [PolicyEnforcementTest::class, 'markObligationAsCalled']);
        $advice     = new Advice(EvaluationEnum::PERMIT, [PolicyEnforcementTest::class, 'markAdviceAsCalled']);

        return (new Rule())->setTarget(static::targetMulti([
            ContextProperties::PARAM_OPERATION         => static::OPERATION_CREATE,
            ContextProperties::PARAM_USER_IS_SIGNED_IN => true,
        ]))->setName('create')->setObligations([$obligation])->setAdvice([$advice]);
    }

    /**
     * @return RuleInterface
     */
    protected static function onUpdate()
    {
        return (new Rule())
            ->setTarget(static::targetMulti([
                ContextProperties::PARAM_OPERATION         => static::OPERATION_UPDATE,
                ContextProperties::PARAM_USER_IS_SIGNED_IN => true,
            ]))
            ->setCondition(static::conditionIsCommentOwnerOrAdmin())
            ->setName('update');
    }

    /**
     * @return MethodInterface
     */
    protected static function conditionIsCommentOwnerOrAdmin()
    {
        return new Logical([static::class, 'isCommentOwnerOrAdmin']);
    }

    /**
     * @return RuleInterface
     */
    protected static function onDelete()
    {
        return (new Rule())
            ->setTarget(static::targetMulti([
                ContextProperties::PARAM_OPERATION         => static::OPERATION_DELETE,
                ContextProperties::PARAM_USER_IS_SIGNED_IN => true,
            ]))
            ->setCondition(static::conditionIsCommentOwnerOrAdmin())
            ->setName('delete');
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function isCommentOwnerOrAdmin(ContextInterface $context)
    {
        $commentId = $context->get(ContextProperties::PARAM_RESOURCE_IDENTITY);
        // for testing purposes let's pretend current user is owner of comment with ID 123
        $isOwner = $commentId === 123;
        $result  = $isOwner === true || static::isAdmin($context) === true;

        return $result;
    }
}
