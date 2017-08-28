<?php namespace Limoncello\Flute\Adapters;

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

use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;

/**
 * @package Limoncello\Flute
 */
class PaginationStrategy implements PaginationStrategyInterface
{
    /** Paging constant */
    const DEFAULT_LIMIT_SIZE = 20;

    /** Paging constant */
    const MAX_LIMIT_SIZE = 100;

    /**
     * @var int
     */
    private $defaultPageSize;

    /**
     * @param int $defaultPageSize
     */
    public function __construct(int $defaultPageSize = self::DEFAULT_LIMIT_SIZE)
    {
        $this->defaultPageSize = $defaultPageSize;
    }

    /**
     * @inheritdoc
     */
    public function getParameters(string $rootClass, string $class, string $path, string $relationshipName): array
    {
        // input parameters are ignored (same paging params for all)
        // feel free to change it in child classes
        $rootClass && $class && $path && $relationshipName ?: null;

        $offset = 0;

        return [$offset, $this->defaultPageSize + 1];
    }

    /**
     * @inheritdoc
     */
    public function parseParameters(?array $parameters): array
    {
        if ($parameters === null) {
            return [0, $this->defaultPageSize + 1];
        }

        $offset = $this->getValue(
            $parameters,
            static::PARAM_PAGING_SKIP,
            0,
            0,
            PHP_INT_MAX
        );
        $size   = $this->getValue(
            $parameters,
            static::PARAM_PAGING_SIZE,
            static::DEFAULT_LIMIT_SIZE,
            1,
            max($this->defaultPageSize, static::MAX_LIMIT_SIZE)
        );

        return [$offset, $size + 1];
    }

    /**
     * @param array  $parameters
     * @param string $key
     * @param int    $default
     * @param int    $min
     * @param int    $max
     *
     * @return int
     */
    private function getValue(array $parameters, string $key, int $default, int $min, int $max): int
    {
        $result = $default;
        if (isset($parameters[$key]) === true) {
            $value = $parameters[$key];
            if (is_numeric($value) === true) {
                $value = intval($value);
                if ($value < $min) {
                    $result = $min;
                } elseif ($value > $max) {
                    $result = $max;
                } else {
                    $result = $value;
                }
            }
        }

        return $result;
    }
}
