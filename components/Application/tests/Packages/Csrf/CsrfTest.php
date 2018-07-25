<?php namespace Limoncello\Tests\Application\Packages\Csrf;

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

use ArrayIterator;
use Countable;
use Limoncello\Application\Contracts\Csrf\CsrfTokenGeneratorInterface;
use Limoncello\Application\Contracts\Csrf\CsrfTokenStorageInterface;
use Limoncello\Application\Packages\Csrf\CsrfContainerConfigurator;
use Limoncello\Application\Packages\Csrf\CsrfSettings as C;
use Limoncello\Container\Container;
use Limoncello\Contracts\Session\SessionInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;
use ReflectionException;

/**
 * @package Limoncello\Tests\Application
 */
class CsrfTest extends TestCase
{
    /** @var SessionInterface */
    private $session;

    /** @var Container */
    private $container;

    /**
     * @inheritdoc
     *
     * @throws ReflectionException
     */
    protected function setUp()
    {
        parent::setUp();

        CsrfContainerConfigurator::configureContainer($container = new Container());

        /** @var Mock $provider */
        $provider                                    = Mockery::mock(SettingsProviderInterface::class);
        $container[SettingsProviderInterface::class] = $provider;

        $provider->shouldReceive('has')->zeroOrMoreTimes()->with(C::class)->andReturn(true);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($this->getDefaultCsrfSettings());

        $container[SessionInterface::class] = $this->session = new class implements SessionInterface, Countable
        {
            private $session = [];

            /**
             * @inheritdoc
             */
            public function getIterator()
            {
                return new ArrayIterator($this->session);
            }

            /**
             * @inheritdoc
             */
            public function offsetExists($offset)
            {
                return isset($this->session[$offset]);
            }

            /**
             * @inheritdoc
             */
            public function offsetGet($offset)
            {
                return $this->session[$offset];
            }

            /**
             * @inheritdoc
             */
            public function offsetSet($offset, $value)
            {
                $this->session[$offset] = $value;
            }

            /**
             * @inheritdoc
             */
            public function offsetUnset($offset)
            {
                unset($this->session[$offset]);
            }

            /**
             * @inheritdoc
             */
            public function count()
            {
                return count($this->session);
            }
        };

        $this->container = $container;
    }

    /**
     * Test container configurator.
     */
    public function testContainerConfigurator(): void
    {
        $this->assertNotNull($this->container->get(CsrfTokenGeneratorInterface::class));
        $this->assertNotNull($this->container->get(CsrfTokenStorageInterface::class));
        $this->assertSame(
            $this->container->get(CsrfTokenGeneratorInterface::class),
            $this->container->get(CsrfTokenStorageInterface::class)
        );
    }

    /**
     * Test create token.
     *
     * @throws ReflectionException
     */
    public function testCreateAndCheckToken(): void
    {
        /** @var CsrfTokenStorageInterface $storage */
        $storage = $this->container->get(CsrfTokenStorageInterface::class);

        $this->assertCount(0, $this->getTokenStorageFromSession());
        $this->assertNotEmpty($token = $storage->create());
        $this->assertCount(1, $this->getTokenStorageFromSession());

        // now test check
        $this->assertTrue($storage->check($token));
        $this->assertCount(0, $this->getTokenStorageFromSession());
        $this->assertFalse($storage->check($token));
        $this->assertCount(0, $this->getTokenStorageFromSession());
    }

    /**
     * Test create token.
     *
     * @throws ReflectionException
     */
    public function testCreateTokenWithGarbageCollection(): void
    {
        [C::MAX_TOKENS => $maxTokens, C::MAX_TOKENS_THRESHOLD => $gcThreshold] = $this->getDefaultCsrfSettings();
        $this->assertGreaterThan(0, $maxTokens);
        $this->assertGreaterThan(1, $gcThreshold);

        /** @var CsrfTokenStorageInterface $storage */
        $storage = $this->container->get(CsrfTokenStorageInterface::class);

        for ($i = 0; $i < $maxTokens + $gcThreshold; ++$i) {
            $this->assertNotEmpty($storage->create());
        }
        $this->assertCount($maxTokens + $gcThreshold, $this->getTokenStorageFromSession());

        // add one more token and make sure that multiple tokens were trashed
        $this->assertNotEmpty($storage->create());
        $this->assertCount($maxTokens, $this->getTokenStorageFromSession());
    }

    /**
     * @return array
     *
     * @throws ReflectionException
     */
    private function getTokenStorageFromSession(): array
    {
        [C::TOKEN_STORAGE_KEY_IN_SESSION => $key] = $this->getDefaultCsrfSettings();
        $storage = $this->session[$key] ?? [];

        return $storage;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private function getDefaultCsrfSettings(): array
    {
        $appConfig       = [];
        $defaultSettings = (new C())->get($appConfig);

        return $defaultSettings;
    }
}
