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

use Limoncello\OAuthServer\Contracts\ClientInterface;
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
        $scope = $this->clientReadStringValue($parameters, 'scope');

        return empty($scope) === false ? explode(' ', $scope) : null;
    }

    /**
     * @param string[]        $parameters
     * @param ClientInterface $determinedClient
     *
     * @return ResponseInterface
     */
    protected function clientIssueToken(array $parameters, ClientInterface $determinedClient): ResponseInterface
    {
        if ($determinedClient->isClientGrantEnabled() === false) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        }

        $scope = $this->clientGetScope($parameters);
        list ($isScopeValid, $scopeList, $isScopeModified) =
            $this->clientGetIntegration()->clientValidateScope($determinedClient, $scope);
        if ($isScopeValid === false) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_SCOPE);
        }

        $response = $this->clientGetIntegration()->clientCreateAccessTokenResponse(
            $determinedClient,
            $isScopeModified,
            $scopeList,
            $parameters
        );

        return $response;
    }

    /**
     * @param array  $parameters
     * @param string $name
     *
     * @return null|string
     */
    private function clientReadStringValue(array $parameters, string $name)
    {
        return array_key_exists($name, $parameters) === true && is_string($value = $parameters[$name]) === true ?
            $value : null;
    }
}
