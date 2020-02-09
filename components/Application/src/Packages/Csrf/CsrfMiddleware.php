<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Csrf;

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
use Limoncello\Application\Contracts\Csrf\CsrfTokenStorageInterface;
use Limoncello\Contracts\Application\MiddlewareInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use function array_key_exists;
use function assert;
use function call_user_func;
use function is_array;
use function is_string;
use function strtoupper;

/**
 * @package Limoncello\Application
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /** Middleware handler */
    const CALLABLE_HANDLER = [self::class, self::MIDDLEWARE_METHOD_NAME];

    /** @var string Default error response factory on invalid/absent CSRF token */
    const DEFAULT_ERROR_RESPONSE_METHOD = 'defaultErrorResponse';

    /**
     * @inheritdoc
     */
    public static function handle(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ): ResponseInterface {
        $settings = static::getCsrfSettings($container);
        $methods  = $settings[CsrfSettings::INTERNAL_HTTP_METHODS_TO_CHECK_AS_UC_KEYS];

        if (array_key_exists(strtoupper($request->getMethod()), $methods) === true) {
            $token = static::readToken($request, $settings[CsrfSettings::HTTP_REQUEST_CSRF_TOKEN_KEY]);
            if (is_string($token) === false || static::getTokenStorage($container)->check($token) === false) {
                $errResponseMethod = $settings[CsrfSettings::CREATE_ERROR_RESPONSE_METHOD];
                $errResponse       = call_user_func($errResponseMethod, $container, $request);

                return $errResponse;
            }
        }

        return $next($request);
    }

    /**
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function defaultErrorResponse(
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        assert($container !== null && $request !== null);

        // forbid if no valid CSRF token
        return new EmptyResponse(403);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $tokenKey
     *
     * @return null|string
     */
    private static function readToken(ServerRequestInterface $request, string $tokenKey): ?string
    {
        $token = is_array($form = $request->getParsedBody()) === true ? ($form[$tokenKey] ?? null) : null;

        return $token;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return CsrfTokenStorageInterface
     */
    private static function getTokenStorage(ContainerInterface $container): CsrfTokenStorageInterface
    {
        assert($container->has(CsrfTokenStorageInterface::class) === true);
        /** @var CsrfTokenStorageInterface $csrfStorage */
        $csrfStorage = $container->get(CsrfTokenStorageInterface::class);

        return $csrfStorage;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    private static function getCsrfSettings(ContainerInterface $container): array
    {
        /** @var SettingsProviderInterface $provider */
        assert($container->has(SettingsProviderInterface::class) === true);
        $provider = $container->get(SettingsProviderInterface::class);

        assert($provider->has(CsrfSettings::class));
        $settings = $provider->get(CsrfSettings::class);

        return $settings;
    }
}
