<?php namespace App\Routes;

use App\Web\Controllers\PostsController;
use Limoncello\Application\Packages\L10n\L10nContainerConfigurator;
use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Flute\Http\Traits\FluteRoutesTrait;

/**
 * @package App
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PostWebRoutes implements RoutesConfiguratorInterface
{
    use FluteRoutesTrait;

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function configureRoutes(GroupInterface $routes): void
    {
        $routes->group('', function (GroupInterface $routes): void {

            $routes->addContainerConfigurators([
                L10nContainerConfigurator::CONFIGURATOR,
            ]);

            for ($i = 0; $i < 10 + 10 * 5; ++$i) {
                $routes->post("/posts$i/create", [PostsController::class, PostsController::METHOD_CREATE]);
            }

        });
    }

    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [];
    }
}
