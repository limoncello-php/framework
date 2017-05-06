<?php namespace Limoncello\Flute\Package;

use Generator;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;

/**
 * @package Limoncello\Flute
 */
abstract class FluteSettings implements SettingsInterface
{
    /**
     * @return string
     */
    abstract protected function getSchemesPath(): string;

    /**
     * @param string $path
     * @param string $implementClassName
     *
     * @return Generator
     */
    abstract protected function selectClasses(string $path, string $implementClassName): Generator;

    /** Config key */
    const KEY_MODEL_TO_SCHEME_MAP = 0;

    /** Config key */
    const KEY_RELATIONSHIP_PAGING_SIZE = self::KEY_MODEL_TO_SCHEME_MAP + 1;

    /** Config key */
    const KEY_JSON_ENCODE_OPTIONS = self::KEY_RELATIONSHIP_PAGING_SIZE + 1;

    /** Config key */
    const KEY_JSON_ENCODE_DEPTH = self::KEY_JSON_ENCODE_OPTIONS + 1;

    /** Config key */
    const KEY_IS_SHOW_VERSION = self::KEY_JSON_ENCODE_DEPTH + 1;

    /** Config key */
    const KEY_META = self::KEY_IS_SHOW_VERSION + 1;

    /** Config key */
    const KEY_URI_PREFIX = self::KEY_META + 1;

    /** Config key */
    const KEY_LAST = self::KEY_URI_PREFIX + 1;

    /**
     * @return array
     */
    public function get(): array
    {
        $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;

        $map = [];
        foreach ($this->selectClasses($this->getSchemesPath(), SchemaInterface::class) as $schemeClass) {
            /** @var SchemaInterface $schemeClass */
            $map[$schemeClass::MODEL] =$schemeClass;
        }

        return [
            static::KEY_MODEL_TO_SCHEME_MAP      => $map,
            static::KEY_RELATIONSHIP_PAGING_SIZE => 20,
            static::KEY_JSON_ENCODE_OPTIONS      => $jsonOptions,
            static::KEY_JSON_ENCODE_DEPTH        => 512,
            static::KEY_IS_SHOW_VERSION          => false,
            static::KEY_META                     => null,
            static::KEY_URI_PREFIX               => null,
        ];
    }
}
