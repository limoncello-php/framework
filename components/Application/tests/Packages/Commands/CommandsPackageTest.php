<?php namespace Limoncello\Tests\Application\Packages\Commands;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Application\Packages\Commands\CommandSettings as C;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class CommandsPackageTest extends TestCase
{
    /**
     * Test settings.
     */
    public function testSettings(): void
    {
        $settings = new C();

        $this->assertNotEmpty($settings->get([]));
    }
}
