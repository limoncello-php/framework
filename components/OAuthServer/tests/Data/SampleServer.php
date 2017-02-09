<?php namespace Limoncello\Tests\OAuthServer\Data;

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

use Limoncello\OAuthServer\BaseAuthorizationServer;
use Limoncello\OAuthServer\Contracts\GrantTypes;
use Limoncello\OAuthServer\Contracts\AuthorizationCodeInterface;
use Limoncello\OAuthServer\Contracts\ResponseTypes;
use Limoncello\OAuthServer\Contracts\Clients\ClientInterface;
use Limoncello\OAuthServer\Exceptions\OAuthRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Tests\OAuthServer
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SampleServer extends BaseAuthorizationServer
{
    /** Test data */
    const TEST_USER_NAME = 'john@dow.foo';

    /** Test data */
    const TEST_PASSWORD = 'password';

    /** Test data */
    const TEST_AUTH_CODE = '5ebe2294ecd0e0f08eab7690d2a6ee69';

    /** Test data */
    const TEST_USED_AUTH_CODE = 'c7f7a58918e790e28827a4e272462914';

    /** Test data */
    const TEST_TOKEN = '4142011b2689166ce7760644a0b5f8d0';

    /** Test data */
    const TEST_REFRESH_TOKEN = 'e57941dbe1246fe97a4ffc16e85b5df9';

    /** Test data */
    const TEST_TOKEN_TYPE = 'bearer';

    /** Test data */
    const TEST_TOKEN_EXPIRES_IN = 3600;

    /** Test data */
    const TEST_UNSUPPORTED_GRANT_TYPE_ERROR_URI = 'http://example.com/error123';

    /** Test data */
    const TEST_CLIENT_ID = 'some_client_id';

    /** Test data */
    const TEST_CLIENT_REDIRECT_URI = 'https://client.foo/redirect';

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        parent::__construct();

        $this->setRepository($repository);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function createAuthorization(array $parameters): ResponseInterface
    {
        try {
            $client       = null;
            $redirectUri  = null;
            $responseType = $this->getResponseType($parameters);
            switch ($responseType) {
                case ResponseTypes::AUTHORIZATION_CODE:
                    $redirectUri  = $this->codeGetRedirectUri($parameters);
                    // check client identifier and redirect URI
                    $isInvalid =
                        ($clientId = $this->codeGetClientId($parameters)) === null ||
                        ($client = $this->getRepository()->readClient($clientId)) === null ||
                        $this->isValidRedirectUri($client, $redirectUri) === false;
                    if ($isInvalid === true) {
                        $response = $this->createInvalidClientAndRedirectUriErrorResponse();
                    } else {
                        $response = $this->codeAskResourceOwnerForApproval(
                            $parameters,
                            $client,
                            $redirectUri,
                            $this->getMaxStateLength()
                        );
                    }
                    break;
                case ResponseTypes::IMPLICIT:
                    $redirectUri  = $this->implicitGetRedirectUri($parameters);
                    // check client identifier and redirect URI
                    $isInvalid =
                        ($clientId = $this->implicitGetClientId($parameters)) === null ||
                        ($client = $this->getRepository()->readClient($clientId)) === null ||
                        $this->isValidRedirectUri($client, $redirectUri) === false;
                    if ($isInvalid === true) {
                        $response = $this->createInvalidClientAndRedirectUriErrorResponse();
                    } else {
                        $response = $this->implicitAskResourceOwnerForApproval(
                            $parameters,
                            $client,
                            $redirectUri,
                            $this->getMaxStateLength()
                        );
                    }
                    break;
                default:
                    throw new OAuthTokenBodyException(
                        OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE,
                        self::TEST_UNSUPPORTED_GRANT_TYPE_ERROR_URI
                    );
            }
        } catch (OAuthRedirectException $exception) {
            $response = $this->createRedirectErrorResponse($exception);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function postCreateToken(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $parameters          = $request->getParsedBody();
            $authenticatedClient = $this->authenticateClient($request);

            switch ($this->getGrantType($parameters)) {
                case GrantTypes::AUTHORIZATION_CODE:
                    $response = $this->codeIssueToken($parameters, $authenticatedClient);
                    break;
                case GrantTypes::RESOURCE_OWNER_PASSWORD_CREDENTIALS:
                    $response = $this->passIssueToken($parameters, $authenticatedClient);
                    break;
                case GrantTypes::CLIENT_CREDENTIALS:
                    if ($authenticatedClient === null) {
                        throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_CLIENT);
                    }
                    $response = $this->clientIssueToken($parameters, $authenticatedClient);
                    break;
                default:
                    throw new OAuthTokenBodyException(
                        OAuthTokenBodyException::ERROR_UNSUPPORTED_GRANT_TYPE,
                        self::TEST_UNSUPPORTED_GRANT_TYPE_ERROR_URI
                    );
            }
        } catch (OAuthTokenBodyException $exception) {
            $response = $this->createBodyErrorResponse($exception);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function codeCreateAskResourceOwnerForApprovalResponse(
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null
    ): ResponseInterface {
        // This method should return some kind of HTML response with a list of scopes asking resource owner for
        // approval. If the scope is approved the controller should create and save authentication code and
        // make redirect with this code to client.
        //
        // As this logic is app specific it's not a part of server code.
        //
        // For demonstration purposes and simplicity we skip 'approval' step and issue the code right away.

        $code = (new AuthorizationCode(static::TEST_AUTH_CODE, $client->getIdentifier(), $redirectUri, $scopeList))
            ->setState($state);
        $isScopeModified === true ? $code->setScopeModified() : $code->setScopeUnmodified();

        $response = $this->createRedirectCodeResponse($client, $code);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function codeReadAuthenticationCode(string $code)
    {
        // As out server do not actually implement storing auth codes we will emulate it.
        $result = null;

        if ($code === static::TEST_AUTH_CODE) {
            $result = new AuthorizationCode($code, static::TEST_CLIENT_ID, static::TEST_CLIENT_REDIRECT_URI);
        } elseif ($code === static::TEST_USED_AUTH_CODE) {
            $result = new AuthorizationCode($code, static::TEST_CLIENT_ID, static::TEST_CLIENT_REDIRECT_URI);
            $result->setHasBeenUsedEarlier();
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function codeCreateAccessTokenResponse(AuthorizationCodeInterface $code): ResponseInterface
    {
        $token        = static::TEST_TOKEN;
        $type         = static::TEST_TOKEN_TYPE;
        $expiresIn    = static::TEST_TOKEN_EXPIRES_IN;
        $refreshToken = static::TEST_REFRESH_TOKEN;
        $scopeList    = $code->isScopeModified() === true ? $code->getScope() : null;

        // let's pretend we've saved the token parameters for the user

        $response = $this->createBodyTokenResponse($token, $type, $expiresIn, $refreshToken, $scopeList);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function codeReadClient(string $identifier)
    {
        return $this->getRepository()->readClient($identifier);
    }

    /**
     * @inheritdoc
     */
    public function codeRevokeTokens(AuthorizationCodeInterface $code)
    {
        // pretend we actually revoke all related tokens
    }

    /**
     * @inheritdoc
     */
    public function implicitCreateAskResourceOwnerForApprovalResponse(
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null
    ): ResponseInterface {
        // This method should return some kind of HTML response with a list of scopes asking resource owner for
        // approval. If the scope is approved the controller should create and save token and return it.
        // As this logic is app specific it's not a part of server code.
        //
        // For demonstration purposes and simplicity we skip 'approval' step and issue the token right away.

        $redirectUri = $this->selectRedirectUri($client, $redirectUri);

        $token     = static::TEST_TOKEN;
        $type      = static::TEST_TOKEN_TYPE;
        $expiresIn = static::TEST_TOKEN_EXPIRES_IN;
        $scopes    = $isScopeModified === true ? $scopeList : null;

        $response = $this->createRedirectTokenResponse($redirectUri, $token, $type, $expiresIn, $scopes, $state);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function passValidateCredentialsAndCreateAccessTokenResponse(
        $userName,
        $password,
        ClientInterface $client = null,
        bool $isScopeModified = false,
        array $scope = null
    ): ResponseInterface {
        // let's pretend we've made a query to our database and checked the credentials
        $areCredentialsValid = $userName === static::TEST_USER_NAME && $password === static::TEST_PASSWORD;

        if ($areCredentialsValid === false) {
            throw new OAuthTokenBodyException(OAuthTokenBodyException::ERROR_INVALID_GRANT);
        }

        $token        = static::TEST_TOKEN;
        $type         = static::TEST_TOKEN_TYPE;
        $expiresIn    = static::TEST_TOKEN_EXPIRES_IN;
        $refreshToken = static::TEST_REFRESH_TOKEN;

        // let's pretend we've saved the token parameters for the user

        $response = $this->createBodyTokenResponse($token, $type, $expiresIn, $refreshToken);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function passReadDefaultClient(): ClientInterface
    {
        return $this->getRepository()->readDefaultClient();
    }

    /**
     * @inheritdoc
     */
    public function clientCreateAccessTokenResponse(
        ClientInterface $client,
        bool $isScopeModified,
        array $scope = null
    ): ResponseInterface {
        $token     = static::TEST_TOKEN;
        $type      = static::TEST_TOKEN_TYPE;
        $expiresIn = static::TEST_TOKEN_EXPIRES_IN;

        // let's pretend we've saved the token parameters for the client

        $response = $this->createBodyTokenResponse($token, $type, $expiresIn);

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ClientInterface|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function authenticateClient(ServerRequestInterface $request)
    {
        // As an example let's implement `Basic` client authorization

        $client = null;
        if (empty($headerArray = $request->getHeader('Authorization')) === false) {
            if (empty($authHeader = $headerArray[0]) === true ||
                ($tokenPos = strpos($authHeader, 'Basic ')) === false ||
                $tokenPos !== 0 ||
                ($authValue = substr($authHeader, 6)) === '' ||
                $authValue === false ||
                ($decodedValue = base64_decode($authValue, true)) === false ||
                count($nameAndPassword = explode(':', $decodedValue, 2)) !== 2 ||
                ($client = $this->getRepository()->readClient($nameAndPassword[0])) === null ||
                password_verify($nameAndPassword[1], $client->getCredentials()) === false
            ) {
                throw new OAuthTokenBodyException(
                    OAuthTokenBodyException::ERROR_INVALID_CLIENT,
                    null, // error URI
                    401,
                    ['WWW-Authenticate' => 'Basic realm="OAuth"']
                );
            }
        }

        return $client;
    }

    /**
     * @param ClientInterface            $client
     * @param AuthorizationCodeInterface $code
     *
     * @return ResponseInterface
     */
    private function createRedirectCodeResponse(
        ClientInterface $client,
        AuthorizationCodeInterface $code
    ): ResponseInterface {
        // for authorization code format @link https://tools.ietf.org/html/rfc6749#section-4.1.2
        $parameters = array_filter([
            'code'  => $code->getCode(),
            'state' => $code->getState(),
        ], function ($value) {
            return $value !== null;
        });

        $fragment = $this->encodeAsXWwwFormUrlencoded($parameters);

        $redirectUri = $this->selectRedirectUri($client, $code->getRedirectUri());
        $response = new RedirectResponse((new Uri($redirectUri))->withFragment($fragment));

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string        $redirectUri
     * @param string        $token
     * @param string        $type
     * @param int           $expiresIn
     * @param string[]|null $scopeList
     * @param string|null   $state
     *
     * @return ResponseInterface
     */
    private function createRedirectTokenResponse(
        string $redirectUri,
        string $token,
        string $type,
        int $expiresIn,
        array $scopeList = null,
        string $state = null
    ): ResponseInterface {
        // for access token format @link https://tools.ietf.org/html/rfc6749#section-4.2.2
        //
        // Also from #4.2.2
        //
        // Developers should note that some user-agents do not support the inclusion of a fragment component in the
        // HTTP "Location" response header field.  Such clients will require using other methods for redirecting
        // the client than a 3xx redirection response -- for example, returning an HTML page that includes a
        // 'continue' button with an action linked to the redirection URI.

        $scope      = $scopeList === null ? null : implode(' ', $scopeList);
        $parameters = array_filter([
            'access_token' => $token,
            'token_type'   => $type,
            'expires_in'   => $expiresIn,
            'scope'        => $scope,
            'state'        => $state,
        ], function ($value) {
            return $value !== null;
        });

        $fragment = $this->encodeAsXWwwFormUrlencoded($parameters);

        $response = new RedirectResponse((new Uri($redirectUri))->withFragment($fragment));

        return $response;
    }

    /**
     * @param string        $token
     * @param string        $type
     * @param int           $expiresIn
     * @param string|null   $refreshToken
     * @param string[]|null $scopeList
     *
     * @return ResponseInterface
     */
    private function createBodyTokenResponse(
        string $token,
        string $type,
        int $expiresIn,
        string $refreshToken = null,
        array $scopeList = null
    ): ResponseInterface {
        // for access token format @link https://tools.ietf.org/html/rfc6749#section-5.1
        $scope      = $scopeList === null ? null : implode(' ', $scopeList);
        $parameters = array_filter([
            'access_token'  => $token,
            'token_type'    => $type,
            'expires_in'    => $expiresIn,
            'refresh_token' => $refreshToken,
            'scope'         => $scope,
        ], function ($value) {
            return $value !== null;
        });

        $response = new JsonResponse($parameters, 200, [
            'Cache-Control' => 'no-store',
            'Pragma'        => 'no-cache'
        ]);

        return $response;
    }

    /**
     * @param OAuthRedirectException $exception
     *
     * @return ResponseInterface
     */
    private function createRedirectErrorResponse(OAuthRedirectException $exception): ResponseInterface
    {
        $parameters = array_filter([
            'error'             => $exception->getErrorCode(),
            'error_description' => $exception->getErrorDescription(),
            'error_uri'         => $exception->getErrorUri(),
            'state'             => $exception->getState(),
        ], function ($value) {
            return $value !== null;
        });
        $fragment = $this->encodeAsXWwwFormUrlencoded($parameters);
        $uri      = (new Uri($exception->getRedirectUri()))->withFragment($fragment);

        $response = new RedirectResponse($uri, 302, $exception->getHttpHeaders());

        return $response;
    }

    /**
     * @param OAuthTokenBodyException $exception
     *
     * @return ResponseInterface
     */
    private function createBodyErrorResponse(OAuthTokenBodyException $exception): ResponseInterface
    {
        $response =
            new JsonResponse($exception->toArray(), $exception->getHttpCode(), $exception->getHttpHeaders());

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    private function createInvalidClientAndRedirectUriErrorResponse(): ResponseInterface
    {
        return new HtmlResponse('Combination of client identifier and redirect URI is invalid. <br>', 400);
    }

    /**
     * @return RepositoryInterface
     */
    private function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * @param RepositoryInterface $repository
     *
     * @return SampleServer
     */
    private function setRepository(RepositoryInterface $repository): SampleServer
    {
        $this->repository = $repository;

        return $this;
    }
}
