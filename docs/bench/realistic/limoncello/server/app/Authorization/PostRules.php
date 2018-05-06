<?php namespace App\Authorization;

//use App\Api\PostsApi;
//use App\Data\Models\Post;
use App\Json\Schemas\PostSchema as Schema;
use Limoncello\Application\Contracts\Authorization\ResourceAuthorizationRulesInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
//use Limoncello\Flute\Contracts\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package App
 */
class PostRules implements ResourceAuthorizationRulesInterface
{
    use RulesTrait;

    /** Action name */
    const ACTION_VIEW_POSTS = 'canViewPosts';

    /** Action name */
    const ACTION_CREATE_POST = 'canCreatePost';

    /** Action name */
    const ACTION_EDIT_POST = 'canEditPost';

//    /** Action name */
//    const ACTION_VIEW_POST_OTHERS = 'canViewPostOthers';

    /**
     * @inheritdoc
     */
    public static function getResourcesType(): string
    {
        return Schema::TYPE;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function canViewPosts(ContextInterface $context): bool
    {
        assert($context);

        return true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function canCreatePost(ContextInterface $context): bool
    {
        $userId = self::getCurrentUserIdentity($context);

        return $userId !== null;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function canEditPost(ContextInterface $context): bool
    {
        assert(self::reqGetResourceType($context) === Schema::TYPE);

        $userId = self::getCurrentUserIdentity($context);

        return $userId !== null;
    }

//    /**
//     * @param ContextInterface $context
//     *
//     * @return bool
//     */
//    public static function canViewPostOthers(ContextInterface $context): bool
//    {
//        assert($context);
//
//        return true;
//    }

//    /**
//     * @param ContextInterface $context
//     *
//     * @return bool
//     *
//     * @throws ContainerExceptionInterface
//     * @throws NotFoundExceptionInterface
//     */
//    private static function canCurrentUserChangePost(ContextInterface $context): bool
//    {
//        $canChange = false;
//
//        if (($userId = self::getCurrentUserIdentity($context)) !== null) {
//            $userId = (int)$userId;
//
//            /** @var FactoryInterface $factory */
//            $container = self::ctxGetContainer($context);
//            $factory   = $container->get(FactoryInterface::class);
//            $postId    = self::reqGetResourceIdentity($context);
//            $post      = $factory->createApi(PostsApi::class)->read($postId);
//            $canChange = $post === null || $post->{Post::FIELD_ID_USER} === $userId;
//        }
//
//        return $canChange;
//    }
}
