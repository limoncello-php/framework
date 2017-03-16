<?php namespace Limoncello\AppCache\Contracts;

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

/**
 * @package Limoncello\AppCache
 */
interface FileSystemInterface
{
    /**
     * @param string $path
     *
     * @return bool
     */
    public function exists($path);

    /**
     * @param string $filePath
     *
     * @return string|false
     */
    public function read($filePath);

    /**
     * @param string $filePath
     * @param string $contents
     *
     * @return bool
     */
    public function write($filePath, $contents);

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public function delete($filePath);

    /**
     * @param string $folderPath
     *
     * @return array|bool
     */
    public function scanFolder($folderPath);

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isFolder($path);

    /**
     * @param string $folderPath
     *
     * @return bool
     */
    public function createFolder($folderPath);

    /**
     * @param string $folderPath
     *
     * @return bool
     */
    public function deleteFolder($folderPath);

    /**
     * @param string $folderPath
     *
     * @return bool
     */
    public function deleteFolderRecursive($folderPath);
}
