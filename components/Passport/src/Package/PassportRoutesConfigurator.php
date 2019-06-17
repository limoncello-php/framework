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

use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Routing\GroupInterface;

/**
 * @package Limoncello\Passport
 */
class PassportRoutesConfigurator implements RoutesConfiguratorInterface
{
    /** Route group prefix */
    const GROUP_PREFIX = '';

    /** Passport URI */
    const AUTHORIZE_URI = 'authorize';

    /** Passport URI */
    const TOKEN_URI = 'token';

    /**
     * @inheritdoc
     */
    public static function configureRoutes(GroupInterface $routes): void
    {
        $routes->group(static::GROUP_PREFIX, function (GroupInterface $group) {
            $group->get(static::AUTHORIZE_URI, PassportController::AUTHORIZE_HANDLER);
            $group->post(static::TOKEN_URI, PassportController::TOKEN_HANDLER);
        });
    }

    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [];
    }
}
