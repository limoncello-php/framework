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

/**
 * @package Limoncello\Crypt
 */
abstract class BasePrivateKeyAsymmetricCrypt extends BaseAsymmetricCrypt
{
    /**
     * @param string $privateKeyOrPath Could be either private key or path to file prefixed with 'file://'.
     */
    public function __construct($privateKeyOrPath)
    {
        $this->clearErrors();

        $privateKey = openssl_pkey_get_private($privateKeyOrPath);
        $privateKey !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));

        $this->setKey($privateKey);
    }
}
