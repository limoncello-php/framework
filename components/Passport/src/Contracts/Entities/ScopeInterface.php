<?php namespace Limoncello\Passport\Contracts\Entities;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use DateTimeInterface;

/**
 * @package Limoncello\Passport
 */
interface ScopeInterface
{
    /**
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * @param string $identifier
     *
     * @return ScopeInterface
     */
    public function setIdentifier(string $identifier): ScopeInterface;

    /**
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * @param string|null $description
     *
     * @return ScopeInterface
     */
    public function setDescription(string $description = null): ScopeInterface;

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt(): ?DateTimeInterface;

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return ScopeInterface
     */
    public function setCreatedAt(DateTimeInterface $createdAt): ScopeInterface;

    /**
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt(): ?DateTimeInterface;

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return ScopeInterface
     */
    public function setUpdatedAt(DateTimeInterface $createdAt): ScopeInterface;
}
