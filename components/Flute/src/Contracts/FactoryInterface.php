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
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Adapters\FilterOperationsInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
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
     * @param ModelSchemeInfoInterface $modelSchemes
     *
     * @return ModelStorageInterface
     */
    public function createModelStorage(ModelSchemeInfoInterface $modelSchemes): ModelStorageInterface;

    /**
     * @return TagStorageInterface
     */
    public function createTagStorage(): TagStorageInterface;

    /**
     * @param Connection                $connection
     * @param ModelSchemeInfoInterface  $modelSchemes
     * @param FilterOperationsInterface $filterOperations
     * @param FormatterInterface        $fluteMsgFormatter
     *
     * @return RepositoryInterface
     */
    public function createRepository(
        Connection $connection,
        ModelSchemeInfoInterface $modelSchemes,
        FilterOperationsInterface $filterOperations,
        FormatterInterface $fluteMsgFormatter
    ): RepositoryInterface;

    /**
     * @param array                    $schemes
     * @param ModelSchemeInfoInterface $modelSchemes
     *
     * @return JsonSchemesInterface
     */
    public function createJsonSchemes(array $schemes, ModelSchemeInfoInterface $modelSchemes): JsonSchemesInterface;

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

    /**
     * @param string $apiClass
     *
     * @return CrudInterface
     */
    public function createApi(string $apiClass);
}
