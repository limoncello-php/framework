<?php namespace Limoncello\Passport\Contracts;

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

use Limoncello\OAuthServer\Contracts\AuthorizationServerInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\Passport
 */
interface PassportServerInterface extends AuthorizationServerInterface
{
    /**
     * Create response with an authorization code. Could be used for a final step in code authorization grant.
     *
     * @param TokenInterface $code
     * @param string|null    $state
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.4
     */
    public function createCodeResponse(TokenInterface $code, string $state = null): ResponseInterface;

    /**
     * Create response with a token. Could be used for a final step in implicit grant.
     *
     * @param TokenInterface $token
     * @param string|null    $state
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2
     */
    public function createTokenResponse(TokenInterface $token, string $state = null): ResponseInterface;
}
