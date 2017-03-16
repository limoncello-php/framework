<?php namespace Limoncello\Flute\Models;

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

use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;

/**
 * @package Limoncello\Models
 */
class ModelStorage implements ModelStorageInterface
{
    /**
     * @var array
     */
    private $models = [];

    /**
     * @var ModelSchemesInterface
     */
    private $schemaStorage;

    /**
     * @param ModelSchemesInterface $schemaStorage
     */
    public function __construct(ModelSchemesInterface $schemaStorage)
    {
        $this->schemaStorage = $schemaStorage;
    }

    /**
     * @inheritdoc
     */
    public function register($model)
    {
        if ($model === null) {
            return null;
        }

        $class  = get_class($model);
        $pkName = $this->schemaStorage->getPrimaryKey($class);
        $index  = $model->{$pkName};

        if ($this->has($class, $index) === false) {
            $this->models[$class][$index] = $model;
            return $model;
        }

        return $this->models[$class][$index];
    }

    /**
     * @inheritdoc
     */
    public function has($class, $index)
    {
        $result = isset($this->models[$class][$index]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function get($class, $index)
    {
        $result = $this->models[$class][$index];

        return $result;
    }
}
