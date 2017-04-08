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
use Limoncello\Flute\Contracts\Adapters\FilterOperationsInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\Api\ModelsDataInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\I18n\TranslatorInterface;
use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\RelationshipStorageInterface;
use Limoncello\Flute\Contracts\Models\TagStorageInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
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
     * @return RelationshipStorageInterface
     */
    public function createRelationshipStorage(): RelationshipStorageInterface;

    /**
     * @param ModelSchemesInterface $modelSchemes
     *
     * @return ModelStorageInterface
     */
    public function createModelStorage(ModelSchemesInterface $modelSchemes): ModelStorageInterface;

    /**
     * @return TagStorageInterface
     */
    public function createTagStorage(): TagStorageInterface;

    /**
     * @param PaginatedDataInterface       $paginatedData
     * @param RelationshipStorageInterface $relationshipStorage
     *
     * @return ModelsDataInterface
     */
    public function createModelsData(
        PaginatedDataInterface $paginatedData,
        RelationshipStorageInterface $relationshipStorage = null
    ): ModelsDataInterface;

    /**
     * @return TranslatorInterface
     */
    public function createTranslator(): TranslatorInterface;

    /**
     * @param Connection                $connection
     * @param ModelSchemesInterface     $modelSchemes
     * @param FilterOperationsInterface $filterOperations
     * @param TranslatorInterface       $translator
     *
     * @return RepositoryInterface
     */
    public function createRepository(
        Connection $connection,
        ModelSchemesInterface $modelSchemes,
        FilterOperationsInterface $filterOperations,
        TranslatorInterface $translator
    ): RepositoryInterface;

    /**
     * @param array                 $schemes
     * @param ModelSchemesInterface $modelSchemes
     *
     * @return JsonSchemesInterface
     */
    public function createJsonSchemes(array $schemes, ModelSchemesInterface $modelSchemes): JsonSchemesInterface;

    /**
     * @param JsonSchemesInterface $schemes
     * @param EncoderOptions       $encoderOptions
     *
     * @return EncoderInterface
     */
    public function createEncoder(
        JsonSchemesInterface $schemes,
        EncoderOptions $encoderOptions = null
    ): EncoderInterface;

    /**
     * @param mixed $data
     *
     * @return PaginatedDataInterface
     */
    public function createPaginatedData($data): PaginatedDataInterface;
}
