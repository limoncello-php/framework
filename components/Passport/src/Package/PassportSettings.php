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
use ReflectionParameter;

/**
 * @package Limoncello\Passport
 */
abstract class PassportSettings implements SettingsInterface
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

    /** Config key */
    const KEY_USER_CREDENTIALS_VALIDATOR = self::KEY_USER_PRIMARY_KEY_NAME + 1;

    /** Config key */
    const KEY_FAILED_AUTHENTICATION_FACTORY = self::KEY_USER_CREDENTIALS_VALIDATOR + 1;

    /** Config key */
    const KEY_USER_SCOPE_VALIDATOR = self::KEY_FAILED_AUTHENTICATION_FACTORY + 1;

    /** Config key */
    const KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER = self::KEY_USER_SCOPE_VALIDATOR + 1;

    /** Config key */
    const KEY_LAST = self::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER + 1;

    /**
     * @return string
     */
    abstract protected function getApprovalUri(): string;

    /**
     * @return string
     */
    abstract protected function getErrorUri(): string;

    /**
     * @return string
     */
    abstract protected function getDefaultClientId(): string;

    /**
     * @return string
     */
    abstract protected function getUserTableName(): string;

    /**
     * @return string
     */
    abstract protected function getUserPrimaryKeyName(): string;

    /**
     * Should return static callable for user credentials validator (login and password).
     *
     * Examples ['SomeNamespace\ClassName', 'staticMethodName'] or 'SomeNamespace\ClassName::staticMethodName'
     *
     * Method signature
     *
     * public static function validateUser(ContainerInterface $container, string $userName, string $password): ?int
     *
     * which returns either user ID (int) or null if user not found/invalid credentials.
     *
     * @return callable
     */
    abstract protected function getUserCredentialsValidator(): callable;

    /**
     * Should return static callable for user scope validator (allowed scope identities).
     *
     * Examples ['SomeNamespace\ClassName', 'staticMethodName'] or 'SomeNamespace\ClassName::staticMethodName'
     *
     * Method signature
     *
     * public static function validateScope(ContainerInterface $container, int $userId, array $scopeIds = null): ?array
     *
     * which returns either changed allowed scope IDs or null if scope was not changed or throws auth exception.
     *
     * @return callable
     */
    abstract protected function getUserScopeValidator(): callable;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        $credentialsValidator = $this->getUserCredentialsValidator();
        $scopeValidator       = $this->getUserScopeValidator();
        $customPropsProvider  = $this->getTokenCustomPropertiesProvider();

        // check that validators are valid callable (static with proper in/out signature).
        assert($this->checkPublicStaticCallable(
            $credentialsValidator,
            [ContainerInterface::class, 'string', 'string']
        ));
        assert($this->checkPublicStaticCallable(
            $scopeValidator,
            [
                ContainerInterface::class,
                'int',
                function (ReflectionParameter $parameter) {
                    return $parameter->allowsNull() === true && $parameter->isArray() === true;
                }
            ]
        ));
        assert($customPropsProvider === null || $this->checkPublicStaticCallable(
            $customPropsProvider,
            [ContainerInterface::class, TokenInterface::class],
            'array'
        ));

        return [
            static::KEY_IS_LOG_ENABLED                       => false,
            static::KEY_APPROVAL_URI_STRING                  => $this->getApprovalUri(),
            static::KEY_ERROR_URI_STRING                     => $this->getErrorUri(),
            static::KEY_DEFAULT_CLIENT_ID                    => $this->getDefaultClientId(),
            static::KEY_CODE_EXPIRATION_TIME_IN_SECONDS      => 10 * 60,
            static::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS     => 60 * 60,
            static::KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH => true,
            static::KEY_USER_TABLE_NAME                      => $this->getUserTableName(),
            static::KEY_USER_PRIMARY_KEY_NAME                => $this->getUserPrimaryKeyName(),
            static::KEY_USER_CREDENTIALS_VALIDATOR           => $credentialsValidator,
            static::KEY_USER_SCOPE_VALIDATOR                 => $scopeValidator,
            static::KEY_TOKEN_CUSTOM_PROPERTIES_PROVIDER     => $customPropsProvider,
        ];
    }

    /**
     * @return null|callable (static)
     */
    protected function getTokenCustomPropertiesProvider(): ?callable
    {
        return null;
    }
}
