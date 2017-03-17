<?php namespace Limoncello\Tests\Templates;

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
use Limoncello\AppCache\Contracts\FileSystemInterface;
use Limoncello\Templates\Scripts\BaseCacheTemplates;
use Mockery;
use Mockery\Mock;
use Twig_Environment;

/**
 * @package Limoncello\Tests\AppCache
 */
class BaseCacheTemplatesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BaseCacheTemplates
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
     * @var Mock
     */
    private $twigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = Mockery::mock(BaseCacheTemplates::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->mock    = $this->command;

        $this->assertInstanceOf(FileSystemInterface::class, call_user_func([$this->command, 'createFileSystem']));
        $this->assertInstanceOf(
            Twig_Environment::class,
            call_user_func([$this->command, 'createCachingTemplateEngine'], __DIR__, __DIR__)
        );

        $this->fsMock = Mockery::mock(FileSystemInterface::class);
        $this->mock->shouldReceive('createFileSystem')->zeroOrMoreTimes()->withNoArgs()->andReturn($this->fsMock);

        $this->twigMock = Mockery::mock(Twig_Environment::class);
        $this->mock->shouldReceive('createCachingTemplateEngine')
            ->zeroOrMoreTimes()->withAnyArgs()->andReturn($this->twigMock);
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
     * Test cache templates.
     */
    public function testCacheTemplates()
    {
        $templateName = 'templateName';

        $this->twigMock->shouldReceive('loadTemplate')->once()->with($templateName)->andReturnUndefined();

        /** @var Mock $event */
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        call_user_func([$this->command, 'cacheTemplates'], $event, 'templateFolder', 'cacheFolder', [$templateName]);
    }

    /**
     * Test clear cache folder.
     */
    public function testClearCacheFolderOK()
    {
        $cacheFolder     = 'cacheFolder';
        $cacheSubFolder1 = 'cacheSubFolder1';
        $subFolderPath1  = "$cacheFolder/$cacheSubFolder1";

        $this->fsMock->shouldReceive('scanFolder')->once()->with($cacheFolder)->andReturn([$cacheSubFolder1]);
        $this->fsMock->shouldReceive('isFolder')->once()->with($subFolderPath1)->andReturn(true);
        $this->fsMock->shouldReceive('deleteFolderRecursive')->once()->with($subFolderPath1)->andReturn(true);

        /** @var Mock $event */
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        call_user_func([$this->command, 'clearCacheFolder'], $event, $cacheFolder);
    }

    /**
     * Test clear cache folder.
     */
    public function testClearCacheFolderFail()
    {
        $cacheFolder     = 'cacheFolder';
        $cacheSubFolder1 = 'cacheSubFolder1';
        $subFolderPath1  = "$cacheFolder/$cacheSubFolder1";

        $this->fsMock->shouldReceive('scanFolder')->once()->with($cacheFolder)->andReturn([$cacheSubFolder1]);
        $this->fsMock->shouldReceive('isFolder')->once()->with($subFolderPath1)->andReturn(true);
        $this->fsMock->shouldReceive('deleteFolderRecursive')->once()->with($subFolderPath1)->andReturn(false);

        /** @var Mock $event */
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getIO')->once()->withNoArgs()->andReturnSelf();
        $event->shouldReceive('writeError')->once()->withAnyArgs()->andReturnUndefined();

        call_user_func([$this->command, 'clearCacheFolder'], $event, $cacheFolder);
    }
}
