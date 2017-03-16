<?php namespace Limoncello\Auth\Authorization\PolicyInformation;

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

use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\RequestInterface;
use Limoncello\Container\Container;

/**
 * @package Limoncello\Auth
 */
class Context extends Container implements ContextInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     * @param array            $contextDefinitions
     */
    public function __construct(RequestInterface $request, array $contextDefinitions = [])
    {
        parent::__construct($contextDefinitions);

        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return parent::has($key) === true || $this->getRequest()->has($key) === true;
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (parent::has($key) === true) {
            return parent::get($key);
        }

        return $this->getRequest()->get($key);
    }

    /**
     * @return RequestInterface
     */
    protected function getRequest()
    {
        return $this->request;
    }
}
