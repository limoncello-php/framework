<?php namespace Limoncello\Tests\OAuthServer;

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
use Limoncello\OAuthServer\Contracts\TokenInterface;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\Tests\OAuthServer\Data\Client;
use Limoncello\Tests\OAuthServer\Data\RepositoryInterface;
use Limoncello\Tests\OAuthServer\Data\SampleServer;
use Limoncello\Tests\OAuthServer\Data\Token;
use Mockery;
use Mockery\Mock;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request;

/**
 * @package Limoncello\Tests\OAuthServer
 */
class RefreshTokenTest extends ServerTestCase
{
    /**
     * Grant type.
     */
    const GRANT_TYPE = 'refresh_token';

    /**
     * Client id.
     */
    const CLIENT_ID = 'some_client';

    /**
     * Test successful token issue.
     */
    public function testSuccessfulTokenIssueWithoutScopeChange()
    {
        $client = $this->createDefaultClient();
        $token  = $this->createToken($client);
        $server = new SampleServer($this->createClientRepositoryMock($client, $token));

        $scope    = null;
        $request  = $this->createTokenRequest($token->getRefreshValue(), $scope, $client->getIdentifier());
        $response = $server->postCreateToken($request);

        $this->validateBodyResponse($response, 200, $this->getExpectedBodyToken());
    }

    /**
     * Test successful token issue.
     */
    public function testSuccessfulTokenIssueWithScopeChange()
    {
        $client = $this->createDefaultClient();
        $token  = $this->createToken($client);
        $server = new SampleServer($this->createClientRepositoryMock($client, $token));

        $scope    = SampleServer::TEST_SCOPES[0];
        $request  = $this->createTokenRequest($token->getRefreshValue(), $scope, $client->getIdentifier());
        $response = $server->postCreateToken($request);

        $this->validateBodyResponse($response, 200, $this->getExpectedScopeBodyToken($scope));
    }

    /**
     * Test failed token issue.
     */
    public function testFailedTokenIssueWithScopeChange()
    {
        $client = $this->createDefaultClient();
        $token  = $this->createToken($client);
        $server = new SampleServer($this->createClientRepositoryMock($client, $token));

        $scope = SampleServer::TEST_SCOPES[0] . ' xxx'; // <-- additional invalid scope

        $request  = $this->createTokenRequest($token->getRefreshValue(), $scope, $client->getIdentifier());
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_INVALID_SCOPE;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * Test with client where 'refresh grant' is disabled.
     */
    public function testUnauthorizedClient()
    {
        $client = $this->createDefaultClient()->disableRefreshGrant();
        $server = new SampleServer($this->createClientRepositoryMock($client));

        $refreshValue = SampleServer::TEST_REFRESH_TOKEN;

        $scope    = null;
        $request  = $this->createTokenRequest($refreshValue, $scope, $client->getIdentifier());
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * Test no refresh token.
     */
    public function testNoRefreshToken()
    {
        $client = $this->createDefaultClient();
        $server = new SampleServer($this->createClientRepositoryMock($client));

        $refreshValue = null;

        $scope    = null;
        $request  = $this->createTokenRequest($refreshValue, $scope, $client->getIdentifier());
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_INVALID_REQUEST;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * Test invalid refresh token.
     */
    public function testInvalidRefreshToken()
    {
        $client = $this->createDefaultClient();
        $server = new SampleServer($this->createClientRepositoryMockNoTokenFound($client));

        $scope        = null;
        $refreshValue = SampleServer::TEST_REFRESH_TOKEN . '_xxx'; // <-- token made invalid here
        $request      = $this->createTokenRequest($refreshValue, $scope, $client->getIdentifier());
        $response     = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_INVALID_GRANT;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * @param string $identifier
     *
     * @return Client
     */
    private function createDefaultClient(string $identifier = self::CLIENT_ID)
    {
        return (new Client($identifier))->setPublic()->enableRefreshGrant();
    }

    /**
     * @param ClientInterface     $client
     * @param TokenInterface|null $token
     *
     * @return RepositoryInterface
     */
    private function createClientRepositoryMock(ClientInterface $client, TokenInterface $token = null)
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(RepositoryInterface::class);
        $mock->shouldReceive('readClient')->zeroOrMoreTimes()->with($client->getIdentifier())->andReturn($client);

        if ($token !== null) {
            $mock->shouldReceive('readTokenByRefreshValue')->once()->with($token->getRefreshValue())->andReturn($token);
        }

        return $mock;
    }

    /**
     * @param ClientInterface $client
     *
     * @return RepositoryInterface
     */
    private function createClientRepositoryMockNoTokenFound(ClientInterface $client)
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(RepositoryInterface::class);
        $mock->shouldReceive('readClient')->zeroOrMoreTimes()->with($client->getIdentifier())->andReturn($client);
        $mock->shouldReceive('readTokenByRefreshValue')->once()->withAnyArgs()->andReturnNull();

        return $mock;
    }

    /**
     * @param string|null $refreshValue
     * @param string|null $scope
     * @param string|null $clientId
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createTokenRequest(
        string $refreshValue = null,
        string $scope = null,
        string $clientId = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshValue,
            'scope'         => $scope,
            'client_id'     => $clientId,
        ], null, $headers);

        return $request;
    }

    /**
     * @inheritdoc
     */
    protected function getExpectedBodyToken(
        string $token = SampleServer::TEST_TOKEN_NEW,
        string $type = 'bearer',
        int $expiresIn = 3600,
        string $refreshToken = SampleServer::TEST_REFRESH_TOKEN_NEW,
        string $scope = null
    ): array {
        return parent::getExpectedBodyToken($token, $type, $expiresIn, $refreshToken, $scope);
    }

    /**
     * @param string $scope
     *
     * @return array
     */
    protected function getExpectedScopeBodyToken(string $scope): array
    {
        return $this->getExpectedBodyToken(
            SampleServer::TEST_TOKEN_NEW,
            'bearer',
            3600,
            SampleServer::TEST_REFRESH_TOKEN_NEW,
            $scope
        );
    }

    /**
     * @param ClientInterface $client
     * @param null            $userIdentifier
     * @param array           $scopeIdentifiers
     * @param string          $tokenValue
     * @param string          $refreshValue
     *
     * @return TokenInterface
     */
    private function createToken(
        ClientInterface $client,
        $userIdentifier = null,
        array $scopeIdentifiers = SampleServer::TEST_SCOPES,
        string $tokenValue = SampleServer::TEST_TOKEN,
        string $refreshValue = SampleServer::TEST_REFRESH_TOKEN
    ): TokenInterface {
        return new Token($client->getIdentifier(), $userIdentifier, $scopeIdentifiers, $tokenValue, $refreshValue);
    }
}
