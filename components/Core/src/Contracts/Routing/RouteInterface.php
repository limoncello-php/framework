<?php namespace Limoncello\Core\Contracts\Routing;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

/**
 * @package Limoncello\Core
 */
interface RouteInterface
{
    /** Parameter key */
    const PARAM_NAME = 'as';

    /** Parameter key */
    const PARAM_MIDDLEWARE_LIST = 'middleware_list';

    /** Parameter key */
    const PARAM_CONTAINER_CONFIGURATORS = 'container_configurators';

    /** Parameter key */
    const PARAM_REQUEST_FACTORY = 'request_factory';

    /** Parameter key */
    const PARAM_FACTORY_FROM_GROUP = 'use_factory_from_group';

    /**
     * @return GroupInterface
     */
    public function getGroup();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @return string
     */
    public function getUriPath();

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @return callable[]
     */
    public function getMiddleware();

    /**
     * @return callable
     */
    public function getHandler();

    /**
     * @return callable[]
     */
    public function getContainerConfigurators();

    /**
     * @return callable
     */
    public function getRequestFactory();
}
