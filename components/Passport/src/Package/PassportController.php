<?php namespace Limoncello\Passport\Package;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Passport\Contracts\PassportServerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Passport
 */
class PassportController
{
    /** @var callable */
    const AUTHORIZE_HANDLER = [self::class, 'authorize'];

    /** @var callable */
    const TOKEN_HANDLER = [self::class, 'token'];

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function authorize(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        assert($routeParams !== null && $request !== null);

        /** @var PassportServerInterface $passportServer */
        $passportServer = $container->get(PassportServerInterface::class);
        $tokenResponse  = $passportServer->getCreateAuthorization($request);

        return $tokenResponse;
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function token(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        assert($routeParams !== null && $request !== null);

        /** @var PassportServerInterface $passportServer */
        $passportServer = $container->get(PassportServerInterface::class);
        $tokenResponse  = $passportServer->postCreateToken($request);

        return $tokenResponse;
    }
}
