<?php declare(strict_types=1);

namespace Limoncello\Flute\Types;

/**
 * @package Limoncello\Flute
 */
class MultiPolygonType extends \Brick\Geo\Doctrine\Types\MultiPolygonType
{
    /** Type name */
    const NAME = 'limoncelloMultiPolygon';
}
