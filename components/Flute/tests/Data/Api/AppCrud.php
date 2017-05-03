<?php namespace Limoncello\Tests\Flute\Data\Api;

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

use Doctrine\DBAL\Query\QueryBuilder;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Flute\Api\Crud;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Tests\Flute\Data\Models\Model;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests
 */
abstract class AppCrud extends Crud
{
    /** Model class */
    const MODEL_CLASS = null;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct(
            $this->getContainer()->get(FactoryInterface::class),
            static::MODEL_CLASS,
            $this->getContainer()->get(RepositoryInterface::class),
            $this->getContainer()->get(ModelSchemeInfoInterface::class),
            $this->getContainer()->get(PaginationStrategyInterface::class)
        );
    }

    /**
     * @return ContainerInterface|null
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    protected function filterAttributesOnCreate(string $modelClass, array $attributes, string $index = null): array
    {
        $allowedChanges = parent::filterAttributesOnCreate($modelClass, $attributes, $index);

        $allowedChanges[Model::FIELD_CREATED_AT] = date('Y-m-d H:i:s');

        return $allowedChanges;
    }

    /**
     * @inheritdoc
     */
    protected function filterAttributesOnUpdate(string $modelClass, array $attributes): array
    {
        $allowedChanges = parent::filterAttributesOnUpdate($modelClass, $attributes);

        $allowedChanges[Model::FIELD_UPDATED_AT] = date('Y-m-d H:i:s');

        return $allowedChanges;
    }

    /**
     * @inheritdoc
     */
    protected function builderSaveRelationshipOnCreate($relationshipName, QueryBuilder $builder): QueryBuilder
    {
        $builder = parent::builderSaveRelationshipOnCreate($relationshipName, $builder);

        $builder->setValue(Model::FIELD_CREATED_AT, $builder->createNamedParameter(date('Y-m-d H:i:s')));

        return $builder;
    }

    /**
     * @inheritdoc
     */
    protected function builderSaveRelationshipOnUpdate($relationshipName, QueryBuilder $builder): QueryBuilder
    {
        $builder = parent::builderSaveRelationshipOnUpdate($relationshipName, $builder);

        $builder->setValue(Model::FIELD_CREATED_AT, $builder->createNamedParameter(date('Y-m-d H:i:s')));

        return $builder;
    }
}
