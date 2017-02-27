<?php namespace Limoncello\Passport\Traits;

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

use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Passport
 */
trait BasicClientAuthenticationTrait
{
    /**
     * @param ServerRequestInterface             $request
     * @param PassportServerIntegrationInterface $integration
     * @param string                             $realm
     *
     * @return ClientInterface|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function authenticateClient(
        ServerRequestInterface $request,
        PassportServerIntegrationInterface $integration,
        $realm = 'OAuth'
    ) {
        $client = null;
        if (empty($headerArray = $request->getHeader('Authorization')) === false) {
            if (empty($authHeader = $headerArray[0]) === true ||
                ($tokenPos = strpos($authHeader, 'Basic ')) === false ||
                $tokenPos !== 0 ||
                ($authValue = substr($authHeader, 6)) === '' ||
                $authValue === false ||
                ($decodedValue = base64_decode($authValue, true)) === false ||
                count($nameAndPassword = explode(':', $decodedValue, 2)) !== 2 ||
                ($client = $integration->getClientRepository()->read($nameAndPassword[0])) === null ||
                ($credentials = $client->getCredentials()) === null ||
                $integration->verifyPassword($nameAndPassword[1], $credentials) === false
            ) {
                throw new OAuthTokenBodyException(
                    OAuthTokenBodyException::ERROR_INVALID_CLIENT,
                    null, // error URI
                    401,
                    ['WWW-Authenticate' => 'Basic realm="' . $realm . '"']
                );
            }
        }

        return $client;
    }
}
