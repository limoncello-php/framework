<?php declare(strict_types=1);

namespace Limoncello\Flute\Types;

/**
 * @package Limoncello\Flute
 */
class MultiLineStringType extends \Brick\Geo\Doctrine\Types\MultiLineStringType
{
    /** Type name */
    const NAME = 'limoncelloMultiLineString';
}
