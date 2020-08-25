<?php declare(strict_types=1);

namespace Limoncello\Tests\Validation\Rules;

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

use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function assert;

/**
 * @package Sample
 */
class DbRule extends ExecuteRule
{
    /**
     * @param mixed            $value
     * @param ContextInterface $context
     * @param null             $primaryKeyValue
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        // it emulates it takes connection from context's container and work with database.
        $pdo = $context->getContainer()->get(PDO::class);
        assert($pdo !== null);

        return static::createSuccessReply($value);
    }
}
