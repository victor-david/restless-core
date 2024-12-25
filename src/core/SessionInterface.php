<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Describes the interface that a session implementor must use.
 */
interface SessionInterface
{
    /**
     * Authenticates the specified credentials.
     *
     * This method receives an object of credentials and checks to see if those
     * credentials are valid. If so, it returns an object that contains user info.
     * If not, throws.
     *
     * @param CredentialObject $credential
     * @throws AuthenicationException
     */
    public function authenticate(CredentialObject $credential): object;

    /**
     * Creates the session.
     *
     * This method is called during the authenticate($credential) method, if the authenticate
     * callback returns an object of user info (that is, the authentication was successful).
     *
     * This method receives an object of user information (obtained by the authenicate callback)
     * and the timeout. This method creates the session and returns a UserSession object.
     *
     * @param OpenObject $user
     * @param int $timeout
     */
    public function createSession(object $user, int $timeout): UserSession;

    /**
     * Validates the session.
     *
     * This method checks that the session is valid. If so, return a UserSession object. Otherwise, null.
     *
     * @param mixed $sessionId
     * @param mixed $token
     * @param int $appId
     */
    public function validateSession($sessionId, $token, int $appId): ?UserSession;

    /**
     * Ends the session
     *
     * @param UserSession
     */
    public function endSession(UserSession $session);
}
?>