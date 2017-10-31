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

use Limoncello\Application\Cookies\CookieJar;
use Limoncello\Application\Exceptions\InvalidArgumentException;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Application
 */
class CookieJarTest extends TestCase
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

        $jar = new CookieJar($path, $domain, $isSecure, $isHttpOnly, $isRaw);

        $this->assertFalse($jar->has($name));

        $cookie = $jar->create($name)->setValue($value)->setExpiresAtUnixTime($expire);

        $this->assertEquals($name, $cookie->getName());
        $this->assertEquals($value, $cookie->getValue());
        $this->assertEquals($expire, $cookie->getExpiresAtUnixTime());
        $this->assertEquals($path, $cookie->getPath());
        $this->assertEquals($domain, $cookie->getDomain());
        $this->assertEquals($isSecure, $cookie->isSendOnlyOverSecureConnection());
        $this->assertEquals(!$isSecure, $cookie->isSendOverAnyConnection());
        $this->assertEquals($isHttpOnly, $cookie->isAccessibleOnlyThroughHttp());
        $this->assertEquals(!$isHttpOnly, $cookie->isAccessibleThroughHttpAndScripts());
        $this->assertEquals($isRaw, $cookie->isRaw());
        $this->assertEquals(!$isRaw, $cookie->isNotRaw());

        $this->assertTrue($jar->has($name));
        $this->assertSame($cookie, $jar->get($name));

        $counter = 0;
        foreach ($jar->getAll() as $item) {
            $counter++;
            $this->assertSame($cookie, $item);
        }
        $this->assertEquals(1, $counter);

        try {
            $jar->create($name);
        } catch (InvalidArgumentException $exception) {
        }
        $this->assertTrue(isset($exception));

        $jar->delete($name);
        $this->assertFalse($jar->has($name));
    }
}
