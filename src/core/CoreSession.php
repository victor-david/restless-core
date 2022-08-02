<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Provides core session services for the Restless framework.
*
* CoreSession provides methods and properties to handle authenticated sessions
*
* @author Victor D. Sandiego
*/
class CoreSession
{
  private $implementor;

  /**
  * Gets the current application
  *
  * @var App
  */
  public $app;

  /**
  * Gets or sets the session info. Used as a cached object to avoid calling implementor repeatedly
  *
  * @var UserSession
  */
  private $sessionInfo;

  /**
  * Class constructor
  */
  public function __construct(App $app)
  {
    $this->app = $app;
    $this->sessionInfo = null;
  }

  /**
  * Sets the implementor that will handle the session authentication, creation, etc.
  *
  * @param SessionInterface $implementor
  */
  public function setImplementor(SessionInterface $implementor)
  {
    $this->implementor = $implementor;
  }

  /**
  * Authenticates the specified credentials
  *
  * @param CredentialObject $credential
  * @throws Exception if credentials don't authenticate
  */
  public function authenticate(CredentialObject $credential)
  {
    $this->validateImplementor();
    /* authenicateUser() throws if can't authenticate */
    $user = $this->implementor->authenticate($credential);
    /* if we get here, authentication is okay */
    $session = $this->implementor->createSession($user, (int)$this->app->timeout);
    setcookie($session->id, $session->token, 0, $this->app->sessionpath, '', $this->app->https ? true : false, true);
  }

  /**
  * Gets the session info
  *
  * @return UserSession|null
  */
  public function getSessionInfo(): ?UserSession
  {
    /* Get cached session info if available */
    if ($this->sessionInfo)
    {
      return $this->sessionInfo;
    }
    $this->validateImplementor();

    foreach ($_COOKIE as $key=>$value)
    {
      $sessionInfo = $this->implementor->validateSession($key, $value, (int)$this->app->id);
      if ($sessionInfo)
      {
        $this->sessionInfo = $sessionInfo;
        return $sessionInfo;
      }
    }
    return null;
  }

  public function endSession()
  {
    $this->validateImplementor();
    $session = $this->getSessionInfo();
    if ($session)
    {
      $this->implementor->endSession($session);
      setcookie($session->id, '', time() - 3600, $this->app->sessionpath);
    }
  }

  /**
  * Gets a value that indicates if the caller is currently logged in.
  *
  * @return int 1 if logged in, 0 if not logged in.
  */
  public function isLoggedIn()
  {
    return ($this->getSessionInfo()) ? 1 : 0;
  }

  /**
  * Gets a value that indicates if the caller can view the site while it's offline.
  * @return int 1 if the caller can view the site while it's offline; otherwise, zero.
  */
  public function canViewOffline()
  {
    return $this->isLoggedIn();
  }

  private function validateImplementor()
  {
    if ($this->implementor == null)
    {
      throw new \Exception('Session implementor not set');
    }
  }
}
?>