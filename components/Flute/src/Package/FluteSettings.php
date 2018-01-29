<?php namespace Limoncello\Flute\Package;

use Generator;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\FormRuleSetInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiRuleSetInterface;
use Limoncello\Flute\Contracts\Validation\QueryRuleSetInterface;
use Limoncello\Flute\Validation\Form\Execution\AttributeRulesSerializer;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiRuleSerializer;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Flute
 */
abstract class FluteSettings implements SettingsInterface
{
    /**
     * Namespace for string resources.
     */
    const VALIDATION_NAMESPACE = 'Limoncello.Flute.Validation';

    /**
     * @param string $path
     * @param string $implementClassName
     *
     * @return Generator
     */
    abstract protected function selectClasses(string $path, string $implementClassName): Generator;

    /** Config key */
    const KEY_DO_NOT_LOG_EXCEPTIONS_LIST = 0;

    /** Config key */
    const KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS = self::KEY_DO_NOT_LOG_EXCEPTIONS_LIST + 1;

    /** Config key
     *
     * By default it checks that all Schemes have unique resource types. That's a legit case
     * to have multiple Schemes for a same resource type however it's more likely that developer
     * just forgot to set a unique one. If you do need multiple Schemes for a resource feel free
     * to set it to `false`.
     *
     * Default: true
     */
    const KEY_SCHEMES_REQUIRE_UNIQUE_TYPES = self::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS + 1;

    /** Config key */
    const KEY_SCHEMES_FOLDER = self::KEY_SCHEMES_REQUIRE_UNIQUE_TYPES + 1;

    /** Config key */
    const KEY_SCHEMES_FILE_MASK = self::KEY_SCHEMES_FOLDER + 1;

    /** Config key */
    const KEY_JSON_VALIDATORS_FOLDER = self::KEY_SCHEMES_FILE_MASK + 1;

    /** Config key */
    const KEY_JSON_VALIDATORS_FILE_MASK = self::KEY_JSON_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_FORM_VALIDATORS_FOLDER = self::KEY_JSON_VALIDATORS_FILE_MASK + 1;

    /** Config key */
    const KEY_FORM_VALIDATORS_FILE_MASK = self::KEY_FORM_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_QUERY_VALIDATORS_FOLDER = self::KEY_FORM_VALIDATORS_FILE_MASK + 1;

    /** Config key */
    const KEY_QUERY_VALIDATORS_FILE_MASK = self::KEY_QUERY_VALIDATORS_FOLDER + 1;

    /** Config key */
    const KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE = self::KEY_QUERY_VALIDATORS_FILE_MASK + 1;

    /** Config key */
    const KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER = self::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE + 1;

    /** Config key */
    const KEY_MODEL_TO_SCHEME_MAP = self::KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER + 1;

    /** Config key */
    const KEY_JSON_VALIDATION_RULE_SETS_DATA = self::KEY_MODEL_TO_SCHEME_MAP + 1;

    /** Config key */
    const KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA = self::KEY_JSON_VALIDATION_RULE_SETS_DATA + 1;

    /** Config key */
    const KEY_DEFAULT_PAGING_SIZE = self::KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA + 1;

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
    protected const KEY_LAST = self::KEY_URI_PREFIX + 1;

