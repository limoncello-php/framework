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

use Limoncello\Crypt\Contracts\DecryptInterface;
use Limoncello\Crypt\Contracts\EncryptInterface;

/**
 * @package Limoncello\Crypt
 */
class SymmetricCrypt extends BaseCrypt implements EncryptInterface, DecryptInterface
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $password;

    /**
     * Such as OPENSSL_RAW_DATA, OPENSSL_ZERO_PADDING.
     *
     * @var int
     */
    private $options;

    /**
     * @var string
     */
    private $initializationVector = null;

    /**
     * @param string $method
     * @param string $password
     */
    public function __construct($method, $password)
    {
        $this->setMethod($method)->setPassword($password)->asRaw();
    }

    /**
     * @inheritdoc
     */
    public function decrypt($data)
    {
        assert(is_string($data) === true);

        $this->clearErrors();

        $decrypted = $this->openSslDecrypt(
            $data,
            $this->getMethod(),
            $this->getPassword(),
            $this->getOptions(),
            $this->getInitializationVector()
        );

        $decrypted !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));

        return $decrypted;
    }

    /**
     * @inheritdoc
     */
    public function encrypt($data)
    {
        assert(is_string($data) === true);

        $this->clearErrors();

        $encrypted = $this->openSslEncrypt(
            $data,
            $this->getMethod(),
            $this->getPassword(),
            $this->getOptions(),
            $this->getInitializationVector()
        );

        $encrypted !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));

        return $encrypted;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        assert(is_string($method) === true && in_array($method, openssl_get_cipher_methods(true)) === true);

        $this->method = $method;

        return $this;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        assert(is_string($password) === true && empty($password) === false);

        $this->password = $password;

        return $this;
    }

    /**
     * @param null|string $value
     *
     * @return $this
     */
    public function resetInitializationVector($value = null)
    {
        assert(is_string($value) === true || $value === null);

        $this->initializationVector = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getInitializationVector()
    {
        if ($this->initializationVector === null) {
            $this->initializationVector = $this->generateInitializationVector();
        }

        return $this->initializationVector;
    }

    /**
     * @return $this
     */
    public function asRaw()
    {
        return $this->setOption(OPENSSL_RAW_DATA);
    }

    /**
     * @return $this
     */
    public function asBase64()
    {
        return $this->clearOption(OPENSSL_RAW_DATA);
    }

    /**
     * @return $this
     */
    public function withZeroPadding()
    {
        return $this->setOption(OPENSSL_ZERO_PADDING);
    }

    /**
     * @return $this
     */
    public function withoutZeroPadding()
    {
        return $this->clearOption(OPENSSL_ZERO_PADDING);
    }

    /**
     * @return int
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $options
     *
     * @return $this
     */
    protected function setOptions($options)
    {
        assert(is_int($options) === true);

        $this->options = $options;

        return $this;
    }

    /**
     * @return string
     */
    protected function generateInitializationVector()
    {
        $vector = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->getMethod()));

        return $vector;
    }

    /**
     * @param int $option
     *
     * @return $this
     */
    protected function setOption($option)
    {
        assert(is_int($option) === true);

        $this->setOptions($this->getOptions() | $option);

        return $this;
    }

    /**
     * @param int $option
     *
     * @return $this
     */
    protected function clearOption($option)
    {
        assert(is_int($option) === true);

        $this->setOptions($this->getOptions() & ~$option);

        return $this;
    }

    /**
     * We need this wrapper for testing purposes so we can mock system call to Open SSL.
     *
     * @param string $data
     * @param string $method
     * @param string $password
     * @param int    $options
     * @param string $initializationVector
     *
     * @return string|false
     */
    protected function openSslDecrypt($data, $method, $password, $options, $initializationVector)
    {
        return openssl_decrypt($data, $method, $password, $options, $initializationVector);
    }

    /**
     * We need this wrapper for testing purposes so we can mock system call to Open SSL.
     *
     * @param string $data
     * @param string $method
     * @param string $password
     * @param int    $options
     * @param string $initializationVector
     *
     * @return string|false
     */
    protected function openSslEncrypt($data, $method, $password, $options, $initializationVector)
    {
        return openssl_encrypt($data, $method, $password, $options, $initializationVector);
    }
}
