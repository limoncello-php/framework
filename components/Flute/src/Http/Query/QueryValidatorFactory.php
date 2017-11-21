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

use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryValidatorInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 */
class QueryValidatorFactory implements QueryValidatorFactoryInterface
{
    use HasContainerTrait;

    /**
     * @var array
     */
    private $rulesData;

    /**
     * @var PaginationStrategyInterface
     */
    private $paginationStrategy;

    /**
     * @param ContainerInterface          $container
     * @param PaginationStrategyInterface $paginationStrategy
     * @param array                       $rulesData
     */
    public function __construct(
        ContainerInterface $container,
        PaginationStrategyInterface $paginationStrategy,
        array $rulesData
    ) {
        $this->setContainer($container)->setPaginationStrategy($paginationStrategy)->setRulesData($rulesData);
    }

    /**
     * @inheritdoc
     */
    public function createValidator(string $class): QueryValidatorInterface
    {
        return (new QueryValidator($this->getRulesData(), $this->getContainer(), $this->getPaginationStrategy()))
            ->withValidatedFilterFields($class);
    }

    /**
     * @param PaginationStrategyInterface $paginationStrategy
     *
     * @return self
     */
    private function setPaginationStrategy(PaginationStrategyInterface $paginationStrategy): self
    {
        $this->paginationStrategy = $paginationStrategy;

        return $this;
    }

    /**
     * @return PaginationStrategyInterface
     */
    private function getPaginationStrategy(): PaginationStrategyInterface
    {
        return $this->paginationStrategy;
    }

    /**
     * @return array
     */
    private function getRulesData(): array
    {
        return $this->rulesData;
    }

    /**
     * @param array $rulesData
     *
     * @return self
     */
    private function setRulesData(array $rulesData): self
    {
        $this->rulesData = $rulesData;

        return $this;
    }
}
