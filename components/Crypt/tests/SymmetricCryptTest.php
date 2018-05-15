<?php namespace Limoncello\Tests\Crypt;

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

use Exception;
use Limoncello\Crypt\Exceptions\CryptException;
use Limoncello\Crypt\SymmetricCrypt;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Crypt
 */
class SymmetricCryptTest extends TestCase
{
    /**
     * Test encrypt & decrypt.
     *
     * @throws Exception
     */
    public function testEncryptDecrypt(): void
    {
        $input = str_repeat('Hello world' . PHP_EOL, 1000);

        $crypt = new SymmetricCrypt('aes128', 'secret');
        $encrypted = $crypt->withoutZeroPadding()->disableAuthentication()->encrypt($input);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($input, $encrypted);

        $crypt = new SymmetricCrypt('aes128', 'secret');
        $decrypted = $crypt->decrypt($encrypted);
        $this->assertNotEmpty($decrypted);
        $this->assertEquals($input, $decrypted);
    }

    /**
     * Test encrypt & decrypt.
     *
     * @throws Exception
     */
    public function testAuthenticatedEncryptDecrypt(): void
    {
        $input = str_repeat('Hello world' . PHP_EOL, 1000);

        $crypt = new SymmetricCrypt('aes-256-gcm', 'secret');
        $encrypted = $crypt
            ->withoutZeroPadding()
            ->enableAuthentication()
            ->setAdditionalAuthenticationData('Extra data to be authenticated but not encrypted')
            ->setTagLength(16)
            ->encrypt($input);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($input, $encrypted);

        $crypt = new SymmetricCrypt('aes-256-gcm', 'secret');
        $decrypted = $crypt
            ->withoutZeroPadding()
            ->enableAuthentication()
            ->setAdditionalAuthenticationData('Extra data to be authenticated but not encrypted')
            ->setTagLength(16)
            ->decrypt($encrypted);
        $this->assertNotEmpty($decrypted);
        $this->assertEquals($input, $decrypted);
    }

    /**
     * Test error handling.
     *
     * @throws Exception
     */
    public function testSslErrorOccurred(): void
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(
            SymmetricCrypt::class . "[openSslErrorString,openSslEncryptImpl]",
            ['aes128', 'secret']
        );
        $mock->shouldAllowMockingProtectedMethods()->makePartial();

        $errorMessage = 'Some error message';
        $mock->shouldReceive('openSslEncryptImpl')->once()->withAnyArgs()->andReturn(false);

        // Not sure if it's a feature or bug but on first call it will return error message and then false.
        // Vice versa will not work (will always return error message).
        $mock->shouldReceive('openSslErrorString')->once()->withNoArgs()->andReturn(false);
        $mock->shouldReceive('openSslErrorString')->once()->withNoArgs()->andReturn($errorMessage);

        /** @var SymmetricCrypt $crypt */
        $crypt = $mock;

        // just for coverage
        $crypt->withZeroPadding()->withoutZeroPadding();

        $exception = null;
        try {
            $crypt->encrypt('whatever');
        } catch (CryptException $exception) {
        }

        $this->assertNotNull($exception);
        $this->assertEquals($errorMessage, $exception->getMessage());
    }

    /**
     * Test invalid input on decrypt.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptException
     */
    public function testInvalidInputOnDecrypt1(): void
    {
        $encrypted = 'too short';

        $crypt = new SymmetricCrypt('aes128', 'secret');
        $crypt->decrypt($encrypted);
    }

    /**
     * Test invalid input on decrypt.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptException
     */
    public function testInvalidInputOnDecrypt2(): void
    {
        // resembles IV but not enough characters for tag
        $encrypted = '1234567890123456_no_tag';

        $crypt = new SymmetricCrypt('aes-256-gcm', 'secret');
        $crypt->enableAuthentication()->decrypt($encrypted);
    }

    /**
     * Test invalid input on decrypt.
     *
     * @expectedException \Limoncello\Crypt\Exceptions\CryptException
     */
    public function testInvalidInputOnDecrypt3(): void
    {
        // input resembles IV (16 symbols) but no encrypted data
        $encrypted = '1234567890123456';

        $crypt = new SymmetricCrypt('aes128', 'secret');
        $crypt->decrypt($encrypted);
    }

    /**
     * Test compatibility with Open SSL CLI.
     *
     * @throws Exception
     */
    public function testCompatibilityWithOpenSslCli(): void
    {
        // That's original text that was encoded
        $input = 'Hello world';

        // These are algorithm and password the input was encoded with
        $method   = 'aes-128-cbc';
        $password = 'secret';

        // Input was encoded with OPENSSL_RAW_DATA option (so it's not in base64 as by default)
        $fileName = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Data', 'sample.enc']);

        // File was encoded with this vector (in hex format below)
        $hexVector = 'c3a9bb998b50f41caebf8385fb50869e';

        $hexPassword = bin2hex($password);
        $exec = "openssl enc -$method -d -in $fileName -K $hexPassword -iv $hexVector";

        $encoded = exec($exec, $output, $retVal);

        $this->assertEquals(0, $retVal);
        $this->assertEquals($input, $encoded);

        // check content of the file is identical to actual encryption result
        $crypt = (new SymmetricCrypt($method, $password))
            ->withoutZeroPadding()
            ->setIV(hex2bin($hexVector));
        $this->assertEquals(file_get_contents($fileName), $crypt->encrypt($input));
    }
}
