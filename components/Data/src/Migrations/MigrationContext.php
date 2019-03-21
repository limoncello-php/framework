<?php declare (strict_types = 1);

namespace Limoncello\Data\Migrations;

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

use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Data\Contracts\MigrationContextInterface;

/**
 * @package Limoncello\Data
 */
class MigrationContext implements MigrationContextInterface
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var ModelSchemaInfoInterface
     */
    private $modelSchemas;

    /**
     * @param string                   $modelClass
     * @param ModelSchemaInfoInterface $modelSchemas
     */
    public function __construct(string $modelClass, ModelSchemaInfoInterface $modelSchemas)
    {
        $this->modelClass   = $modelClass;
        $this->modelSchemas = $modelSchemas;
    }

    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    public function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }
}
