<?php namespace Limoncello\Crypt\Package;

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

use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Crypt\Contracts\DecryptInterface;
use Limoncello\Crypt\Contracts\EncryptInterface;
use Limoncello\Crypt\Exceptions\CryptConfigurationException;
use Limoncello\Crypt\Package\AsymmetricCryptSettings as C;
use Limoncello\Crypt\PrivateKeyAsymmetricEncrypt;
use Limoncello\Crypt\PublicKeyAsymmetricDecrypt;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Crypt
 */
class AsymmetricPrivateEncryptPublicDecryptContainerConfigurator implements ContainerConfiguratorInterface
{
    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container)
    {
        $container[EncryptInterface::class] = function (PsrContainerInterface $container) {
            $settings  = $container->get(SettingsProviderInterface::class)->get(C::class);
            $keyOrPath = $settings[C::KEY_PRIVATE_PATH_OR_KEY_VALUE] ?? null;
            if (empty($keyOrPath) === true) {
                throw new CryptConfigurationException();
            }

            $crypt = new PrivateKeyAsymmetricEncrypt($keyOrPath);

            return $crypt;
        };

        $container[DecryptInterface::class] = function (PsrContainerInterface $container) {
            $settings  = $container->get(SettingsProviderInterface::class)->get(C::class);
            $keyOrPath = $settings[C::KEY_PUBLIC_PATH_OR_KEY_VALUE] ?? null;
            if (empty($keyOrPath) === true) {
                throw new CryptConfigurationException();
            }

            $crypt = new PublicKeyAsymmetricDecrypt($keyOrPath);

            return $crypt;
        };
    }
}
