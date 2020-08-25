<?php declare (strict_types = 1);

namespace Limoncello\Flute\Package;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Api\BasicRelationshipPaginationStrategy;
use Limoncello\Flute\Contracts\Api\RelationshipPaginationStrategyInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Limoncello\Flute\Factory;
use Limoncello\Flute\Http\Query\ParametersMapper;
use Limoncello\Flute\Http\ThrowableHandlers\FluteThrowableHandler;
use Limoncello\Flute\Types\DateTimeType;
use Limoncello\Flute\Types\DateType;
use Limoncello\Flute\Types\GeometryCollectionType;
use Limoncello\Flute\Types\LineStringType;
use Limoncello\Flute\Types\MultiLineStringType;
use Limoncello\Flute\Types\MultiPointType;
use Limoncello\Flute\Types\MultiPolygonType;
use Limoncello\Flute\Types\PointType;
use Limoncello\Flute\Types\PolygonType;
use Limoncello\Flute\Types\UuidType;
use Limoncello\Flute\Validation\Form\Execution\FormValidatorFactory;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiParserFactory;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FluteContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /** @var callable */
    const CONFIGURE_EXCEPTION_HANDLER = [self::class, 'configureExceptionHandler'];

    /**
     * @inheritdoc
     *
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $factory = new Factory($container);

        $container[FactoryInterface::class] = $factory;

        $container[JsonSchemasInterface::class] = function (PsrContainerInterface $container) use ($factory) {
            $settings     = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);
            $modelSchemas = $container->get(ModelSchemaInfoInterface::class);

            return $factory->createJsonSchemas(
                $settings[FluteSettings::KEY_MODEL_TO_SCHEMA_MAP],
                $settings[FluteSettings::KEY_TYPE_TO_SCHEMA_MAP],
                $modelSchemas
            );
        };

        $container[ParametersMapperInterface::class] = function (PsrContainerInterface $container) {
            return new ParametersMapper($container->get(JsonSchemasInterface::class));
        };

        $container[EncoderInterface::class] = function (PsrContainerInterface $container) use ($factory) {
            /** @var JsonSchemasInterface $jsonSchemas */
            $jsonSchemas = $container->get(JsonSchemasInterface::class);
            $settings    = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);
            $encoder     = $factory
                ->createEncoder($jsonSchemas)
                ->withEncodeOptions($settings[FluteSettings::KEY_JSON_ENCODE_OPTIONS])
                ->withEncodeDepth($settings[FluteSettings::KEY_JSON_ENCODE_DEPTH])
                ->withUrlPrefix($settings[FluteSettings::KEY_URI_PREFIX]);
            isset($settings[FluteSettings::KEY_META]) ? $encoder->withMeta($settings[FluteSettings::KEY_META]) : null;
            ($settings[FluteSettings::KEY_IS_SHOW_VERSION] ?? false) ?
                $encoder->withJsonApiVersion(FluteSettings::DEFAULT_JSON_API_VERSION) : null;

            return $encoder;
        };

        $container[RelationshipPaginationStrategyInterface::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);

            return new BasicRelationshipPaginationStrategy($settings[FluteSettings::KEY_DEFAULT_PAGING_SIZE]);
        };

        $container[JsonApiParserFactoryInterface::class] = function (PsrContainerInterface $container) {
            $factory = new JsonApiParserFactory($container);

            return $factory;
        };

        $container[FormValidatorFactoryInterface::class] = function (PsrContainerInterface $container) {
            $factory = new FormValidatorFactory($container);

            return $factory;
        };

        // register date/date time types
        Type::hasType(DateTimeType::NAME) === true ?: Type::addType(DateTimeType::NAME, DateTimeType::class);
        Type::hasType(DateType::NAME) === true ?: Type::addType(DateType::NAME, DateType::class);

        // register spatial types
        Type::hasType(PointType::NAME) === true ?: Type::addType(PointType::NAME, PointType::class);
        Type::hasType(MultiPointType::NAME) === true ?: Type::addType(MultiPointType::NAME, MultiPointType::class);
        Type::hasType(LineStringType::NAME) === true ?: Type::addType(LineStringType::NAME, LineStringType::class);
        Type::hasType(MultiLineStringType::NAME) === true ?: Type::addType(MultiLineStringType::NAME, MultiLineStringType::class);
        Type::hasType(PolygonType::NAME) === true ?: Type::addType(PolygonType::NAME, PolygonType::class);
        Type::hasType(MultiPolygonType::NAME) === true ?: Type::addType(MultiPolygonType::NAME, MultiPolygonType::class);
        Type::hasType(GeometryCollectionType::NAME) === true ?: Type::addType(GeometryCollectionType::NAME, GeometryCollectionType::class);

        // register UUID types
        Type::hasType(UuidType::NAME) === true ?: Type::addType(UuidType::NAME, UuidType::class);
    }

    /**
     * @param LimoncelloContainerInterface $container
     *
     * @return void
     */
    public static function configureExceptionHandler(LimoncelloContainerInterface $container)
    {
        $container[ThrowableHandlerInterface::class] = function (PsrContainerInterface $container) {
            /** @var CacheSettingsProviderInterface $provider */
            $provider      = $container->get(CacheSettingsProviderInterface::class);
            $appConfig     = $provider->getApplicationConfiguration();
            $fluteSettings = $provider->get(FluteSettings::class);

            $isLogEnabled = $appConfig[A::KEY_IS_LOG_ENABLED];
            $isDebug      = $appConfig[A::KEY_IS_DEBUG];

            $ignoredErrorClasses = $fluteSettings[FluteSettings::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS];
            $codeForUnexpected   = $fluteSettings[FluteSettings::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE];
            $throwableConverter  =
                $fluteSettings[FluteSettings::KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER] ?? null;

            /** @var EncoderInterface $encoder */
            $encoder = $container->get(EncoderInterface::class);

            $handler = new FluteThrowableHandler(
                $encoder,
                $ignoredErrorClasses,
                $codeForUnexpected,
                $isDebug,
                $throwableConverter
            );

            if ($isLogEnabled === true && $container->has(LoggerInterface::class) === true) {
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);
                $handler->setLogger($logger);
            }

            return $handler;
        };
    }
}
