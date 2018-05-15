<?php namespace Limoncello\Passport\Contracts\Repositories;

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

use Closure;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;

/**
 * @package Limoncello\Passport
 */
interface ScopeRepositoryInterface
{
    /**
     * @param Closure $closure
     *
     * @return void
     */
    public function inTransaction(Closure $closure): void;

    /**
     * @return ScopeInterface[]
     */
    public function index(): array;

    /**
     * @param ScopeInterface $scope
     *
     * @return ScopeInterface
     */
    public function create(ScopeInterface $scope): ScopeInterface;

    /**
     * @param string $identifier
     *
     * @return ScopeInterface
     */
    public function read(string $identifier): ScopeInterface;

    /**
     * @param ScopeInterface $scope
     *
     * @return void
     */
    public function update(ScopeInterface $scope): void;

    /**
     * @param string $identifier
     *
     * @return void
     */
    public function delete(string $identifier): void;
}
