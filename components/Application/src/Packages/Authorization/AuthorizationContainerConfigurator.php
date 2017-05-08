<?php namespace Limoncello\Application\Packages\Authorization;

use Limoncello\Application\Authorization\AuthorizationManager;
use Limoncello\Application\Packages\Authorization\AuthorizationSettings as S;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Authorization\AuthorizationManagerInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Application
 */
class AuthorizationContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const HANDLER = [self::class, self::METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configure(LimoncelloContainerInterface $container)
    {
        $container[AuthorizationManagerInterface::class] = function (PsrContainerInterface $container) {
            $settingsProvider = $container->get(SettingsProviderInterface::class);
            $settings         = $settingsProvider->get(S::class);

            $manager = new AuthorizationManager($container, $settings[S::KEY_POLICIES_DATA]);
            if ($settings[S::KEY_LOG_IS_ENABLED] === true) {
                $logger = $container->get(LoggerInterface::class);
                $manager->setLogger($logger);
            }

            return $manager;
        };
    }
}
