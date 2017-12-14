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
    /**
     * @var int
     */
    private $defaultPageSize;

    /**
     * @var int
     */
    private $maxPageSize;

    /**
     * @param int $defaultPageSize
     * @param int $maxPageSize
     */
    public function __construct(int $defaultPageSize, int $maxPageSize)
    {
        assert($defaultPageSize > 0 && $maxPageSize > 0);

        $this->defaultPageSize = $defaultPageSize;
        $this->maxPageSize     = $maxPageSize;
    }

    /**
     * @inheritdoc
     */
    public function getParameters(string $rootClass, string $class, string $path, string $relationshipName): array
    {
        // input parameters are ignored (same paging params for all)
        // feel free to change it in child classes
        assert($rootClass || $class || $path || $relationshipName);

        $offset = 0;

        return [$offset, $this->getDefaultPageSize()];
    }

    /**
     * @inheritdoc
     */
    public function parseParameters(?array $parameters): array
    {
        if ($parameters === null) {
            return [0, $this->getDefaultPageSize()];
        }

        $offset = $this->getValue(
            $parameters,
            static::PARAM_PAGING_OFFSET,
            0,
            0,
            PHP_INT_MAX
        );
        $size   = $this->getValue(
            $parameters,
            static::PARAM_PAGING_LIMIT,
            $this->getDefaultPageSize(),
            1,
            max($this->getDefaultPageSize(), $this->getMaxPageSize())
        );

        return [$offset, $size];
    }

    /**
     * @param array  $parameters
     * @param string $key
     * @param int    $default
     * @param int    $min
     * @param int    $max
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
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

    /**
     * @return int
     */
    public function getDefaultPageSize(): int
    {
        return $this->defaultPageSize;
    }

    /**
     * @return int
     */
    public function getMaxPageSize(): int
    {
        return $this->maxPageSize;
    }
}
