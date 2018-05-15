<?php namespace Limoncello\Tests\Passport\Data;

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

use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @package Limoncello\Tests\Templates
 */
class PassportSettings extends \Limoncello\Passport\Package\PassportSettings
{
    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        return [

                static::KEY_APPROVAL_URI_STRING                   => '/approve-uri',
                static::KEY_ERROR_URI_STRING                      => '/error-uri',
                static::KEY_DEFAULT_CLIENT_ID                     => 'default_client_id',
                static::KEY_USER_TABLE_NAME                       => 'user_table',
                static::KEY_USER_PRIMARY_KEY_NAME                 => 'id_user',
                static::KEY_USER_CREDENTIALS_VALIDATOR            => [static::class, 'validateUser'],
                static::KEY_USER_SCOPE_VALIDATOR                  => [static::class, 'validateScope'],
                static::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER      => [self::class, 'tokenCustomPropertiesProvider'],
                static::KEY_FAILED_CUSTOM_UNAUTHENTICATED_FACTORY => [self::class, 'customUnAuthFactory'],

            ] + parent::getSettings();
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
    public static function validateScope(ContainerInterface $container, int $userId, array $scope = null): ?array
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

    /**
     * @return ResponseInterface
     */
    public static function customUnAuthFactory(): ResponseInterface
    {
        return new EmptyResponse(401);
    }
}
