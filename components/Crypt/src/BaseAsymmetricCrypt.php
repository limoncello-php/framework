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

use Generator;

/**
 * @package Limoncello\Crypt
 */
abstract class BaseAsymmetricCrypt extends BaseCrypt
{
    /**
     * @var resource|null
     */
    private $key = null;

    /**
     * @var int|null
     */
    private $keyBytes = null;

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->closeKey();
    }

    /**
     * @return $this
     */
    public function closeKey()
    {
        if ($this->key !== null) {
            openssl_pkey_free($this->key);
            $this->key      = null;
            $this->keyBytes = null;
        }

        return $this;
    }

    /**
     * @return resource|null
     */
    protected function getKey()
    {
        return $this->key;
    }

    /**
     * @param resource $key
     *
     * @return $this
     */
    protected function setKey($key)
    {
        assert(is_resource($key) === true);

        $this->closeKey();
        $this->key = $key;

        return $this;
    }

    /**
     * @return int|null
     */
    protected function getKeyBytes()
    {
        if ($this->keyBytes === null && $this->getKey() !== null) {
            $this->clearErrors();
            $details = openssl_pkey_get_details($this->getKey());
            $details !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));
            $this->keyBytes = $details['bits'] / 8;
        }

        return $this->keyBytes;
    }

    /**
     * @return int|null
     */
    protected function getEncryptChunkSize()
    {
        $keyBytes = $this->getKeyBytes();

        // 11 is a kind of magic number related to padding.
        $result = $keyBytes === null ? null : $keyBytes - 11;

        return $result;
    }

    /**
     * @return int|null
     */
    protected function getDecryptChunkSize()
    {
        $keyBytes = $this->getKeyBytes();
        $result   = $keyBytes === null ? null : $keyBytes;

        return $result;
    }

    /**
     * @param string $value
     * @param int    $maxSize
     *
     * @return Generator
     */
    protected function chunkString($value, $maxSize)
    {
        $isValidInput = is_string($value) === true && is_int($maxSize) === true && $maxSize > 0;

        assert($isValidInput === true);

        if ($isValidInput === true) {
            $start  = 0;
            $length = strlen($value);
            if ($length === 0) {
                yield $value;
            }
            while ($start < $length) {
                yield substr($value, $start, $maxSize);
                $start += $maxSize;
            }
        }
    }
}
