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

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Serializable;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FilterParameterCollection implements IteratorAggregate, ArrayAccess, Serializable, Countable
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @var bool
     */
    private $isJoinWithAND = true;

    /**
     * @return boolean
     */
    public function isWithAnd()
    {
        return $this->isJoinWithAND;
    }

    /**
     * @return bool
     */
    public function isWithOr(): bool
    {
        return $this->isWithAnd() === false;
    }

    /**
     * @return self
     */
    public function withAnd(): self
    {
        $this->isJoinWithAND = true;

        return $this;
    }

    /**
     * @return self
     */
    public function withOr(): self
    {
        $this->isJoinWithAND = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->items);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->items = unserialize($serialized);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * @inheritdoc
     *
     * @return FilterParameterInterface
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $offset === null ? $this->add($value) : $this->items[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * @return FilterParameterInterface[]
     */
    public function getArrayCopy(): array
    {
        return $this->items;
    }

    /**
     * @param FilterParameterInterface $parameter
     *
     * @return self
     */
    public function add(FilterParameterInterface $parameter): self
    {
        $this->items[] = $parameter;

        return $this;
    }
}
