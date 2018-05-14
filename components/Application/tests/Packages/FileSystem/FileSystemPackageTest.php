<?php namespace Limoncello\Tests\Application\Packages\FileSystem;

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

use Limoncello\Application\Packages\FileSystem\FileSystemContainerConfigurator;
use Limoncello\Application\Packages\FileSystem\FileSystemProvider;
use Limoncello\Container\Container;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class FileSystemPackageTest extends TestCase
{
    /**
     * Test provider.
     */
    public function testProvider(): void
    {
        $this->assertNotEmpty(FileSystemProvider::getContainerConfigurators());
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $container = new Container();

        FileSystemContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(FileSystemInterface::class));
    }
}
