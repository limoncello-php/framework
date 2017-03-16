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

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Limoncello\Flute\Contracts\Adapters\FilterOperationsInterface;
use Limoncello\Flute\Contracts\I18n\TranslatorInterface as T;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FilterOperations implements FilterOperationsInterface
{
    /**
     * @var T
     */
    private $translator;

    /**
     * @var string|null
     */
    private $errMsgInvalidParam = null;

    /**
     * FilterOperations constructor.
     *
     * @param T $translator
     */
    public function __construct(T $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function applyEquals(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'eq', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyNotEquals(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'neq', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyGreaterThan(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'gt', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyGreaterOrEquals(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'gte', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyLessThan(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'lt', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyLessOrEquals(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'lte', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyLike(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'like', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyNotLike(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $params
    ) {
        $this->applyComparisonMethod($builder, $link, $errors, $table, $column, 'notLike', $params);
    }

    /**
     * @inheritdoc
     */
    public function applyIn(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        array $values
    ) {
        if ($this->isArrayOfScalars($values) === false) {
            $this->addInvalidQueryParameterError($errors, $column);
            return;
        }

        $placeholders = null;
        foreach ($values as $value) {
            $placeholders[] = $builder->createNamedParameter((string)$value);
        }
        $placeholders === null ?:
            $link->add($builder->expr()->in($this->getTableColumn($table, $column), $placeholders));
    }

    /**
     * @inheritdoc
     */
    public function applyNotIn(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        array $values
    ) {
        if ($this->isArrayOfScalars($values) === false) {
            $this->addInvalidQueryParameterError($errors, $column);
            return;
        }

        $placeholders = null;
        foreach ($values as $value) {
            $placeholders[] = $builder->createNamedParameter((string)$value);
        }
        $placeholders === null ?:
            $link->add($builder->expr()->notIn($this->getTableColumn($table, $column), $placeholders));
    }

    /**
     * @inheritdoc
     */
    public function applyIsNull(QueryBuilder $builder, CompositeExpression $link, $table, $column)
    {
        $link->add($builder->expr()->isNull($this->getTableColumn($table, $column)));
    }

    /**
     * @inheritdoc
     */
    public function applyIsNotNull(QueryBuilder $builder, CompositeExpression $link, $table, $column)
    {
        $link->add($builder->expr()->isNotNull($this->getTableColumn($table, $column)));
    }

    /**
     * @param QueryBuilder        $builder
     * @param CompositeExpression $link
     * @param ErrorCollection     $errors
     * @param string              $table
     * @param string              $column
     * @param string              $method
     * @param string|array        $params
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function applyComparisonMethod(
        QueryBuilder $builder,
        CompositeExpression $link,
        ErrorCollection $errors,
        $table,
        $column,
        $method,
        $params
    ) {
        // params could be in form of 1 value or array of values

        if (is_array($params) === true) {
            foreach ($params as $param) {
                if (is_scalar($param) === true) {
                    $param = (string)$builder->createNamedParameter($param);
                    $link->add($builder->expr()->{$method}($this->getTableColumn($table, $column), $param));
                } else {
                    $this->addInvalidQueryParameterError($errors, $column);
                }
            }
        } elseif (is_scalar($params) === true) {
            $param = $builder->createNamedParameter((string)$params);
            $link->add($builder->expr()->{$method}($this->getTableColumn($table, $column), $param));
        } else {
            // parameter is neither array nor string/scalar
            $this->addInvalidQueryParameterError($errors, $column);
        }
    }

    /**
     * @return T
     */
    protected function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param ErrorCollection $errors
     * @param string          $name
     *
     * @return void
     */
    protected function addInvalidQueryParameterError(ErrorCollection $errors, $name)
    {
        $errors->addQueryParameterError($name, $this->getInvalidParameterErrorMessage());
    }

    /**
     * @return string
     */
    protected function getInvalidParameterErrorMessage()
    {
        if ($this->errMsgInvalidParam === null) {
            $this->errMsgInvalidParam = $this->getTranslator()->get(T::MSG_ERR_INVALID_PARAMETER);
        }

        return $this->errMsgInvalidParam;
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    private function isArrayOfScalars(array $input)
    {
        foreach ($input as $value) {
            if (is_scalar($value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @return string
     */
    private function getTableColumn($table, $column)
    {
        return "`$table`.`$column`";
    }
}
