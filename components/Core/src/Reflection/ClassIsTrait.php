<?php namespace Limoncello\Core\Reflection;

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

use Exception;
use Generator;
use GlobIterator;
use ReflectionClass;

/**
 * @package Limoncello\Core
 */
trait ClassIsTrait
{
    /**
     * @param string $class
     * @param string $interface
     *
     * @return bool
     */
    protected static function classImplements(string $class, string $interface): bool
    {
        assert(class_exists($class));
        assert(interface_exists($interface));

        $implements = array_key_exists($interface, class_implements($class));

        return $implements;
    }

    /**
     * @param string $class
     * @param string $parentClass
     *
     * @return bool
     */
    protected static function classExtends(string $class, string $parentClass): bool
    {
        assert(class_exists($class));
        assert(class_exists($parentClass));

        $isParent = array_key_exists($parentClass, class_parents($class));

        return $isParent;
    }

    /**
     * @param string $class
     * @param string $classOrInterface
     *
     * @return bool
     */
    protected static function classInherits(string $class, string $classOrInterface): bool
    {
        assert(class_exists($class));
        assert(class_exists($classOrInterface) || interface_exists($classOrInterface));

        $isSubclass = is_subclass_of($class, $classOrInterface);

        return $isSubclass;
    }

    /**
     * @param string[] $classes
     * @param string   $interface
     *
     * @return Generator
     */
    protected static function selectClassImplements(array $classes, string $interface): Generator
    {
        foreach ($classes as $className) {
            if (static::classImplements($className, $interface) === true) {
                yield $className;
            }
        }
    }

    /**
     * @param string[] $classes
     * @param string   $parentClass
     *
     * @return Generator
     */
    protected static function selectClassExtends(array $classes, string $parentClass): Generator
    {
        foreach ($classes as $className) {
            if (static::classExtends($className, $parentClass) === true) {
                yield $className;
            }
        }
    }

    /**
     * @param string[] $classes
     * @param string   $classOrInterface
     *
     * @return Generator
     */
    protected static function selectClassInherits(array $classes, string $classOrInterface): Generator
    {
        foreach ($classes as $className) {
            if (static::classInherits($className, $classOrInterface) === true) {
                yield $className;
            }
        }
    }

    /**
     * Reads file(s) by specified path mask and select only those which implement given class or interface.
     *
     * @param string $path
     * @param string $classOrInterface
     *
     * @return Generator
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function selectClasses(string $path, string $classOrInterface): Generator
    {
        $selectedFiles = [];

        $flags = GlobIterator::SKIP_DOTS | GlobIterator::CURRENT_AS_PATHNAME;
        foreach (new GlobIterator($path, $flags) as $filePath) {
            if (is_file($filePath) === true) {
                $filePath = realpath($filePath);
                try {
                    /** @noinspection PhpIncludeInspection */
                    require_once $filePath;
                } catch (Exception $ex) {
                    // Files might have syntax errors and etc.
                    // For the purposes of this method it doesn't matter so just skip it.
                    continue;
                }
                $selectedFiles[$filePath] = true;
            }
        }

        foreach (get_declared_classes() as $class) {
            // if class actually implements requested one and ...
            if ($class === $classOrInterface || static::classInherits($class, $classOrInterface) === true) {
                // ... it was loaded from a file we've selected then...
                $reflectionClass = new ReflectionClass($class);
                if ($reflectionClass->isInstantiable() === true &&
                    ($classFileName = $reflectionClass->getFileName()) !== false &&
                    array_key_exists($classFileName, $selectedFiles) === true
                ) {
                    // ... that's what we need.
                    yield $class;
                }
            }
        }
    }
}
