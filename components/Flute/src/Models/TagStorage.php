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

use Limoncello\Flute\Contracts\Models\TagStorageInterface;

/**
 * @package Limoncello\Models
 */
class TagStorage implements TagStorageInterface
{
    private $tags = [];

    /**
     * @inheritdoc
     */
    public function register($item, $tag)
    {
        $uniqueId = spl_object_hash($item);

        $this->tags[$tag][$uniqueId] = $item;
    }

    /**
     * @inheritdoc
     */
    public function registerArray($item, array $tags)
    {
        $uniqueId = spl_object_hash($item);

        foreach ($tags as $tag) {
            $this->tags[$tag][$uniqueId] = $item;
        }
    }

    /**
     * @inheritdoc
     */
    public function get($tag)
    {
        $result = array_key_exists($tag, $this->tags) === true ? $this->tags[$tag] : [];

        return $result;
    }
}
