<?php namespace Limoncello\Tests\AppCache;

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

use Composer\Script\Event;
use Limoncello\AppCache\CacheScript;
use Limoncello\AppCache\Contracts\FileSystemInterface;
use Mockery;
use Mockery\Mock;

/**
 * @package Limoncello\Tests\AppCache
 */
class CacheScriptTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheScript
     */
    private $command;

    /**
     * @var Mock
     */
    private $mock;

    /**
     * @var Mock
     */
    private $fsMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = Mockery::mock(CacheScript::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->mock    = $this->command;

        $this->assertInstanceOf(FileSystemInterface::class, call_user_func([$this->command, 'getFileSystem']));

        $this->fsMock = Mockery::mock(FileSystemInterface::class);
        $this->mock->shouldReceive('getFileSystem')->zeroOrMoreTimes()->withNoArgs()->andReturn($this->fsMock);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test compose cache content.
     */
    public function testComposerContent()
    {
        $result = call_user_func_array(
            [$this->command, 'composeContent'],
            ['ClassName', ['some value'], 'method', 'Namespace']
        );
        $this->assertStringStartsWith('<?php namespace ', $result);
    }

    /**
     * Test get cache directory.
     */
    public function testGetCachedDir()
    {
        /** @var Mock $event */
        $event = Mockery::mock(Event::class);

        $event->shouldReceive('getComposer')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('getPackage')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('getAutoload')->once()->withNoArgs()
            ->andReturn(['psr-4' => [CacheScript::CACHED_NAMESPACE . '\\' => __DIR__]]);

        $result = call_user_func([$this->command, 'getFilePath'], $event);
        // CacheScript::CACHED_CLASS actually null as we test abstract class
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . CacheScript::CACHED_CLASS . '.php', $result);
    }

    /**
     * Test get cache directory.
     */
    public function testGetCachedDirNotFound()
    {
        /** @var Mock $event */
        $event = Mockery::mock(Event::class);

        $event->shouldReceive('getComposer')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('getPackage')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('getAutoload')->once()->withNoArgs()->andReturn([]);
        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('writeError')->once()->withAnyArgs()->andReturnUndefined();

        $result = call_user_func([$this->command, 'getFilePath'], $event);
        $this->assertNull($result);
    }

    /**
     * Test get cache directory.
     */
    public function testClearOK()
    {
        /** @var Mock $event */
        $event = Mockery::mock(Event::class);

        $this->mock->shouldReceive('getFilePath')->with($event)->andReturn('some path');
        $this->fsMock->shouldReceive('delete')->with('some path')->andReturn(true);

        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $result = call_user_func([$this->command, 'clear'], $event);
        $this->assertNull($result);
    }

    /**
     * Test get cache directory.
     */
    public function testClearFail()
    {
        /** @var Mock $event */
        $event = Mockery::mock(Event::class);

        $this->mock->shouldReceive('getFilePath')->with($event)->andReturn('some path');
        $this->fsMock->shouldReceive('delete')->with('some path')->andReturn(false);

        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('writeError')->once()->withAnyArgs()->andReturnUndefined();

        $result = call_user_func([$this->command, 'clear'], $event);
        $this->assertNull($result);
    }

    /**
     * Test get cache directory.
     */
    public function testCacheDataOK()
    {
        /** @var Mock $event */
        $event = Mockery::mock(Event::class);

        $this->mock->shouldReceive('getFilePath')->with($event)->andReturn('some path');
        $this->fsMock->shouldReceive('write')->withAnyArgs()->andReturn(true);

        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $result = call_user_func([$this->command, 'cacheData'], [], $event);
        $this->assertNull($result);
    }

    /**
     * Test get cache directory.
     */
    public function testCacheDataFail()
    {
        /** @var Mock $event */
        $event = Mockery::mock(Event::class);

        $this->mock->shouldReceive('getFilePath')->with($event)->andReturn('some path');
        $this->fsMock->shouldReceive('write')->withAnyArgs()->andReturn(false);

        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('writeError')->once()->withAnyArgs()->andReturnUndefined();

        $result = call_user_func([$this->command, 'cacheData'], [], $event);
        $this->assertNull($result);
    }
}
