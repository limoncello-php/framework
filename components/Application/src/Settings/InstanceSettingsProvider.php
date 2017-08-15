<?php namespace Limoncello\Application\Settings;

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

use Limoncello\Application\Exceptions\AlreadyRegisteredSettingsException;
use Limoncello\Application\Exceptions\AmbiguousSettingsException;
use Limoncello\Application\Exceptions\NotRegisteredSettingsException;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;

/**
 * @package Limoncello\Application
 */
class InstanceSettingsProvider implements SettingsProviderInterface
{
    /**
     * @var SettingsInterface[]
     */
    private $instances = [];

    /**
     * @var bool
     */
    private $isProcessed = true;

    /**
     * @var array
     */
    private $settingsMap = [];

    /**
     * @var array
     */
    private $settingsData = [];

    /**
     * @var array
     */
    private $ambiguousMap = [];

    /**
     * @inheritdoc
     */
    public function has(string $className): bool
    {
        $this->checkInstancesAreProcessed();

        $result = array_key_exists($className, $this->getSettingsMap());

        return $result;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public function get(string $className): array
    {
        if ($this->has($className) === false) {
            if (array_key_exists($className, $this->ambiguousMap) === true) {
                throw new AmbiguousSettingsException($className);
            }
            throw new NotRegisteredSettingsException($className);
        }

        $index = $this->settingsMap[$className];
        $data  = $this->settingsData[$index];

        return $data;
    }

    /**
     * @param SettingsInterface $settings
     *
     * @return InstanceSettingsProvider
     */
    public function register(SettingsInterface $settings): InstanceSettingsProvider
    {
        $className = get_class($settings);
        if (array_key_exists($className, $this->instances) === true) {
            throw new AlreadyRegisteredSettingsException($className);
        }

        $this->instances[$className] = $settings;
        $this->isProcessed           = false;

        return $this;
    }

    /**
     * @return array
     */
    public function getSettingsMap(): array
    {
        $this->checkInstancesAreProcessed();

        return $this->settingsMap;
    }

    /**
     * @return array
     */
    public function getSettingsData(): array
    {
        $this->checkInstancesAreProcessed();

        return $this->settingsData;
    }

    /**
     * @return array
     */
    public function getAmbiguousMap(): array
    {
        $this->checkInstancesAreProcessed();

        return $this->ambiguousMap;
    }

    /**
     * @inheritdoc
     */
    public function isAmbiguous(string $className): bool
    {
        $result = array_key_exists($className, $this->getAmbiguousMap());

        return $result;
    }

    /**
     * @return void
     */
    private function checkInstancesAreProcessed(): void
    {
        $this->isProcessed === true ?: $this->processInstances();
    }

    /**
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function processInstances(): void
    {
        $preliminaryMap = [];
        foreach ($this->instances as $instance) {
            $preliminaryMap[get_class($instance)][] = $instance;
            foreach (class_parents($instance) as $parentClass) {
                $preliminaryMap[$parentClass][] = $instance;
            }
            foreach (class_implements($instance) as $parentClass) {
                $preliminaryMap[$parentClass][] = $instance;
            }
        }

        $nextIndex    = 0;
        $hashMap      = []; // hash  => index
        $settingsData = []; // index => instance data
        $getIndex = function (SettingsInterface $instance) use (&$nextIndex, &$hashMap, &$settingsData): int {
            $hash = spl_object_hash($instance);
            if (array_key_exists($hash, $hashMap) === true) {
                $index = $hashMap[$hash];
            } else {
                $hashMap[$hash]           = $nextIndex;
                $settingsData[$nextIndex] = $instance->get();
                $index                    = $nextIndex++;
            }

            return $index;
        };

        $settingsMap  = []; // class => index
        $ambiguousMap = []; // class => true
        foreach ($preliminaryMap as $class => $instanceList) {
            if (count($instanceList) === 1) {
                $selected = $instanceList[0];
            } else {
                $selected = $this->selectChildSettings($instanceList);
            }
            if ($selected !== null) {
                $settingsMap[$class] = $getIndex($selected);
            } else {
                $ambiguousMap[$class] = true;
            }
        }

        $this->settingsMap  = $settingsMap;
        $this->settingsData = $settingsData;
        $this->ambiguousMap = $ambiguousMap;
        $this->isProcessed  = true;
    }

    /**
     * @param SettingsInterface[] $instanceList
     *
     * @return SettingsInterface|null
     */
    private function selectChildSettings(array $instanceList): ?SettingsInterface
    {
        $count = count($instanceList);
        assert($count > 1);
        $selected = $this->selectChildSettingsAmongTwo($instanceList[0], $instanceList[1]);
        if ($selected !== null) {
            for ($index = 2; $index < $count; ++$index) {
                $selected = $this->selectChildSettingsAmongTwo($selected, $instanceList[$index]);
                if ($selected === null) {
                    break;
                }
            }
        }

        return $selected;
    }

    /**
     * @param SettingsInterface $instance1
     * @param SettingsInterface $instance2
     *
     * @return SettingsInterface|null
     */
    private function selectChildSettingsAmongTwo(
        SettingsInterface $instance1,
        SettingsInterface $instance2
    ) : ?SettingsInterface {
        return is_subclass_of($instance1, get_class($instance2)) === true ?
            $instance1 : (is_subclass_of($instance2, get_class($instance1)) === true ? $instance2 : null);
    }
}
