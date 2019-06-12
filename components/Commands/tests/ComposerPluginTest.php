<?php declare(strict_types=1);

namespace Limoncello\Tests\Commands;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Composer\Composer;
use Composer\IO\NullIO;
use Limoncello\Commands\CommandConstants;
use Limoncello\Commands\ComposerCommandProvider;
use Limoncello\Commands\ComposerPlugin;
use Mockery;

/**
 * @package Limoncello\Tests\Commands
 */
class ComposerPluginTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * Test plugin.
     */
    public function testActivate()
    {
        /** @var Mockery\Mock $composer */
        $composer = Mockery::mock(Composer::class);

        $fileName = implode(DIRECTORY_SEPARATOR, ['tests', 'Data', 'TestCacheData.php']);
        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('getExtra')->once()->withNoArgs()->andReturn([
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__COMMANDS_CACHE => $fileName,
            ],
        ]);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';
        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('get')->once()->with('vendor-dir')->andReturn($vendorDir);

        /** @var Composer $composer */

        $ioInterface = new NullIO();
        $plugin      = new ComposerPlugin();

        $this->assertNotEmpty($plugin->getCapabilities());

        ComposerCommandProvider::setCommands([]);
        $plugin->activate($composer, $ioInterface);
        /** @noinspection PhpParamsInspection */
        $this->assertCount(2, (new ComposerCommandProvider())->getCommands());
    }
}
