<?php namespace Limoncello\Tests\Testing;

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

use Limoncello\Tests\Testing\Data\ApplicationWrapper;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Testing
 */
class ApplicationWrapperTraitTest extends TestCase
{
    /**
     * Test wrapper methods.
     */
    public function testWrapperMethods(): void
    {
        $onRequest          = false;
        $onResponse         = false;
        $onContainerCreated = false;
        $onContainerLast    = false;

        $wrapper = new ApplicationWrapper();

        $wrapper->addOnHandleRequest(function () use (&$onRequest) {
            $onRequest = true;
        });
        $wrapper->addOnHandleResponse(function () use (&$onResponse) {
            $onResponse = true;
        });
        $wrapper->addOnContainerCreated(function () use (&$onContainerCreated) {
            $onContainerCreated = true;
        });
        $wrapper->addOnContainerLastConfigurator(function () use (&$onContainerLast) {
            $onContainerLast = true;
        });

        $this->assertNotNull($container = $wrapper->invokeCreateContainer());
        $this->assertTrue($onContainerCreated);
        $this->assertFalse($onContainerLast);
        $this->assertFalse($onRequest);
        $this->assertFalse($onResponse);
        $onContainerCreated = false;

        $wrapper->invokeConfigureContainer($container);
        $this->assertFalse($onContainerCreated);
        $this->assertTrue($onContainerLast);
        $this->assertFalse($onRequest);
        $this->assertFalse($onResponse);
        $onContainerLast = false;

        $handler = function () {
        };
        $wrapper->invokeHandleRequest($handler);
        $this->assertFalse($onContainerCreated);
        $this->assertFalse($onContainerLast);
        $this->assertTrue($onRequest);
        $this->assertTrue($onResponse);

        $this->assertTrue($wrapper->invokeGetContainer() instanceof ContainerInterface);
    }
}
