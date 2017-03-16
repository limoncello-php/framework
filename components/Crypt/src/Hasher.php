<?php namespace Limoncello\Crypt;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Crypt\Contracts\HasherInterface;

/**
 * @package Limoncello\Crypt
 */
class Hasher implements HasherInterface
{
    /**
     * @var int
     */
    private $algorithm;

    /**
     * @var null|array
     */
    private $options;

    /**
     * @param int $algorithm
     * @param int $cost
     */
    public function __construct($algorithm = PASSWORD_DEFAULT, $cost = 10)
    {
        $this->algorithm = $algorithm;
        $this->options   = [
            'cost' => $cost,
        ];
    }

    /**
     * @inheritdoc
     */
    public function hash($password)
    {
        $hash = password_hash($password, $this->algorithm, $this->options);

        return $hash;
    }

    /**
     * @inheritdoc
     */
    public function verify($password, $hash)
    {
        $result = password_verify($password, $hash);

        return $result;
    }
}
