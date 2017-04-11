<?php namespace Limoncello\Tests\Passport;

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

use Doctrine\DBAL\Connection;
use Limoncello\OAuthServer\Contracts\GrantTypes;
use Limoncello\OAuthServer\Exceptions\OAuthRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\Passport\Adaptors\Generic\GenericPassportServerIntegration;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\RedirectUri;
use Limoncello\Passport\Adaptors\Generic\RedirectUriRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Adaptors\Generic\Token;
use Limoncello\Passport\Adaptors\Generic\TokenRepository;
use Limoncello\Passport\Contracts\PassportServerInterface;
use Limoncello\Passport\PassportServer;
use Limoncello\Passport\Traits\DatabaseSchemeMigrationTrait;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Tests\Passport
 */
class PassportServerTest extends TestCase
{
    use DatabaseSchemeMigrationTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initSqliteDatabase();
    }

    const TEST_DEFAULT_CLIENT_ID   = 'MainClient';
    const TEST_DEFAULT_CLIENT_PASS = 'secret';
    const TEST_USER_NAME           = 'john';
    const TEST_USER_PASSWORD       = 'secret';
    const TEST_USER_ID             = 5;
    const TEST_ERROR_URI           = 'http://example.app/auth_request_error';
    const TEST_APPROVAL_URI        = 'http://example.app/resource_owner_approval';
    const TEST_CLIENT_REDIRECT_URI = 'http://client.server/redirect_uri';
    const TEST_SCOPE_1             = 'scope1';
    const TEST_SCOPE_2             = 'scope2';

    /**
     * Test issuing resource owner password token.
     */
    public function testResourceOwnerPasswordToken()
    {
        $server   = $this->createPassportServer();
        $response = $server->postCreateToken(
            $this->createPasswordTokenRequest(static::TEST_USER_NAME, static::TEST_USER_PASSWORD)
        );
        $this->assertNotNull($response);
        $token = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($token);
        $this->assertTrue(property_exists($token, 'refresh_token'));
        $this->assertTrue(property_exists($token, 'scope'));
        $this->assertNotEmpty($token->scope);
        $this->assertNotEmpty($token->refresh_token);

        // check scopes were saved
        $tokenRepo  = new TokenRepository($this->getConnection(), $this->getDatabaseScheme());
        $this->assertNotNull($savedToken = $tokenRepo->readByValue($token->access_token, 100));
        $this->assertNotEmpty($savedToken->getScopeIdentifiers());

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test issuing resource owner password token.
     */
    public function testResourceOwnerPasswordTokenInvalidCredentials()
    {
        $server   = $this->createPassportServer();
        $response = $server->postCreateToken($this->createPasswordTokenRequest(
            static::TEST_USER_NAME,
            static::TEST_USER_PASSWORD . 'XXX' // <- invalid passwod
        ));
        $this->assertEquals(400, $response->getStatusCode());
        $error = json_decode((string)$response->getBody());
        $this->assertTrue(property_exists($error, 'error'));
        $this->assertEquals(OAuthTokenBodyException::ERROR_INVALID_GRANT, $error->error);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test refresh token.
     */
    public function testRefreshToken()
    {
        $server = $this->createPassportServer();

        // create initial token
        $response = $server->postCreateToken(
            $this->createPasswordTokenRequest(static::TEST_USER_NAME, static::TEST_USER_PASSWORD)
        );
        $this->assertNotNull($response);
        $token = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($token);
        $this->assertTrue(property_exists($token, 'scope'));
        $this->assertNotEmpty($token->scope);
        $this->assertNotEmpty($refreshToken = $token->refresh_token);

        // refresh the token
        $response = $server->postCreateToken(
            $this->createRefreshTokenRequest($refreshToken, null, PassportServerTest::TEST_DEFAULT_CLIENT_ID)
        );
        $this->assertNotNull($response);
        $newToken = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($newToken);

        $this->assertNotEquals($token->access_token, $newToken->access_token);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test refresh token.
     */
    public function testRefreshTokenWithNewScope()
    {
        $server = $this->createPassportServer();

        // create initial token
        $response = $server->postCreateToken(
            $this->createPasswordTokenRequest(static::TEST_USER_NAME, static::TEST_USER_PASSWORD)
        );
        $this->assertNotNull($response);
        $token = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($token);
        $this->assertTrue(property_exists($token, 'scope'));
        $this->assertNotEmpty($token->scope);
        $this->assertNotEmpty($refreshToken = $token->refresh_token);

        // refresh the token
        $response = $server->postCreateToken($this->createRefreshTokenRequest(
            $refreshToken,
            static::TEST_SCOPE_1,
            PassportServerTest::TEST_DEFAULT_CLIENT_ID
        ));
        $this->assertNotNull($response);
        $newToken = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($newToken);
        $this->assertTrue(property_exists($newToken, 'scope'));
        $this->assertNotEmpty($newToken->scope);

        $this->assertNotEquals($token->access_token, $newToken->access_token);

        $this->assertNotEmpty($foo = $this->getLogs());
    }

    /**
     * Test client grant with invalid client.
     */
    public function testRefreshTokenInvalidClient()
    {
        $noClientIdRequest = $this->createServerRequest([
            'grant_type' => GrantTypes::REFRESH_TOKEN

            // note no client_id
        ]);
        $response = $this->createPassportServer()->postCreateToken($noClientIdRequest);
        $this->assertEquals(400, $response->getStatusCode());
        $error = json_decode((string)$response->getBody());
        $this->assertTrue(property_exists($error, 'error'));
        $this->assertEquals(OAuthTokenBodyException::ERROR_INVALID_CLIENT, $error->error);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test implicit grant.
     */
    public function testImplicitGrant()
    {
        $server = $this->createPassportServer();

        // Step 1 - Ask resource owner for an approval.
        $response = $server->getCreateAuthorization($this->createImplicitRequest());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_APPROVAL_URI, $location);

        // Step 2 - Get a token.
        // Resource owner agreed to auth some scopes for the client and browser sends approval to server.
        $token = (new Token())
            ->setRedirectUriString(static::TEST_CLIENT_REDIRECT_URI)
            ->setClientIdentifier(static::TEST_DEFAULT_CLIENT_ID)
            ->setScopeIdentifiers([static::TEST_SCOPE_1, static::TEST_SCOPE_2])
            ->setUserIdentifier(static::TEST_USER_ID)
            ->setScopeModified();
        $response = $server->createTokenResponse($token);
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_CLIENT_REDIRECT_URI, $location);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test implicit grant.
     */
    public function testImplicitGrantForClientWithMultipleRedirectUris()
    {
        $server = $this->createPassportServer();

        // step 0 - add one more redirect URI to client so the server will not know which one to use (no default)
        $connection      = $this->getConnection();
        $scheme          = $this->getDatabaseScheme();
        $redirectUriRepo = new RedirectUriRepository($connection, $scheme);
        $redirectUriRepo->create(
            $uri1 = (new RedirectUri())
                ->setClientIdentifier(PassportServerTest::TEST_DEFAULT_CLIENT_ID)
                ->setValue(static::TEST_CLIENT_REDIRECT_URI . 'XXX') // <- not very creative URI :smile:
        );

        // Step 1 - Ask resource owner for an approval.
        $response = $server->getCreateAuthorization($this->createImplicitRequest());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_ERROR_URI, $location);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test implicit grant with invalid redirect URI.
     */
    public function testImplicitGrantInvalidRedirectUri()
    {
        $server = $this->createPassportServer();

        // Step 1 - Ask resource owner for an approval.
        $response = $server->getCreateAuthorization($this->createImplicitRequest());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_APPROVAL_URI, $location);

        // Step 2 - Get a token.
        // Resource owner agreed to auth some scopes for the client and browser sends approval to server.
        $token = (new Token())
            ->setRedirectUriString(static::TEST_CLIENT_REDIRECT_URI . 'XXX') // <- invalid redirect URI
            ->setClientIdentifier(static::TEST_DEFAULT_CLIENT_ID)
            ->setScopeIdentifiers([static::TEST_SCOPE_1, static::TEST_SCOPE_2])
            ->setUserIdentifier(static::TEST_USER_ID)
            ->setScopeModified();
        $response = $server->createTokenResponse($token);
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_ERROR_URI, $location);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test implicit grant with invalid client.
     */
    public function testImplicitGrantInvalidClient()
    {
        $server = $this->createPassportServer();

        $invalidClientId = 'XXX';
        // Step 1 - Ask resource owner for an approval.
        $response = $server->getCreateAuthorization($this->createImplicitRequest($invalidClientId));
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_ERROR_URI, $location);
    }

    /**
     * Test code grant.
     */
    public function testCodeGrant()
    {
        $server = $this->createPassportServer();

        // Step 1 - ask resource owner for approval
        $response = $server->getCreateAuthorization($this->createCodeRequest());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_APPROVAL_URI, $location);

        // Step 2 - Get a code.
        // Resource owner agreed to auth some scopes for the client and browser sends approval to server.
        $token    = (new Token())
            ->setRedirectUriString(static::TEST_CLIENT_REDIRECT_URI)
            ->setClientIdentifier(static::TEST_DEFAULT_CLIENT_ID)
            ->setScopeIdentifiers([static::TEST_SCOPE_1, static::TEST_SCOPE_2])
            ->setUserIdentifier(static::TEST_USER_ID)
            ->setScopeModified();
        $state    = 'some state 123';
        $response = $server->createCodeResponse($token, $state);
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_CLIENT_REDIRECT_URI, $location);
        parse_str((new Uri($location))->getQuery(), $parameters);
        $this->assertArrayHasKey('code', $parameters);
        $this->assertArrayHasKey('state', $parameters);
        $this->assertEquals($state, $parameters['state']);

        $code = $parameters['code'];

        // Step 3 - Exchange the code for token.
        $response = $server->postCreateToken($this->createTokenRequest($code));
        $this->assertNotNull($response);
        $token = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($token);
        $this->assertNotEmpty($refreshToken = $token->refresh_token);

        $this->assertNotEmpty($this->getLogs());

        // Step 3a - Try to use the code second time
        $response = $server->postCreateToken($this->createTokenRequest($code));
        $error = json_decode((string)$response->getBody());
        $this->assertTrue(property_exists($error, 'error'));
        $this->assertEquals(OAuthTokenBodyException::ERROR_INVALID_GRANT, $error->error);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test code grant with invalid redirect URI.
     */
    public function testCodeGrantInvalidRedirectUri()
    {
        $server = $this->createPassportServer();

        // Step 1 - ask resource owner for approval
        $response = $server->getCreateAuthorization($this->createCodeRequest());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_APPROVAL_URI, $location);

        // Step 2 - Get a code.
        // Resource owner agreed to auth some scopes for the client and browser sends approval to server.
        $token    = (new Token())
            ->setRedirectUriString(static::TEST_CLIENT_REDIRECT_URI . 'XXX') // <- invalid redirect URI
            ->setClientIdentifier(static::TEST_DEFAULT_CLIENT_ID)
            ->setScopeIdentifiers([static::TEST_SCOPE_1, static::TEST_SCOPE_2])
            ->setUserIdentifier(static::TEST_USER_ID)
            ->setScopeModified()
        ;
        $state    = 'some state 123';
        $response = $server->createCodeResponse($token, $state);
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_ERROR_URI, $location);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test code grant with invalid client.
     */
    public function testCodeGrantInvalidClient()
    {
        $server = $this->createPassportServer();

        $invalidClientId = 'XXX';
        // Step 1 - ask resource owner for approval
        $response = $server->getCreateAuthorization($this->createCodeRequest($invalidClientId));
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $this->assertStringStartsWith(static::TEST_ERROR_URI, $location);
    }

    /**
     * Test client grant.
     */
    public function testClientGrant()
    {
        $server = $this->createPassportServer();

        // add client authentication so we can actually get any tokens
        $clientRepo    = new ClientRepository($this->getConnection(), $this->getDatabaseScheme());
        $defaultClient = $clientRepo->read(static::TEST_DEFAULT_CLIENT_ID);
        $defaultClient->enableClientGrant()->setCredentials(
            password_hash(static::TEST_DEFAULT_CLIENT_PASS, PASSWORD_DEFAULT)
        );
        $clientRepo->update($defaultClient);

        $response = $server->postCreateToken($this->createClientTokenRequest());
        $this->assertNotNull($response);
        $token = json_decode((string)$response->getBody());
        $this->checkItLooksLikeValidToken($token);
        $this->assertFalse(property_exists($token, 'refresh_token'));
        $this->assertTrue(property_exists($token, 'scope'));
        $this->assertNotEmpty($token->scope);

        // check scopes were saved
        $tokenRepo  = new TokenRepository($this->getConnection(), $this->getDatabaseScheme());
        $this->assertNotNull($savedToken = $tokenRepo->readByValue($token->access_token, 100));
        $this->assertNotEmpty($savedToken->getScopeIdentifiers());

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test client grant with invalid client.
     */
    public function testClientGrantInvalidClient()
    {
        $noClientIdRequest = $this->createServerRequest([
            'grant_type' => GrantTypes::CLIENT_CREDENTIALS

            // note no client_id
        ]);
        $response = $this->createPassportServer()->postCreateToken($noClientIdRequest);
        $this->assertEquals(400, $response->getStatusCode());
        $error = json_decode((string)$response->getBody());
        $this->assertTrue(property_exists($error, 'error'));
        $this->assertEquals(OAuthTokenBodyException::ERROR_INVALID_CLIENT, $error->error);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * Test invalid grant.
     */
    public function testInvalidResponseType()
    {
        $server = $this->createPassportServer();

        $request = $this->createServerRequest(null, [
            'response_type' => 'UNKNOWN_RESPONSE_TYPE',
            'client_id'     => self::TEST_DEFAULT_CLIENT_ID,
        ]);

        // Step 1 - Ask resource owner for an approval.
        $response = $server->getCreateAuthorization($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $location = $response->getHeader('location')[0];
        $locationUri = new Uri($location);
        parse_str($locationUri->getFragment(), $fragments);
        $this->assertArrayHasKey('error', $fragments);
        $this->assertEquals(OAuthRedirectException::ERROR_UNSUPPORTED_RESPONSE_TYPE, $fragments['error']);
    }

    /**
     * Test client grant with invalid client.
     */
    public function testInvalidGrantType()
    {
        $noClientIdRequest = $this->createServerRequest([
            'grant_type' => 'UNKNOWN_GRANT_TYPE',
            'client_id'  => self::TEST_DEFAULT_CLIENT_ID,
        ]);
        $response = $this->createPassportServer()->postCreateToken($noClientIdRequest);
        $this->assertEquals(400, $response->getStatusCode());
        $error = json_decode((string)$response->getBody());
        $this->assertTrue(property_exists($error, 'error'));
        $this->assertEquals(OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE, $error->error);

        $this->assertNotEmpty($this->getLogs());
    }

    /**
     * @param string|null $username
     * @param string|null $password
     * @param string|null $scope
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createPasswordTokenRequest(
        string $username = null,
        string $password = null,
        string $scope = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest([
            'grant_type' => 'password',
            'username'   => $username,
            'password'   => $password,
            'scope'      => $scope,
        ], null, $headers);

        return $request;
    }

    /**
     * @param string      $clientId
     * @param string|null $redirectUri
     * @param string|null $scope
     * @param string|null $state
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createImplicitRequest(
        string $clientId = self::TEST_DEFAULT_CLIENT_ID,
        string $redirectUri = null,
        string $scope = null,
        string $state = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest(null, [
            'response_type' => 'token',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => $scope,
            'state'         => $state,
        ], $headers);

        return $request;
    }

    /**
     * @param string      $clientId
     * @param string|null $redirectUri
     * @param string|null $scope
     * @param string|null $state
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createCodeRequest(
        string $clientId = self::TEST_DEFAULT_CLIENT_ID,
        string $redirectUri = null,
        string $scope = null,
        string $state = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest(null, [
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => $scope,
            'state'         => $state,
        ], $headers);

        return $request;
    }

    /**
     * @param string $code
     * @param string $clientId
     * @param string $redirectUri
     * @param array  $headers
     *
     * @return ServerRequestInterface
     */
    private function createTokenRequest(
        string $code,
        string $clientId = self::TEST_DEFAULT_CLIENT_ID,
        string $redirectUri = self::TEST_CLIENT_REDIRECT_URI,
        array $headers = []
    ) {
        $request = $this->createServerRequest([
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $redirectUri,
            'client_id'    => $clientId,
        ], null, $headers);

        return $request;
    }

    /**
     * @param string|null $scope
     * @param string      $clientId
     * @param string      $clientPass
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createClientTokenRequest(
        string $scope = null,
        string $clientId = self::TEST_DEFAULT_CLIENT_ID,
        string $clientPass = self::TEST_DEFAULT_CLIENT_PASS,
        array $headers = []
    ) {
        $clientAuthHeader = [
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientPass),
        ];

        $request = $this->createServerRequest([
            'grant_type' => 'client_credentials',
            'scope'      => $scope,
        ], null, $headers + $clientAuthHeader);

        return $request;
    }

    /**
     * @param string|null $refreshToken
     * @param string|null $scope
     * @param string|null $clientId
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createRefreshTokenRequest(
        string $refreshToken = null,
        string $scope = null,
        string $clientId = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope'         => $scope,
            'client_id'     => $clientId,
        ], null, $headers);

        return $request;
    }

    /**
     * @return PassportServerInterface
     */
    private function createPassportServer(): PassportServerInterface
    {
        $connection      = $this->getConnection();
        $scheme          = $this->getDatabaseScheme();
        $scopeRepo       = new ScopeRepository($connection, $scheme);
        $clientRepo      = new ClientRepository($connection, $scheme);
        $redirectUriRepo = new RedirectUriRepository($connection, $scheme);

        $clientRepo->inTransaction(function () use ($scopeRepo, $clientRepo, $redirectUriRepo) {
            $client = $clientRepo->create(
                (new Client())
                    ->setIdentifier(PassportServerTest::TEST_DEFAULT_CLIENT_ID)
                    ->setName('client name')
                    ->enableCodeGrant()
                    ->enableImplicitGrant()
                    ->enablePasswordGrant()
                    ->enableRefreshGrant()
                    ->useDefaultScopesOnEmptyRequest()
            );

            $scopeRepo->create($scope1 = (new Scope())->setIdentifier(static::TEST_SCOPE_1));
            $scopeRepo->create($scope2 = (new Scope())->setIdentifier(static::TEST_SCOPE_2));
            $clientRepo->bindScopes($client->getIdentifier(), [$scope1, $scope2]);

            $redirectUriRepo->create(
                $uri1 = (new RedirectUri())
                    ->setClientIdentifier($client->getIdentifier())
                    ->setValue(static::TEST_CLIENT_REDIRECT_URI)
            );
        });

        $integration = new class ($connection) extends GenericPassportServerIntegration
        {

            /**
             * @param Connection $connection
             */
            public function __construct(Connection $connection)
            {
                parent::__construct(
                    $connection,
                    PassportServerTest::TEST_DEFAULT_CLIENT_ID,
                    PassportServerTest::TEST_APPROVAL_URI,
                    PassportServerTest::TEST_ERROR_URI
                );
            }

            /**
             * @inheritdoc
             */
            public function validateUserId(string $userName, string $password)
            {
                return
                    $userName === PassportServerTest::TEST_USER_NAME &&
                    $password === PassportServerTest::TEST_USER_PASSWORD ?
                        PassportServerTest::TEST_USER_ID : null;
            }
        };

        $server = new PassportServer($integration);
        $server->setLogger($this->getLogger());

        return $server;
    }

    /**
     * @param $token
     */
    private function checkItLooksLikeValidToken($token)
    {
        $this->assertTrue(property_exists($token, 'access_token'));
        $this->assertTrue(property_exists($token, 'token_type'));
        $this->assertTrue(property_exists($token, 'expires_in'));

        $this->assertNotEmpty($token->access_token);
        $this->assertEquals('bearer', $token->token_type);
        $this->assertTrue(is_int($token->expires_in));
    }
}
