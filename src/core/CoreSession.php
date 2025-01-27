<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Provides core session services for the Restless framework.
 *
 * CoreSession provides methods and properties to handle authenticated sessions
 */
class CoreSession
{
    private $implementor;
    private $sessionTimeout;
    private $sessionPath;
    private $sessionHttps;

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
     * Class constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->sessionInfo = null;
        $this->sessionTimeout = 60;
        $this->sessionPath = '/';
        $this->sessionHttps = false;
    }

    /**
     * Static creator, useful for call chaining.
     *
     * @param App $app
     *
     * @return CoreSession
     */
    public static function create(App $app) : self
    {
        return new CoreSession($app);
    }

    /**
     * Sets the implementor that will handle the session authentication, creation, etc.
     *
     * @param SessionInterface $implementor
     *
     * @return CoreSession
     */
    public function setImplementor(SessionInterface $implementor) : self
    {
        $this->implementor = $implementor;
        return $this;
    }

    /**
     * Sets the session timeout in minutes. Default is 60.
     *
     * @param int $value
     *
     * @return CoreSession
     */
    public function setTimeout(int $value) : self
    {
        $this->sessionTimeout = $value;
        return $this;
    }

    /**
     * Sets the session path. Default is '/'
     *
     * @param string $value
     *
     * @return CoreSession
     */
    public function setSessionPath(string $value) : self
    {
        $this->sessionPath = $value;
        return $this;
    }

    /**
     * Sets a value that determines if the session cookie is only sent over https. Default is false.
     *
     * @param bool $value
     *
     * @return CoreSession
     */
    public function setSessionHttps(bool $value) : self
    {
        $this->sessionHttps = $value;
        return $this;
    }

    /**
     * Authenticates the specified credentials.
     *
     * @param CredentialObject $credential
     *
     * @throws Exception if credentials don't authenticate
     */
    public function authenticate(CredentialObject $credential)
    {
        $this->validateImplementor();
        /* authenicate() throws if can't authenticate */
        $user = $this->implementor->authenticate($credential);
        /* if we get here, authentication is okay */
        $session = $this->implementor->createSession($user, (int)$this->sessionTimeout);
        setcookie($session->id, $session->token, 0, $this->sessionPath, '', $this->sessionHttps, true);
    }

    /**
     * Gets the session info.
     *
     * @return UserSession|null
     */
    public function getSessionInfo(): UserSession|null
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

    /**
     * Ends the session
     */
    public function endSession()
    {
        $this->validateImplementor();
        $session = $this->getSessionInfo();
        if ($session)
        {
            $this->implementor->endSession($session);
            setcookie($session->id, '', time() - 3600, $this->sessionPath);
        }
    }

    /**
     * Gets a value that indicates if the caller is currently logged in.
     *
     * @return int 1 if logged in, 0 if not logged in.
     */
    public function isLoggedIn() : int
    {
        return ($this->getSessionInfo()) ? 1 : 0;
    }

    /**
     * Gets a value that indicates if the caller can view the site while it's offline.
     *
     * This method returns the same value as isLoggedIn(). It may include other factors in a later version.
     *
     * @return int 1 if the caller can view the site while it's offline; otherwise, zero.
     */
    public function canViewOffline() : int
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