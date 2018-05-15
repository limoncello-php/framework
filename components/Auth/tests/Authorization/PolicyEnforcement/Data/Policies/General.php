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

use Limoncello\Auth\Authorization\PolicyAdministration\AllOf;
use Limoncello\Auth\Authorization\PolicyAdministration\AnyOf;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyAdministration\Target;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\ContextProperties;

/**
 * @package Limoncello\Tests\Auth
 */
abstract class General
{
    /** Operation identity */
    const OPERATION_CREATE = 'create';

    /** Operation identity */
    const OPERATION_READ = 'read';

    /** Operation identity */
    const OPERATION_UPDATE = 'update';

    /** Operation identity */
    const OPERATION_DELETE = 'delete';

    /** Operation identity */
    const OPERATION_INDEX = 'index';

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function isAdmin(ContextInterface $context)
    {
        $curUserRole = $context->get(ContextProperties::PARAM_CURRENT_USER_ROLE);
        $result      = $curUserRole === 'admin';

        return $result;
    }

    /**
     * @param string|int       $key
     * @param string|int|float $value (any scalar)
     *
     * @return TargetInterface
     */
    protected static function target($key, $value)
    {
        return static::targetMulti([$key => $value]);
    }

    /**
     *
     * @param array $properties
     *
     * @return TargetInterface
     */
    protected static function targetMulti(array $properties)
    {
        $target = new Target(new AnyOf([new AllOf($properties)]));

        $stringPairs = [];
        foreach ($properties as $key => $value) {
            $stringPairs[] = "$key=$value";
        }
        $target->setName(implode(',', $stringPairs));

        return $target;
    }

    /**
     * @return TargetInterface
     */
    protected static function targetOperationRead()
    {
        return static::target(ContextProperties::PARAM_OPERATION, static::OPERATION_READ);
    }

    /**
     * @return TargetInterface
     */
    protected static function targetOperationUpdate()
    {
        return static::target(ContextProperties::PARAM_OPERATION, static::OPERATION_UPDATE);
    }

    /**
     * @return TargetInterface
     */
    protected static function targetOperationDelete()
    {
        return static::target(ContextProperties::PARAM_OPERATION, static::OPERATION_DELETE);
    }

    /**
     * @return TargetInterface
     */
    protected static function targetOperationIndex()
    {
        return static::target(ContextProperties::PARAM_OPERATION, static::OPERATION_INDEX);
    }

    /**
     * @return RuleInterface
     */
    protected static function rulePermit()
    {
        return (new Rule())->setName('permit');
    }
}
