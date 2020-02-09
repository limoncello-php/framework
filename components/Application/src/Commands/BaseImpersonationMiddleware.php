<?php declare(strict_types=1);

namespace Limoncello\Application\Commands;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Closure;
use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Commands\MiddlewareInterface;
use Limoncello\Contracts\Passport\PassportAccountInterface;
use Limoncello\Contracts\Settings\Packages\CommandSettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;
use function array_key_exists;
use function assert;
use function call_user_func;
use function is_int;
use function is_string;

/**
 * @package Limoncello\Application
 */
abstract class BaseImpersonationMiddleware implements MiddlewareInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return Closure
     */
    abstract protected static function createReadScopesClosure(ContainerInterface $container): Closure;

    /**
     * @inheritdoc
     */
    public static function handle(
        IoInterface $inOut,
        Closure $next,
        ContainerInterface $container
    ): void {
        /** @var SettingsProviderInterface $provider */
        $provider       = $container->get(SettingsProviderInterface::class);
        $settings       = $provider->get(CommandSettingsInterface::class);
        $userIdentity   = $settings[CommandSettingsInterface::KEY_IMPERSONATE_AS_USER_IDENTITY] ?? null;
        $userProperties = $settings[CommandSettingsInterface::KEY_IMPERSONATE_WITH_USER_PROPERTIES] ?? [];

        /** @var AccountManagerInterface $manager */
        $manager = $container->get(AccountManagerInterface::class);
        $manager->setAccount(
            static::createCliPassport($userIdentity, static::createReadScopesClosure($container), $userProperties)
        );

        call_user_func($next, $inOut);
    }

    /**
     * @param int|string $userIdentity
     * @param Closure    $readUserScopes
     * @param array      $properties
     *
     * @return PassportAccountInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected static function createCliPassport(
        $userIdentity,
        Closure $readUserScopes,
        array $properties
    ): PassportAccountInterface {
        return new class ($userIdentity, $readUserScopes, $properties) implements PassportAccountInterface
        {
            /**
             * @var array
             */
            private $properties;

            /**
             * @var int
             */
            private $userIdentity;

            /**
             * @var Closure
             */
            private $readUserScopes;

            /**
             * @param int|string $userIdentity
             * @param Closure    $readUserScopes
             * @param array      $properties
             */
            public function __construct($userIdentity, Closure $readUserScopes, array $properties)
            {
                assert(is_int($userIdentity) === true || is_string($userIdentity) === true);

                $this->userIdentity   = $userIdentity;
                $this->properties     = $properties;
                $this->readUserScopes = $readUserScopes;
            }

            /**
             * @inheritdoc
             */
            public function hasProperty($key): bool
            {
                return array_key_exists($key, $this->properties);
            }

            /**
             * @inheritdoc
             */
            public function getProperty($key)
            {
                return $this->properties[$key];
            }

            /**
             * @inheritdoc
             */
            public function hasUserIdentity(): bool
            {
                return true;
            }

            /**
             * @inheritdoc
             */
            public function getUserIdentity()
            {
                return $this->userIdentity;
            }

            /**
             * @inheritdoc
             */
            public function hasClientIdentity(): bool
            {
                return false;
            }

            /**
             * @inheritdoc
             */
            public function getClientIdentity()
            {
                return null;
            }

            /**
             * @inheritdoc
             */
            public function hasScope(string $scope): bool
            {
                // we typically do just one call during a session so it's fine to work with unsorted data.
                return in_array($scope, $this->getScopes());
            }

            /**
             * @inheritdoc
             */
            public function hasScopes(): bool
            {
                return true;
            }

            /**
             * @inheritdoc
             */
            public function getScopes(): array
            {
                $scopes = call_user_func($this->readUserScopes, $this->getUserIdentity());

                return $scopes;
            }
        };
    }
}
