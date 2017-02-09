<?php namespace Limoncello\OAuthServer\GrantTraits;

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
use Limoncello\OAuthServer\Contracts\Clients\ClientInterface;
use Limoncello\OAuthServer\Contracts\Integration\ClientIntegrationInterface;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\OAuthServer
 *
 * @link https://tools.ietf.org/html/rfc6749#section-1.3
 * @link https://tools.ietf.org/html/rfc6749#section-4.4
 */
trait ClientGrantTrait
{
    /**
     * @var ClientIntegrationInterface
     */
    private $clientIntegration;

    /**
     * @return ClientIntegrationInterface
     */
    protected function clientGetIntegration()
    {
        return $this->clientIntegration;
    }

    /**
     * @param ClientIntegrationInterface $clientIntegration
     *
     * @return void
     */
    public function clientSetIntegration(ClientIntegrationInterface $clientIntegration)
    {
        $this->clientIntegration = $clientIntegration;
    }

    /**
     * @param string[] $parameters
     *
     * @return string[]|null
     */
    protected function clientGetScope(array $parameters)
    {
        $scope    = null;
        $hasScope =
            array_key_exists('scope', $parameters) === true &&
            is_string($scope = $parameters['scope']) === true;

        return $hasScope === true ? explode(' ', $scope) : null;
    }

    /**
     * @param string[]        $parameters
     * @param ClientInterface $client
     *
     * @return ResponseInterface
     */
    protected function clientIssueToken(array $parameters, ClientInterface $client): ResponseInterface
    {
        if ($client->isClientGrantEnabled() === false) {
            // TODO we limit here possible Exception params. Think of a) add b) use helper factory
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        }

        $scope = $this->clientGetScope($parameters);
        list ($isScopeValid, $scopeList, $isScopeModified) =
            $this->clientGetIntegration()->clientValidateScope($client, $scope);
        if ($isScopeValid === false) {
            // TODO we limit here possible Exception params. Think of a) add b) use helper factory
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_SCOPE);
        }

        $response = $this->clientGetIntegration()->clientCreateAccessTokenResponse(
            $client,
            $isScopeModified,
            $scopeList
        );

        return $response;
    }
}
