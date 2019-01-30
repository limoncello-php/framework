<?php namespace Limoncello\Flute;

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

use Doctrine\DBAL\Connection;
use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\TagStorageInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Limoncello\Flute\Encoder\Encoder;
use Limoncello\Flute\Models\ModelStorage;
use Limoncello\Flute\Models\PaginatedData;
use Limoncello\Flute\Models\TagStorage;
use Limoncello\Flute\Schema\JsonSchemas;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface as JsonApiFactoryInterface;
use Neomerx\JsonApi\Factories\Factory as JsonApiFactory;
use Neomerx\JsonApi\Schema\ErrorCollection;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Factory implements FactoryInterface
{
    use HasContainerTrait;

    /**
     * @var JsonApiFactoryInterface
     */
    private $jsonApiFactory = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @return JsonApiFactoryInterface
     */
    public function getJsonApiFactory()
    {
        if ($this->jsonApiFactory === null) {
            $this->jsonApiFactory = new JsonApiFactory();
        }

        return $this->jsonApiFactory;
    }

    /**
     * @inheritdoc
     */
    public function createModelQueryBuilder(
        Connection $connection,
        string $modelClass,
        ModelSchemaInfoInterface $modelSchemas
    ): ModelQueryBuilder {
        return new ModelQueryBuilder($connection, $modelClass, $modelSchemas);
    }

    /**
     * @param mixed $data
     *
     * @return PaginatedDataInterface
     */
    public function createPaginatedData($data): PaginatedDataInterface
    {
        return new PaginatedData($data);
    }

    /**
     * @inheritdoc
     */
    public function createErrorCollection(): ErrorCollection
    {
        return new ErrorCollection();
    }

    /**
     * @inheritdoc
     */
    public function createModelStorage(ModelSchemaInfoInterface $modelSchemas): ModelStorageInterface
    {
        return new ModelStorage($modelSchemas);
    }

    /**
     * @inheritdoc
     */
    public function createTagStorage(): TagStorageInterface
    {
        return new TagStorage();
    }

    /**
     * @inheritdoc
     */
    public function createJsonSchemas(
        array $modelToSchemaMap,
        array $typeToSchemaMap,
        ModelSchemaInfoInterface $modelSchemas
    ): JsonSchemasInterface {
        return new JsonSchemas($this->getJsonApiFactory(), $modelToSchemaMap, $typeToSchemaMap, $modelSchemas);
    }

    /**
     * @inheritdoc
     */
    public function createEncoder(JsonSchemasInterface $schemas): EncoderInterface
    {
        return new Encoder($this->getJsonApiFactory(), $schemas);
    }

    /**
     * @inheritdoc
     */
    public function createApi(string $apiClass): CrudInterface
    {
        $api = new $apiClass($this->getContainer());

        return $api;
    }
}
