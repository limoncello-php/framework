<?php namespace Limoncello\Flute;

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

use Doctrine\DBAL\Connection;
use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\TagStorageInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Encoder\Encoder;
use Limoncello\Flute\Models\ModelStorage;
use Limoncello\Flute\Models\PaginatedData;
use Limoncello\Flute\Models\TagStorage;
use Limoncello\Flute\Schema\JsonSchemes;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface as JsonApiFactoryInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Factories\Factory as JsonApiFactory;
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
        ModelSchemeInfoInterface $modelSchemes
    ): ModelQueryBuilder {
        return new ModelQueryBuilder($connection, $modelClass, $modelSchemes);
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
    public function createModelStorage(ModelSchemeInfoInterface $modelSchemes): ModelStorageInterface
    {
        return new ModelStorage($modelSchemes);
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
    public function createJsonSchemes(array $schemes, ModelSchemeInfoInterface $modelSchemes): JsonSchemesInterface
    {
        return new JsonSchemes($this->getJsonApiFactory(), $schemes, $modelSchemes);
    }

    /**
     * @inheritdoc
     */
    public function createEncoder(
        JsonSchemesInterface $schemes,
        EncoderOptions $encoderOptions = null
    ): EncoderInterface {
        return new Encoder($this->getJsonApiFactory(), $schemes, $encoderOptions);
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
