<?php namespace Limoncello\Flute\Contracts;

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
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\TagStorageInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * @package Limoncello\Flute
 */
interface FactoryInterface
{
    /**
     * @return ErrorCollection
     */
    public function createErrorCollection(): ErrorCollection;

    /**
     * @param Connection               $connection
     * @param string                   $modelClass
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @return ModelQueryBuilder
     */
    public function createModelQueryBuilder(
        Connection $connection,
        string $modelClass,
        ModelSchemaInfoInterface $modelSchemas
    ): ModelQueryBuilder;

    /**
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @return ModelStorageInterface
     */
    public function createModelStorage(ModelSchemaInfoInterface $modelSchemas): ModelStorageInterface;

    /**
     * @return TagStorageInterface
     */
    public function createTagStorage(): TagStorageInterface;

    /**
     * @param array                    $schemas
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @return JsonSchemasInterface
     */
    public function createJsonSchemas(array $schemas, ModelSchemaInfoInterface $modelSchemas): JsonSchemasInterface;

    /**
     * @param JsonSchemasInterface $schemas
     * @param EncoderOptions       $encoderOptions
     *
     * @return EncoderInterface
     */
    public function createEncoder(
        JsonSchemasInterface $schemas,
        EncoderOptions $encoderOptions = null
    ): EncoderInterface;

    /**
     * @param mixed $data
     *
     * @return PaginatedDataInterface
     */
    public function createPaginatedData($data): PaginatedDataInterface;

    /**
     * @param string $apiClass
     *
     * @return CrudInterface
     */
    public function createApi(string $apiClass): CrudInterface;
}
