<?php namespace Limoncello\Crypt;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Crypt\Contracts\EncryptInterface;
use Limoncello\Crypt\Exceptions\CryptException;

/**
 * @package Limoncello\Crypt
 */
class PublicKeyAsymmetricEncrypt extends BasePublicKeyAsymmetricCrypt implements EncryptInterface
{
    /**
     * @inheritdoc
     */
    public function encrypt(string $data): string
    {
        $result           = null;
        $encryptChunkSize = $this->getEncryptChunkSize();
        if ($encryptChunkSize !== null) {
            $key = $this->getKey();
            $this->clearErrors();
            foreach ($this->chunkString($data, $encryptChunkSize) as $chunk) {
                $retVal = openssl_public_encrypt($chunk, $encrypted, $key);
                $retVal === true ?: $this->throwException(new CryptException($this->getErrorMessage()));
                $result .= $encrypted;
            }
        }

        return $result;
    }
}
