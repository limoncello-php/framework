<?php namespace Limoncello\Application\Authorization;

/**
 * @package Limoncello\Application
 */
class ContextProperties extends RequestProperties
{
    /** Context key */
    const CTX_CONTAINER = self::REQ_LAST + 1;

    /** Context key */
    const CTX_LAST = self::CTX_CONTAINER + 1;
}
