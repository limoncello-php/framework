<?php declare(strict_types=1);

namespace Limoncello\Auth\Authorization\PolicyInformation;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\RequestInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\PolicyInformationPointInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @package Limoncello\Auth
 */
class PolicyInformationPoint implements PolicyInformationPointInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    private $contextDefinitions;

    /**
     * @param array $contextDefinitions
     */
    public function __construct(array $contextDefinitions)
    {
        $this->contextDefinitions = $contextDefinitions;
    }

    /**
     * @inheritdoc
     */
    public function createContext(RequestInterface $request): ContextInterface
    {
        return new Context($request, $this->contextDefinitions);
    }
}
