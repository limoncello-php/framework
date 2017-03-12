<?php namespace Limoncello\Passport\Adaptors\MySql;

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

/**
 * @package Limoncello\Passport
 */
class Client extends \Limoncello\Passport\Entities\Client
{
    use DbDateFormatTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if ($this->hasDynamicProperty(static::FIELD_ID) === true) {
            $this->setScopeIdentifiers(
                empty($scope = $this->{static::FIELD_SCOPES}) === false ? explode(' ', $scope) : []
            );
            $this->setRedirectUriStrings(
                empty($uris = $this->{static::FIELD_REDIRECT_URIS}) === false ? explode(' ', $uris) : []
            );
        }
    }
}
