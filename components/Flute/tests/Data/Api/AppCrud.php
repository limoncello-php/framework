<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Data\Api;

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

use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Api\Crud;
use Limoncello\Tests\Flute\Data\Models\Model;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Flute
 */
abstract class AppCrud extends Crud
{
    /** @var string|null Model class */
    const MODEL_CLASS = null;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, static::MODEL_CLASS);
    }

    /**
     * @inheritdoc
     */
    protected function filterAttributesOnCreate(?string $index, iterable $attributes): iterable
    {
        foreach (parent::filterAttributesOnCreate($index, $attributes) as $attribute => $value) {
            yield $attribute => $value;
        }

        yield Model::FIELD_CREATED_AT => $this->now();
    }

    /**
     * @inheritdoc
     */
    protected function filterAttributesOnUpdate(iterable $attributes): iterable
    {
        foreach (parent::filterAttributesOnUpdate($attributes) as $attribute => $value) {
            yield $attribute => $value;
        }

        yield Model::FIELD_UPDATED_AT => $this->now();
    }

    /**
     * @inheritdoc
     */
    protected function builderSaveRelationshipOnCreate($relationshipName, ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $builder = parent::builderSaveRelationshipOnCreate($relationshipName, $builder);

        $builder->setValue(Model::FIELD_CREATED_AT, $builder->createNamedParameter($this->now()));

        return $builder;
    }

    /**
     * @inheritdoc
     */
    protected function builderSaveRelationshipOnUpdate($relationshipName, ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $builder = parent::builderSaveRelationshipOnUpdate($relationshipName, $builder);

        $builder->setValue(Model::FIELD_CREATED_AT, $builder->createNamedParameter($this->now()));

        return $builder;
    }

    /**
     * @inheritdoc
     */
    protected function builderOnCreateInBelongsToManyRelationship(
        $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        $builder = parent::builderOnCreateInBelongsToManyRelationship($relationshipName, $builder);

        $builder->setValue(Model::FIELD_CREATED_AT, $builder->createNamedParameter($this->now()));

        return $builder;
    }

    /**
     * @return string
     */
    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
