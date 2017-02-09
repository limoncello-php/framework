<?php namespace Limoncello\OAuthServer\Contracts;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\OAuthServer
 */
interface AuthorizationServerInterface
{
    /**
     * Authorization Endpoint (GET method).
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.1
     */
    public function getCreateAuthorization(ServerRequestInterface $request): ResponseInterface;

    /**
     * Authorization Endpoint (POST method).
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.1
     */
    public function postCreateAuthorization(ServerRequestInterface $request): ResponseInterface;

    /**
     * Token Endpoint (POST method).
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.2
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.3
     * @link https://tools.ietf.org/html/rfc6749#section-4.3.2
     * @link https://tools.ietf.org/html/rfc6749#section-4.4.2
     */
    public function postCreateToken(ServerRequestInterface $request): ResponseInterface;
}
