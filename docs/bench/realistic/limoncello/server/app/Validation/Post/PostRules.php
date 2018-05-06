<?php namespace App\Validation\Post;

use App\Api\postsApi;
use App\Data\Models\Post as Model;
use App\Json\Schemas\PostSchema;
use App\Validation\BaseRules;
use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package App
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class PostRules extends BaseRules
{
    /**
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function postType(RuleInterface $next = null): RuleInterface
    {
        return self::equals(PostSchema::TYPE);
    }

    /**
     * NOTE: It recommended to move this method to `BaseRules` so it will be accessible in all validation rule sets.
     *
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function postId(RuleInterface $next = null): RuleInterface
    {
        return self::stringToInt(self::readable(postsApi::class, $next));
    }

    /**
     * NOTE: It recommended to move this method to `BaseRules` so it will be accessible in all validation rule sets.
     *
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function postRelationship(RuleInterface $next = null): RuleInterface
    {
        return self::toOneRelationship(PostSchema::TYPE, self::postId($next));
    }

    /**
     * NOTE: It recommended to move this method to `BaseRules` so it will be accessible in all validation rule sets.
     *
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function postsRelationship(RuleInterface $next = null): RuleInterface
    {
        $readableAll = self::stringArrayToIntArray(self::readableAll(postsApi::class, $next));

        return self::toManyRelationship(PostSchema::TYPE, $readableAll);
    }

    /**
     * @return RuleInterface
     */
    public static function title(): RuleInterface
    {
        $maxLength = Model::getAttributeLengths()[Model::FIELD_TITLE];

        return self::asSanitizedString(self::stringLengthMax($maxLength));
    }

    /**
     * @return RuleInterface
     */
    public static function text(): RuleInterface
    {
        return self::asSanitizedString();
    }
}
