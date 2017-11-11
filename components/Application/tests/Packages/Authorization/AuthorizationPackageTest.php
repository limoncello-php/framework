<?php namespace Limoncello\Tests\Application\Packages\Authorization;

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

use Limoncello\Application\Packages\Authorization\AuthorizationContainerConfigurator;
use Limoncello\Application\Packages\Authorization\AuthorizationProvider;
use Limoncello\Application\Packages\Authorization\AuthorizationSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Authorization\AuthorizationManagerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Application
 */
class AuthorizationPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider()
    {
        $this->assertNotEmpty(AuthorizationProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator()
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $container[LoggerInterface::class]           = new NullLogger();
        $provider->shouldReceive('get')->once()->with(A::class)->andReturn([
            A::KEY_IS_DEBUG => true,
        ]);
        $appSettings = [];
        $provider->shouldReceive('get')->once()->with(C::class)
            ->andReturn($this->getAuthorizationSettings()->get($appSettings));

        AuthorizationContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(AuthorizationManagerInterface::class));
    }

    /**
     * @return C
     */
    public static function getAuthorizationSettings(): C
    {
        return new class extends C
        {
            /**
             * @inheritdoc
             */
            protected function getSettings(): array
            {
                $policiesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Authorization']);

                return [
                        static::KEY_POLICIES_FOLDER => $policiesFolder,
                    ] + parent::getSettings();
            }
        };
    }
}
