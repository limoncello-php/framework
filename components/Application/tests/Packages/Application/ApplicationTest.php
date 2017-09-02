<?php namespace Limoncello\Tests\Application\Packages\Application;

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

use Closure;
use ErrorException;
use Exception;
use Limoncello\Application\Packages\Application\Application;
use Limoncello\Application\Settings\CacheSettingsProvider;
use Limoncello\Application\Settings\InstanceSettingsProvider;
use Limoncello\Container\Container;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Tests\Application\CoreSettings\CoreSettingsTest;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @package Limoncello\Tests\Application
 */
class ApplicationTest extends TestCase
{
    /**
     * Test create container.
     */
    public function testCreateContainerOnTheFly()
    {
        $application = $this->createApplication();

        $this->assertNotNull($application->createContainer('SOME_METHOD', '/some_path'));
    }

    /**
     * Test create container.
     */
    public function testCreateContainerFromCache()
    {
        /** @var callable $settingCacheMethod */
        $settingCacheMethod = [static::class, 'getCachedSettings'];
        $application        = $this->createApplication($settingCacheMethod);

        $this->assertNotNull($application->createContainer('SOME_METHOD', '/some_path'));
    }

    /**
     * @return array
     */
    public static function getCachedSettings(): array
    {
        $provider = new InstanceSettingsProvider();

        $provider->register(CoreSettingsTest::createCoreSettings());

        $cached = (new CacheSettingsProvider())->setInstanceSettings($provider)->serialize();

        return $cached;
    }

    /**
     * @param null|string|array|callable $settingCacheMethod
     *
     * @return Application
     */
    private function createApplication($settingCacheMethod = null): Application
    {
        $settingsPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Application', 'Settings', '*.php']);
        $application  = new Application($settingsPath, $settingCacheMethod);

        return $application;
    }

    /**
     * @return Application
     */
    private function createApplicationWithLastErrorMock(): Application
    {
        $settingsPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Data', 'Application', 'Settings', '*.php']);
        $application  = Mockery::mock(Application::class . '[getLastError]', [$settingsPath]);
        $application->shouldAllowMockingProtectedMethods();

        /** @var Application $application */

        return $application;
    }
}
