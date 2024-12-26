<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents the base controller. All controllers derive from this class
 *
 * @abstract
 */
abstract class CoreController implements AppCollectionInterface
{
    public const DEFAULT_CHARSET = 'utf-8';
    public const DEFAULT_ASSET_PATH = 'asset';

    /**
     * The core request.
     *
     * @var CoreRequest
     */
    protected $request;

    /**
     * The core view object for the controller.
     *
     * @var CoreView
     */
    public $view;

    /**
     * Arguments passed in get.
     *
     * @var OpenObject
     */
    protected $get;

    /**
     * Arguments passed in post.
     *
     * @var OpenObject
     */
    protected $post;

    /**
     * Arguments passed in cookie.
     *
     * @var OpenObject
     */
    public $cookie;

    /**
     * Server values.
     *
     * @var OpenObject
     */
    public $server;

    /**
     * System values.
     *
     * @var OpenObject
     */
    public $system;

    private $assetPath;

    /**
     * Class constructor
     *
     * @param CoreRequest $request
     * @param string $assetPath, may be accessed via $this->server->asset
     */
    public function __construct(CoreRequest $request, string $assetPath = self::DEFAULT_ASSET_PATH)
    {
        $this->request = $request;
        $this->assetPath = $assetPath;
        $this->view = new CoreView($this, $this->request->app);
        $this->get = $this->getArguments($_GET);
        $this->post = $this->getArguments($_POST);
        $this->cookie = $this->getArguments($_COOKIE);
        $this->server = $this->getServer();
        $this->system = $this->getSystem();
        unset($_GET);
        unset($_POST);
    }

    /**
     * Gets a collection of applications.
     *
     * You must override this method in a derived class to enable
     * core view to substitute app values.
     */
    public function getAppCollection() : ?AppCollection
    {
        return null;
    }

    /**
     * Gets a config object.
     *
     * You must override this method in a derived class to enable
     * core view to substitute config values
     */
    public function getConfig() : ?object
    {
        return null;
    }

    /**
     * Magic method called when a non-existent or inaccessible method is
     * called on an object of this class. Used to execute before and after
     * filter methods on action methods. Action methods need to be named
     * with an "Action" suffix, e.g. indexAction, showAction etc.
     *
     * @param string $name  Method name
     * @param array $args Arguments passed to the method
     *
     */
    public function __call($name, $args)
    {
        $this->disableCache();
        $method = $name . 'Action';

        $methodExists = method_exists($this, $method);
        if (!$methodExists)
        {
            $override = $this->getMethodOverride($this->request->action);
            if (count($override) == 2)
            {
                $this->request->pushParameter($override[1], $this->request->action);
                $this->request->action = $override[0];
                $method = $override[0] . 'Action';
                $methodExists = method_exists($this, $method);
            }
        }

        if ($methodExists)
        {
            if ($this->before())
            {
                $this->initialize();
                call_user_func_array([$this, $method], $args);
                $this->after();
            }
        }
        else
        {
            $controller = get_class($this);
            ControllerException::throwMethodNotFoundException("Method [$method] not found in controller [$controller]");
        }
    }

    /**
     * Gets a boolean value that indicates if the specified action is valid.
     *
     * @param string $name
     * @return bool
     */
    public function validateAction($method) : bool
    {
        $method .= 'Action';
        return method_exists($this, $method);
    }

    /**
     * Called when the method inside the controller doesn't exist to route to another method
     * and pass the missing piece as a parm.
     *
     * This method should return an array with two elements [0=>the method name, 1 => the parm name]
     *
     * @param string $name
     *
     * @return array
     */
    protected function getMethodOverride(string $name): array
    {
        return [];
    }

    /**
     * This method is called before an action. If this method returns false, the action is not called.
     *
     * The base method always return true
     *
     * @return bool
     */
    protected function before(): bool
    {
        return true;
    }

    /**
     * This method is called after $this->before() returns true and before the action.
     * Override to perform initialization specific to the controller such as assigning
     * items to $this->view. Always call the base method.
     */
    protected function initialize()
    {
        // $this->view->setActiveMenu(0, $this->request->controller);
        // $this->view->setActiveMenu(1, $this->request->action);
    }

    /**
     * This method is called after the action. Override to perform cleanup or last tasks.
     *
     */
    protected function after()
    {
    }

    /**
     * Returns a callback that is used with $this->view->insertLoop()
     * to select the object in the loop with the specified value.
     *
     * @param mixed $value
     * @return callable
     */
    protected function selectIdWhen($value) : callable
    {
        return function($obj) use ($value)
        {
            if ($obj->id == $value) $obj->selected = 'selected';
        };
    }

    /**
     * Gets a fully qualified location (internal links only)
     *
     * @param string $location The location to fully qualify
     * @return string The fully qualified location
     */
    protected function getLocation($location)
    {
        if (strpos($location, 'http') !== 0)
        {
            // location doesn't start with 'http' - need to construct
            $protocol = (!empty($this->server->https) && $this->server->https != 'off') ? 'https' : 'http';
            if ($location == '/') $location = null;
            $separator = (empty($location)) ? '' : '/';
            $location = sprintf('%s://%s%s%s', $protocol, $this->server->server_name, $separator, $location);
        }
        return $location;
    }

