<?php namespace App\Validation\Post;

use App\Json\Schemas\PostSchema as Schema;
//use App\Json\Schemas\UserSchema;
use App\Validation\Post\PostRules as r;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Settings\ApplicationApi;

/**
 * @package App
 */
class PostsReadQuery implements JsonApiQueryRulesInterface
{
    /**
     * @return RuleInterface[]|null
     */
    public static function getFilterRules(): ?array
    {
        return [
            Schema::RESOURCE_ID                             => r::stringToInt(r::moreThan(0)),
            Schema::ATTR_TITLE                              => r::asSanitizedString(),
            Schema::ATTR_TEXT                               => r::asSanitizedString(),
            Schema::ATTR_CREATED_AT                         => r::asJsonApiDateTime(),

            // it can filter by fields in relationships too (using SQL JOIN)
//            Schema::REL_USER                                => r::asSanitizedString(),
//            Schema::REL_USER . '.' . UserSchema::ATTR_EMAIL => r::asSanitizedString(),
        ];
    }

    /**
     * @return RuleInterface[]|null
     */
    public static function getFieldSetRules(): ?array
    {
        return [
            // if fields sets are given only the following fields are OK
            Schema::TYPE     => r::inValues([
                Schema::RESOURCE_ID,
                Schema::ATTR_TITLE,
                Schema::ATTR_TEXT,
                Schema::ATTR_CREATED_AT,
                Schema::ATTR_UPDATED_AT,
//                Schema::REL_USER,
            ]),
//            // for `users` type any field set would be OK
//            UserSchema::TYPE => r::success(),
        ];
    }

    /**
     * @return RuleInterface|null
     */
    public static function getSortsRule(): ?RuleInterface
    {
        // only the following fields could be used for sorting
        return r::isString(r::inValues([
            Schema::RESOURCE_ID,
            Schema::ATTR_TITLE,
            Schema::ATTR_TEXT,
            Schema::ATTR_CREATED_AT,
            Schema::ATTR_UPDATED_AT,

            // it can sort by fields in relationships too (using SQL JOIN)
//            Schema::REL_USER,
//            Schema::REL_USER . '.' . UserSchema::ATTR_EMAIL,
        ]));
    }

    /**
     * @return RuleInterface|null
     */
    public static function getIncludesRule(): ?RuleInterface
    {
        // only the following relationships could be requested to be included in a response
        return r::isString(r::inValues([
//            Schema::REL_USER,
        ]));
    }

    /**
     * @return RuleInterface|null
     */
    public static function getPageOffsetRule(): ?RuleInterface
    {
        // defaults are fine
        return DefaultQueryValidationRules::getPageOffsetRule();
    }

    /**
     * @return RuleInterface|null
     */
    public static function getPageLimitRule(): ?RuleInterface
    {
        // defaults are fine
        return DefaultQueryValidationRules::getPageLimitRuleForDefaultAndMaxSizes(
            ApplicationApi::DEFAULT_PAGE_SIZE,
            ApplicationApi::DEFAULT_MAX_PAGE_SIZE
        );
    }
}
