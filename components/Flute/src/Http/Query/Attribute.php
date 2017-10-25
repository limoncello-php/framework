<?php namespace Limoncello\Flute\Http\Query;

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

use Limoncello\Flute\Contracts\Http\Query\AttributeInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;

/**
 * @package Limoncello\Flute
 */
class Attribute implements AttributeInterface
{
    /**
     * @var string
     */
    private $nameInScheme;

    /**
     * @var string
     */
    private $nameInModel;

    /**
     * @var SchemaInterface
     */
    private $scheme;

    /**
     * @param string          $nameInScheme
     * @param SchemaInterface $scheme
     */
    public function __construct(string $nameInScheme, SchemaInterface $scheme)
    {
        $this->nameInScheme = $nameInScheme;
        $this->scheme       = $scheme;

        $this->nameInModel = null;
    }

    /**
     * @return string
     */
    public function getNameInScheme(): string
    {
        return $this->nameInScheme;
    }

    /**
     * @return SchemaInterface
     */
    public function getScheme(): SchemaInterface
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getNameInModel():string
    {
        if ($this->nameInModel === null) {
            $this->nameInModel = $this->getScheme()->getAttributeMapping($this->getNameInScheme());
        }

        return $this->nameInModel;
    }
}
