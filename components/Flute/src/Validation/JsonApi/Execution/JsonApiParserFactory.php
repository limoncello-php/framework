<?php namespace Limoncello\Flute\Validation\JsonApi\Execution;

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

use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Flute\Validation\JsonApi\DataParser;
use Limoncello\Flute\Validation\JsonApi\QueryParser;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Execution\ContextStorage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JsonApiParserFactory implements JsonApiParserFactoryInterface
{
    use HasContainerTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createDataParser(string $rulesClass): JsonApiDataParserInterface
    {
        $serializedData = FluteSettings::getJsonDataSerializedRules($this->getFluteSettings());

        /** @var FormatterFactoryInterface $formatterFactory */
        $formatterFactory = $this->getContainer()->get(FormatterFactoryInterface::class);
        $validator = new DataParser(
            $rulesClass,
            JsonApiDataRulesSerializer::class,
            $serializedData,
            new ContextStorage(JsonApiQueryRulesSerializer::readBlocks($serializedData), $this->getContainer()),
            new JsonApiErrorCollection($formatterFactory->createFormatter(FluteSettings::VALIDATION_NAMESPACE)),
            $this->getContainer()->get(FormatterFactoryInterface::class)
        );

        return $validator;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createQueryParser(string $rulesClass): JsonApiQueryParserInterface
    {
        $serializedData = FluteSettings::getJsonQuerySerializedRules($this->getFluteSettings());

        /** @var FormatterFactoryInterface $formatterFactory */
        $formatterFactory = $this->getContainer()->get(FormatterFactoryInterface::class);
        $validator        = new QueryParser(
            $rulesClass,
            JsonApiQueryRulesSerializer::class,
            $serializedData,
            new ContextStorage(JsonApiQueryRulesSerializer::readBlocks($serializedData), $this->getContainer()),
            new CaptureAggregator(),
            new ErrorAggregator(),
            new JsonApiErrorCollection($formatterFactory->createFormatter(FluteSettings::VALIDATION_NAMESPACE)),
            $this->getContainer()->get(FormatterFactoryInterface::class)
        );

        return $validator;
    }

    /**
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getFluteSettings(): array
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->getContainer()->get(SettingsProviderInterface::class);
        $settings         = $settingsProvider->get(FluteSettings::class);

        return $settings;
    }
}
