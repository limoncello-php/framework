### Authorization

The [Auth](https://github.com/limoncello-php/framework/tree/master/components/Auth) component solves the problem of [checking current user permissions](https://en.wikipedia.org/wiki/Authorization).

#### Checking Permissions

In order to check the permissions it is needed to get `AuthorizationManagerInterface` from application container.

```php
    /** @var \Psr\Container\ContainerInterface $container */
    $container = ...;
    
    $action           = 'read';
    $resourceType     = 'posts';
    $resourceIdentity = 123;

    /** @var \Limoncello\Contracts\Authorization\AuthorizationManagerInterface $manager */
    $manager = $container->get(AuthorizationManagerInterface::class);
    $manager->authorize($action, $resourceType, $resourceIdentity);
    
    // it can handle just actions or actions for resource types as well
    $manager->authorize('canSendEmail');
    $manager->authorize('index', 'posts');
```

> Method `authorize` also has parameter `$extraParams` which could be used to pass additional parameters to rules.

#### Managing Permission Rules

By default permission rules are described in `app/Authorization` folder.

If rules work with actions (e.g. `canSendGreetingsEmail`, and etc) and do not work with specific resource types a class implementing `AuthorizationRulesInterface` should be created. The interface has no methods to implement and serves as a marker. Methods with names identical to actions should be added.

```php
use Limoncello\Application\Authorization\AuthorizationRulesTrait;
use Limoncello\Application\Contracts\Authorization\AuthorizationRulesInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;

class PostRules implements AuthorizationRulesInterface
{
    use AuthorizationRulesTrait;

    /** It makes sense to use constants to refer to the action instead of 'magic' strings. */
    const ACTION_CAN_SEND_GREETINGS_EMAIL = 'canSendGreetingsEmail';

    public static function canSendGreetingsEmail(ContextInterface $context): bool
    {
        // you will get everything you need out of context
    
        $result = ...;

        return $result;
    }
}
```

All the required additional data and services could be taken from the context. Trait `AuthorizationRulesTrait` provides a few useful methods such as

```php
    /** @var \Psr\Container\ContainerInterface $container */
    $container = self::ctxGetContainer($context);
```

From the container you can get any application service such as API, database connection, and etc.

Current user can also be taken from the context and there are a couple of helpers

```php
    /** @var bool $isUserLoggedIn */
    $isUserLoggedIn = self::ctxHasCurrentAccount($context);

    /** @var \Limoncello\Contracts\Authentication\AccountInterface $currentUser */
    $currentUser = self::ctxGetCurrentAccount($context);
```

> Default application will return $currentUser as `\Limoncello\Passport\Contracts\Authentication\PassportAccountInterface` which has additional OAuth data methods.

If rules work with resources (e.g. `read` post with ID 123 or just `index` all posts) a class implementing `ResourceAuthorizationRulesInterface` should be created. The interface has a method `getResourcesType` that returns resource type (string) it works with.

> The classification of the rules as untyped and typed helps to optimize search among them.

```php

use App\Data\Models\Post;
use App\Json\Api\PostsApi;
use App\Json\Schemes\PostScheme as Scheme;
use Limoncello\Application\Contracts\Authorization\ResourceAuthorizationRulesInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Passport\Contracts\Authentication\PassportAccountInterface;
use Settings\Passport;

class PostRules implements ResourceAuthorizationRulesInterface
{
    use AuthorizationRulesTrait;

    const ACTION_CREATE_POST = 'canCreatePost';
    const ACTION_EDIT_POST   = 'canEditPost';

    public static function getResourcesType(): string
    {
        return Scheme::TYPE;
    }

    public static function canCreatePost(ContextInterface $context): bool
    {
        $userId = self::getCurrentUserIdentity($context);

        return $userId !== null;
    }

    public static function canEditPost(ContextInterface $context): bool
    {
        return
            self::hasScope($context, Passport::SCOPE_ADMIN_MESSAGES) ||
            self::isCurrentUserPostAuthor($context);
    }

    private static function isCurrentUserPostAuthor(ContextInterface $context): bool
    {
        $isAuthor = false;

        if (($userId = self::getCurrentUserIdentity($context)) !== null) {
            $identity = self::reqGetResourceIdentity($context);

            /** @var PostsApi $api */
            /** @var FactoryInterface $factory */
            $container = self::ctxGetContainer($context);
            $factory   = $container->get(FactoryInterface::class);
            $api       = $factory->createApi(PostsApi::class);
            $post      = $api->readResource($identity);
            $isAuthor  = $post !== null && $post->{Post::FIELD_ID_USER} === $userId;
        }

        return $isAuthor;
    }

    private static function hasScope(ContextInterface $context, string $scope): bool
    {
        $result = false;

        if (static::ctxHasCurrentAccount($context) === true) {
            /** @var PassportAccountInterface $account */
            $account = self::ctxGetCurrentAccount($context);
            $result  = $account->hasScope($scope);
        }

        return $result;
    }

    private static function getCurrentUserIdentity(ContextInterface $context)
    {
        $userId = null;

        /** @var PassportAccountInterface $account */
        if (self::ctxHasCurrentAccount($context) === true &&
            ($account = self::ctxGetCurrentAccount($context)) !== null &&
            $account->hasUserIdentity() === true
        ) {
            $userId = $account->getUserIdentity();
        }

        return $userId;
    }
}
```

The example above demonstrates how to

- Create such application services as API and request database with `isCurrentUserPostAuthor` method.
- Check if current user has required [OAuth scope](https://tools.ietf.org/html/rfc6749#section-3.3) with `hasScope` method.
- Get current user ID with `getCurrentUserIdentity` method.

As it was mentioned earlier method `authorize` can also send additional data through `$extraParams`. The data could be obtained as follows

```php
    // suppose $extraParams were send with ['some_key' => 'some_value'];

    $hasValue = $context->has('some_key'); // true
    $value    = $context->get('some_key'); // 'some_value'
```

#### Final Words

This functionality is implemented in [Auth](https://github.com/limoncello-php/framework/tree/master/components/Auth) component (with some assistance from [Application](https://github.com/limoncello-php/framework/tree/master/components/Application) component to make its usage simpler). The implementation was inspired by ideas of [XACML](https://en.wikipedia.org/wiki/XACML) and it is a **very** powerful tool. `Auth` component could be used as a standalone one and do not depend on the rest of the framework.
