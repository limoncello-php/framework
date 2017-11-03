<?php namespace Limoncello\Tests\Application\FormValidation;

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

use Generator;
use Limoncello\Application\Contracts\Validation\FormValidatorFactoryInterface;
use Limoncello\Application\Packages\FormValidation\FormValidationContainerConfigurator;
use Limoncello\Application\Packages\FormValidation\FormValidationSettings;
use Limoncello\Application\Packages\FormValidation\FormValidationSettings as C;
use Limoncello\Application\Packages\L10n\L10nContainerConfigurator;
use Limoncello\Container\Container;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Core\Reflection\ClassIsTrait;
use Limoncello\Tests\Application\Data\FormValidators\CommentCreate;
use Limoncello\Tests\Application\Data\Models\Comment;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Limoncello\Application\Packages\L10n\L10nSettings as S;

/**
 * @package Limoncello\Tests\Application
 */
class ValidatorTest extends TestCase
{
    /**
     * @return void
     */
    public function testValidator(): void
    {
        $container = $this->createContainer();

        /** @var FormValidatorFactoryInterface $factory */
        $this->assertNotNull($factory = $container->get(FormValidatorFactoryInterface::class));

        $this->assertNotNull($validator = $factory->createValidator(CommentCreate::class));

        $this->assertTrue($validator->validate([Comment::FIELD_TEXT => 'some text']));
        $this->assertFalse($validator->validate([Comment::FIELD_TEXT => false]));
        $this->assertEquals(
            [Comment::FIELD_TEXT => 'The value should be a string.'],
            $this->iterableToArray($validator->getMessages())
        );
    }

    /**
     * @return void
     *
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     */
    public function testInvalidInput(): void
    {
        $container = $this->createContainer();

        /** @var FormValidatorFactoryInterface $factory */
        $this->assertNotNull($factory = $container->get(FormValidatorFactoryInterface::class));

        $this->assertNotNull($validator = $factory->createValidator(CommentCreate::class));

        $validator->validate('not array');
    }

    /**
     * @return ContainerInterface
     */
    private function createContainer(): ContainerInterface
    {
        $container = new Container();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $provider->shouldReceive('get')->once()->with(C::class)->andReturn($this->getValidationSettings());
        $provider->shouldReceive('get')->once()->with(S::class)->andReturn($this->getL10nSettings());

        FormValidationContainerConfigurator::configureContainer($container);
        L10nContainerConfigurator::configureContainer($container);

        return $container;
    }

    /**
     * @return array
     */
    private function getValidationSettings(): array
    {
        $settings = new class extends FormValidationSettings
        {
            use ClassIsTrait;

            /**
             * @inheritdoc
             */
            protected function getSettings(): array
            {
                $validatorsFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'FormValidators']);

                return [
                        static::KEY_VALIDATORS_FOLDER  => $validatorsFolder,
                    ] + parent::getSettings();
            }
        };

        $result = $settings->get();

        return $result;
    }

    /**
     * @return array
     */
    private function getL10nSettings(): array
    {
        $settings = new class extends S
        {
            /**
             * @inheritdoc
             */
            protected function getSettings(): array
            {
                $localesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'L10n']);

                return [
                        static::KEY_LOCALES_FOLDER => $localesFolder,
                    ] + parent::getSettings();
            }
        };

        $result = $settings->get();

        return $result;
    }

    /**
     * @param iterable $iterable
     *
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof Generator ? iterator_to_array($iterable) : $iterable;
    }
}
