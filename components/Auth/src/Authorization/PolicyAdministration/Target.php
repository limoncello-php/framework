<?php namespace Limoncello\Auth\Authorization\PolicyAdministration;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\AnyOfInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;

/**
 * @package Limoncello\Auth
 */
class Target implements TargetInterface
{
    /**
     * @var null|string
     */
    private $name;

    /**
     * @var AnyOfInterface
     */
    private $anyOff;

    /**
     * @param AnyOfInterface $anyOff
     * @param string|null    $name
     */
    public function __construct(AnyOfInterface $anyOff, string $name = null)
    {
        $this->setAnyOff($anyOff)->setName($name);
    }

    /**
     * @inheritdoc
     */
    public function getAnyOf(): AnyOfInterface
    {
        return $this->anyOff;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     *
     * @return $this
     */
    public function setName(string $name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param AnyOfInterface $anyOff
     *
     * @return self
     */
    public function setAnyOff(AnyOfInterface $anyOff): self
    {
        $this->anyOff = $anyOff;

        return $this;
    }
}
