<?php declare(strict_types=1);

namespace Limoncello\Common\Reflection;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use function count;
use function explode;
use function is_array;
use function is_callable;
use function is_string;
use function strpos;

/**
 * @package Limoncello\Common
 */
trait CheckCallableTrait
{
    /**
     * Checks input callable is public static function with parameters and return type as specified.
     *
     * @param mixed       $callable
     * @param array       $parameters
     * @param string|null $returnType
     *
     * @return bool
     *
     * @throws ReflectionException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    protected function checkPublicStaticCallable(
        $callable,
        array $parameters = [],
        string $returnType = null
    ): bool {
        /** @var callable|array|string $callable */

        // first of all check input is callable (class, method) in form of array or string...
        if (is_callable($callable) && is_string($callable) === true && strpos($callable, '::') !== false) {
            list ($class, $method) = explode('::', $callable, 2);
        } elseif (is_callable($callable) &&
            is_array($callable) === true &&
            count($callable) === 2 &&
            is_string($class = $callable[0]) &&
            is_string($method = $callable[1])
        ) {
        } else {
            return false;
        }

        $reflectionMethod = new ReflectionMethod($class, $method);
        unset($class);
        unset($method);

        // now check it's public and static
        if ($reflectionMethod->isPublic() !== true || $reflectionMethod->isStatic() !== true) {
            return false;
        }

        // then check method parameters...
        $methodParams = $reflectionMethod->getParameters();
        if (count($methodParams) > count($parameters)) {
            return false;
        }

        $index          = 0;
        $areAllParamsOk = true;
        foreach ($parameters as $parameter) {
            if ($index >= count($methodParams)) {
                continue;
            }

            $isParamOk   = true;
            $methodParam = $methodParams[$index];
            if (is_string($parameter) === true) {
                $methodType = $methodParam->getType();
                if ($methodType === null || (string)$methodType !== $parameter) {
                    $isParamOk = false;
                }
            } elseif ($parameter === null) {
                if ($methodParam->getType() !== null) {
                    $isParamOk = false;
                }
            } elseif ($parameter instanceof Closure) {
                $isOk = $parameter($methodParam);
                if ($isOk !== true) {
                    $isParamOk = false;
                }
            } else {
                throw new InvalidArgumentException('Parameters should be either strings or Closures.');
            }

            if ($isParamOk !== true) {
                $areAllParamsOk = false;
                break;
            }

            $index++;
        }

        // ... and return type
        $isReturnTypeOk = true;
        if ($areAllParamsOk === true && $returnType !== null) {
            $methodRetType  = $reflectionMethod->getReturnType();
            $isReturnTypeOk = $methodRetType !== null && (string)$methodRetType === $returnType;
        }

        $isOk = $areAllParamsOk === true && $isReturnTypeOk === true;

        if ($isOk === false) {
            // you can put here a breakpoint and see the failed condition
            $callable || $areAllParamsOk || $isReturnTypeOk ?: null;
        }

        return $isOk;
    }
}
