<?php namespace Limoncello\Tests\Passport\Package;

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

use Exception;
use Limoncello\Tests\Passport\Data\PassportSettings;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Templates
 */
class PassportSettingsTest extends TestCase
{
    /**
     * Test settings could be instantiated.
     *
     * @throws Exception
     */
    public function testGetSettings()
    {
        $appConfig = [];
        $this->assertNotEmpty((new PassportSettings())->get($appConfig));
    }
}
