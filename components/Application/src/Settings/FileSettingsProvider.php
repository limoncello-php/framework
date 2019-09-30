<?php declare(strict_types=1);

namespace Limoncello\Application\Settings;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Application\Exceptions\InvalidSettingsClassException;
use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Contracts\Settings\SettingsInterface;
use ReflectionClass;
use ReflectionException;
use function assert;

/**
 * @package Limoncello\Application
 */
class FileSettingsProvider extends InstanceSettingsProvider
{
    use ClassIsTrait;

    /**
     * @param string $path
     *
     * @return FileSettingsProvider
     *
     * @throws ReflectionException
     */
    public function load(string $path): FileSettingsProvider
    {
        foreach ($this->selectClasses($path, SettingsInterface::class) as $settingsClass) {
            assert($this->checkDoNotHaveRequiredParametersOnCreate($settingsClass));
            /** @var SettingsInterface $settings */
            $settings = new $settingsClass();
            $this->register($settings);
        }

        return $this;
    }

    /**
     * @param string $className
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    private function checkDoNotHaveRequiredParametersOnCreate(string $className): bool
    {
        try {
            $reflection  = new ReflectionClass($className);
            if ($reflection->isInstantiable() === false) {
                throw new InvalidSettingsClassException($className);
            }
            if (($constructor = $reflection->getConstructor()) !== null) {
                foreach ($constructor->getParameters() as $parameter) {
                    if ($parameter->isOptional() === false && $parameter->isDefaultValueAvailable() === false) {
                        // there is no default constructor
                        throw new InvalidSettingsClassException($className);
                    }
                }
            }
        } catch (ReflectionException $exception) {
            throw new InvalidSettingsClassException($className, 0, $exception);
        }

        return true;
    }
}
