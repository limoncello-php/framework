<?php namespace Limoncello\Flute\Http\Cors;

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
use Interop\Container\ContainerInterface;
use Limoncello\Flute\Contracts\Http\Cors\CorsStorageInterface;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @package App
 */
class CorsMiddleware
{
    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     * @param ContainerInterface     $container
     *
     * @return ResponseInterface
     */
    public static function handle(ServerRequestInterface $request, Closure $next, ContainerInterface $container)
    {
        /** @var AnalyzerInterface $analyzer */
        $analyzer = $container->get(AnalyzerInterface::class);
        $analysis = $analyzer->analyze($request);

        switch ($analysis->getRequestType()) {
            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                // call next middleware handler
                return $next($request);

            case AnalysisResultInterface::TYPE_ACTUAL_REQUEST:
                // actual CORS request
                $corsHeaders = $analysis->getResponseHeaders();

                self::rememberCorsHeaders($container, $corsHeaders);

                /** @var ResponseInterface $response */
                $response = $next($request);

                // add CORS headers to Response $response
                foreach ($corsHeaders as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $response;

            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $corsHeaders = $analysis->getResponseHeaders();

                // return 200 HTTP with $corsHeaders
                return new EmptyResponse(200, $corsHeaders);

            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
                return static::getErrorNoHostHeaderResponse($analysis);

            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                return static::getErrorOriginNotAllowedResponse($analysis);

            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                return static::getErrorMethodNotSupportedResponse($analysis);

            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return static::getErrorHeadersNotSupportedResponse($analysis);

            default:
                return new EmptyResponse(400);
        }
    }

    /**
     * @param AnalysisResultInterface $analysis
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function getErrorNoHostHeaderResponse(
        /** @noinspection PhpUnusedParameterInspection */ AnalysisResultInterface $analysis
    ) {
        return new EmptyResponse(400);
    }

    /**
     * @param AnalysisResultInterface $analysis
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function getErrorOriginNotAllowedResponse(
        /** @noinspection PhpUnusedParameterInspection */ AnalysisResultInterface $analysis
    ) {
        return new EmptyResponse(400);
    }

    /**
     * @param AnalysisResultInterface $analysis
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function getErrorMethodNotSupportedResponse(
        /** @noinspection PhpUnusedParameterInspection */ AnalysisResultInterface $analysis
    ) {
        return new EmptyResponse(400);
    }

    /**
     * @param AnalysisResultInterface $analysis
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function getErrorHeadersNotSupportedResponse(
        /** @noinspection PhpUnusedParameterInspection */ AnalysisResultInterface $analysis
    ) {
        return new EmptyResponse(400);
    }

    /**
     * @param ContainerInterface $container
     * @param array              $headers
     *
     * @return void
     */
    private static function rememberCorsHeaders(ContainerInterface $container, array $headers)
    {
        if ($container->has(CorsStorageInterface::class) === true) {
            /** @var CorsStorageInterface $storage */
            $storage = $container->get(CorsStorageInterface::class);

            // storage must present in container so no check for `null` here.
            $storage->setHeaders($headers);
        }
    }
}
