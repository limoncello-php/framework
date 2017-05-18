<?php namespace Limoncello\Passport\Authentication;

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
use Limoncello\Passport\Contracts\Authentication\PassportAccountManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Passport
 */
class PassportMiddleware implements MiddlewareInterface
{
    /** @var callable */
    const HANDLER = [self::class, self::METHOD_NAME];

    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     * @param ContainerInterface     $container
     *
     * @return ResponseInterface
     */
    public static function handle(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ): ResponseInterface {
        $header = $request->getHeader('Authorization');
        // if value has Bearer token and it is a valid json with 2 required fields and they are strings
        if (empty($header) === false &&
            substr($value = $header[0], 0, 7) === 'Bearer ' &&
            is_string($tokenValue = substr($value, 7)) === true &&
            empty($tokenValue) === false
        ) {
            assert($container->has(PassportAccountManagerInterface::class));

            /** @var PassportAccountManagerInterface $accountManager */
            $accountManager = $container->get(PassportAccountManagerInterface::class);
            $accountManager->setAccountWithTokenValue($tokenValue);
        }

        // call next middleware handler
        return $next($request);
    }
}
