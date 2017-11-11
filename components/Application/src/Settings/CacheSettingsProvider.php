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

use Limoncello\Application\CoreSettings\CoreData;
use Limoncello\Application\Exceptions\AmbiguousSettingsException;
use Limoncello\Application\Exceptions\NotRegisteredSettingsException;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;

/**
 * @package Limoncello\Application
 */
class CacheSettingsProvider implements CacheSettingsProviderInterface
{
    /** Internal data index */
    const KEY_APPLICATION_CONFIGURATION = 0;

    /** Internal data index */
    const KEY_CORE_DATA = self::KEY_APPLICATION_CONFIGURATION + 1;

    /** Internal data index */
    const KEY_SETTINGS_MAP = self::KEY_CORE_DATA + 1;

    /** Internal data index */
    const KEY_SETTINGS_DATA = self::KEY_SETTINGS_MAP + 1;

    /** Internal data index */
    const KEY_AMBIGUOUS_MAP = self::KEY_SETTINGS_DATA + 1;

    /**
     * @var array
     */
    private $appConfig = [];

    /**
     * @var array
     */
    private $coreData = [];

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
    public function get(string $className): array
    {
        if ($this->has($className) === false) {
            if (array_key_exists($className, $this->ambiguousMap) === true) {
                throw new AmbiguousSettingsException($className);
            }
            throw new NotRegisteredSettingsException($className);
        }

        $data = $this->settingsData[$this->settingsMap[$className]];

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getApplicationConfiguration(): array
    {
        return $this->appConfig;
    }

    /**
     * @inheritdoc
     */
    public function getCoreData(): array
    {
        return $this->coreData;
    }

    /**
     * @inheritdoc
     */
    public function has(string $className): bool
    {
        $result = array_key_exists($className, $this->settingsMap);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function isAmbiguous(string $className): bool
    {
        $result = array_key_exists($className, $this->ambiguousMap);

        return $result;
    }

    /**
     * @param ApplicationConfigurationInterface $appConfig
     * @param CoreData                          $coreData
     * @param InstanceSettingsProvider          $provider
     *
     * @return self
     */
    public function setInstanceSettings(
        ApplicationConfigurationInterface $appConfig,
        CoreData $coreData,
        InstanceSettingsProvider $provider
    ): self {
        $this->unserialize([
            static::KEY_APPLICATION_CONFIGURATION => $appConfig->get(),
            static::KEY_CORE_DATA                 => $coreData->get(),
            static::KEY_SETTINGS_MAP              => $provider->getSettingsMap(),
            static::KEY_SETTINGS_DATA             => $provider->getSettingsData(),
            static::KEY_AMBIGUOUS_MAP             => $provider->getAmbiguousMap(),
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): array
    {
        return [
            static::KEY_APPLICATION_CONFIGURATION => $this->appConfig,
            static::KEY_CORE_DATA                 => $this->coreData,
            static::KEY_SETTINGS_MAP              => $this->settingsMap,
            static::KEY_SETTINGS_DATA             => $this->settingsData,
            static::KEY_AMBIGUOUS_MAP             => $this->ambiguousMap,
        ];
    }

    /**
     * @inheritdoc
     */
    public function unserialize(array $serialized): void
    {
        list (
            static::KEY_APPLICATION_CONFIGURATION => $this->appConfig,
            static::KEY_CORE_DATA => $this->coreData,
            static::KEY_SETTINGS_MAP => $this->settingsMap,
            static::KEY_SETTINGS_DATA => $this->settingsData,
            static::KEY_AMBIGUOUS_MAP => $this->ambiguousMap,
            ) = $serialized;
    }
}
