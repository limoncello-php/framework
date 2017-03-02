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
use Limoncello\OAuthServer\Contracts\Integration\PasswordIntegrationInterface;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Psr\Http\Message\ResponseInterface;

/**
 * Implements Resource Owner Password Credentials Grant.
 *
 * @package Limoncello\OAuthServer
 *
 * @link https://tools.ietf.org/html/rfc6749#section-1.3
 * @link https://tools.ietf.org/html/rfc6749#section-4.3
 */
trait PasswordGrantTrait
{
    /**
     * @var PasswordIntegrationInterface
     */
    private $passIntegration;

    /**
     * @return PasswordIntegrationInterface
     */
    protected function passGetIntegration()
    {
        return $this->passIntegration;
    }

    /**
     * @param PasswordIntegrationInterface $passIntegration
     *
     * @return void
     */
    public function passSetIntegration(PasswordIntegrationInterface $passIntegration)
    {
        $this->passIntegration = $passIntegration;
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function passGetUserName(array $parameters)
    {
        return $this->passReadStringValue($parameters, 'username');
    }

    /**
     * @param string[] $parameters
     *
     * @return string|null
     */
    protected function passGetPassword(array $parameters)
    {
        return $this->passReadStringValue($parameters, 'password');
    }

    /**
     * @param string[] $parameters
     *
     * @return string[]|null
     */
    protected function passGetScope(array $parameters)
    {
        return ($scope = $this->passReadStringValue($parameters, 'scope')) !== null ? explode(' ', $scope) : null;
    }

    /**
     * @param string[]             $parameters
     * @param ClientInterface|null $determinedClient
     *
     * @return ResponseInterface
     */
    protected function passIssueToken(array $parameters, ClientInterface $determinedClient = null): ResponseInterface
    {
        // if client is not given we interpret it as a 'default' client should be used
        if ($determinedClient === null) {
            $determinedClient = $this->passGetIntegration()->passReadDefaultClient();
            if ($determinedClient->hasCredentials() === true) {
                throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
            }
        }

        if ($determinedClient !== null && $determinedClient->isPasswordGrantEnabled() === false) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        }

        $scope = $this->passGetScope($parameters);
        list ($isScopeValid, $scopeList, $isScopeModified) =
            $this->passGetIntegration()->passValidateScope($determinedClient, $scope);
        if ($isScopeValid === false) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_SCOPE);
        }

        if (($userName = $this->passGetUserName($parameters)) === null ||
            ($password = $this->passGetPassword($parameters)) === null
        ) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_REQUEST);
        }

        $response = $this->passGetIntegration()->passValidateCredentialsAndCreateAccessTokenResponse(
            $userName,
            $password,
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
    private function passReadStringValue(array $parameters, string $name)
    {
        return array_key_exists($name, $parameters) === true && is_string($value = $parameters[$name]) === true ?
            $value : null;
    }
}
