<?php namespace App\Validation\Post;

use App\Json\Schemas\PostSchema as Schema;
use App\Validation\Post\PostRules as r;
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;

/**
 * @package App
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class PostCreateForm implements FormRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Schema::ATTR_TITLE      => r::required(r::title()),
            Schema::ATTR_TEXT       => r::required(r::text()),
            Schema::ATTR_CREATED_AT => r::required(r::stringToDateTime('Y-m-d')),
        ];
    }
}
