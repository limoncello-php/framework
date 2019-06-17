<?php declare(strict_types=1);

namespace Limoncello\Passport\Package;

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

use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface as CCI;
use Limoncello\Contracts\Provider\ProvidesMigrationsInterface as MI;
use Limoncello\Contracts\Provider\ProvidesRouteConfiguratorsInterface as CI;
use Limoncello\Contracts\Provider\ProvidesMiddlewareInterface as MWI;
use Limoncello\Passport\Authentication\PassportMiddleware;

/**
 * @package Limoncello\Passport
 */
class PostgreSqlPassportProvider implements CCI, MI, CI, MWI
{
    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            PostgreSqlPassportContainerConfigurator::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getMigrations(): array
    {
        return [
            PostgreSqlPassportMigration::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRouteConfigurators(): array
    {
        return [
            PassportRoutesConfigurator::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [
            PassportMiddleware::class,
        ];
    }
}
