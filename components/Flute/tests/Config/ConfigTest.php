<?php namespace Limoncello\Tests\Flute\Config;

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

use Limoncello\Flute\Config\JsonApiConfig;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class ConfigTest extends TestCase
{
    public function testGetSetConfig()
    {
        $config1 = new JsonApiConfig();

        $map = ['whatever'];
        $config1->setModelSchemaMap($map);
        $config1->setShowVersion();
        $size = 123;
        $config1->setRelationshipPagingSize($size);
        $depth = 321;
        $config1->setJsonEncodeDepth($depth);
        $options = 213;
        $config1->setJsonEncodeOptions($options);
        $prefix = 'some-prefix';
        $config1->setUriPrefix($prefix);

        $data = $config1->getConfig();
        $config2 = (new JsonApiConfig())->setConfig($data);

        $this->assertEquals($map, $config2->getModelSchemaMap());
        $this->assertTrue($config2->isShowVersion());
        $this->assertEquals($size, $config2->getRelationshipPagingSize());
        $this->assertEquals($depth, $config2->getJsonEncodeDepth());
        $this->assertEquals($options, $config2->getJsonEncodeOptions());
        $this->assertEquals($prefix, $config2->getUriPrefix());

        $config2->setHideVersion();
        $this->assertFalse($config2->isShowVersion());
    }
}
