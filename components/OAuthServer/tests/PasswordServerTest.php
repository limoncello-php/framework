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

use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\Tests\OAuthServer\Data\Client;
use Limoncello\Tests\OAuthServer\Data\RepositoryInterface;
use Limoncello\Tests\OAuthServer\Data\SampleServer;
use Mockery;
use Mockery\Mock;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request;

/**
 * @package Limoncello\Tests\OAuthServer
 */
class PasswordServerTest extends ServerTestCase
{
    /**
     * Grant type.
     */
    const GRANT_TYPE_PASSWORD = 'password';

    /**
     * Test successful token issue.
     */
    public function testSuccessfulTokenIssue()
    {
        $server = new SampleServer($this->createDefaultClientRepositoryMock());

        $request  = $this->createTokenRequest(
            static::GRANT_TYPE_PASSWORD,
            SampleServer::TEST_USER_NAME,
            SampleServer::TEST_PASSWORD
        );
        $response = $server->postCreateToken($request);

        $this->validateBodyResponse($response, 200, $this->getExpectedBodyToken());
    }

    /**
     * Test unsupported grant type.
     */
    public function testUnsupportedGrantType()
    {
        $server = new SampleServer($this->createDefaultClientRepositoryMock());

        $request  = $this->createTokenRequest(
            'invalid_grant',
            SampleServer::TEST_USER_NAME,
            SampleServer::TEST_PASSWORD
        );
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE;
        $uri       = SampleServer::TEST_UNSUPPORTED_GRANT_TYPE_ERROR_URI;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode, $uri));
    }

    /**
     * Test invalid scope.
     */
    public function testInvalidScope()
    {
        $server = new SampleServer($this->createDefaultClientRepositoryMock());

        $request  = $this->createTokenRequest(
            static::GRANT_TYPE_PASSWORD,
            SampleServer::TEST_USER_NAME,
            SampleServer::TEST_PASSWORD,
            'some scope'
        );
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_INVALID_SCOPE;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * Test absent user name.
     */
    public function testAbsentUserName()
    {
        $server = new SampleServer($this->createDefaultClientRepositoryMock());

        $request  = $this->createTokenRequest(
            static::GRANT_TYPE_PASSWORD,
            null, // user name
            SampleServer::TEST_PASSWORD
        );
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_INVALID_REQUEST;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * Test invalid credentials.
     */
    public function testInvalidCredentials()
    {
        $server = new SampleServer($this->createDefaultClientRepositoryMock());

        $request  = $this->createTokenRequest(
            static::GRANT_TYPE_PASSWORD,
            SampleServer::TEST_USER_NAME,
            SampleServer::TEST_PASSWORD . '1234567890' // invalid password
        );
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_INVALID_GRANT;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * Test with client credentials where client has prohibited 'password grant'.
     */
    public function testUnauthorizedClient()
    {
        $identifier = 'whatever_id';
        $password   = 'secret';
        $client     = (new Client($identifier))
            ->setCredentials(password_hash($password, PASSWORD_DEFAULT))
            ->disablePasswordGrant();

        /** @var Mock $repository */
        $repository = Mockery::mock(RepositoryInterface::class);
        $repository->shouldReceive('readClient')->once()->with($identifier)->andReturn($client);

        /** @var RepositoryInterface $repository */

        $server = new SampleServer($repository);

        $request  = $this->createTokenRequest(
            static::GRANT_TYPE_PASSWORD,
            SampleServer::TEST_USER_NAME,
            SampleServer::TEST_PASSWORD,
            null, // scope
            ['Authorization' => 'Basic ' . base64_encode("$identifier:$password")]
        );
        $response = $server->postCreateToken($request);

        $errorCode = OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT;
        $this->validateBodyResponse($response, 400, $this->getExpectedBodyTokenError($errorCode));
    }

    /**
     * @return RepositoryInterface
     */
    private function createDefaultClientRepositoryMock()
    {
        $identifier    = 'default_client_id';
        $defaultClient = (new Client($identifier))
            ->enablePasswordGrant()
            ->useDefaultScopesOnEmptyRequest();

        /** @var Mock $mock */
        $mock = Mockery::mock(RepositoryInterface::class);
        $mock->shouldReceive('readDefaultClient')->zeroOrMoreTimes()->withNoArgs()->andReturn($defaultClient);

        return $mock;
    }

    /**
     * @param string|null $grantType
     * @param string|null $username
     * @param string|null $password
     * @param string|null $scope
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createTokenRequest(
        string $grantType = null,
        string $username = null,
        string $password = null,
        string $scope = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest([
            'grant_type' => $grantType,
            'username'   => $username,
            'password'   => $password,
            'scope'      => $scope,
        ], null, $headers);

        return $request;
    }
}
