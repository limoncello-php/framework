<?php namespace Limoncello\Commands\Commands;

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

use Limoncello\Commands\Exceptions\ConfigurationException;
use Limoncello\Contracts\Application\ApplicationSettingsInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Serializable\ArraySerializableInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Templates\Commands\TemplatesCreate;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Commands
 */
class CacheCreate extends CacheBase
{
    /**
     * @inheritdoc
     */
    public function getCommandData(): array
    {
        return [
            self::COMMAND_NAME        => 'limoncello:cache',
            self::COMMAND_DESCRIPTION => 'Creates application caches.',
            self::COMMAND_HELP        => 'This command creates caches for routes, settings, templates and etc.',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function execute(ContainerInterface $container, IoInterface $inOut)
    {
        $appSettings   = $this->getApplicationSettings($container);
        $cacheDir      = $appSettings[ApplicationSettingsInterface::KEY_CACHE_FOLDER];
        $cacheCallable = $appSettings[ApplicationSettingsInterface::KEY_CACHE_CALLABLE];
        list ($namespace, $class, $method) = $this->parseCacheCallable($cacheCallable);
        if ($class === null || $namespace === null || $method === null) {
            throw new ConfigurationException();
        }

        $settingsProvider = $container->get(SettingsProviderInterface::class);
        assert($settingsProvider instanceof ArraySerializableInterface);
        $settingsData = $settingsProvider->serialize();
        $content      = $this->composeContent($settingsData, $namespace, $class, $method);

        $path = $cacheDir . DIRECTORY_SEPARATOR . $class . '.php';
        file_put_contents($path, $content);

        (new TemplatesCreate())->execute($container, $inOut);
    }

    /**
     * @param mixed  $value
     * @param string $className
     * @param string $methodName
     * @param string $namespace
     *
     * @return string
     */
    protected function composeContent(
        $value,
        string $namespace,
        string $className,
        string $methodName
    ) {
        $now     = date(DATE_RFC2822);
        $data    = var_export($value, true);
        $content = <<<EOT
<?php namespace $namespace;

// THIS FILE IS AUTO GENERATED. DO NOT EDIT IT MANUALLY.
// Generated at: $now

class $className
{
    const DATA = $data;

    public static function $methodName()
    {
        return static::DATA;
    }
}

EOT;

        return $content;
    }
}
