<?php namespace Limoncello\Flute\Package;

use Generator;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiRuleSetInterface;
use Limoncello\Flute\Validation\Execution\JsonApiRuleSerializer;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Flute
 */
abstract class FluteSettings implements SettingsInterface
{
    /**
     * By default it checks that all Schemes have unique resource types. That's a legit case
     * to have multiple Schemes for a same resource type however it's more likely that developer
     * just forgot to set a unique one. If you do need multiple Schemes for a resource feel free
     * to set it to `false`.
     *
     * @var bool
     */
    protected $requireUniqueTypes = true;

    /**
     * @return string
     */
    abstract protected function getSchemesPath(): string;

    /**
     * @return string
     */
    abstract protected function getRuleSetsPath(): string;

    /**
     * @param string $path
     * @param string $implementClassName
     *
     * @return Generator
     */
    abstract protected function selectClasses(string $path, string $implementClassName): Generator;

    /** Config key */
    const KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS = 0;

    /** Config key */
    const KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE = self::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS + 1;

    /** Config key */
    const KEY_MODEL_TO_SCHEME_MAP = self::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE + 1;

    /** Config key */
    const KEY_VALIDATION_RULE_SETS_DATA = self::KEY_MODEL_TO_SCHEME_MAP + 1;

    /** Config key */
    const KEY_DEFAULT_PAGING_SIZE = self::KEY_VALIDATION_RULE_SETS_DATA + 1;

    /** Config key */
    const KEY_MAX_PAGING_SIZE = self::KEY_DEFAULT_PAGING_SIZE + 1;

    /** Config key */
    const KEY_JSON_ENCODE_OPTIONS = self::KEY_MAX_PAGING_SIZE + 1;

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

        return [
            static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS => array_flip($this->getDoNotLogExceptionsList()),
            static::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE  => 500,
            static::KEY_MODEL_TO_SCHEME_MAP                 => $this->createModelToSchemeMap(),
            static::KEY_VALIDATION_RULE_SETS_DATA           => $this->createRulesSetData(),
            static::KEY_DEFAULT_PAGING_SIZE                 => 20,
            static::KEY_MAX_PAGING_SIZE                     => 100,
            static::KEY_JSON_ENCODE_OPTIONS                 => $jsonOptions,
            static::KEY_JSON_ENCODE_DEPTH                   => 512,
            static::KEY_IS_SHOW_VERSION                     => false,
            static::KEY_META                                => null,
            static::KEY_URI_PREFIX                          => null,
        ];
    }

    /**
     * @return string[]
     */
    protected function getDoNotLogExceptionsList(): array
    {
        return [
            JsonApiException::class,
        ];
    }

    /**
     * @return array
     */
    private function createModelToSchemeMap(): array
    {
        $map   = [];
        $types = [];
        foreach ($this->selectClasses($this->getSchemesPath(), SchemaInterface::class) as $schemeClass) {
            assert(
                is_string($schemeClass) &&
                class_exists($schemeClass) &&
                array_key_exists(SchemaInterface::class, class_implements($schemeClass))
            );
            /** @var SchemaInterface $schemeClass */
            $modelClass   = $schemeClass::MODEL;
            $resourceType = $schemeClass::TYPE;

            assert(is_string($modelClass) === true && empty($modelClass) === false);
            assert(is_string($resourceType) === true && empty($resourceType) === false);

            // By default it checks that all Schemes have unique resource types. That's a legit case
            // to have multiple Schemes for a same resource type however it's more likely that developer
            // just forgot to set a unique one. If you do need multiple Schemes for a resource feel free
            // to set to turn off this check.
            assert(
                $this->requireUniqueTypes === false || array_key_exists($resourceType, $types) === false,
                "Are you sure it's not an error to use resource type `$resourceType` more than once?"
            );
            $types[$resourceType] = true;

            $map[$modelClass] = $schemeClass;
        }

        return $map;
    }

    /**
     * @return array
     */
    private function createRulesSetData(): array
    {
        $serializer = new JsonApiRuleSerializer();
        foreach ($this->selectClasses($this->getRuleSetsPath(), JsonApiRuleSetInterface::class) as $setClass) {
            /** @var string $setName */
            $setName = $setClass;
            assert(
                is_string($setClass) &&
                class_exists($setClass) &&
                array_key_exists(JsonApiRuleSetInterface::class, class_implements($setClass))
            );
            /** @var JsonApiRuleSetInterface $setClass */
            $serializer->addResourceRules(
                $setName,
                $setClass::getIdRule(),
                $setClass::getTypeRule(),
                $setClass::getAttributeRules(),
                $setClass::getToOneRelationshipRules(),
                $setClass::getToManyRelationshipRules()
            );
        }

        $ruleSetsData = $serializer->getData();

        return $ruleSetsData;
    }
}
