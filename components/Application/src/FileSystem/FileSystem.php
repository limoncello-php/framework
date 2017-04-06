<?php namespace Limoncello\Application\FileSystem;

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

use DirectoryIterator;
use Limoncello\Application\Exceptions\FileSystemException;
use Limoncello\Contracts\FileSystem\FileSystemInterface;

/**
 * @package Limoncello\Application
 */
class FileSystem implements FileSystemInterface
{
    /**
     * @inheritdoc
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @inheritdoc
     */
    public function read(string $filePath): string
    {
        $content = file_get_contents($filePath);
        $content !== false ?: $this->throwEx(new FileSystemException());

        return $content;
    }

    /**
     * @inheritdoc
     */
    public function write(string $filePath, string $contents)
    {
        $bytesWritten = file_put_contents($filePath, $contents);
        $bytesWritten !== false ?: $this->throwEx(new FileSystemException());
    }

    /**
     * @inheritdoc
     */
    public function delete(string $filePath)
    {
        $isDeleted = file_exists($filePath) === false || unlink($filePath) === true;
        $isDeleted === true ?: $this->throwEx(new FileSystemException());
    }

    /**
     * @inheritdoc
     */
    public function scanFolder(string $folderPath): array
    {
        is_dir($folderPath) === true ?: $this->throwEx(new FileSystemException());

        $iterator = call_user_func(function () use ($folderPath) {
            foreach (new DirectoryIterator($folderPath) as $directoryIterator) {
                /** @var DirectoryIterator $directoryIterator */
                if ($directoryIterator->isDot() === false) {
                    yield $directoryIterator->getFilename() => $directoryIterator->getRealPath();
                }
            }
        });

        $result = iterator_to_array($iterator);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function isFolder(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * @inheritdoc
     */
    public function createFolder(string $folderPath)
    {
        $isCreated = mkdir($folderPath);
        $isCreated === true ?: $this->throwEx(new FileSystemException());
    }

    /**
     * @inheritdoc
     */
    public function deleteFolder(string $folderPath)
    {
        $isDeleted = is_dir($folderPath) === true && rmdir($folderPath) === true;
        $isDeleted === true ?: $this->throwEx(new FileSystemException());
    }

    /**
     * @inheritdoc
     */
    public function deleteFolderRecursive(string $folderPath)
    {
        foreach ($this->scanFolder($folderPath) as $path) {
            $this->isFolder($path) === true ? $this->deleteFolderRecursive($path) : $this->delete($path);
        }

        $this->deleteFolder($folderPath);
    }

    /**
     * @param FileSystemException $exception
     *
     * @return void
     */
    protected function throwEx(FileSystemException $exception)
    {
        throw $exception;
    }
}
