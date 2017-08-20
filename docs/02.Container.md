### Container

Container is a primary way to create services provided by framework, 3rd party libraries and application itself. Limoncello uses [PSR-11](http://www.php-fig.org/psr/psr-11/) container.

The application has an access to the container instance everywhere in controllers, middleware, API, and etc.

#### Reading from Container

It is very simple. Just a few examples so you get the idea

```php
    /** @var \Psr\Container\ContainerInterface $container */
    $container = ...;

    /** @var \Doctrine\DBAL\Connection $connection */
    $connection = $container->get(Connection::class);

    /** @var \Limoncello\Contracts\Settings\SettingsProviderInterface $provider */
    $provider = $container->get(SettingsProviderInterface::class);

    /** @var \Limoncello\Contracts\Authentication\AccountManagerInterface $provider */
    $provider = $container->get(AccountManagerInterface::class);
```

#### Custom Services

You can put your services to container as well. In order to do that a container configurator file should be created in `app/Container` folder. It should be a class that implements `ContainerConfiguratorInterface`.

Here is an example,


```php
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Settings\SomeSettings;

class CustomConfigurator implements ContainerConfiguratorInterface
{
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[SomeInterface::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(SomeSettings::class);
            $data     = $settings[SomeSettings::KEY_SOME_VALUE];
            
            // use settings $data to create and configure a resource
            $resource = ...;

            return $resource;
        };
        
        // you can add to $container as many items as you need
    }
}
```

Then you can have an instance of `SomeInterface` anywhere in the application

```php
    /** @var \Psr\Container\ContainerInterface $container */
    $container = ...;

    $yourService = $container->get(SomeInterface::class);
```
