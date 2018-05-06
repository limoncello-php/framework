<?php namespace App\Validation\Role;

use App\Json\Schemas\RoleSchema as Schema;
use App\Validation\Role\RoleRules as r;
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;

/**
 * @package App
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class RoleCreateForm implements FormRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Schema::RESOURCE_ID      => r::required(r::isUniqueRoleId()),
            Schema::ATTR_DESCRIPTION => r::required(r::description()),
        ];
    }
}
