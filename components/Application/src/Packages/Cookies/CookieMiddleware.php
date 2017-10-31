<?php namespace Limoncello\Application\Packages\Cookies;

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

use Closure;
use Limoncello\Contracts\Application\MiddlewareInterface;
use Limoncello\Contracts\Cookies\CookieInterface;
use Limoncello\Contracts\Cookies\CookieJarInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Application
 */
class CookieMiddleware implements MiddlewareInterface
{
    /**
     * A callable to set an ordinary (encoded) cookie.
     */
    protected const SET_COOKIE_CALLABLE = '\setcookie';

    /**
     * A callable to set a raw (not encoded) cookie.
     */
    protected const SET_RAW_COOKIE_CALLABLE = '\setrawcookie';

    /**
     * @inheritdoc
     */
    public static function handle(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ): ResponseInterface {
        /** @var ResponseInterface $response */
        $response = $next($request);

        if ($container->has(CookieJarInterface::class) === true) {
            /** @var CookieJarInterface $cookieJar */
            $cookieJar = $container->get(CookieJarInterface::class);
            foreach ($cookieJar->getAll() as $cookie) {
                /** @var CookieInterface $cookie */

                // The reason why methods for setting cookies are called with call_user_func and their names
                // is that calling `setcookie` is not possible during testing. It can't add any headers
                // in a console app which produces some output.
                //
                // Using functions by names allows replace them with test mocks and check input values.
                //
                // Notice constants are used with `static::`. Their values can and will be replaced in tests.
                $setCookieFunction =
                    $cookie->isNotRaw() === true ? static::SET_COOKIE_CALLABLE : static::SET_RAW_COOKIE_CALLABLE;

                call_user_func(
                    $setCookieFunction,
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpiresAtUnixTime(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $cookie->isSendOnlyOverSecureConnection(),
                    $cookie->isAccessibleOnlyThroughHttp()
                );
            }
        }

        return $response;
    }
}
