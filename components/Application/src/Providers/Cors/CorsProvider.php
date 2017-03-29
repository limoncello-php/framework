<?php namespace Limoncello\Application\Providers\Cors;

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

use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface as CCI;
use Limoncello\Contracts\Provider\ProvidesMiddlewareInterface as MI;
use Limoncello\Contracts\Provider\ProvidesSettingsInterface as SI;

/**
 * @package Limoncello\Application
 */
class CorsProvider implements SI, CCI, MI
{
    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            CorsContainerConfigurator::HANDLER,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getSettings(): array
    {
        return [
            new CorsSettings(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [
            CorsMiddleware::HANDLER,
        ];
    }
}
