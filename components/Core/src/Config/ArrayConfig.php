<?php namespace Limoncello\Core\Config;

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

use Limoncello\Core\Contracts\Config\ConfigInterface;

/**
 * @package Limoncello\Core
 */
class ArrayConfig implements ConfigInterface
{
    /**
     * @var string[]|null
     */
    private $interfaces = null;

    /**
     * @var array
     */
    private $configs;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @inheritdoc
     */
    public function getConfigInterfaces()
    {
        if ($this->interfaces === null) {
            $this->interfaces = array_keys($this->configs);
        }

        return $this->interfaces;
    }

    /**
     * @inheritdoc
     */
    public function getConfig($interfaceClass)
    {
        $result = $this->configs[$interfaceClass];

        return $result;
    }
}
