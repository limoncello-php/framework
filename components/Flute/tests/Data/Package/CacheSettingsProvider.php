<?php namespace Limoncello\Tests\Flute\Data\Package;

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

use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use LogicException;

/**
 * @package Limoncello\Tests\Flute
 */
class CacheSettingsProvider implements CacheSettingsProviderInterface
{
    /**
     * @var array
     */
    private $appConf;

    /**
     * @var array
     */
    private $settings;

    /**
     * @param array $appConf
     * @param array $settings
     */
    public function __construct(array $appConf, array $settings)
    {
        $this->appConf  = $appConf;
        $this->settings = $settings;
    }

    /**
     * @inheritdoc
     */
    public function has(string $className): bool
    {
        return array_key_exists($className, $this->settings);
    }

    /**
     * @inheritdoc
     */
    public function get(string $className): array
    {
        return $this->settings[$className];
    }

    /**
     * @inheritdoc
     */
    public function serialize(): array
    {
        throw new LogicException('Not implemented in test mock-up.');
    }

    /**
     * @inheritdoc
     */
    public function unserialize(array $serialized): void
    {
        throw new LogicException('Not implemented in test mock-up.');
    }

    /**
     * @inheritdoc
     */
    public function getApplicationConfiguration(): array
    {
        return $this->appConf;
    }

    /**
     * @inheritdoc
     */
    public function getCoreData(): array
    {
        throw new LogicException('Not implemented in test mock-up.');
    }
}