    /**
     * Issues an http redirect and exits. Uses $this->getAppLocation()
     *
     * @param string $location
     */
    protected function httpRedirect($location)
    {
        $location = $this->getLocation($location);
        @ob_clean();
        @header('Location: ' . $location);
        exit();
    }

    /**
     * Sends a header and terminates processing.
     *
     * This method enables you to halt processing and return a header to the browser.
     * Example: $this->terminate(403, 'Forbidden');
     */
    protected function terminate($code, $msg)
    {
        $header = sprintf('%s %s %s', $_SERVER['SERVER_PROTOCOL'], $code, $msg);
        @header($header, true, $code);
        die($msg);
    }



    /**
     * Sets the content type by emitting a Content-Type header
     *
     * @param string $contentType The content type, i.e. 'application/json'
     * @param string $charSet The char set or omit for default of utf-8
     */
    protected function setContentType($contentType, $charSet = self::DEFAULT_CHARSET)
    {
        if ($contentType)
        {
            @header(sprintf('Content-Type: %s; charset=%s', $contentType, $charSet), true);
        }
    }

    /**
     * Sets content type to application/json
     *
     * @param string $charSet The char set or omit for default of utf-8
     */
    protected function setJsonContentType($charSet = self::DEFAULT_CHARSET)
    {
        self::setContentType('application/json', $charSet);
    }

    /**
     * Gets a boolean value that indicates if the request is ajax
     *
     * @return bool
     */
    protected function isAjaxRequest(): bool
    {
        return isset($this->server->http_x_requested_with) && $this->server->http_x_requested_with === 'XMLHttpRequest';
    }

    /**
     * Terminates with a json encoded object that includes a 'session expired' message
     *
     * @param string $commonFileName [default 'ajax.session.expired.html']
     */
    protected function ajaxTerminateNotAuthenticated(string $commonFileName = 'ajax.session.expired.html')
    {
        //$result = OpenObject::ResultRecord();
        $result = new \stdClass();
        $result->valid = 0;
        $result->data = [];
        $template = $this->view->getCommonFileName($commonFileName);
        $this->view->setTemplateFile($template);
        $result->html = $this->view->getPresent();
        die(json_encode($result));
    }

    /**
     * Force https if requested. From PHP manual:
     * $_SERVER['HTTPS'] Set to a non-empty value if the script was queried through the HTTPS protocol.
     * Note that when using ISAPI with IIS, the value will be 'off' if the request was not made through the HTTPS protocol.
     *
     * TODO: forces only when a controller runs. Not on a 404
     */
    protected function forceHttpsIf($force)
    {
        if (php_sapi_name() != 'cli' && $force)
        {
            if (empty($this->server->https) || $this->server->https == 'off')
            {
                // for HSTS preload, expiration minimum is: 10886400 (18 weeks)
                // https://hstspreload.appspot.com/
                @header('Strict-Transport-Security: max-age=10886400');
                @header('Location: https://' . $this->server->http_host . $this->server->request_uri, true, 301);
                die();
            }
        }
    }

    /**
     * Emits headers to disable client side caching
     */
    protected function disableCache()
    {
        @header('Cache-Control: no-store, no-cache, must-revalidate');
        @header('Cache-Control: post-check=0, pre-check=0', false);
        @header('Pragma: no-cache');
    }

    /**
     * Emits headers to enable client side caching
     *
     * @param int $minutes
     */
    protected function enableCache(int $minutes)
    {
        $seconds = max(abs($minutes) * 60, 60);
        @header("cache-control: public, max-age=$seconds, s-maxage=$seconds, immutable");
    }

    /**
     * Converts a dotted string 'get.publisher' to Studly 'GetPublisher';
     *
     * @param string $string
     * @return string
     */
    protected function convertToStudlyCaps($string)
    {
        return str_replace(' ', '', ucwords(str_replace('.', ' ', $string)));
    }

    /**
     * Converts a dotted string 'get.publisher' to camel 'getPublisher';
     *
     * @param string $string
     * @return string
     */
    protected function convertToCamelCase($string)
    {
        return lcfirst($this->convertToStudlyCaps($string));
    }

    private function getArguments($args): OpenObject
    {
        $result = [];
        if (is_array($args))
        {
            foreach($args as $key => $value)
            {
                $result[strtolower((string)$key)] = (is_array($value)) ? $this->getArguments($value) : trim($value);
            }
        }
        return new OpenObject($result);
    }

    private function getServer(): OpenObject
    {
        $server = array();
        foreach($_SERVER as $key => $value)
        {
            $server[strtolower($key)] = $value;
        }
        return new OpenObject($server);
    }

    private function getSystem(): OpenObject
    {
        return new OpenObject(
        [
            'dateyear' => date('Y', time()),
            'siteurl' => $this->getLocation('/'),
            'controller' => $this->request->controller,
            'method' => $this->request->action,
            'encoding' => self::DEFAULT_CHARSET,
            'asset' => $this->assetPath,
            'year' => date('Y', time()),
            'ip' => $this->server->remote_addr
        ]);
    }
}
?>