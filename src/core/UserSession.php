<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents a session object. Information about a logged in user.
 */
class UserSession extends StrictObject
{
    /**
     * Gets the string session id
     *
     * @var string
     */
    public $id;

    /**
     * Gets the string session token
     *
     * @var string
     */
    public $token;

    /**
     * Gets the integer id of the session user
     *
     * @var int
     */
    public $userid;

    /**
     * Gets the integer application id of the session user
     *
     * @var int
     */
    public $appid;
}
?>