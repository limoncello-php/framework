<?php namespace Limoncello\Flute\Package;

use Generator;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Settings\Packages\FluteSettingsInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\L10n\Messages;
use Limoncello\Flute\Validation\Form\Execution\FormRulesSerializer;
use Limoncello\Flute\Validation\JsonApi\DefaultQueryValidationRules;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiDataRulesSerializer;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiQueryRulesSerializer;
use Limoncello\Validation\Execution\BlockSerializer;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Flute
 */
abstract class FluteSettings implements FluteSettingsInterface
{
    /**
     * Namespace for string resources.
     */
    const GENERIC_NAMESPACE = Messages::RESOURCES_NAMESPACE;

    /**
     * Namespace for string resources.
     */
    public const VALIDATION_NAMESPACE = 'Limoncello.Flute.Validation';

    /**
     * Default page size.
     */
    public const DEFAULT_PAGE_SIZE = 10;

    /**
     * Default page size.
     */
    public const DEFAULT_MAX_PAGE_SIZE = 30;

    /** Serialized validation data index */
    protected const JSON_API_DATA_VALIDATION_RULES_SERIALIZED = 0;

    /** Serialized validation data index */
    protected const JSON_API_QUERIES_VALIDATION_RULES_SERIALIZED = self::JSON_API_DATA_VALIDATION_RULES_SERIALIZED + 1;

    /**
     * @param string $path
     * @param string $implementClassName
     *
     * @return Generator
     */
    abstract protected function selectClasses(string $path, string $implementClassName): Generator;

