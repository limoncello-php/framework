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
use Limoncello\Core\Contracts\Config\ConfigManagerInterface;
use ReflectionClass;

/**
 * @package Limoncello\Core
 */
class ConfigManager implements ConfigManagerInterface
{
    /**
     * @var string
     */
    private $globConfigPatterns;

    /**
     * @var int
     */
    private $globFlags = 0;

    /**
     * @var string
     */
    private $configNamespace = '';

    /**
     * @param string $globConfigPatterns
     */
    public function __construct($globConfigPatterns = '*.php')
    {
        $this->setGlobConfigPatterns($globConfigPatterns);
        $this->setGlobFlags(0);
    }

    /**
     * @inheritdoc
     */
    public function loadConfigs($configNamespace, $pathToDirectory)
    {
        $this->setConfigNamespace($configNamespace);

        $configs = $this->loadConfigsImpl($pathToDirectory, $this->getGlobConfigPatterns());
        $result  = new ArrayConfig($configs);

        return $result;
    }

    /**
     * @param string $configsPath
     * @param string $globConfigPatterns
     *
     * @return array
     */
    protected function loadConfigsImpl($configsPath, $globConfigPatterns)
    {
        assert(is_string($configsPath) === true && empty($configsPath) === false);
        assert(is_string($globConfigPatterns) === true && empty($globConfigPatterns) === false);

        $configsPath = realpath($configsPath);

        foreach (glob($configsPath . DIRECTORY_SEPARATOR . $globConfigPatterns, $this->getGlobFlags()) as $fileName) {
            /** @noinspection PhpIncludeInspection */
            require_once $fileName;
        }

        $globalConfigs = [];
        foreach (get_declared_classes() as $class) {
            if ($this->isConfigClassToRead($class) === true) {
                $this->readConfigsFromClass($class, $globalConfigs);
            }
        }

        return $globalConfigs;
    }

    /**
     * @return string
     */
    protected function getGlobConfigPatterns()
    {
        return $this->globConfigPatterns;
    }

    /**
     * @param string $globConfigPatterns
     */
    protected function setGlobConfigPatterns($globConfigPatterns)
    {
        assert(is_string($globConfigPatterns) === true && empty($globConfigPatterns) === false);
        $this->globConfigPatterns = $globConfigPatterns;
    }

    /**
     * @return int
     */
    protected function getGlobFlags()
    {
        return $this->globFlags;
    }

    /**
     * @param int $globFlags
     */
    protected function setGlobFlags($globFlags)
    {
        assert(is_int($globFlags));
        $this->globFlags = $globFlags;
    }

    /**
     * @return string
     */
    protected function getConfigNamespace()
    {
        return $this->configNamespace;
    }

    /**
     * @param string $configNamespace
     */
    protected function setConfigNamespace($configNamespace)
    {
        assert(is_string($configNamespace) === true && empty($configNamespace) === false);
        $this->configNamespace = $configNamespace;
    }

    /**
     * @return bool
     */
    protected function hasConfigNamespace()
    {
        return empty($this->getConfigNamespace()) === false;
    }

    /**
     * @param $class
     * @param $globalConfigs
     */
    private function readConfigsFromClass($class, &$globalConfigs)
    {
        /** @var ConfigInterface $config */
        $config = new $class();
        foreach ($config->getConfigInterfaces() as $interface) {
            $globalConfigs[$interface] = $config->getConfig($interface);
        }
    }

    /**
     * @param $class
     *
     * @return bool
     */
    private function isConfigClassToRead($class)
    {
        $reflection          = new ReflectionClass($class);
        $shouldBeInNamespace = $this->hasConfigNamespace();
        $isConfigToRead      =
            $reflection->inNamespace() === $shouldBeInNamespace &&
            ($shouldBeInNamespace === false || $reflection->getNamespaceName() === $this->getConfigNamespace()) &&
            $reflection->implementsInterface(ConfigInterface::class) === true;

        return $isConfigToRead;
    }
}
