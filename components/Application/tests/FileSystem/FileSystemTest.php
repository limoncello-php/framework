<?php namespace Limoncello\Tests\Application\FileSystem;

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

use Limoncello\Application\FileSystem\FileSystem;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class FileSystemTest extends TestCase
{
    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @var Mock
     */
    private $mock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystem = Mockery::mock(FileSystem::class)->makePartial();
        $this->mock       = $this->fileSystem;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test exists for files and folders.
     */
    public function testExists(): void
    {
        $this->assertTrue($this->fileSystem->exists(__DIR__));
        $this->assertTrue($this->fileSystem->exists(__FILE__));
        $this->assertFalse($this->fileSystem->exists(__DIR__ . '1234567890'));
        $this->assertFalse($this->fileSystem->exists(__FILE__ . '1234567890'));
    }

    /**
     * Test read.
     */
    public function testRead(): void
    {
        $this->assertContains('class FileSystemTest', $this->fileSystem->read(__FILE__));
    }

    /**
     * Test create/delete for files.
     */
    public function testFileCreateAndDelete(): void
    {
        $tmpFileName = tempnam(null, null);

        $this->assertTrue($this->fileSystem->exists($tmpFileName));
        $this->fileSystem->delete($tmpFileName);
        $this->assertFalse($this->fileSystem->exists($tmpFileName));

        $contents = 'Hello';
        $this->fileSystem->write($tmpFileName, $contents);
        $this->assertTrue($this->fileSystem->exists($tmpFileName));
        $this->assertFalse($this->fileSystem->isFolder($tmpFileName));
        $this->assertEquals($contents, $this->fileSystem->read($tmpFileName));
        $this->fileSystem->delete($tmpFileName);
    }

    /**
     * Test delete non existent file will not fail.
     */
    public function testFileDelete(): void
    {
        $tmpFileName = tempnam(null, null);

        $this->assertTrue($this->fileSystem->exists($tmpFileName));
        $this->fileSystem->delete($tmpFileName);
        $this->assertFalse($this->fileSystem->exists($tmpFileName));

        // delete of non-existing file do not fail
        $this->fileSystem->delete($tmpFileName);
    }

    /**
     * Test folder is writable.
     *
     * @return void
     */
    public function testIsWritable(): void
    {
        $this->assertTrue($this->fileSystem->isWritable(__DIR__));
    }

    /**
     * Test folder scan.
     */
    public function testScanFolder1(): void
    {
        $foundCurrentFile = false;
        foreach ($this->fileSystem->scanFolder(__DIR__) as $fileOrFolder) {
            if (__FILE__ === $fileOrFolder) {
                $foundCurrentFile = true;
                break;
            }
        }

        $this->assertTrue($foundCurrentFile);
    }

    /**
     * Test folder scan.
     */
    public function testScanFolderShouldSeeFolders(): void
    {
        $foundCurrentFolder = false;
        foreach ($this->fileSystem->scanFolder(__DIR__ . DIRECTORY_SEPARATOR . '..') as $fileOrFolder) {
            if (__DIR__ === realpath($fileOrFolder)) {
                $foundCurrentFolder = true;
                break;
            }
        }

        $this->assertTrue($foundCurrentFolder);
    }

    /**
     * Test create/delete for folders.
     */
    public function testFolderCreateAndDelete(): void
    {
        // we create tmp file, remove it and reuse its name as folder name.
        $tmpFolderName = tempnam(null, null);
        $this->fileSystem->delete($tmpFolderName);

        $this->assertFalse($this->fileSystem->exists($tmpFolderName));
        $this->fileSystem->createFolder($tmpFolderName);
        $this->assertTrue($this->fileSystem->exists($tmpFolderName));
        $this->assertTrue($this->fileSystem->isFolder($tmpFolderName));
        $this->fileSystem->deleteFolder($tmpFolderName);
        $this->assertFalse($this->fileSystem->exists($tmpFolderName));
    }

    /**
     * Test recursive delete.
     */
    public function testRecursiveDelete(): void
    {
        $rootPath        = 'root';
        $rootItems       = [
            'root/folder1',
            'root/file1',
        ];
        $folder1Items    = [
            'root/folder1/subFolder1',
            'root/folder1/file11',
        ];
        $subFolder1Items = [];

        $this->mock->shouldReceive('scanFolder')->withArgs([$rootPath])->once()->andReturn($rootItems);
        $this->mock->shouldReceive('scanFolder')->withArgs(['root/folder1'])->once()->andReturn($folder1Items);
        $this->mock->shouldReceive('scanFolder')
            ->withArgs(['root/folder1/subFolder1'])->once()->andReturn($subFolder1Items);

        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1/subFolder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/file1'])->once()->andReturn(false);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1/file11'])->once()->andReturn(false);

        $this->mock->shouldReceive('deleteFolder')->withArgs(['root/folder1/subFolder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('delete')->withArgs(['root/folder1/file11'])->once()->andReturn(true);
        $this->mock->shouldReceive('deleteFolder')->withArgs(['root/folder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('delete')->withArgs(['root/file1'])->once()->andReturn(true);
        $this->mock->shouldReceive('deleteFolder')->withArgs(['root'])->once()->andReturn(true);

        $this->fileSystem->deleteFolderRecursive($rootPath);

        // Mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test recursive delete.
     *
     * @expectedException \Limoncello\Application\Exceptions\FileSystemException
     */
    public function testRecursiveDeleteFailedOnFile(): void
    {
        $rootPath        = 'root';
        $rootItems       = [
            'root/folder1',
            'root/file1',
        ];
        $folder1Items    = [
            'root/folder1/subFolder1',
            'root/folder1/file11',
        ];
        $subFolder1Items = [];

        $this->mock->shouldReceive('scanFolder')->withArgs([$rootPath])->once()->andReturn($rootItems);
        $this->mock->shouldReceive('scanFolder')->withArgs(['root/folder1'])->once()->andReturn($folder1Items);
        $this->mock->shouldReceive('scanFolder')
            ->withArgs(['root/folder1/subFolder1'])->once()->andReturn($subFolder1Items);

        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1/subFolder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1/file11'])->once()->andReturn(false);

        $this->mock->shouldReceive('deleteFolder')->withArgs(['root/folder1/subFolder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('delete')->withArgs(['root/folder1/file11'])->once()->andReturn(false);

        $this->fileSystem->deleteFolderRecursive($rootPath);
    }

    /**
     * Test recursive delete.
     *
     * @expectedException \Limoncello\Application\Exceptions\FileSystemException
     */
    public function testRecursiveDeleteFailedOnFolder(): void
    {
        $rootPath        = 'root';
        $rootItems       = [
            'root/folder1',
            'root/file1',
        ];
        $folder1Items    = [
            'root/folder1/subFolder1',
            'root/folder1/file11',
        ];
        $subFolder1Items = [];

        $this->mock->shouldReceive('scanFolder')->withArgs([$rootPath])->once()->andReturn($rootItems);
        $this->mock->shouldReceive('scanFolder')->withArgs(['root/folder1'])->once()->andReturn($folder1Items);
        $this->mock->shouldReceive('scanFolder')
            ->withArgs(['root/folder1/subFolder1'])->once()->andReturn($subFolder1Items);

        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1/subFolder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('isFolder')->withArgs(['root/folder1/file11'])->once()->andReturn(false);

        $this->mock->shouldReceive('deleteFolder')->withArgs(['root/folder1/subFolder1'])->once()->andReturn(true);
        $this->mock->shouldReceive('delete')->withArgs(['root/folder1/file11'])->once()->andReturn(true);
        $this->mock->shouldReceive('deleteFolder')->withArgs(['root/folder1'])->once()->andReturn(false);

        $this->fileSystem->deleteFolderRecursive($rootPath);
    }

    /**
     * Test symlinks.
     */
    public function testSymlink(): void
    {
        $contents = 'some content';

        // create tmp file with content
        $tmpFileName = tempnam(null, null);
        $this->fileSystem->write($tmpFileName, $contents);

        // tmp name for symlink
        $tmpSymlinkName = tempnam(null, null);
        $this->fileSystem->delete($tmpSymlinkName);

        // create symlink and read content
        $this->fileSystem->symlink($tmpFileName, $tmpSymlinkName);
        $readContent = $this->fileSystem->read($tmpSymlinkName);
        $this->assertEquals($contents, $readContent);

        // clean
        $this->fileSystem->delete($tmpSymlinkName);
        $this->fileSystem->delete($tmpFileName);
    }

    /**
     * Test require file.
     */
    public function testRequire(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'TestArray.php';
        $this->assertNotEmpty($this->fileSystem->requireFile($path));

        // second time to make sure it causes no problems with requiring arrays.
        $this->assertNotEmpty($this->fileSystem->requireFile($path));
    }
}
