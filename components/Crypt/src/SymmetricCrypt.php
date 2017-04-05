<?php namespace Limoncello\Crypt;

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

use Limoncello\Crypt\Contracts\DecryptInterface;
use Limoncello\Crypt\Contracts\EncryptInterface;
use Limoncello\Crypt\Exceptions\CryptException;

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
    private $options = 0;

    /**
     * @var string
     */
    private $initializationVector = '';

    /**
     * @param string $method
     * @param string $password
     */
    public function __construct(string $method, string $password)
    {
        $this->setMethod($method)->setPassword($password)->asRaw();
    }

    /**
     * @inheritdoc
     */
    public function decrypt(string $data): string
    {
        $this->clearErrors();

        $vector = $this->getIV();
        if (empty($vector) === true) {
            $ivLength = $this->openSslIvLength($this->getMethod());
            $vector   = $this->readIV($data, $ivLength);
            $data     = $this->extractData($data, $ivLength);
        }

        $decrypted = $this->openSslDecrypt(
            $data,
            $this->getMethod(),
            $this->getPassword(),
            $this->getOptions(),
            $vector
        );

        return $decrypted;
    }

    /**
     * @inheritdoc
     */
    public function encrypt(string $data): string
    {
        $this->clearErrors();

        $isAddIvToOutput = false;
        $vector          = $this->getIV();
        if (empty($vector) === true) {
            $vector          = $this->generateIV();
            $isAddIvToOutput = true;
        }

        $encrypted = $this->openSslEncrypt(
            $data,
            $this->getMethod(),
            $this->getPassword(),
            $this->getOptions(),
            $vector
        );

        // Add initialization vector (IV) if it was generated otherwise it won't be possible to encrypt the message.
        //
        // Also @see http://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38a.pdf
        //
        // Appendix C: Generation of Initialization Vectors
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // ...
        // The IV need not be secret, so the IV, or information sufficient to determine the IV, may be
        // transmitted with the ciphertext.
        // ...
        $result = $isAddIvToOutput === false ? $encrypted : $vector . $encrypted;

        return $result;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return SymmetricCrypt
     */
    public function setMethod(string $method): SymmetricCrypt
    {
        assert(
            ($availableMethods = openssl_get_cipher_methods(true)) !== false &&
            in_array($method, $availableMethods) === true
        );

        $this->method = $method;

        return $this;
    }

    /**
     * @param string $password
     *
     * @return SymmetricCrypt
     */
    public function setPassword(string $password): SymmetricCrypt
    {
        assert(empty($password) === false);

        $this->password = $password;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return SymmetricCrypt
     */
    public function setIV(string $value): SymmetricCrypt
    {
        $this->initializationVector = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getIV(): string
    {
        return $this->initializationVector;
    }

    /**
     * @return SymmetricCrypt
     */
    public function withZeroPadding(): SymmetricCrypt
    {
        return $this->setOption(OPENSSL_ZERO_PADDING);
    }

    /**
     * @return SymmetricCrypt
     */
    public function withoutZeroPadding(): SymmetricCrypt
    {
        return $this->clearOption(OPENSSL_ZERO_PADDING);
    }

    /**
     * @return SymmetricCrypt
     */
    protected function asRaw(): SymmetricCrypt
    {
        return $this->setOption(OPENSSL_RAW_DATA);
    }

    /**
     * @return int
     */
    protected function getOptions(): int
    {
        return $this->options;
    }

    /**
     * @param int $options
     *
     * @return SymmetricCrypt
     */
    protected function setOptions(int $options): SymmetricCrypt
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return string
     */
    protected function generateIV(): string
    {
        $ivLength = $this->openSslIvLength($this->getMethod());
        $ivLength !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));

        $vector = openssl_random_pseudo_bytes($ivLength);

        return $vector;
    }

    /**
     * @param int $option
     *
     * @return SymmetricCrypt
     */
    protected function setOption(int $option): SymmetricCrypt
    {
        $this->setOptions($this->getOptions() | $option);

        return $this;
    }

    /**
     * @param int $option
     *
     * @return SymmetricCrypt
     */
    protected function clearOption(int $option): SymmetricCrypt
    {
        $this->setOptions($this->getOptions() & ~$option);

        return $this;
    }

    /**
     * @param string $data
     * @param int    $ivLength
     *
     * @return string
     */
    protected function readIV(string $data, int $ivLength): string
    {
        $vector = substr($data, 0, $ivLength);
        $isOk   = $vector !== false && strlen($vector) === $ivLength;

        $isOk === true ?: $this->throwException(new CryptException($this->getReadVectorErrorMessage()));

        return $vector;
    }

    /**
     * @param string $data
     * @param int    $ivLength
     *
     * @return string
     */
    protected function extractData(string $data, int $ivLength): string
    {
        $result = substr($data, $ivLength);

        $isOk = $result !== false && empty($result) === false;
        $isOk === true ?: $this->throwException(new CryptException($this->getExtractDataErrorMessage()));

        return $result;
    }

    /**
     * @param string $data
     * @param string $method
     * @param string $password
     * @param int    $options
     * @param string $initializationVector
     *
     * @return string
     */
    protected function openSslEncrypt(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector
    ): string {
        $encrypted = $this->openSslEncryptImpl($data, $method, $password, $options, $initializationVector);

        $message = $this->getErrorMessage();
        $encrypted !== false ?: $this->throwException(new CryptException($message));

        return $encrypted;
    }

    /**
     * @param string $data
     * @param string $method
     * @param string $password
     * @param int    $options
     * @param string $initializationVector
     *
     * @return string
     */
    protected function openSslDecrypt(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector
    ): string {
        $decrypted = $this->openSslDecryptImpl($data, $method, $password, $options, $initializationVector);

        $decrypted !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));

        return $decrypted;
    }

    /**
     * @param string $method
     *
     * @return int
     */
    protected function openSslIvLength(string $method): int
    {
        $ivLength = $this->openSslIvLengthImpl($method);

        $ivLength !== false ?: $this->throwException(new CryptException($this->getErrorMessage()));

        return $ivLength;
    }

    /**
     * @return string
     */
    protected function getReadVectorErrorMessage(): string
    {
        return 'Reading Initialization Vector (IV) failed';
    }

    /**
     * @return string
     */
    protected function getExtractDataErrorMessage(): string
    {
        return 'Extracting ciphertext from input data failed';
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
    protected function openSslEncryptImpl(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector
    ) {
        return openssl_encrypt($data, $method, $password, $options, $initializationVector);
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
    protected function openSslDecryptImpl(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector
    ) {
        return openssl_decrypt($data, $method, $password, $options, $initializationVector);
    }

    /**
     * We need this wrapper for testing purposes so we can mock system call to Open SSL.
     *
     * @param string $method
     *
     * @return int|false
     */
    protected function openSslIvLengthImpl(string $method)
    {
        return openssl_cipher_iv_length($method);
    }
}
