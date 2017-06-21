<?php namespace Limoncello\Tests\Passport\Data;

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

use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Templates
 */
class PassportSettings extends \Limoncello\Passport\Package\PassportSettings
{
    /**
     * @inheritdoc
     */
    protected function getApprovalUri(): string
    {
        return '/approve-uri';
    }

    /**
     * @inheritdoc
     */
    protected function getErrorUri(): string
    {
        return '/error-uri';
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultClientId(): string
    {
        return 'default_client_id';
    }

    /**
     * @inheritdoc
     */
    protected function getUserTableName(): string
    {
        return 'user_table';
    }

    /**
     * @inheritdoc
     */
    protected function getUserPrimaryKeyName(): string
    {
        return 'id_user';
    }

    /**
     * @inheritdoc
     */
    protected function getUserCredentialsValidator(): callable
    {
        return [static::class, 'validateUser'];
    }

    /**
     * @inheritdoc
     */
    protected function getUserScopeValidator(): callable
    {
        return [static::class, 'validateScope'];
    }

    /**
     * @inheritdoc
     */
    protected function getTokenCustomPropertiesProvider()
    {
        assert(parent::getTokenCustomPropertiesProvider() === null);

        return [self::class,  'tokenCustomPropertiesProvider'];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $userName
     * @param string             $password
     *
     * @return int|null
     */
    public static function validateUser(ContainerInterface $container, string $userName, string $password)
    {
        assert($container || $userName || $password);

        return 123;
    }

    /**
     * @param ContainerInterface $container
     * @param int                $userId
     * @param array|null         $scope
     *
     * @return null|array
     */
    public static function validateScope(ContainerInterface $container, int $userId, array $scope = null)
    {
        assert($container || $userId || $scope);

        return null;
    }

    /**
     * @param ContainerInterface $container
     * @param TokenInterface     $token
     *
     * @return array
     */
    public static function tokenCustomPropertiesProvider(ContainerInterface $container, TokenInterface $token): array
    {
        assert($container !== null);

        return [
            'user_id' => $token->getUserIdentifier(),
        ];
    }
}
