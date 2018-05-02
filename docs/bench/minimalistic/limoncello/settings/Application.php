<?php namespace Settings;

use Dotenv\Dotenv;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface;

/**
 * @package Settings
 */
class Application implements ApplicationConfigurationInterface
{
    /** @var callable */
    const CACHE_CALLABLE = '\\Cached\\Application::get';

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        (new Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..'))->load();

        $routesPath     = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'app', 'Http', 'Routes', '*.php']);
        $confPath       = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'app', 'Container', '*.php']);
        $commandsFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'app', 'Commands']);
        $cacheFolder    = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'storage', 'cache', 'settings']);

        $originScheme = getenv('APP_ORIGIN_SCHEME');
        $originHost   = getenv('APP_ORIGIN_HOST');
        $originPort   = getenv('APP_ORIGIN_PORT');
        $originUri    = filter_var("$originScheme://$originHost:$originPort", FILTER_VALIDATE_URL);
        assert(is_string($originUri) === true);

        return [
            static::KEY_APP_NAME                     => getenv('APP_NAME'),
            static::KEY_IS_DEBUG                     => false,
            static::KEY_ROUTES_PATH                  => $routesPath,
            static::KEY_CONTAINER_CONFIGURATORS_PATH => $confPath,
            static::KEY_CACHE_FOLDER                 => $cacheFolder,
            static::KEY_CACHE_CALLABLE               => static::CACHE_CALLABLE,
            static::KEY_COMMANDS_FOLDER              => $commandsFolder,
            static::KEY_APP_ORIGIN_SCHEMA            => $originScheme,
            static::KEY_APP_ORIGIN_HOST              => $originHost,
            static::KEY_APP_ORIGIN_PORT              => $originPort,
            static::KEY_APP_ORIGIN_URI               => $originUri,
            static::KEY_PROVIDER_CLASSES             => [
                \Limoncello\Application\Packages\Application\ApplicationProvider::class,
            ],
        ];
    }
}
