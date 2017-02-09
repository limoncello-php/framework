<?php namespace Limoncello\OAuthServer;

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

use Limoncello\OAuthServer\Contracts\AuthorizationServerInterface;
use Limoncello\OAuthServer\Contracts\Clients\ClientInterface;
use Limoncello\OAuthServer\Contracts\Integration\CodeIntegrationInterface as AII;
use Limoncello\OAuthServer\Contracts\Integration\ClientIntegrationInterface as CII;
use Limoncello\OAuthServer\Contracts\Integration\ImplicitIntegrationInterface as III;
use Limoncello\OAuthServer\Contracts\Integration\PasswordIntegrationInterface as PII;
use Limoncello\OAuthServer\GrantTraits\ClientGrantTrait;
use Limoncello\OAuthServer\GrantTraits\CodeGrantTrait;
use Limoncello\OAuthServer\GrantTraits\ImplicitGrantTrait;
use Limoncello\OAuthServer\GrantTraits\PasswordGrantTrait;
use Limoncello\OAuthServer\ServerTraits\OAuthServerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\OAuthServer
 */
abstract class BaseAuthorizationServer implements AuthorizationServerInterface, AII, III, PII, CII
{
    use OAuthServerTrait,
        CodeGrantTrait, ImplicitGrantTrait, PasswordGrantTrait, ClientGrantTrait;

    /**
     * Implements Authorization Endpoint.
     *
     * @param array $parameters
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.1
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.1
     */
    abstract protected function createAuthorization(array $parameters): ResponseInterface;

    /**
     * @var null|int
     */
    private $maxStateLength = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->codeSetIntegration($this);
        $this->implicitSetIntegration($this);
        $this->passSetIntegration($this);
        $this->clientSetIntegration($this);
    }

    /**
     * @inheritdoc
     */
    public function getCreateAuthorization(ServerRequestInterface $request): ResponseInterface
    {
        return $this->createAuthorization($request->getQueryParams());
    }

    /**
     * @inheritdoc
     */
    public function postCreateAuthorization(ServerRequestInterface $request): ResponseInterface
    {
        return $this->createAuthorization($request->getParsedBody());
    }

    /**
     * @inheritdoc
     */
    public function codeValidateScope(ClientInterface $client, array $scopes = null): array
    {
        return $this->validateScope($client, $scopes);
    }

    /**
     * @inheritdoc
     */
    public function implicitValidateScope(ClientInterface $client, array $scopes = null): array
    {
        return $this->validateScope($client, $scopes);
    }

    /**
     * @inheritdoc
     */
    public function passValidateScope(ClientInterface $client = null, array $scopes = null): array
    {
        return $this->validateScope($client, $scopes);
    }

    /**
     * @inheritdoc
     */
    public function clientValidateScope(ClientInterface $client, array $scopes = null): array
    {
        return $this->validateScope($client, $scopes);
    }

    /**
     * @return int|null
     */
    public function getMaxStateLength()
    {
        return $this->maxStateLength;
    }

    /**
     * @param int|null $maxStateLength
     *
     * @return BaseAuthorizationServer
     */
    public function setMaxStateLength(int $maxStateLength = null): BaseAuthorizationServer
    {
        assert($maxStateLength === null || $maxStateLength > 0);

        $this->maxStateLength = $maxStateLength;

        return $this;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function encodeAsXWwwFormUrlencoded(array $parameters): string
    {
        return http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
}
