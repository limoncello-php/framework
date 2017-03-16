<?php namespace Limoncello\Tests\Core\Config;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Core\Config\ConfigManager;
use Limoncello\Tests\Core\TestCase;

/**
 * @package Limoncello\Tests\Core
 */
class ConfigManagerTest extends TestCase
{
    /**
     * Test loading configs from files.
     */
    public function testLoadConfigsFromFiles()
    {
        $configManager = new ConfigManager();
        $configs       = $configManager->loadConfigs('Limoncello\\Core\\Routing', __DIR__ . '/../../src/Routing');
        $this->assertNotNull($configs);
        $this->assertCount(1, $configs->getConfigInterfaces());
    }
}
