<?php namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies;

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

use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\ContextProperties;

/**
 * The idea about this class is to provide Rules with simple targets that could be replaced with a switch.
 *
 * @package Limoncello\Tests\Auth
 */
abstract class Posts extends General
{
    /** Operation identity */
    const RESOURCE_TYPE = 'posts';

    /**
     * @return PolicyInterface
     */
    public static function getPolicies()
    {
        return (new Policy([
            static::onIndex(),
            static::onRead(),
            static::onUpdate(),
            static::onDelete(),
        ], RuleAlgorithm::firstApplicable())
        )
            ->setTarget(static::target(ContextProperties::PARAM_RESOURCE_TYPE, static::RESOURCE_TYPE))
            ->setName('Posts');
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
    protected static function onUpdate()
    {
        return (new Rule())->setTarget(static::targetOperationUpdate())->setName('update');
    }

    /**
     * @return RuleInterface
     */
    protected static function onDelete()
    {
        return (new Rule())->setTarget(static::targetOperationDelete())->setName('delete');
    }
}
