<?php namespace App\Http\Routes;

use Limoncello\Application\Commands\ApplicationCommand;
use Limoncello\Application\Commands\DataCommand;
use Limoncello\Application\Packages\Application\ApplicationContainerConfigurator;
use Limoncello\Application\Packages\Data\DataContainerConfigurator;
use Limoncello\Application\Packages\FileSystem\FileSystemContainerConfigurator;
use Limoncello\Application\Packages\L10n\L10nContainerConfigurator;
use Limoncello\Application\Packages\Monolog\MonologFileContainerConfigurator;
use Limoncello\Commands\CommandRoutesTrait;
use Limoncello\Commands\CommandsCommand;
use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Crypt\Package\HasherContainerConfigurator;

/**
 * @package App
 */
class Cli implements RoutesConfiguratorInterface
{
    use CommandRoutesTrait;

    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [
            //ClassName::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function configureRoutes(GroupInterface $routes): void
    {
        // commands require composer
        if (class_exists('Composer\Command\BaseCommand') === true) {
            $commonConfigurators = [
                ApplicationContainerConfigurator::CONFIGURATOR,
                DataContainerConfigurator::CONFIGURATOR,
                L10nContainerConfigurator::CONFIGURATOR,
                MonologFileContainerConfigurator::CONFIGURATOR,
                FileSystemContainerConfigurator::CONFIGURATOR,
                HasherContainerConfigurator::CONFIGURATOR,
            ];

            self::commandContainer($routes, DataCommand::NAME, $commonConfigurators);
            self::commandContainer($routes, ApplicationCommand::NAME, $commonConfigurators);
            self::commandContainer($routes, CommandsCommand::NAME, $commonConfigurators);
        }
    }
}
