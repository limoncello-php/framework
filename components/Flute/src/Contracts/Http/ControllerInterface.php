<?php namespace Limoncello\Flute\Contracts\Http;

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

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Flute
 */
interface ControllerInterface
{
    /** Handler's method name (could be used in routing table) */
    const METHOD_INDEX = 'index';

    /** Handler's method name (could be used in routing table) */
    const METHOD_CREATE = 'create';

    /** Handler's method name (could be used in routing table) */
    const METHOD_READ = 'read';

    /** Handler's method name (could be used in routing table) */
    const METHOD_UPDATE = 'update';

    /** Handler's method name (could be used in routing table) */
    const METHOD_DELETE = 'delete';

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function index(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface;

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function create(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface;

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function read(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface;

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function update(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface;

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function delete(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface;

    /**
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    public static function parseInputOnCreate(
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array;

    /**
     * @param string|int             $index
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    public static function parseInputOnUpdate(
        $index,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array;
}