    /**
     * @param array $appConfig
     *
     * @return array
     */
    final public function get(array $appConfig): array
    {
        $defaults = $this->getSettings();

        $defaults[static::KEY_ROUTES_FOLDER]          = $appConfig[A::KEY_ROUTES_FOLDER];
        $defaults[static::KEY_WEB_CONTROLLERS_FOLDER] = $appConfig[A::KEY_WEB_CONTROLLERS_FOLDER];

        $apiFolder            = $defaults[static::KEY_API_FOLDER] ?? null;
        $valRulesFolder       = $defaults[static::KEY_JSON_VALIDATION_RULES_FOLDER] ?? null;
        $jsonCtrlFolder       = $defaults[static::KEY_JSON_CONTROLLERS_FOLDER] ?? null;
        $schemasFolder        = $defaults[static::KEY_SCHEMAS_FOLDER] ?? null;
        $schemasFileMask      = $defaults[static::KEY_SCHEMAS_FILE_MASK] ?? null;
        $jsonDataValFolder    = $defaults[static::KEY_JSON_VALIDATORS_FOLDER] ?? null;
        $jsonDataValFileMask  = $defaults[static::KEY_JSON_VALIDATORS_FILE_MASK] ?? null;
        $formsValFolder       = $defaults[static::KEY_FORM_VALIDATORS_FOLDER] ?? null;
        $formsValFileMask     = $defaults[static::KEY_FORM_VALIDATORS_FILE_MASK] ?? null;
        $jsonQueryValFolder   = $defaults[static::KEY_QUERY_VALIDATORS_FOLDER] ?? null;
        $jsonQueryValFileMask = $defaults[static::KEY_QUERY_VALIDATORS_FILE_MASK] ?? null;

        assert(
            $apiFolder !== null && empty(glob($apiFolder)) === false,
            "Invalid API folder `$apiFolder`."
        );
        assert(
            $valRulesFolder !== null && empty(glob($valRulesFolder)) === false,
            "Invalid validation rules folder `$valRulesFolder`."
        );
        assert(
            $jsonCtrlFolder !== null && empty(glob($jsonCtrlFolder)) === false,
            "Invalid JSON API controllers' folder `$jsonCtrlFolder`."
        );
        assert(
            $schemasFolder !== null && empty(glob($schemasFolder)) === false,
            "Invalid Schemas folder `$schemasFolder`."
        );
        assert(empty($schemasFileMask) === false, "Invalid Schemas file mask `$schemasFileMask`.");
        assert(
            $jsonDataValFolder !== null && empty(glob($jsonDataValFolder)) === false,
            "Invalid JSON Validators folder `$jsonDataValFolder`."
        );
        assert(empty($jsonDataValFileMask) === false, "Invalid JSON Validators file mask `$jsonDataValFileMask`.");
        assert(
            $formsValFolder !== null && empty(glob($formsValFolder)) === false,
            "Invalid Forms Validators folder `$formsValFolder`."
        );
        assert(empty($formsValFileMask) === false, "Invalid Forms Validators file mask `$formsValFileMask`.");
        assert(
            $jsonQueryValFolder !== null && empty(glob($jsonQueryValFolder)) === false,
            "Invalid Query Validators folder `$jsonQueryValFolder`."
        );
        assert(empty($jsonQueryValFileMask) === false, "Invalid Query Validators file mask `$jsonQueryValFileMask`.");

        $schemasPath         = $schemasFolder . DIRECTORY_SEPARATOR . $schemasFileMask;
        $jsonDataValPath     = $jsonDataValFolder . DIRECTORY_SEPARATOR . $jsonDataValFileMask;
        $formsValidatorsPath = $formsValFolder . DIRECTORY_SEPARATOR . $formsValFileMask;
        $jsonQueryValPath    = $jsonQueryValFolder . DIRECTORY_SEPARATOR . $jsonQueryValFileMask;

        $requireUniqueTypes = $defaults[static::KEY_SCHEMAS_REQUIRE_UNIQUE_TYPES] ?? true;

        $doNotLogExceptions = $defaults[static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST] ?? [];
        unset($defaults[static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST]);

        return $defaults + [
                static::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS => array_flip($doNotLogExceptions),

                static::KEY_MODEL_TO_SCHEMA_MAP => $this->createModelToSchemaMap($schemasPath, $requireUniqueTypes),

                static::KEY_JSON_VALIDATION_RULE_SETS_DATA =>
                    $this->serializeJsonValidationRules($jsonDataValPath, $jsonQueryValPath),

                static::KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA =>
                    $this->serializeFormValidationRules($formsValidatorsPath),
            ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;

        return [
            static::KEY_SCHEMAS_REQUIRE_UNIQUE_TYPES              => true,
            static::KEY_SCHEMAS_FILE_MASK                         => '*.php',
            static::KEY_JSON_VALIDATORS_FILE_MASK                 => '*.php',
            static::KEY_FORM_VALIDATORS_FILE_MASK                 => '*.php',
            static::KEY_QUERY_VALIDATORS_FILE_MASK                => '*.php',
            static::KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER => null,
            static::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE        => 500,
            static::KEY_DEFAULT_PAGING_SIZE                       => static::DEFAULT_PAGE_SIZE,
            static::KEY_MAX_PAGING_SIZE                           => static::DEFAULT_MAX_PAGE_SIZE,
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
     * @param string $schemasPath
     * @param bool   $requireUniqueTypes
     *
     * @return array
     */
    private function createModelToSchemaMap(string $schemasPath, bool $requireUniqueTypes): array
    {
        $map   = [];
        $types = [];
        foreach ($this->selectClasses($schemasPath, SchemaInterface::class) as $schemaClass) {
            assert(
                is_string($schemaClass) &&
                class_exists($schemaClass) &&
                array_key_exists(SchemaInterface::class, class_implements($schemaClass))
            );
            /** @var SchemaInterface $schemaClass */
            $modelClass   = $schemaClass::MODEL;
            $resourceType = $schemaClass::TYPE;

            assert(is_string($modelClass) === true && empty($modelClass) === false);
            assert(is_string($resourceType) === true && empty($resourceType) === false);

            // By default it checks that all Schemas have unique resource types. That's a legit case
            // to have multiple Schemas for a same resource type however it's more likely that developer
            // just forgot to set a unique one. If you do need multiple Schemas for a resource feel free
            // to set to turn off this check.
            assert(
                $requireUniqueTypes === false || array_key_exists($resourceType, $types) === false,
                "Are you sure it's not an error to use resource type `$resourceType` more than once?"
            );
            $types[$resourceType] = true;

            $map[$modelClass] = $schemaClass;
        }

        return $map;
    }

    /**
     * @param string $validatorsPath
     * @param string $queriesValPath
     *
     * @return array
     */
    private function serializeJsonValidationRules(string $validatorsPath, string $queriesValPath): array
    {
        // JSON API data validation rules
        $dataSerializer = new JsonApiDataRulesSerializer(new BlockSerializer());
        foreach ($this->selectClasses($validatorsPath, JsonApiDataRulesInterface::class) as $rulesClass) {
            $dataSerializer->addRulesFromClass($rulesClass);
        }

        // JSON API query validation rules
        $querySerializer = new JsonApiQueryRulesSerializer(new BlockSerializer());
        // Add predefined rules for queries...
        $querySerializer->addRulesFromClass(DefaultQueryValidationRules::class);
        // ... and add user defined ones.
        foreach ($this->selectClasses($queriesValPath, JsonApiQueryRulesInterface::class) as $rulesClass) {
            $querySerializer->addRulesFromClass($rulesClass);
        }

        return [
            static::JSON_API_DATA_VALIDATION_RULES_SERIALIZED    => $dataSerializer->getData(),
            static::JSON_API_QUERIES_VALIDATION_RULES_SERIALIZED => $querySerializer->getData(),
        ];
    }

    /**
     * @param string $formsValPath
     *
     * @return array
     */
    private function serializeFormValidationRules(string $formsValPath): array
    {
        $serializer = new FormRulesSerializer(new BlockSerializer());

        foreach ($this->selectClasses($formsValPath, FormRulesInterface::class) as $rulesClass) {
            $serializer->addRulesFromClass($rulesClass);
        }

        return $serializer->getData();
    }

    // serialization above makes some assumptions about format of returned data
    // the methods below help to deal with the data encapsulation

    /**
     * @param array $fluteSettings
     *
     * @return array
     */
    public static function getJsonDataSerializedRules(array $fluteSettings): array
    {
        $serializedRulesKey = static::KEY_JSON_VALIDATION_RULE_SETS_DATA;
        $dataSubKey         = static::JSON_API_DATA_VALIDATION_RULES_SERIALIZED;

        return $fluteSettings[$serializedRulesKey][$dataSubKey];
    }

    /**
     * @param array $fluteSettings
     *
     * @return array
     */
    public static function getJsonQuerySerializedRules(array $fluteSettings): array
    {
        $serializedRulesKey = static::KEY_JSON_VALIDATION_RULE_SETS_DATA;
        $dataSubKey         = static::JSON_API_QUERIES_VALIDATION_RULES_SERIALIZED;

        return $fluteSettings[$serializedRulesKey][$dataSubKey];
    }

    /**
     * @param array $fluteSettings
     *
     * @return array
     */
    public static function getFormSerializedRules(array $fluteSettings): array
    {
        return $fluteSettings[static::KEY_ATTRIBUTE_VALIDATION_RULE_SETS_DATA];
    }
}
