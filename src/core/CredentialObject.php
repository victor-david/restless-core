<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Represents credentials to access an application.
*
* @property int     $authtype     Type of authentication, oauth or email/password
* @property string  $appxid       App xid of the app for authenication
* @property string  $email        Email, when auth is email/password
* @property string  $identifier   Provider identifer, when auth is oauth
* @property string  $oauthsecret  Oauth secret
* @property string  $oauthtoken   Oauth token
* @property string  $password     Password, when auth is email/password
* @property int     $providerid   Id of credential provider, or zero (default)
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

  public $authtype;
  public $appxid;
  public $email;
  public $identifier;
  public $oauthsecret;
  public $oauthtoken;
  public $password;
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