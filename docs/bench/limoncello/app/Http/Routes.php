<?php namespace App\Http;

use App\Http\Controllers\HomeController;
use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Routing\GroupInterface;

/**
 * @package App
 */
class Routes implements RoutesConfiguratorInterface
{
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
        $routes
            ->get('/', HomeController::INDEX_HANDLER, HomeController::PARAMETERS);
    }
}
