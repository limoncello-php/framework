<?php namespace App\Validation\Post;

use App\Json\Schemas\PostSchema as Schema;
use App\Validation\Post\PostRules as r;
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;

/**
 * @package App
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class PostUpdateForm implements FormRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Schema::ATTR_TITLE => r::title(),
            Schema::ATTR_TEXT  => r::text(),
//            Schema::REL_OTHER  => r::otherId(),
        ];
    }
}
