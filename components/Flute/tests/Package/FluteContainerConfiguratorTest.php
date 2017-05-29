<?php namespace Limoncello\Tests\Flute\Package;

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

use Doctrine\DBAL\Connection;
use Limoncello\Container\Container;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Exceptions\ExceptionHandlerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Adapters\FilterOperationsInterface;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\I18n\TranslatorInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Package\FluteContainerConfigurator;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Tests\Flute\Data\Package\Flute;
use Limoncello\Tests\Flute\Data\Package\SettingsProvider;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\Contracts\TranslatorInterface as ValidationTranslatorInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class FluteContainerConfiguratorTest extends TestCase
{
    /**
     * Test configurator.
     */
    public function testProvider()
    {
        $container = new Container();

        $container[SettingsProviderInterface::class] = new SettingsProvider([
            FluteSettings::class => (new Flute($this->getSchemeMap()))->get(),
        ]);
        $container[ModelSchemeInfoInterface::class] = $this->getModelSchemes();
        $container[Connection::class] = $this->createConnection();

        FluteContainerConfigurator::configureContainer($container);
        FluteContainerConfigurator::configureExceptionHandler($container);

        $this->assertNotNull($container->get(FactoryInterface::class));
        $this->assertNotNull($container->get(QueryParametersParserInterface::class));
        $this->assertNotNull($container->get(TranslatorInterface::class));
        $this->assertNotNull($container->get(FilterOperationsInterface::class));
        $this->assertNotNull($container->get(ValidationTranslatorInterface::class));
        $this->assertNotNull($container->get(ExceptionHandlerInterface::class));
        $this->assertNotNull($container->get(JsonSchemesInterface::class));
        $this->assertNotNull($container->get(EncoderInterface::class));
        $this->assertNotNull($container->get(RepositoryInterface::class));
        $this->assertNotNull($container->get(PaginationStrategyInterface::class));
    }
}
