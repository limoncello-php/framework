<?php namespace Limoncello\Tests\Flute\Package;

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

use Limoncello\Flute\Package\FluteProvider;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class FluteProviderTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider()
    {
        $this->assertNotEmpty(FluteProvider::getContainerConfigurators());
        $this->assertNotEmpty(FluteProvider::getMessageDescriptions());
    }
}
