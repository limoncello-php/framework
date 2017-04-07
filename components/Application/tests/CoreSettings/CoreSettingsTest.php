<?php namespace Limoncello\Tests\Application\CoreSettings;

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

use Limoncello\Application\CoreSettings\CoreSettings;
use Limoncello\Tests\Application\Data\CoreSettings\Providers\Provider1;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class CoreSettingsTest extends TestCase
{
    public function testXXX()
    {
        $coreSettings = new CoreSettings(
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'CoreSettings', 'Routes', '*.php']),
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'CoreSettings', 'Configurators', '*.php']),
            [Provider1::class]
        );

        // TODO add actual asserts
        $foo = $coreSettings->get();
    }
}
