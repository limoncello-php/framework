<?php namespace Limoncello\Application\Contracts\Authorization;

/**
 * @package Limoncello\Application
 */
interface ResourceAuthorizationRulesInterface extends AuthorizationRulesInterface
{
    /**
     * @return string
     */
    public static function getResourcesType(): string;
}
