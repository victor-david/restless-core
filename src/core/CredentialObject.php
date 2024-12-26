<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents credentials to access an application.
 */
class CredentialObject
{
    /**
     * Specifies that the credential type is oauth
     */
    public const AUTH_OAUTH = 1;

    /**
     * Specifies that the credential type is email / password
     */
    public const AUTH_PASSWORD = 2;

    /**
     * Specifies that the credential type is email only (token callback)
     */
    public const AUTH_EMAIL = 3;

    /**
     * Authentication type, oauth, email/password, or email only (token callback)
     *
     * @var int
     */
    public $authtype;

    /**
     * External id of the application
     *
     * @var string
     */
    public $appxid;

    /**
     * Email, when auth is email/password.
     *
     * @var string
     */
    public $email;

    /**
     * Provider identifer, when auth is oauth.
     *
     * @var string
     */
    public $identifier;

    /**
     * Oauth secret.
     *
     * @var string
     */
    public $oauthsecret;

    /**
     * Oauth token.
     *
     * @var string
     */
    public $oauthtoken;

    /**
     * Password, when auth is email/password.
     *
     * @var string
     */
    public $password;

    /**
     * Id of credential provider, or zero (default)
     *
     * @var int
     */
    public $providerid;

    private function __construct(int $authtype)
    {
        $this->authtype = $authtype;
        $this->providerid = 0;
    }

    /**
     * Creates a new instance of this class.
     *
     * @param int $authtype
     * @return \Restless\Core\CredentialObject
     */
    public static function create(int $authtype): self
    {
        return new CredentialObject($authtype);
    }

    /**
     * Sets the application xid, needed for all auth types.
     *
     * @param mixed $value
     * @return \Restless\Core\CredentialObject
     */
    public function setAppXid($value): self
    {
        $this->appxid = $value;
        return $this;
    }

    /**
     * Sets the email, needed for all auth types.
     *
     * @param mixed $value
     * @return \Restless\Core\CredentialObject
     */
    public function setEmail($value): self
    {
        $this->email = $value;
        return $this;
    }

    /**
     * Sets the identifier from the provider, needed only for self::AUTH_OAUTH
     *
     * @param mixed $value
     * @return \Restless\Core\CredentialObject
     */
    public function setIdentifier($value): self
    {
        $this->identifier = $value;
        return $this;
    }

    /**
     * Sets the oauth secret from the provider, needed only for self::AUTH_OAUTH
     *
     * @param mixed $value
     * @return \Restless\Core\CredentialObject
     */
    public function setOauthSecret($value): self
    {
        $this->oauthsecret = $value;
        return $this;
    }

    /**
     * Sets the oauth token from the provider, needed only for self::AUTH_OAUTH
     *
     * @param mixed $value
     * @return \Restless\Core\CredentialObject
     */
    public function setOauthToken($value): self
    {
        $this->oauthtoken = $value;
        return $this;
    }

    /**
     * Sets the password, needed only for self::AUTH_PASSWORD
     *
     * @param mixed $value
     * @return \Restless\Core\CredentialObject
     */
    public function setPassword($value): self
    {
        $this->password = $value;
        return $this;
    }

    /**
     * Sets the provider id, needed only for self::AUTH_OAUTH
     *
     * @param int $value
     * @return \Restless\Core\CredentialObject
     */
    public function setProviderId(int $id): self
    {
        $this->providerid = $id;
        return $this;
    }
}
?>