    /**
     * @param array $appConfig
     *
     * @return array
     */
    final public function get(array $appConfig): array
    {
        // suppress 'unused variable'
        assert(is_array($appConfig));

        $defaults = $this->getSettings();

        $schemesFolder    = $defaults[static::KEY_SCHEMES_FOLDER] ?? null;
        $schemesFileMask  = $defaults[static::KEY_SCHEMES_FILE_MASK] ?? null;
        $jsonValFolder    = $defaults[static::KEY_JSON_VALIDATORS_FOLDER] ?? null;
        $jsonValFileMask  = $defaults[static::KEY_JSON_VALIDATORS_FILE_MASK] ?? null;
        $formsValFolder   = $defaults[static::KEY_FORM_VALIDATORS_FOLDER] ?? null;
        $formsValFileMask = $defaults[static::KEY_FORM_VALIDATORS_FILE_MASK] ?? null;
        $queryValFolder   = $defaults[static::KEY_QUERY_VALIDATORS_FOLDER] ?? null;
        $queryValFileMask = $defaults[static::KEY_QUERY_VALIDATORS_FILE_MASK] ?? null;

        assert(
            $schemesFolder !== null && empty(glob($schemesFolder)) === false,
            "Invalid Schemes folder `$schemesFolder`."
        );
        assert(empty($schemesFileMask) === false, "Invalid Schemes file mask `$schemesFileMask`.");
        assert(
            $jsonValFolder !== null && empty(glob($jsonValFolder)) === false,
            "Invalid JSON Validators folder `$jsonValFolder`."
        );
        assert(empty($jsonValFileMask) === false, "Invalid JSON Validators file mask `$jsonValFileMask`.");
        assert(
            $formsValFolder !== null && empty(glob($formsValFolder)) === false,
            "Invalid Forms Validators folder `$formsValFolder`."
        );
        assert(empty($formsValFileMask) === false, "Invalid Forms Validators file mask `$formsValFileMask`.");
        assert(
            $queryValFolder !== null && empty(glob($queryValFolder)) === false,
            "Invalid Query Validators folder `$queryValFolder`."
        );
        assert(empty($queryValFileMask) === false, "Invalid Query Validators file mask `$queryValFileMask`.");

        $schemesPath         = $schemesFolder . DIRECTORY_SEPARATOR . $schemesFileMask;
        $jsonValidatorsPath  = $jsonValFolder . DIRECTORY_SEPARATOR . $jsonValFileMask;
        $formsValidatorsPath = $formsValFolder . DIRECTORY_SEPARATOR . $formsValFileMask;
        $queryValidatorsPath = $queryValFolder . DIRECTORY_SEPARATOR . $queryValFileMask;

        $requireUniqueTypes = $defaults[static::KEY_SCHEMES_REQUIRE_UNIQUE_TYPES] ?? true;

        $doNotLogExceptions = $defaults[static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST] ?? [];
        unset($defaults[static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST]);

        return $defaults + [
                static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS => array_flip($doNotLogExceptions),

                static::KEY_MODEL_TO_SCHEME_MAP => $this->createModelToSchemeMap($schemesPath, $requireUniqueTypes),

                static::KEY_JSON_VALIDATION_RULE_SETS_DATA =>
                    $this->createJsonValidationRulesSetData($jsonValidatorsPath),

                static::KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA =>
                    $this->createValidationAttributeRulesSetData($formsValidatorsPath, $queryValidatorsPath),
            ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;

        return [
            static::KEY_SCHEMES_REQUIRE_UNIQUE_TYPES              => true,
            static::KEY_SCHEMES_FILE_MASK                         => '*.php',
            static::KEY_JSON_VALIDATORS_FILE_MASK                 => '*.php',
            static::KEY_FORM_VALIDATORS_FILE_MASK                 => '*.php',
            static::KEY_QUERY_VALIDATORS_FILE_MASK                => '*.php',
            static::KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER => null,
            static::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE        => 500,
            static::KEY_DEFAULT_PAGING_SIZE                       => 20,
            static::KEY_MAX_PAGING_SIZE                           => 100,
            static::KEY_JSON_ENCODE_OPTIONS                       => $jsonOptions,
            static::KEY_JSON_ENCODE_DEPTH                         => 512,
            static::KEY_IS_SHOW_VERSION                           => false,
            static::KEY_META                                      => null,
            static::KEY_URI_PREFIX                                => null,

            static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST => [
                JsonApiException::class,
            ],
        ];
    }

    /**
     * @param string $schemesPath
     * @param bool   $requireUniqueTypes
     *
     * @return array
     */
    private function createModelToSchemeMap(string $schemesPath, bool $requireUniqueTypes): array
    {
        $map   = [];
        $types = [];
        foreach ($this->selectClasses($schemesPath, SchemaInterface::class) as $schemeClass) {
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
                $requireUniqueTypes === false || array_key_exists($resourceType, $types) === false,
                "Are you sure it's not an error to use resource type `$resourceType` more than once?"
            );
            $types[$resourceType] = true;

            $map[$modelClass] = $schemeClass;
        }

        return $map;
    }

    /**
     * @param string $validatorsPath
     *
     * @return array
     */
    private function createJsonValidationRulesSetData(string $validatorsPath): array
    {
        $serializer = new JsonApiRuleSerializer();
        foreach ($this->selectClasses($validatorsPath, JsonApiRuleSetInterface::class) as $setClass) {
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

    /**
     * @param string $formsValPath
     * @param string $queriesValPath
     *
     * @return array
     */
    private function createValidationAttributeRulesSetData(string $formsValPath, string $queriesValPath): array
    {
        $serializer = new AttributeRulesSerializer();
        foreach ($this->selectClasses($formsValPath, FormRuleSetInterface::class) as $setClass) {
            /** @var string $setName */
            $setName = $setClass;
            assert(
                is_string($setClass) &&
                class_exists($setClass) &&
                array_key_exists(FormRuleSetInterface::class, class_implements($setClass))
            );
            /** @var FormRuleSetInterface $setClass */
            $serializer->addResourceRules($setName, $setClass::getAttributeRules());
        }
        foreach ($this->selectClasses($queriesValPath, QueryRuleSetInterface::class) as $setClass) {
            /** @var string $setName */
            $setName = $setClass;
            assert(
                is_string($setClass) &&
                class_exists($setClass) &&
                array_key_exists(QueryRuleSetInterface::class, class_implements($setClass))
            );
            /** @var QueryRuleSetInterface $setClass */
            $serializer->addResourceRules($setName, $setClass::getAttributeRules());
        }

        $ruleSetsData = $serializer->getData();

        return $ruleSetsData;
    }
}
