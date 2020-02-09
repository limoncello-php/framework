<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Session;

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

use Closure;
use Limoncello\Application\Contracts\Session\SessionFunctionsInterface;
use Limoncello\Application\Packages\Session\SessionSettings as C;
use Limoncello\Contracts\Application\MiddlewareInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function call_user_func;

/**
 * @package Limoncello\Application
 */
class SessionMiddleware implements MiddlewareInterface
{
    /** Middleware handler */
    const CALLABLE_HANDLER = [self::class, self::MIDDLEWARE_METHOD_NAME];

    /**
     * @inheritdoc
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function handle(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ): ResponseInterface {

        $sessionFunctions = static::getSessionFunctions($container);

        $couldBeStarted = call_user_func($sessionFunctions->getCouldBeStartedCallable());
        if ($couldBeStarted === true) {
            call_user_func($sessionFunctions->getStartCallable(), static::getSessionSettings($container));
        }

        $response = $next($request);

        call_user_func($sessionFunctions->getWriteCloseCallable());

        return $response;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SessionFunctionsInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function getSessionFunctions(ContainerInterface $container): SessionFunctionsInterface
    {
        /** @var SessionFunctionsInterface $sessionFunctions */
        $sessionFunctions = $container->get(SessionFunctionsInterface::class);

        return $sessionFunctions;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function getSessionSettings(ContainerInterface $container): array
    {
        /** @var SettingsProviderInterface $provider */
        $provider = $container->get(SettingsProviderInterface::class);
        $settings = $provider->get(C::class);

        return $settings;
    }
}
