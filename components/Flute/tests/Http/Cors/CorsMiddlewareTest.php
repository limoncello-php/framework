<?php namespace Limoncello\Tests\Flute\Http\Cors;

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

use Closure;
use Limoncello\Container\Container;
use Limoncello\Flute\Contracts\Http\Cors\CorsStorageInterface;
use Limoncello\Flute\Http\Cors\CorsMiddleware;
use Limoncello\Flute\Http\Cors\CorsStorage;
use Limoncello\Tests\Flute\TestCase;
use Mockery;
use Mockery\Mock;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @package Limoncello\Tests\Flute
 */
class CorsMiddlewareTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var AnalyzerInterface
     */
    private $analyzer;

    /**
     * @var Mock
     */
    private $analysis;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Closure
     */
    private $next;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->analysis = Mockery::mock(AnalysisResultInterface::class);

        /** @var Mock $analyzer */
        $analyzer = Mockery::mock(AnalyzerInterface::class);
        $analyzer->shouldReceive('analyze')->once()->withAnyArgs()->andReturn($this->analysis);

        $this->analyzer = $analyzer;

        $container = new Container();
        $container[AnalyzerInterface::class] = $this->analyzer;
        $container[CorsStorageInterface::class] = new CorsStorage();
        $this->container = $container;

        $this->request = Mockery::mock(ServerRequestInterface::class);

        $this->next = function () {
            return new EmptyResponse();
        };
    }

    /**
     * Test CORS.
     */
    public function testOutOrCorsScopeRequest()
    {
        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
    }

    /**
     * Test CORS.
     */
    public function testActualRequest()
    {
        $corsHeaders = ['key' => 'value'];

        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::TYPE_ACTUAL_REQUEST);
        $this->analysis->shouldReceive('getResponseHeaders')->once()->withNoArgs()
            ->andReturn($corsHeaders);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertTrue($response->hasHeader('key'));
    }

    /**
     * Test CORS.
     */
    public function testPreFlightRequest()
    {
        $corsHeaders = ['key' => 'value'];

        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST);
        $this->analysis->shouldReceive('getResponseHeaders')->once()->withNoArgs()
            ->andReturn($corsHeaders);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertTrue($response->hasHeader('key'));
    }

    /**
     * Test CORS.
     */
    public function testErrorNoHostRequest()
    {
        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::ERR_NO_HOST_HEADER);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test CORS.
     */
    public function testErrorOriginNotAllowedRequest()
    {
        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test CORS.
     */
    public function testErrorMethodNotSupportedRequest()
    {
        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test CORS.
     */
    public function testErrorHeadersNotSupportedRequest()
    {
        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()
            ->andReturn(AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Test CORS.
     */
    public function testOtherErrorRequest()
    {
        $this->analysis->shouldReceive('getRequestType')->once()->withNoArgs()->andReturn(-1);

        $response = CorsMiddleware::handle($this->request, $this->next, $this->container);
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
