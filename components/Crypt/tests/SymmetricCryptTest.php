<?php namespace Limoncello\Tests\Crypt;

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

use Limoncello\Crypt\CryptException;
use Limoncello\Crypt\SymmetricCrypt;
use Mockery;
use Mockery\Mock;

/**
 * @package Limoncello\Tests\Crypt
 */
class SymmetricCryptTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test encrypt & decrypt.
     */
    public function testEncryptDecrypt()
    {
        $crypt = new SymmetricCrypt('aes128', 'secret');

        $input = str_repeat('Hello world' . PHP_EOL, 1000);

        $encrypted = $crypt->asBase64()->withoutZeroPadding()->encrypt($input);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($input, $encrypted);

        $decrypted = $crypt->decrypt($encrypted);
        $this->assertNotEmpty($decrypted);
        $this->assertEquals($input, $decrypted);
    }

    /**
     * Test error handling.
     */
    public function testErrorOccurred()
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(SymmetricCrypt::class . "[openSslErrorString,openSslEncrypt]", ['aes128', 'secret']);
        $mock->shouldAllowMockingProtectedMethods()->makePartial();

        $errorMessage = 'Some error message';
        $mock->shouldReceive('openSslErrorString')->once()->withNoArgs()->andReturn(false);
        $mock->shouldReceive('openSslEncrypt')->once()->withAnyArgs()->andReturn(false);
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
     * Test compatibility with Open SSL CLI.
     */
    public function testCompatibilityWithOpenSslCli()
    {
        // That's original text that was encoded
        $input = 'Hello world';

        // These are algorithm and password the input was encoded with
        $method   = 'aes-128-cbc';
        $password = 'secret';

        // Input was encoded with OPENSSL_RAW_DATA option (so it's not in base64 as by default)
        $fileName  = __DIR__ . DIRECTORY_SEPARATOR . 'Data'  . DIRECTORY_SEPARATOR .  'sample.enc';

        // File was encoded with this vector (in hex format below)
        $hexVector = 'c3a9bb998b50f41caebf8385fb50869e';

        $hexPassword = bin2hex($password);
        $exec = "openssl enc -$method -d -in $fileName -K $hexPassword -iv $hexVector";

        $encoded = exec($exec, $output, $retVal);

        $this->assertEquals(0, $retVal);
        $this->assertEquals($input, $encoded);

        // check content of the file is identical to actual encryption result
        $crypt = (new SymmetricCrypt($method, $password))
            ->asRaw()
            ->withoutZeroPadding()
            ->resetInitializationVector(hex2bin($hexVector));
        $this->assertEquals(file_get_contents($fileName), $crypt->encrypt($input));
    }
}
