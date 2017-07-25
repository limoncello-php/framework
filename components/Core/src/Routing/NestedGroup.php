<?php namespace Limoncello\Core\Routing;

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

use Limoncello\Contracts\Routing\GroupInterface;

/**
 * @package Limoncello\Core
 */
class NestedGroup extends BaseGroup
{
    /**
     * @param GroupInterface $parent
     */
    public function __construct(GroupInterface $parent)
    {
        $this->setParentGroup($parent);
    }

    /**
     * @inheritdoc
     */
    public function parentGroup()
    {
        return parent::parentGroup();
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        $parentGroupName = $this->parentGroup()->getName();
        $selfName        = parent::getName();
        $result          = $parentGroupName !== null || $selfName !== null ? $parentGroupName . $selfName : null;

        return $result;
    }

    /**
     * @return BaseGroup
     */
    protected function createGroup(): BaseGroup
    {
        $group = (new static($this))->setHasTrailSlash($this->hasTrailSlash());

        return $group;
    }
}
