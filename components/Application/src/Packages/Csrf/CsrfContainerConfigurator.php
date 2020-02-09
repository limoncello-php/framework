<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Csrf;

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

use Limoncello\Application\Contracts\Csrf\CsrfTokenGeneratorInterface;
use Limoncello\Application\Contracts\Csrf\CsrfTokenStorageInterface;
use Limoncello\Application\Packages\Csrf\CsrfSettings as C;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Session\SessionInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use function assert;

/**
 * @package Limoncello\Application
 */
class CsrfContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $storage = null;
        $factory = function (PsrContainerInterface $container) use (&$storage) {
            if ($storage === null) {
                $storage = static::createStorage($container);
            }

            return $storage;
        };

        $container[CsrfTokenGeneratorInterface::class] = $factory;
        $container[CsrfTokenStorageInterface::class]   = $factory;
    }

    /**
     * @param PsrContainerInterface $container
     *
     * @return CsrfTokenStorageInterface
     */
    private static function createStorage(PsrContainerInterface $container): CsrfTokenStorageInterface
    {
        /** @var SettingsProviderInterface $provider */
        assert($container->has(SettingsProviderInterface::class));
        $provider = $container->get(SettingsProviderInterface::class);
        assert($provider->has(C::class));
        [
            C::TOKEN_STORAGE_KEY_IN_SESSION => $sessionKey,
            C::MAX_TOKENS                   => $maxTokens,
            C::MAX_TOKENS_THRESHOLD         => $maxTokensThreshold,
        ]
            = $provider->get(C::class);

        /** @var SessionInterface $session */
        assert($container->has(SessionInterface::class));
        $session = $container->get(SessionInterface::class);

        $storage = new CsrfTokenStorage($session, $sessionKey, $maxTokens, $maxTokensThreshold);

        return $storage;
    }
}
