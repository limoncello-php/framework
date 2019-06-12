<?php declare(strict_types=1);

namespace Limoncello\Tests\Common\Reflection;

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

use DateTimeInterface;
use Exception;
use Limoncello\Common\Reflection\CheckCallableTrait;
use Limoncello\Tests\Common\TestCase;
use ReflectionException;
use ReflectionParameter;

/**
 * @package Limoncello\Tests\Common
 */
class CheckCallableTraitTest extends TestCase
{
    use CheckCallableTrait;

    /**
     * Test check callable.
     *
     * @throws Exception
     */
    public function testCheckCallable(): void
    {
        $isNullableInt = function (ReflectionParameter $parameter): bool {
            $type = $parameter->getType();

            return $parameter->allowsNull() === true && $type !== null && (string)$type === 'int';
        };
        $parameters    = [
            'string',
            null,
            'array',
            DateTimeInterface::class,
            $isNullableInt
        ];

        $this->assertTrue($this->checkPublicStaticCallable([self::class, 'method1'], $parameters));

        $this->assertTrue($this->checkPublicStaticCallable(self::class . '::method1', $parameters, 'bool'));

        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method1'], $parameters, 'int'));

        // we method expects more parameters than we send it should fail
        $lessRequirements = array_slice($parameters, 0, count($parameters) - 1);
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method1'], $lessRequirements));

        // however if method do not want to use all the parameters we send it should pass
        $moreRequirements = array_merge($parameters, [$isNullableInt]);
        $this->assertTrue($this->checkPublicStaticCallable([self::class, 'method1'], $moreRequirements));

        // make sure it works for method without parameters and return type
        $this->assertTrue($this->checkPublicStaticCallable([self::class, 'method2']));

        // if we pass non public callable it should fail
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method3']));

        // if we pass non static callable it should fail
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method4']));

        // if we pass Closure it should fail
        $dummyClosure = function () {
        };
        $this->assertFalse($this->checkPublicStaticCallable($dummyClosure));

        // if parameters do not match it should fail
        $nonMatchingParameters    = [
            'int', // <-- this one do not match
            null,
            'array',
            DateTimeInterface::class,
            $isNullableInt
        ];
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method1'], $nonMatchingParameters));

        // if parameters do not match it should fail
        $nonMatchingParameters    = [
            'string',
            'int', // <-- this one do not match
            'array',
            DateTimeInterface::class,
            $isNullableInt
        ];
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method1'], $nonMatchingParameters));

        // if parameters do not match it should fail
        $nonMatchingParameters    = [
            'string',
            null,
            null, // <-- this one do not match
            DateTimeInterface::class,
            $isNullableInt
        ];
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method1'], $nonMatchingParameters));

        // if parameter closure return `false` it should fail
        $retFalseClosure = function (): bool {
            return false;
        };
        $nonMatchingParameters = [
            'string',
            null,
            'array',
            DateTimeInterface::class,
            $retFalseClosure
        ];
        $this->assertFalse($this->checkPublicStaticCallable([self::class, 'method1'], $nonMatchingParameters));
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @throws ReflectionException
     */
    public function testCheckCallableWithInvalidParameters(): void
    {
        // parameters should be described either by strings, nulls or with Closures.
        $parameters    = [
            false,
            null,
            null,
            null,
            null
        ];

        $this->checkPublicStaticCallable([self::class, 'method1'], $parameters);
    }

    /**
     * @param string            $param1
     * @param mixed             $param2
     * @param array             $param3
     * @param DateTimeInterface $param4
     * @param int|null          $param5
     *
     * @return bool
     */
    public static function method1(
        string $param1,
        $param2,
        array $param3,
        DateTimeInterface $param4,
        int $param5 = null
    ): bool {
        assert($param1 || $param2 || $param3 || $param4 || $param5);

        return true;
    }

    /**
     * Method for tests.
     */
    public static function method2(): void
    {
    }

    /**
     * Method for tests.
     */
    protected static function method3(): void
    {
    }

    /**
     * Method for tests.
     */
    public function method4(): void
    {
    }
}
