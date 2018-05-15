<?php namespace Limoncello\Contracts\Settings\Packages;

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

use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * Provides individual settings for a component.
 *
 * @package Limoncello\Contracts
 */
interface PassportSettingsInterface extends SettingsInterface
{
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
}
