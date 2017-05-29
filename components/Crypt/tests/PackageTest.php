<?php namespace Limoncello\Tests\Crypt;

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
use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Crypt\Contracts\DecryptInterface;
use Limoncello\Crypt\Contracts\EncryptInterface;
use Limoncello\Crypt\Contracts\HasherInterface;
use Limoncello\Crypt\Package\AsymmetricCryptSettings;
use Limoncello\Crypt\Package\AsymmetricPrivateEncryptPublicDecryptProvider;
use Limoncello\Crypt\Package\AsymmetricPublicEncryptPrivateDecryptProvider;
use Limoncello\Crypt\Package\HasherProvider;
use Limoncello\Crypt\Package\HasherSettings;
use Limoncello\Crypt\Package\SymmetricCryptProvider;
use Limoncello\Crypt\Package\SymmetricCryptSettings;
use Limoncello\Tests\Crypt\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Crypt
 */
class PackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testAsymmetricPrivateEncryptPublicDecrypt1()
    {
        $container = new TestContainer();

        $this->addAsymmetricCryptSettingsProvider($container);

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = AsymmetricPrivateEncryptPublicDecryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(EncryptInterface::class));
        $this->assertNotNull($container->get(DecryptInterface::class));
    }

    /**
     * Test provider.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptConfigurationException
     */
    public function testAsymmetricPrivateEncryptPublicDecrypt2()
    {
        $container = new TestContainer();

        $this->addInvalidAsymmetricCryptSettingsProvider($container);

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = AsymmetricPrivateEncryptPublicDecryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(EncryptInterface::class));
    }

    /**
     * Test provider.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptConfigurationException
     */
    public function testAsymmetricPrivateEncryptPublicDecrypt3()
    {
        $container = new TestContainer();

        $this->addInvalidAsymmetricCryptSettingsProvider($container);

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = AsymmetricPrivateEncryptPublicDecryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(DecryptInterface::class));
    }

    /**
     * Test provider.
     */
    public function testPublicEncryptPrivateDecrypt1()
    {
        $container = new TestContainer();

        $this->addAsymmetricCryptSettingsProvider($container);

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = AsymmetricPublicEncryptPrivateDecryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(EncryptInterface::class));
        $this->assertNotNull($container->get(DecryptInterface::class));
    }

    /**
     * Test provider.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptConfigurationException
     */
    public function testPublicEncryptPrivateDecrypt2()
    {
        $container = new TestContainer();

        $this->addInvalidAsymmetricCryptSettingsProvider($container);

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = AsymmetricPublicEncryptPrivateDecryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(EncryptInterface::class));
    }

    /**
     * Test provider.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptConfigurationException
     */
    public function testPublicEncryptPrivateDecrypt3()
    {
        $container = new TestContainer();

        $this->addInvalidAsymmetricCryptSettingsProvider($container);

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = AsymmetricPublicEncryptPrivateDecryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(DecryptInterface::class));
    }

    /**
     * Test provider.
     */
    public function testSymmetricCrypt()
    {
        $container = new TestContainer();

        $settings = new class extends SymmetricCryptSettings {

            /**
             * @inheritdoc
             */
            protected function getPassword(): string
            {
                return 'secret';
            }
        };
        $this->addSettings($container, SymmetricCryptSettings::class, $settings->get());

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = SymmetricCryptProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(EncryptInterface::class));
        $this->assertNotNull($container->get(DecryptInterface::class));
    }

    /**
     * Test provider.
     */
    public function testHasher()
    {
        $container = new TestContainer();

        /** @var HasherSettings $settings */
        list($settings) = HasherProvider::getSettings();
        $this->addSettings($container, HasherSettings::class, $settings->get());

        /** @var ContainerConfiguratorInterface $configuratorClass */
        list ($configuratorClass) = HasherProvider::getContainerConfigurators();
        $configuratorClass::configureContainer($container);

        $this->assertNotNull($container->get(HasherInterface::class));
    }

    /**
     * @param ContainerInterface $container
     *
     * @return PackageTest
     */
    private function addAsymmetricCryptSettingsProvider(ContainerInterface $container): self
    {
        return $this->addSettings($container, AsymmetricCryptSettings::class, [
            AsymmetricCryptSettings::KEY_PUBLIC_PATH_OR_KEY_VALUE  => $this->getPathToPublicKey(),
            AsymmetricCryptSettings::KEY_PRIVATE_PATH_OR_KEY_VALUE => $this->getPathToPrivateKey(),
        ]);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return PackageTest
     */
    private function addInvalidAsymmetricCryptSettingsProvider(ContainerInterface $container): self
    {
        return $this->addSettings($container, AsymmetricCryptSettings::class, [
            AsymmetricCryptSettings::KEY_PUBLIC_PATH_OR_KEY_VALUE  => null,
            AsymmetricCryptSettings::KEY_PRIVATE_PATH_OR_KEY_VALUE => null,
        ]);
    }

    /**
     * @param ContainerInterface $container
     * @param string             $settingsClass
     * @param array              $settings
     *
     * @return PackageTest
     */
    private function addSettings(ContainerInterface $container, string $settingsClass, array $settings): self
    {
        /** @var Mock $settingsMock */
        $settingsMock = Mockery::mock(SettingsProviderInterface::class);
        $settingsMock->shouldReceive('get')->once()->with($settingsClass)->andReturn($settings);

        $container->offsetSet(SettingsProviderInterface::class, $settingsMock);

        return $this;
    }

    /**
     * @return string
     */
    private function getPathToPublicKey(): string
    {
        return 'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'sample_public_key.pem';
    }

    /**
     * @return string
     */
    private function getPathToPrivateKey(): string
    {
        return 'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'sample_private_key.pem';
    }
}
