<?php declare(strict_types=1);

namespace Limoncello\Crypt;

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

use Limoncello\Crypt\Contracts\DecryptInterface;
use Limoncello\Crypt\Contracts\EncryptInterface;
use Limoncello\Crypt\Exceptions\CryptException;
use function assert;
use function in_array;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function openssl_get_cipher_methods;
use function openssl_random_pseudo_bytes;
use function strlen;
use function substr;

/**
 * @package Limoncello\Crypt
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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

    // Authenticated Encryption with Associated Data options (since PHP 7.1)

    /**
     * Use Authenticated Encryption with Associated Data (since PHP 7.1)
     *
     * @var bool
     */
    private $useAuthentication = false;

    /**
     * Additional authentication data.
     *
     * @var string
     */
    private $aad = '';

    /**
     * The length of the authentication tag. Its value can be between 4 and 16 for GCM (Galois/Counter Mode) mode.
     *
     * @var int
     */
    private $tagLength = 16;

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
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
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

        if ($this->isUseAuthentication() === true) {
            $tagLength = $this->getTagLength();
            $tag       = $this->readTag($data, $tagLength);
            $data      = $this->extractData($data, $tagLength);

            $decrypted = $this->openSslDecryptAuthenticated(
                $data,
                $this->getMethod(),
                $this->getPassword(),
                $this->getOptions(),
                $vector,
                $this->getAdditionalAuthenticationData(),
                $tag
            );
        } else {
            $decrypted = $this->openSslDecrypt(
                $data,
                $this->getMethod(),
                $this->getPassword(),
                $this->getOptions(),
                $vector
            );
        }

        return $decrypted;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
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

        if ($this->isUseAuthentication() === true) {
            $encrypted = $this->openSslEncryptAuthenticated(
                $data,
                $this->getMethod(),
                $this->getPassword(),
                $this->getOptions(),
                $vector,
                $this->getAdditionalAuthenticationData(),
                $tag,
                $this->getTagLength()
            );

            // Tag/Message authentication code should be sent with the encrypted message
            // otherwise it won't be possible to validate and encrypt the message.
            // Though https://tools.ietf.org/html/rfc5084 do not directly says it should
            // be passed along with the encrypted message adding it is one of the possible
            // solutions.
            $encrypted = $tag . $encrypted;
        } else {
            $encrypted = $this->openSslEncrypt(
                $data,
                $this->getMethod(),
                $this->getPassword(),
                $this->getOptions(),
                $vector
            );
        }

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
     * @return self
     */
    public function setMethod(string $method): self
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
     * @return self
     */
    public function setPassword(string $password): self
    {
        assert(empty($password) === false);

        $this->password = $password;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setIV(string $value): self
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
     * @return self
     */
    public function withZeroPadding(): self
    {
        return $this->setOption(OPENSSL_ZERO_PADDING);
    }

    /**
     * @return self
     */
    public function withoutZeroPadding(): self
    {
        return $this->clearOption(OPENSSL_ZERO_PADDING);
    }

    /**
     * @return bool
     */
    public function isUseAuthentication(): bool
    {
        return $this->useAuthentication;
    }

    /**
     * Authenticated Encryption with Associated Data available for certain methods since PHP 7.1.
     *
     * @return self
     */
    public function enableAuthentication(): self
    {
        $this->useAuthentication = true;

        return $this;
    }

    /**
     * Authenticated Encryption with Associated Data available for certain methods since PHP 7.1.
     *
     * @return self
     */
    public function disableAuthentication(): self
    {
        $this->useAuthentication = false;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalAuthenticationData(): string
    {
        return $this->aad;
    }

    /**
     * @param string $data
     *
     * @return self
     */
    public function setAdditionalAuthenticationData(string $data): self
    {
        $this->aad = $data;

        return $this;
    }

    /**
     * @return int
     */
    public function getTagLength(): int
    {
        return $this->tagLength;
    }

    /**
     * @param int $length
     *
     * @return self
     */
    public function setTagLength(int $length): self
    {
        assert($this->isTagLengthMightBeValid($length));

        $this->tagLength = $length;

        return $this;
    }

    /**
     * @return self
     */
    protected function asRaw(): self
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
     * @return self
     */
    protected function setOptions(int $options): self
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
     * @return self
     */
    protected function setOption(int $option): self
    {
        $this->setOptions($this->getOptions() | $option);

        return $this;
    }

    /**
     * @param int $option
     *
     * @return self
     */
    protected function clearOption(int $option): self
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
     * @param int    $tagLength
     *
     * @return string
     */
    protected function readTag(string $data, int $tagLength): string
    {
        $tag  = substr($data, 0, $tagLength);
        $isOk = $tag !== false && strlen($tag) === $tagLength;

        $isOk === true ?: $this->throwException(new CryptException($this->getReadTagErrorMessage()));

        return $tag;
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

    /** @noinspection PhpTooManyParametersInspection
     * @param string       $data
     * @param string       $method
     * @param string       $password
     * @param int          $options
     * @param string       $initializationVector
     * @param string       $aad
     * @param string|null &$tag
     * @param int          $tagLength
     *
     * @return string
     */
    protected function openSslEncryptAuthenticated(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector,
        string $aad,
        string &$tag = null,
        int $tagLength = 16
    ): string {
        $encrypted = $this->openSslEncryptAuthenticatedImpl(
            $data,
            $method,
            $password,
            $options,
            $initializationVector,
            $aad,
            $tag,
            $tagLength
        );

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
     * @param string $aad
     * @param string $tag
     *
     * @return string
     */
    protected function openSslDecryptAuthenticated(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector,
        string $aad,
        string $tag
    ): string {
        $decrypted = $this
            ->openSslDecryptAuthenticatedImpl($data, $method, $password, $options, $initializationVector, $aad, $tag);

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
    protected function getReadTagErrorMessage(): string
    {
        return 'Reading Authenticated Encryption Tag failed';
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

    /** @noinspection PhpTooManyParametersInspection
     * We need this wrapper for testing purposes so we can mock system call to Open SSL.
     *
     * @param string       $data
     * @param string       $method
     * @param string       $password
     * @param int          $options
     * @param string       $initializationVector
     * @param string       $aad
     * @param string|null &$tag
     * @param int          $tagLength
     *
     * @return false|string
     */
    protected function openSslEncryptAuthenticatedImpl(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector,
        string $aad,
        string &$tag = null,
        int $tagLength = 16
    ) {
        assert(PHP_VERSION_ID >= 70100);
        assert($this->isTagLengthMightBeValid($tagLength));

        $result = openssl_encrypt($data, $method, $password, $options, $initializationVector, $tag, $aad, $tagLength);

        return $result;
    }

    /**
     * We need this wrapper for testing purposes so we can mock system call to Open SSL.
     *
     * @param string $data
     * @param string $method
     * @param string $password
     * @param int    $options
     * @param string $initializationVector
     * @param string $aad
     * @param string $tag
     *
     * @return false|string
     */
    protected function openSslDecryptAuthenticatedImpl(
        string $data,
        string $method,
        string $password,
        int $options,
        string $initializationVector,
        string $aad,
        string $tag
    ) {
        assert(PHP_VERSION_ID >= 70100);

        return openssl_decrypt($data, $method, $password, $options, $initializationVector, $tag, $aad);
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

    /**
     * @param int $length
     *
     * @return bool
     */
    private function isTagLengthMightBeValid(int $length): bool
    {
        // @link http://php.net/manual/en/function.openssl-encrypt.php

        return 4 <= $length && $length <= 16;
    }
}
