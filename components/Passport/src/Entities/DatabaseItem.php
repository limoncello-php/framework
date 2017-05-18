<?php namespace Limoncello\Passport\Entities;

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

use DateTimeImmutable;
use DateTimeInterface;

/**
 * @package Limoncello\Passport
 */
abstract class DatabaseItem
{
    /**
     * @return string
     */
    abstract protected function getDbDateFormat(): string;

    /** Field name */
    const FIELD_CREATED_AT = 'created_at';

    /** Field name */
    const FIELD_UPDATED_AT = 'updated_at';

    /**
     * @var DateTimeInterface|null
     */
    private $createdAtField;

    /**
     * @var DateTimeInterface|null
     */
    private $updatedAtField;

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        if ($this->createdAtField === null &&
            $this->hasDynamicProperty(static::FIELD_CREATED_AT) === true &&
            ($createdAt = $this->{static::FIELD_CREATED_AT}) !== null
        ) {
            $this->setCreatedAtImpl($this->parseDateTime($createdAt));
        }

        return $this->createdAtField;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        if ($this->updatedAtField === null &&
            $this->hasDynamicProperty(static::FIELD_UPDATED_AT) === true &&
            ($updatedAt = $this->{static::FIELD_UPDATED_AT}) !== null
        ) {
            $this->setUpdatedAtImpl($this->parseDateTime($updatedAt));
        }

        return $this->updatedAtField;
    }

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return DatabaseItem
     */
    protected function setCreatedAtImpl(DateTimeInterface $createdAt): DatabaseItem
    {
        $this->createdAtField = $createdAt;

        return $this;
    }

    /**
     * @param DateTimeInterface $updatedAt
     *
     * @return DatabaseItem
     */
    protected function setUpdatedAtImpl(DateTimeInterface $updatedAt): DatabaseItem
    {
        $this->updatedAtField = $updatedAt;

        return $this;
    }

    /**
     * @param string $createdAt
     *
     * @return DateTimeInterface
     */
    protected function parseDateTime(string $createdAt): DateTimeInterface
    {
        return DateTimeImmutable::createFromFormat($this->getDbDateFormat(), $createdAt);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function hasDynamicProperty(string $name): bool
    {
        return property_exists($this, $name);
    }
}
