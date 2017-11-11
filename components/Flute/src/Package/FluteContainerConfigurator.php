<?php namespace Limoncello\Flute\Package;

use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Adapters\PaginationStrategy;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorFactoryInterface;
use Limoncello\Flute\Factory;
use Limoncello\Flute\Http\Query\ParametersMapper;
use Limoncello\Flute\Http\Query\QueryParser;
use Limoncello\Flute\Http\ThrowableHandlers\FluteThrowableHandler;
use Limoncello\Flute\Types\DateJsonApiStringType;
use Limoncello\Flute\Types\DateTimeJsonApiStringType;
use Limoncello\Flute\Types\JsonApiDateTimeType;
use Limoncello\Flute\Types\JsonApiDateType;
use Limoncello\Flute\Validation\Execution\JsonApiValidatorFactory;
use Neomerx\JsonApi\Encoder\EncoderOptions;
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
    const CONFIGURE_EXCEPTION_HANDLER = [self::class, 'configureExceptionHandler'];

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $factory = new Factory($container);

        $container[FactoryInterface::class] = $factory;

        $container[JsonSchemesInterface::class] = function (PsrContainerInterface $container) use ($factory) {
            $settings     = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);
            $modelSchemes = $container->get(ModelSchemeInfoInterface::class);

            return $factory->createJsonSchemes($settings[FluteSettings::KEY_MODEL_TO_SCHEME_MAP], $modelSchemes);
        };

        $container[QueryParserInterface::class] = function (PsrContainerInterface $container) {
            return new QueryParser($container->get(PaginationStrategyInterface::class));
        };

        $container[ParametersMapperInterface::class] = function (PsrContainerInterface $container) {
            return new ParametersMapper($container->get(JsonSchemesInterface::class));
        };

        $container[EncoderInterface::class] = function (PsrContainerInterface $container) use ($factory) {
            /** @var JsonSchemesInterface $jsonSchemes */
            $jsonSchemes = $container->get(JsonSchemesInterface::class);
            $settings    = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);
            $encoder     = $factory->createEncoder($jsonSchemes, new EncoderOptions(
                $settings[FluteSettings::KEY_JSON_ENCODE_OPTIONS],
                $settings[FluteSettings::KEY_URI_PREFIX],
                $settings[FluteSettings::KEY_JSON_ENCODE_DEPTH]
            ));
            isset($settings[FluteSettings::KEY_META]) ? $encoder->withMeta($settings[FluteSettings::KEY_META]) : null;
            ($settings[FluteSettings::KEY_IS_SHOW_VERSION] ?? false) ? $encoder->withJsonApiVersion() : null;

            return $encoder;
        };

        $container[PaginationStrategyInterface::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);

            return new PaginationStrategy(
                $settings[FluteSettings::KEY_DEFAULT_PAGING_SIZE],
                $settings[FluteSettings::KEY_MAX_PAGING_SIZE]
            );
        };

        $container[JsonApiValidatorFactoryInterface::class] = function (PsrContainerInterface $container) {
            $factory = new JsonApiValidatorFactory($container);

            return $factory;
        };

        // register date/date time types
        if (Type::hasType(DateTimeJsonApiStringType::NAME) === false) {
            Type::addType(DateTimeJsonApiStringType::NAME, DateTimeJsonApiStringType::class);
        }
        if (Type::hasType(DateJsonApiStringType::NAME) === false) {
            Type::addType(DateJsonApiStringType::NAME, DateJsonApiStringType::class);
        }
        if (Type::hasType(JsonApiDateTimeType::NAME) === false) {
            Type::addType(JsonApiDateTimeType::NAME, JsonApiDateTimeType::class);
        }
        if (Type::hasType(JsonApiDateType::NAME) === false) {
            Type::addType(JsonApiDateType::NAME, JsonApiDateType::class);
        }
    }

    /**
     * @param LimoncelloContainerInterface $container
     *
     * @return void
     */
    public static function configureExceptionHandler(LimoncelloContainerInterface $container)
    {
        $container[ThrowableHandlerInterface::class] = function (PsrContainerInterface $container) {
            $appSettings   = $container->get(SettingsProviderInterface::class)->get(A::class);
            $fluteSettings = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);

            $isLogEnabled = $appSettings[A::KEY_IS_LOG_ENABLED];
            $isDebug      = $appSettings[A::KEY_IS_DEBUG];

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
