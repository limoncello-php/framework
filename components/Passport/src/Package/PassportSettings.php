<?php namespace Limoncello\Passport\Package;

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

use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Core\Reflection\CheckCallableTrait;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionParameter;

/**
 * @package Limoncello\Passport
 */
class PassportSettings implements SettingsInterface
{
    use CheckCallableTrait;

    /** Config key */
    const KEY_IS_LOG_ENABLED = 0;

    /** Config key */
    const KEY_APPROVAL_URI_STRING = self::KEY_IS_LOG_ENABLED + 1;

    /** Config key */
    const KEY_ERROR_URI_STRING = self::KEY_APPROVAL_URI_STRING + 1;

    /** Config key */
    const KEY_DEFAULT_CLIENT_ID = self::KEY_ERROR_URI_STRING + 1;

    /** Config key */
    const KEY_CODE_EXPIRATION_TIME_IN_SECONDS = self::KEY_DEFAULT_CLIENT_ID + 1;

    /** Config key */
    const KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS = self::KEY_CODE_EXPIRATION_TIME_IN_SECONDS + 1;

    /** Config key */
    const KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH = self::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS + 1;

    /** Config key */
    const KEY_USER_TABLE_NAME = self::KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH + 1;

    /** Config key */
    const KEY_USER_PRIMARY_KEY_NAME = self::KEY_USER_TABLE_NAME + 1;

    /**
     * Config key
     *
     * Value should be a static callable for user credentials validator (login and password).
     *
     * Examples ['SomeNamespace\ClassName', 'staticMethodName'] or 'SomeNamespace\ClassName::staticMethodName'
     *
     * Method signature
     *
     * public static function validateUser(ContainerInterface $container, string $userName, string $password)
     *
     * which returns either user ID (int|string) or null if user not found/invalid credentials.
     */
    const KEY_USER_CREDENTIALS_VALIDATOR = self::KEY_USER_PRIMARY_KEY_NAME + 1;

    /** Config key */
    const KEY_FAILED_CUSTOM_UNAUTHENTICATED_FACTORY = self::KEY_USER_CREDENTIALS_VALIDATOR + 1;

    /**
     * Config key
     *
     * Value should be a static callable for user scope validator (allowed scope identities).
     *
     * Examples ['SomeNamespace\ClassName', 'staticMethodName'] or 'SomeNamespace\ClassName::staticMethodName'
     *
     * Method signature
     *
     * public static function validateScope(ContainerInterface $container, int $userId, array $scopeIds = null): ?array
     *
     * which returns either changed allowed scope IDs or null if scope was not changed or throws auth exception.
     */
    const KEY_USER_SCOPE_VALIDATOR = self::KEY_FAILED_CUSTOM_UNAUTHENTICATED_FACTORY + 1;

    /**
     * Config key
     *
     * A custom properties provider for auth token. All the values returned from the provider
     * will be added to the token.
     *
     * Value should be a static callable.
     *
     * Examples ['SomeNamespace\ClassName', 'staticMethodName'] or 'SomeNamespace\ClassName::staticMethodName'
     *
     * Method signature
     *
     * public static function getExtraProps(ContainerInterface $container, TokenInterface $token): array
     */
    const KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER = self::KEY_USER_SCOPE_VALIDATOR + 1;

    /** Config key */
    const KEY_LAST = self::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER;

    /**
     * @inheritdoc
     */
    final public function get(): array
    {
        $defaults = $this->getSettings();

        $credentialsValidator = $defaults[static::KEY_USER_CREDENTIALS_VALIDATOR];
        assert(
            $this->checkPublicStaticCallable(
                $credentialsValidator,
                [ContainerInterface::class, 'string', 'string']
            ),
            "Invalid credentials validator."
        );

        $scopeValidator = $defaults[static::KEY_USER_SCOPE_VALIDATOR] ?? null;
        assert(
            $this->checkPublicStaticCallable(
                $scopeValidator,
                [
                    ContainerInterface::class,
                    'int',
                    function (ReflectionParameter $parameter) {
                        return $parameter->allowsNull() === true && $parameter->isArray() === true;
                    },
                ]
            ),
            "Invalid scope validator."
        );

        $customPropsProvider = $defaults[static::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER] ?? null;
        assert(
            $customPropsProvider === null ||
            $this->checkPublicStaticCallable(
                $customPropsProvider,
                [ContainerInterface::class, TokenInterface::class],
                'array'
            ),
            "Invalid token custom properties provider."
        );

        $customUnAuthFactory = $defaults[static::KEY_FAILED_CUSTOM_UNAUTHENTICATED_FACTORY] ?? null;
        assert(
            $customUnAuthFactory === null ||
            $this->checkPublicStaticCallable(
                $customUnAuthFactory,
                [],
                ResponseInterface::class
            ),
            "Invalid custom factory."
        );

        $approvalUri = $defaults[static::KEY_APPROVAL_URI_STRING];
        assert(empty($approvalUri) === false, "Invalid Approval URI `$approvalUri`.");

        $errorUri = $defaults[static::KEY_ERROR_URI_STRING];
        assert(empty($errorUri) === false, "Invalid Error URI `$errorUri`.");

        $defaultClientId = $defaults[static::KEY_DEFAULT_CLIENT_ID];
        assert(empty($defaultClientId) === false, "Invalid Default Client ID `$defaultClientId`.");

        $userTable = $defaults[static::KEY_USER_TABLE_NAME];
        assert(empty($userTable) === false, "Invalid User Table Name `$userTable`.");

        $userPk = $defaults[static::KEY_USER_TABLE_NAME];
        assert(empty($userPk) === false, "Invalid User Primary Key Name `$userPk`.");

        return $defaults + [
                static::KEY_APPROVAL_URI_STRING              => $approvalUri,
                static::KEY_ERROR_URI_STRING                 => $errorUri,
                static::KEY_DEFAULT_CLIENT_ID                => $defaultClientId,
                static::KEY_USER_TABLE_NAME                  => $userTable,
                static::KEY_USER_PRIMARY_KEY_NAME            => $userPk,
                static::KEY_USER_CREDENTIALS_VALIDATOR       => $credentialsValidator,
                static::KEY_USER_SCOPE_VALIDATOR             => $scopeValidator,
                static::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER => $customPropsProvider,
            ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_IS_LOG_ENABLED                       => false,
            static::KEY_CODE_EXPIRATION_TIME_IN_SECONDS      => 10 * 60,
            static::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS     => 60 * 60,
            static::KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH => true,
        ];
    }
}
