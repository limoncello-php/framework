<?php namespace App\Http\Routes;

use App\Http\Controllers\HomeController;
use Limoncello\Commands\CommandRoutesTrait;
use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Routing\GroupInterface;

/**
 * @package App
 */
class Web implements RoutesConfiguratorInterface
{
    use CommandRoutesTrait;

    /**
     * @inheritdoc
     *
     * This middleware will be executed on every request even when no matching route is found.
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
        $routes->get('/', HomeController::INDEX_HANDLER, HomeController::PARAMETERS);
    }
}
