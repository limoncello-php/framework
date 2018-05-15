<?php namespace Limoncello\Tests\OAuthServer;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Exception;
use Limoncello\OAuthServer\Exceptions\OAuthCodeRedirectException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Limoncello\OAuthServer\Exceptions\OAuthTokenRedirectException;
use Limoncello\Tests\OAuthServer\Data\SampleServer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Tests\OAuthServer
 */
abstract class ServerTestCase extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * @param array|null $postData
     * @param array|null $queryParameters
     * @param array|null $headers
     *
     * @return ServerRequestInterface
     */
    protected function createServerRequest(
        array $postData = null,
        array $queryParameters = null,
        array $headers = null
    ): ServerRequestInterface {
        $removeNulls = function (array $values) {
            return array_filter($values, function ($value) {
                return $value !== null;
            });
        };

        $server = null;
        $query  = null;
        $body   = null;

        if ($headers !== null) {
            foreach ($removeNulls($headers) as $header => $value) {
                $server['HTTP_' . $header] = $value;
            }
        }

        if ($queryParameters !== null) {
            $query = $removeNulls($queryParameters);
        }

        if ($postData !== null) {
            $body = $removeNulls($postData);
        }

        $request = ServerRequestFactory::fromGlobals($server, $query, $body);

        return $request;
    }

    /**
     * @param string $token
     * @param string $type
     * @param int    $expiresIn
     * @param string $refreshToken
     * @param string $scope
     *
     * @return array
     */
    protected function getExpectedBodyToken(
        string $token = SampleServer::TEST_TOKEN,
        string $type = 'bearer',
        int $expiresIn = 3600,
        string $refreshToken = SampleServer::TEST_REFRESH_TOKEN,
        string $scope = null
    ): array {
        return array_filter([
            'access_token'  => $token,
            'token_type'    => $type,
            'expires_in'    => $expiresIn,
            'refresh_token' => $refreshToken,
            'scope'         => $scope,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * @param string $token
     * @param string $type
     * @param int    $expiresIn
     *
     * @return array
     */
    protected function getExpectedBodyTokenNoRefresh(
        string $token = SampleServer::TEST_TOKEN,
        string $type = 'bearer',
        int $expiresIn = 3600
    ): array {
        return array_filter([
            'access_token'  => $token,
            'token_type'    => $type,
            'expires_in'    => $expiresIn
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * @param ResponseInterface $response
     * @param int               $httpStatus
     * @param array             $body
     * @param string[]          $headers
     *
     * @return void
     *
     * @throws Exception
     */
    protected function validateBodyResponse(
        ResponseInterface $response,
        int $httpStatus,
        array $body,
        array $headers = []
    ) {
        $headers += [
            'Content-type'  => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma'        => 'no-cache',
        ];

        $this->assertEquals($httpStatus, $response->getStatusCode());
        foreach ($headers as $header => $value) {
            $this->assertEquals([$value], $response->getHeader($header));
        }
        $this->assertNotNull($encoded = json_decode((string)$response->getBody(), true));
        $this->assertEquals($body, $encoded);
    }

    /**
     * @param string      $error
     * @param string|null $description
     * @param string|null $uri
     *
     * @return string[]
     */
    protected function getExpectedBodyTokenError(string $error, string $uri = null, string $description = null): array
    {
        if ($description === null) {
            $description = OAuthTokenBodyException::DEFAULT_MESSAGES[$error];
        }

        return array_filter([
            'error'             => $error,
            'error_description' => $description,
            'error_uri'         => $uri,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * @param string|null $state
     * @param string      $code
     *
     * @return array
     */
    protected function getExpectedRedirectCode(
        string $state = null,
        string $code = SampleServer::TEST_AUTH_CODE
    ): array {
        return array_filter([
            'code'  => $code,
            'state' => $state,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * @param string|null $state
     * @param string|null $scope
     * @param string      $token
     * @param string      $type
     * @param int         $expiresIn
     *
     * @return array
     */
    protected function getExpectedRedirectToken(
        string $state = null,
        string $scope = null,
        string $token = SampleServer::TEST_TOKEN,
        string $type = 'bearer',
        int $expiresIn = 3600
    ): array {
        return array_filter([
            'access_token'  => $token,
            'token_type'    => $type,
            'expires_in'    => $expiresIn,
            'scope'         => $scope,
            'state'         => $state,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * @param ResponseInterface $response
     * @param string            $redirectUri
     * @param array             $expectedFragments
     * @param int               $httpStatus
     * @param string[]          $headers
     *
     * @return void
     *
     * @throws Exception
     */
    protected function validateRedirectResponse(
        ResponseInterface $response,
        string $redirectUri,
        array $expectedFragments,
        int $httpStatus = 302,
        array $headers = []
    ) {
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        /** @noinspection PhpParamsInspection */
        $this->assertCount(1, $response->getHeader('Location'));
        list($location) = $response->getHeader('Location');

        $locationUri = new Uri($location);

        $this->assertEquals($redirectUri, $locationUri->withFragment(''));

        parse_str($locationUri->getFragment(), $fragments);
        $this->assertEquals($expectedFragments, $fragments);

        $this->assertEquals($httpStatus, $response->getStatusCode());
        foreach ($headers as $header => $value) {
            $this->assertEquals([$value], $response->getHeader($header));
        }
        $this->assertEmpty((string)$response->getBody());
    }

    /**
     * @param string      $error
     * @param string      $state
     * @param string|null $uri
     * @param string|null $description
     *
     * @return string[]
     */
    protected function getExpectedRedirectCodeErrorFragments(
        string $error,
        string $state = null,
        string $uri = null,
        string $description = null
    ): array {
        if ($description === null) {
            $description = OAuthCodeRedirectException::DEFAULT_MESSAGES[$error];
        }

        return array_filter([
            'error'             => $error,
            'error_description' => $description,
            'error_uri'         => $uri,
            'state'             => $state,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * @param string      $error
     * @param string      $state
     * @param string|null $uri
     * @param string|null $description
     *
     * @return string[]
     */
    protected function getExpectedRedirectTokenErrorFragments(
        string $error,
        string $state = null,
        string $uri = null,
        string $description = null
    ): array {
        if ($description === null) {
            $description = OAuthTokenRedirectException::DEFAULT_MESSAGES[$error];
        }

        return array_filter([
            'error'             => $error,
            'error_description' => $description,
            'error_uri'         => $uri,
            'state'             => $state,
        ], function ($value) {
            return $value !== null;
        });
    }
}
