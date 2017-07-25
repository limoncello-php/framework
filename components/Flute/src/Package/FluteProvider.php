<?php namespace Limoncello\Flute\Package;

use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface;
use Limoncello\Contracts\Provider\ProvidesMessageResourcesInterface;
use Limoncello\Flute\L10n\Messages;
use Limoncello\Flute\Resources\Messages\En\Generic;
use Limoncello\Flute\Resources\Messages\En\Validation;
use Limoncello\Flute\Validation\Validator;

/**
 * @package Limoncello\Flute
 */
class FluteProvider implements ProvidesContainerConfiguratorsInterface, ProvidesMessageResourcesInterface
{
    /**
     * Namespace for string resources.
     */
    const GENERIC_NAMESPACE = Messages::RESOURCES_NAMESPACE;

    /**
     * Namespace for string resources.
     */
    const VALIDATION_NAMESPACE = Validator::RESOURCES_NAMESPACE;

    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            FluteContainerConfigurator::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getMessageDescriptions(): array
    {
        return [
            ['en', static::GENERIC_NAMESPACE, Generic::class],
            ['en', static::VALIDATION_NAMESPACE, Validation::class],
        ];
    }
}
