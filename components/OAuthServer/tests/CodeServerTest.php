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
use Limoncello\OAuthServer\Exceptions\OAuthCodeRedirectException;
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
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CodeServerTest extends ServerTestCase
{
    /**
     * Client id.
     */
    const CLIENT_ID = 'some_client_id';

    /**
     * Client id.
     */
    const CLIENT_PASSWORD = 'secret';

    /**
     * Client default scope.
     */
    const CLIENT_DEFAULT_SCOPE = 'some scope';

    /**
     * Client redirect URI.
     */
    const REDIRECT_URI_1 = SampleServer::TEST_CLIENT_REDIRECT_URI;

    /**
     * Client redirect URI.
     */
    const REDIRECT_URI_2 = 'http://example.foo/redirect2?param2=value2';

    /**
     * Test successful auth with redirect URI.
     */
    public function testSuccessfulCodeIssue()
    {
        $server = new SampleServer($this->createRepositoryMock($this->createClient()));
        $state  = '123';

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            static::REDIRECT_URI_1,
            static::CLIENT_DEFAULT_SCOPE,
            $state
        );
        $response = $server->getCreateAuthorization($request);

        $this->validateRedirectResponse($response, static::REDIRECT_URI_1, $this->getExpectedRedirectCode($state));
    }

    /**
     * Test successful auth without redirect URI.
     */
    public function testSuccessfulCodeIssueWithoutRedirectUri()
    {
        // as we expect redirect URI to be taken from client the client should have 1 redirect URI
        $client = $this->createClient();
        $client->setRedirectionUris([static::REDIRECT_URI_1]);

        $server = new SampleServer($this->createRepositoryMock($client));
        $server->setInputUriOptional();

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            null,
            static::CLIENT_DEFAULT_SCOPE
        );
        $response = $server->getCreateAuthorization($request);

        $this->validateRedirectResponse($response, static::REDIRECT_URI_1, $this->getExpectedRedirectCode());
    }

    /**
     * Test failed auth without redirect URI.
     */
    public function testFailedCodeIssueWithoutRedirectUri1()
    {
        // as we expect redirect URI to be taken from client the client should have 1 redirect URI
        $client = $this->createClient();
        $client->setRedirectionUris([static::REDIRECT_URI_1]);

        $server = new SampleServer($this->createRepositoryMock($client));
        $server->setInputUriMandatory();

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            null,
            static::CLIENT_DEFAULT_SCOPE
        );
        $response = $server->getCreateAuthorization($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test failed auth without redirect URI.
     */
    public function testFailedCodeIssueWithoutRedirectUri2()
    {
        // make sure client has more than 1 redirect URI so it cannot be determined which one to use automatically
        $client = $this->createClient();
        $this->assertGreaterThan(1, count($client->getRedirectionUris()));

        $server = new SampleServer($this->createRepositoryMock($client));

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            null,
            static::CLIENT_DEFAULT_SCOPE
        );
        $response = $server->getCreateAuthorization($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test failed auth due to too long `state` parameter.
     */
    public function testFailedCodeIssueDueToTooLongStateParameter()
    {
        $client = $this->createClient();
        $client->setRedirectionUris([static::REDIRECT_URI_1]);
        $server = new SampleServer($this->createRepositoryMock($client));

        // limit max state length so it will cause an error
        $state  = '123';
        $server->setMaxStateLength(1);

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            static::REDIRECT_URI_1,
            static::CLIENT_DEFAULT_SCOPE,
            $state
        );
        $response = $server->getCreateAuthorization($request);

        $expectedFragments = $this
            ->getExpectedRedirectCodeErrorFragments(OAuthCodeRedirectException::ERROR_INVALID_REQUEST, $state);
        $this->validateRedirectResponse($response, static::REDIRECT_URI_1, $expectedFragments);
    }

    /**
     * Test failed auth due to invalid scope.
     */
    public function testFailedCodeIssueDueInvalidScope()
    {
        $client = $this->createClient();
        $client->setRedirectionUris([static::REDIRECT_URI_1]);
        $server = new SampleServer($this->createRepositoryMock($client));
        $state  = '123';

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            static::REDIRECT_URI_1,
            static::CLIENT_DEFAULT_SCOPE . ' and something else',
            $state
        );
        $response = $server->getCreateAuthorization($request);

        $expectedFragments = $this
            ->getExpectedRedirectCodeErrorFragments(OAuthCodeRedirectException::ERROR_INVALID_SCOPE, $state);
        $this->validateRedirectResponse($response, static::REDIRECT_URI_1, $expectedFragments);
    }

    /**
     * Test failed auth due to client does not allow code authorization grant.
     */
    public function testFailedCodeIssueDueCodeAuthorizationGrantIsNotAllowed()
    {
        $client = $this->createClient()
            ->setRedirectionUris([static::REDIRECT_URI_1])
            ->disableCodeAuthorization();

        $server = new SampleServer($this->createRepositoryMock($client));

        $request  = $this->createAuthRequest(
            static::CLIENT_ID,
            static::REDIRECT_URI_1,
            static::CLIENT_DEFAULT_SCOPE
        );
        $response = $server->getCreateAuthorization($request);

        $expectedFragments = $this
            ->getExpectedRedirectCodeErrorFragments(OAuthCodeRedirectException::ERROR_UNAUTHORIZED_CLIENT);
        $this->validateRedirectResponse($response, static::REDIRECT_URI_1, $expectedFragments);
    }

    /**
     * Test successful token issue with redirect URI.
     */
    public function testSuccessfulTokenIssue()
    {
        $server = new SampleServer($this->createRepositoryMock($this->createClient()));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID,
            SampleServer::TEST_AUTH_CODE,
            static::REDIRECT_URI_1
        );
        $response = $server->postCreateToken($request);

        $this->validateBodyResponse($response, 200, $this->getExpectedBodyToken());
    }

    /**
     * Test successful token issue with redirect URI and client authentication.
     */
    public function testSuccessfulTokenIssueWithClientAuthentication()
    {
        $identifier = static::CLIENT_ID;
        $password   = static::CLIENT_PASSWORD;

        $client = $this->createClient()
            ->setCredentials(password_hash(static::CLIENT_PASSWORD, PASSWORD_DEFAULT));

        $this->assertEquals($identifier, $client->getIdentifier());

        $server = new SampleServer($this->createRepositoryMock($client));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID,
            SampleServer::TEST_AUTH_CODE,
            static::REDIRECT_URI_1,
            ['Authorization' => 'Basic ' . base64_encode("$identifier:$password")]
        );
        $response = $server->postCreateToken($request);

        $this->validateBodyResponse($response, 200, $this->getExpectedBodyToken());
    }

    /**
     * Test failed token issue with client id not matching client authentication.
     */
    public function testFailedTokenIssueWithClientAuthentication()
    {
        $identifier = static::CLIENT_ID;
        $password   = static::CLIENT_PASSWORD;

        $client = $this->createClient()
            ->setCredentials(password_hash(static::CLIENT_PASSWORD, PASSWORD_DEFAULT));

        $this->assertEquals($identifier, $client->getIdentifier());

        $server = new SampleServer($this->createRepositoryMock($client));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID . '_xxx', // <-- invalid client id here
            SampleServer::TEST_AUTH_CODE,
            static::REDIRECT_URI_1,
            ['Authorization' => 'Basic ' . base64_encode("$identifier:$password")]
        );
        $response = $server->postCreateToken($request);

        $expectedFragments = $this
            ->getExpectedBodyTokenError(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        $this->validateBodyResponse($response, 400, $expectedFragments);
    }

    /**
     * Test failed token issue due to client denied code auth.
     */
    public function testFailedTokenIssueDueToClientDeniedCodeAuth()
    {
        $client = $this->createClient()->disableCodeAuthorization();
        $server = new SampleServer($this->createRepositoryMock($client));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID,
            SampleServer::TEST_AUTH_CODE,
            static::REDIRECT_URI_1
        );
        $response = $server->postCreateToken($request);

        $expectedFragments = $this
            ->getExpectedBodyTokenError(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        $this->validateBodyResponse($response, 400, $expectedFragments);
    }

    /**
     * Test failed token issue due to invalid auth code.
     */
    public function testFailedTokenIssueDueToInvalidAuthCode()
    {
        $server = new SampleServer($this->createRepositoryMock($this->createClient()));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID,
            SampleServer::TEST_AUTH_CODE . '_xxx', // <-- invalid auth code
            static::REDIRECT_URI_1
        );
        $response = $server->postCreateToken($request);

        $expectedFragments = $this
            ->getExpectedBodyTokenError(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        $this->validateBodyResponse($response, 400, $expectedFragments);
    }

    /**
     * Test failed token issue due to used earlier auth code.
     */
    public function testFailedTokenIssueDueToUsedEarlierAuthCode()
    {
        $server = new SampleServer($this->createRepositoryMock($this->createClient()));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID,
            SampleServer::TEST_USED_AUTH_CODE, // <-- 'used earlier' auth code
            static::REDIRECT_URI_1
        );
        $response = $server->postCreateToken($request);

        $expectedFragments = $this
            ->getExpectedBodyTokenError(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        $this->validateBodyResponse($response, 400, $expectedFragments);
    }

    /**
     * Test failed token issue due to the auth token was issued to another client.
     */
    public function testFailedTokenIssueDueToAuthTokenIssuedToAnotherClient()
    {
        $identifier = static::CLIENT_ID . '_modified';
        $password   = static::CLIENT_PASSWORD;

        $client = $this->createClient()
            ->setIdentifier($identifier)
            ->setCredentials(password_hash(static::CLIENT_PASSWORD, PASSWORD_DEFAULT));

        $this->assertEquals($identifier, $client->getIdentifier());

        $server = new SampleServer($this->createRepositoryMock($client));

        $request  = $this->createTokenRequest(
            $identifier,
            SampleServer::TEST_AUTH_CODE, // <-- we know that this token is assigned to other client id
            static::REDIRECT_URI_1,
            ['Authorization' => 'Basic ' . base64_encode("$identifier:$password")]
        );
        $response = $server->postCreateToken($request);

        $expectedFragments = $this
            ->getExpectedBodyTokenError(OAuthTokenBodyException::ERROR_UNAUTHORIZED_CLIENT);
        $this->validateBodyResponse($response, 400, $expectedFragments);
    }

    /**
     * Test failed token issue due to absent redirect URI.
     */
    public function testFailedTokenIssueDueAbsentRedirectUri()
    {
        $server = new SampleServer($this->createRepositoryMock($this->createClient()));

        $request  = $this->createTokenRequest(
            static::CLIENT_ID,
            SampleServer::TEST_AUTH_CODE,
            null // we know that redirect URI was used for getting auth code
        );
        $response = $server->postCreateToken($request);

        $expectedFragments = $this
            ->getExpectedBodyTokenError(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        $this->validateBodyResponse($response, 400, $expectedFragments);
    }

    /**
     * @param ClientInterface $client
     *
     * @return RepositoryInterface
     */
    private function createRepositoryMock(ClientInterface $client): RepositoryInterface
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(RepositoryInterface::class);
        $mock->shouldReceive('readClient')->once()->with($client->getIdentifier())->andReturn($client);

        return $mock;
    }

    /**
     * @return Client
     */
    private function createClient(): Client
    {
        $client = (new Client(static::CLIENT_ID))
            ->enableCodeAuthorization()
            ->setScopes(explode(' ', static::CLIENT_DEFAULT_SCOPE))
            ->setRedirectionUris([static::REDIRECT_URI_1, static::REDIRECT_URI_2]);

        return $client;
    }

    /**
     * @param string      $clientId
     * @param string|null $redirectUri
     * @param string|null $scope
     * @param string|null $state
     *
     * @return ServerRequestInterface
     */
    private function createAuthRequest(
        string $clientId,
        string $redirectUri = null,
        string $scope = null,
        string $state = null
    ) {
        $request = $this->createServerRequest(null, [
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => $scope,
            'state'         => $state,
        ]);

        return $request;
    }

    /**
     * @param string      $clientId
     * @param string      $code
     * @param string|null $redirectUri
     * @param array|null  $headers
     *
     * @return ServerRequestInterface
     */
    private function createTokenRequest(
        string $clientId,
        string $code,
        string $redirectUri = null,
        array $headers = null
    ) {
        $request = $this->createServerRequest([
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $redirectUri,
            'client_id'    => $clientId,
        ], null, $headers);

        return $request;
    }
}
