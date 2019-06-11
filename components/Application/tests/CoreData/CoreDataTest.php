<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\CoreData;

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

use Limoncello\Application\CoreSettings\CoreData;
use Limoncello\Tests\Application\Data\CoreSettings\Providers\Provider1;
use Limoncello\Tests\Application\TestCase;
use ReflectionException;

/**
 * @package Limoncello\Tests\Application
 */
class CoreDataTest extends TestCase
{
    /**
     * Test compose settings.
     *
     * @throws ReflectionException
     */
    public function testSettings(): void
    {
        $coreSettings = $this->createCoreData();

        $this->assertNotEmpty($coreSettings->get());
    }

    /**
     * @return CoreData
     */
    public static function createCoreData(): CoreData
    {
        $coreSettings = new CoreData(
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'CoreSettings', 'Routes', '*.php']),
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'CoreSettings', 'Configurators', '*.php']),
            [Provider1::class]
        );

        return $coreSettings;
    }
}
