<?php declare(strict_types=1);

namespace Limoncello\Tests\Testing;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Contracts\Core\ApplicationInterface;
use Limoncello\Testing\HttpCallsTrait;
use Limoncello\Testing\JsonApiCallsTrait;
use Limoncello\Testing\Sapi;
use Limoncello\Testing\TestCaseTrait;
use Mockery;
use Mockery\Mock;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Stream;

/**
 * @package Limoncello\Tests\Testing
 */
class CallTraitsTest extends TestCase
{
    use TestCaseTrait, HttpCallsTrait, JsonApiCallsTrait;

    /** Default test URI */
    const URI = 'some-uri';

    /**
     * @var Mock|null
     */
    private $sapiMock;

    /**
     * @var Sapi|null
     */
    private $sapi;

    /**
     * @var Mock|null
     */
    private $appMock;

    /**
     * @var ApplicationInterface|null
     */
    private $app;

    /**
     * @var array
     */
    private $createSapiArgs = [];

    /**
     * Test call web method.
     */
    public function testGet(): void
    {
        $headers = ['x-header' => 'value'];
        $server  = ['HTTP_X_HEADER' => 'value'];

        $this->willBeCalled('GET', $server);

        $this->get(self::URI, [], $headers);
    }

    /**
     * Test call web method.
     */
    public function testPost(): void
    {
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];

        $this->willBeCalled('POST', $server);

        $this->post(self::URI);
    }

    /**
     * Test call web method.
     */
    public function testPut(): void
    {
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];

        $this->willBeCalled('PUT', $server);

        $this->put(self::URI);
    }

    /**
     * Test call web method.
     */
    public function testPatch(): void
    {
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];

        $this->willBeCalled('PATCH', $server);

        $this->patch(self::URI);
    }

    /**
     * Test call web method.
     */
    public function testDelete(): void
    {
        $this->willBeCalled('DELETE');

        $this->delete(self::URI);
    }

    /**
     * Test stream convert.
     */
    public function testStreamFromString(): void
    {
        $string = 'whatever';
        $stream = $this->streamFromString($string);

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals($stream, (string)$string);
    }

    /**
     * Test call web method.
     */
    public function testJsonApiPost(): void
    {
        $json   = '{}';
        $server = ['CONTENT_TYPE' => 'application/vnd.api+json'];

        $this->willBeCalled('POST', $server, $json);

        $this->postJsonApi(self::URI, $json);
    }

    /**
     * Test call web method.
     */
    public function testJsonApiPut(): void
    {
        $json   = '{}';
        $server = ['CONTENT_TYPE' => 'application/vnd.api+json'];

        $this->willBeCalled('PUT', $server, $json);

        $this->putJsonApi(self::URI, $json);
    }

    /**
     * Test call web method.
     */
    public function testJsonApiPatch(): void
    {
        $json   = '{}';
        $server = ['CONTENT_TYPE' => 'application/vnd.api+json'];

        $this->willBeCalled('PATCH', $server, $json);

        $this->patchJsonApi(self::URI, $json);
    }

    /**
     * Test call web method.
     */
    public function testJsonApiDelete(): void
    {
        $json   = '{}';
        $server = ['CONTENT_TYPE' => 'application/vnd.api+json'];

        $this->willBeCalled('DELETE', $server, $json);

        $this->deleteJsonApi(self::URI, $json);
    }

    /**
     * Test subscriptions to internal app events.
     */
    public function testEvenSubscriptions(): void
    {
        $this->assertEmpty($this->getHandleRequestEvents());
        $this->assertEmpty($this->getHandleResponseEvents());
        $this->assertEmpty($this->getContainerCreatedEvents());
        $this->assertEmpty($this->getContainerConfiguredEvents());

        $dummyHandler = function () {
        };
        $this->addOnHandleRequestEvent($dummyHandler);
        $this->addOnHandleResponseEvent($dummyHandler);
        $this->addOnContainerCreatedEvent($dummyHandler);
        $this->addOnContainerConfiguredEvent($dummyHandler);

        $this->assertCount(1, $this->getHandleRequestEvents());
        $this->assertCount(1, $this->getHandleResponseEvents());
        $this->assertCount(1, $this->getContainerCreatedEvents());
        $this->assertCount(1, $this->getContainerConfiguredEvents());

        $this->resetEventHandlers();
        $this->assertEmpty($this->getHandleRequestEvents());
        $this->assertEmpty($this->getHandleResponseEvents());
        $this->assertEmpty($this->getContainerCreatedEvents());
        $this->assertEmpty($this->getContainerConfiguredEvents());
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSapiArgs = null;

        $this->sapi = $this->sapiMock = Mockery::mock(Sapi::class);
        $this->app  = $this->appMock = Mockery::mock(ApplicationInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->createSapiArgs = null;

        $this->sapi = $this->sapiMock = null;
        $this->app  = $this->appMock = null;
    }

    /**
     * @inheritdoc
     */
    protected function createApplication(): ApplicationInterface
    {
        return $this->app;
    }

    /**
     * @inheritdoc
     */
    protected function createSapi(
        array $server = null,
        array $queryParams = null,
        array $parsedBody = null,
        array $cookies = null,
        array $files = null,
        $messageBody = 'php://input',
        string $protocolVersion = '1.1'
    ): Sapi {
        assert(
            $server || $queryParams || $parsedBody || $cookies || $files ||
            $messageBody || $protocolVersion || true
        );

        $this->assertEquals($this->createSapiArgs, func_get_args());

        return $this->sapi;
    }

    /**
     * @param string $method
     * @param array  $server
     * @param string $messageBody
     */
    private function willBeCalled($method, array $server = [], string $messageBody = 'php://input'): void
    {
        $server['HTTP_HOST']      = 'localhost';
        $server['REQUEST_URI']    = self::URI;
        $server['REQUEST_METHOD'] = $method;

        $this->createSapiArgs = [
            $server, [], [], [], [], $messageBody, '1.1'
        ];

        /** @var Mock $responseMock */
        $responseMock = Mockery::mock(ResponseInterface::class);

        $this->appMock->shouldReceive('setSapi')->once()->withAnyArgs()->andReturnSelf();
        $this->appMock->shouldReceive('run')->once()->withNoArgs()->andReturnSelf();
        $this->sapiMock->shouldReceive('getResponse')->once()->withAnyArgs()->andReturn($responseMock);
    }
}
