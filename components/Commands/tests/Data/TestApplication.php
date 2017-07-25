<?php namespace Limoncello\Tests\Commands\Data;

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

use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\Core\ApplicationInterface;
use Limoncello\Contracts\Core\SapiInterface;
use Mockery;

/**
 * @package Limoncello\Tests\Commands
 */
class TestApplication implements ApplicationInterface
{
    /**
     * @inheritdoc
     */
    public function setSapi(SapiInterface $sapi): ApplicationInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function createContainer(string $method = null, string $path = null): ContainerInterface
    {
        /** @var ContainerInterface $mock */
        $mock = Mockery::mock(ContainerInterface::class);

        return $mock;
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
    }
}
