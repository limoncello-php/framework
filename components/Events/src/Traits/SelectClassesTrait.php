<?php namespace Limoncello\Events\Traits;

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

use GlobIterator;
use Generator;
use ReflectionClass;

/**
 * @package Limoncello\Events
 */
trait SelectClassesTrait
{
    // TODO this trait and other similar reflection traits are/could be used on more than places. Move it to lib.

    /**
     * Reads file(s) by specified path mask and select only those which implement given class or interface.
     *
     * @param string $path
     * @param string $implementClassName
     *
     * @return Generator
     */
    protected function selectClasses(string $path, string $implementClassName): Generator
    {
        $selectedFiles = [];

        $flags = GlobIterator::SKIP_DOTS | GlobIterator::CURRENT_AS_PATHNAME;
        foreach (new GlobIterator($path, $flags) as $filePath) {
            if (is_file($filePath) === true) {
                $filePath = realpath($filePath);
                /** @noinspection PhpIncludeInspection */
                require_once $filePath;
                $selectedFiles[$filePath] = true;
            }
        }

        foreach (get_declared_classes() as $class) {
            // if class actually implements requested one and ...
            if ($class === $implementClassName || is_subclass_of($class, $implementClassName) === true) {
                // ... it was loaded from a file we've selected then...
                if (array_key_exists((new ReflectionClass($class))->getFileName(), $selectedFiles) === true) {
                    // ... that's what we need.
                    yield $class;
                }
            }
        }
    }
}
