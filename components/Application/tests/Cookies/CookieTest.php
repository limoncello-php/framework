<?php namespace Limoncello\Tests\Application\Cookies;

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

use DateInterval;
use DateTime;
use Limoncello\Application\Cookies\Cookie;
use Limoncello\Application\Exceptions\InvalidArgumentException;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class CookieTest extends TestCase
{
    /**
     * Test basic cookie properties.
     */
    public function testBasicCookieProperties()
    {
        $name       = 'name';
        $value      = 'value';
        $expire     = 123;
        $path       = '/path';
        $domain     = 'domain';
        $isSecure   = true;
        $isHttpOnly = true;
        $isRaw      = true;

        $cookie = new Cookie($name, $value, $expire, $path, $domain, $isSecure, $isHttpOnly, $isRaw);

        $this->assertEquals($name, $cookie->getName());
        $this->assertEquals($value, $cookie->getValue());
        $this->assertEquals($expire, $cookie->getExpiresAtUnixTime());
        $this->assertEquals($path, $cookie->getPath());
        $this->assertEquals($domain, $cookie->getDomain());
        $this->assertEquals($isSecure, $cookie->getSendOnlyOverSecureConnection());
        $this->assertEquals(!$isSecure, $cookie->getSendOverAnyConnection());
        $this->assertEquals($isHttpOnly, $cookie->getAccessibleOnlyThroughHttp());
        $this->assertEquals(!$isHttpOnly, $cookie->getAccessibleThroughHttpAndScripts());
        $this->assertEquals($isRaw, $cookie->getIsRaw());
        $this->assertEquals(!$isRaw, $cookie->getIsNotRaw());

        $isSecure ? $cookie->setSendOverAnyConnection() : $cookie->setSendOnlyOverSecureConnection();
        $this->assertEquals(!$isSecure, $cookie->getSendOnlyOverSecureConnection());
        $this->assertEquals($isSecure, $cookie->getSendOverAnyConnection());

        $isHttpOnly ? $cookie->setAccessibleThroughHttpAndScripts() : $cookie->setAccessibleOnlyThroughHttp();
        $this->assertEquals(!$isHttpOnly, $cookie->getAccessibleOnlyThroughHttp());
        $this->assertEquals($isHttpOnly, $cookie->getAccessibleThroughHttpAndScripts());

        $isRaw ? $cookie->setAsNotRaw() : $cookie->setAsRaw();
        $this->assertEquals(!$isRaw, $cookie->getIsRaw());
        $this->assertEquals($isRaw, $cookie->getIsNotRaw());

        $cookie->setExpiresInSeconds(10);
        $this->assertEquals(time() + 10, $cookie->getExpiresAtUnixTime());

        $in100seconds = (new DateTime())->add(new DateInterval('PT100S'));
        $cookie->setExpiresAtDataTime($in100seconds);
        $this->assertEquals(time() + 100, $cookie->getExpiresAtUnixTime());

        try {
            $cookie->setExpiresAtUnixTime(-1);
        } catch (InvalidArgumentException $exception) {
        }
        $this->assertTrue(isset($exception));
    }
}
