<?php declare(strict_types=1);

namespace Limoncello\Core\Routing\Traits;

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

use LogicException;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use function array_merge;

/**
 * @package Limoncello\Core
 *
 * @method string getCallableToCacheMessage();
 */
trait HasConfiguratorsTrait
{
    /**
     * @var callable[]
     */
    private $configurators = [];

    /**
     * @param callable[] $configurators
     *
     * @return self
     */
    public function setConfigurators(array $configurators): self
    {
        foreach ($configurators as $configurator) {
            $isValid = $this->checkPublicStaticCallable($configurator, [LimoncelloContainerInterface::class]);
            if ($isValid === false) {
                throw new LogicException($this->getCallableToCacheMessage());
            }
        }

        $this->configurators = $configurators;

        return $this;
    }

    /**
     * @param callable[] $configurators
     *
     * @return self
     */
    public function addConfigurators(array $configurators): self
    {
        return $this->setConfigurators(array_merge($this->configurators, $configurators));
    }
}
