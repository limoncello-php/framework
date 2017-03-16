<?php namespace Limoncello\AppCache;

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

use Limoncello\AppCache\Contracts\FileSystemInterface;

/**
 * @package Limoncello\AppCache
 */
class FileSystem implements FileSystemInterface
{
    /**
     * @inheritdoc
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * @inheritdoc
     */
    public function read($filePath)
    {
        return file_get_contents($filePath);
    }

    /**
     * @inheritdoc
     */
    public function write($filePath, $contents)
    {
        return file_put_contents($filePath, $contents) !== false;
    }

    /**
     * @inheritdoc
     */
    public function delete($filePath)
    {
        if (file_exists($filePath) === true) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function scanFolder($folderPath)
    {
        return array_diff(scandir($folderPath), ['.', '..']);
    }

    /**
     * @inheritdoc
     */
    public function isFolder($path)
    {
        return is_dir($path);
    }

    /**
     * @inheritdoc
     */
    public function createFolder($folderPath)
    {
        return mkdir($folderPath);
    }

    /**
     * @inheritdoc
     */
    public function deleteFolder($folderPath)
    {
        return rmdir($folderPath);
    }

    /**
     * @inheritdoc
     */
    public function deleteFolderRecursive($folderPath)
    {
        $result = true;
        foreach ($this->scanFolder($folderPath) as $fileOrFolder) {
            if ($result === true) {
                $path   = $folderPath . DIRECTORY_SEPARATOR . $fileOrFolder;
                $result = $this->isFolder($path) === true ? $this->deleteFolderRecursive($path) : $this->delete($path);
            }
        }

        return $result && $this->deleteFolder($folderPath);
    }
}
