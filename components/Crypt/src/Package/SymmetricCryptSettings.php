<?php namespace Limoncello\Crypt\Package;

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

use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * @package Limoncello\Crypt
 */
class SymmetricCryptSettings implements SettingsInterface
{
    /** Default crypt method */
    const DEFAULT_METHOD = 'aes-256-ctr';

    /** Default Initialization Vector (IV) */
    const DEFAULT_IV = '';

    /**
     * Encryption method to be used. For a list of available methods on your machine see openssl_get_cipher_methods().
     *
     * @see http://php.net/manual/en/function.openssl-get-cipher-methods.php
     */
    const KEY_METHOD = 0;

    /** Settings key */
    const KEY_PASSWORD = self::KEY_METHOD + 1;

    /** Settings key */
    const KEY_IV = self::KEY_PASSWORD + 1;

    /**
     * @see http://php.net/manual/en/function.openssl-encrypt.php OPENSSL_ZERO_PADDING
     *
     * From @link http://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38a.pdf
     * Appendix A: Padding
     * ~~~~~~~~~~~~~~~~~~~
     * For the ECB, CBC, and CFB modes, the plaintext must be a sequence of one or more complete
     * data blocks (or, for CFB mode, data segments). In other words, for these three modes, the total
     * number of bits in the plaintext must be a positive multiple of the block (or segment) size.
     * If the data string to be encrypted does not initially satisfy this property, then the formatting of the
     * plaintext must entail an increase in the number of bits. A common way to achieve the necessary
     * increase is to append some extra bits, called padding, to the trailing end of the data string as the
     * last step in the formatting of the plaintext. An example of a padding method is to append a
     * single ‘1’ bit to the data string and then to pad the resulting string by as few ‘0’ bits, possibly
     * none, as are necessary to complete the final block (segment). Other methods may be used; in
     * general, the formatting of the plaintext is outside the scope of this recommendation.
     * For the above padding method, the padding bits can be removed unambiguously, provided the
     * receiver can determine that the message is indeed padded. One way to ensure that the receiver
     * does not mistakenly remove bits from an unpadded message is to require the sender to pad every
     * message, including messages in which the final block (segment) is already complete. For such
     * messages, an entire block (segment) of padding is appended. Alternatively, such messages can
     * be sent without padding if, for every message, the existence of padding can be reliably inferred,
     * e.g., from a message length indicator.
     */
    const KEY_USE_ZERO_PADDING = self::KEY_IV + 1;

    // Authenticated Encryption with Associated Data specific keys (since PHP 7.1)
    // @link http://php.net/manual/en/function.openssl-encrypt.php

    /** Settings key */
    const KEY_USE_AUTHENTICATION = self::KEY_USE_ZERO_PADDING + 1;

    /** Settings key */
    const KEY_TAG_LENGTH = self::KEY_USE_AUTHENTICATION + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_TAG_LENGTH;

    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
    {
        $defaults = $this->getSettings();

        $password = $defaults[static::KEY_PASSWORD] ?? null;
        assert(empty($password) === false, "Password cannot be empty.");

        return $defaults;
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_METHOD             => static::DEFAULT_METHOD,
            static::KEY_IV                 => static::DEFAULT_IV,
            static::KEY_USE_ZERO_PADDING   => false,
            static::KEY_USE_AUTHENTICATION => false,
            static::KEY_TAG_LENGTH         => 16,
        ];
    }
}
