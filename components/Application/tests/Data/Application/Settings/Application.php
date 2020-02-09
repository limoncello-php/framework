<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Data\Application\Settings;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Contracts\Application\ApplicationConfigurationInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Providers\Provider1;

/**
 * @package Limoncello\Tests\Application
 */
class Application implements ApplicationConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        $commandsFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Commands']);
        $routesPath     = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'CoreSettings', 'Routes', 'Routes1.php']);

        $containerConfPath =
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'CoreSettings', 'Configurators', '*.php']);

        return [
            self::KEY_ROUTES_PATH                  => $routesPath,
            self::KEY_CONTAINER_CONFIGURATORS_PATH => $containerConfPath,
            self::KEY_PROVIDER_CLASSES             => [Provider1::class],
            self::KEY_COMMANDS_FOLDER              => $commandsFolder,
        ];
    }
}
