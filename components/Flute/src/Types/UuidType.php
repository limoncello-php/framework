<?php declare(strict_types=1);

namespace Limoncello\Flute\Types;

/**
 * @package Limoncello\Flute
 */
class UuidType extends \Ramsey\Uuid\Doctrine\UuidType
{
    /** Type name */
    const NAME = 'limoncelloUuid';
}
