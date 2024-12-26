<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents the core request.
 *
 * This class is responsible for parsing the incoming url and assigning app, controller, and action.
 *
 * An automatic routing strategy is employed. That is: the app, controller, action,
 * and parms are taken directly from the url and mapped to a specific class and method.
 */
class CoreRequest
{
    /**
     * The default controller when none specified.
     */
    const DEFAULT_CONTROLLER = 'root';

    /**
     * The default action when none specified.
     */
    const DEFAULT_ACTION = 'default';

    /**
     * Gets the raw url
     *
     * @var mixed
     */
    public $url;

    /**
     * Gets the path (no parms)
     *
     * @var mixed
     */
    public $path;

    /**
     * Gets an array of path parts.
     *
     * @var array
     */
    public $parts;

    /**
     * Gets the string name of the application.
     *
     * @var string
     */
    public $app;

    /**
     * Gets the name of the controller.
     *
     * @var string
     */
    public $controller;

    /**
     * Gets the name of the action.
     *
     * @var string
     */
    public $action;

    /**
     * Gets the action parms
     *
     * @var array
     */
    public $parms;

    /**
     * Gets the populated get.
     *
     * You must call $this->populateGetFromRequestUri()
     * to populate this property. Otherwise, it's an empty array
     *
     * @var array
     */
    public $get;

    /**
     * The user data object. Default is an empty object.
     *
     * @var object
     */
    private $userObj;

    /**
     *  Class constructor
     *
     * @param array $apps
     * @param string|null $url
     */
    public function __construct(string $app, ?string $url)
    {
        $this->app = $app;
        $this->get = [];
        $this->userObj = (object)[];

        /* gets a prepared url */
        $this->url = $this->path = $this->getPreparedUrl($url);

        $this->parms = [];

        $parts = preg_split('@/@', $this->url, -1, PREG_SPLIT_NO_EMPTY);

        $this->controller = self::DEFAULT_CONTROLLER;
        $this->action = self::DEFAULT_ACTION;

        /* Get the first part (if it exists) If it's a parm, the controller stays at default */
        if (count($parts) > 0)
        {
            $value = array_shift($parts);
            if (strpos($value, ':') !== false)
            {
                $this->parms[] = $value;
            }
            else
            {
                $this->controller = $value;
            }
        }

        /**
         * Separate action from parms
         *
         * |app-----------|action-|rest are parms
         * pub.example.com/display/id:45
         *
         * |app-------|rest are parms (action is default)
         * example.com/mode:full/id:45
         *
         * Once a parm has been detected, the rest are parms. The action is either
         * what was in the url before the parms or the default action.
         */
        while (count($parts) > 0)
        {
            $value = array_shift($parts);
            if (strpos($value, ':') !== false || !empty($this->parms))
            {
                $this->parms[] = $value;
            }
            else
            {
                $this->action = $value;
            }
        }

        /* Remove parms from path */
        if (count($this->parms) > 0)
        {
            $this->path = substr($this->url, 0, strpos($this->url, $this->parms[0]) - 1);
        }

        $this->path = empty($this->path) ? '/' : $this->path;

        $this->parts = preg_split('@/@', $this->path, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Injects the user data object.
     *
     * @param object
     */
    public function injectUserObject(object $userObj)
    {
        $this->userObj = $userObj;
    }

    /**
     * Gets the user data object
     *
     * @return object;
     */
    public function getUserObject() : object
    {
        return $this->userObj;
    }

    /**
     * Pushes a parameter onto the request
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function pushParameter($name, $value)
    {
        $this->parms[] = "$name:$value";
    }

    /**
     * Gets a named parameter
     *
     * @param string $name The plain name of the parm, eg. 'id'
     * @param mixed $default Default value ti return if paramater doesn't exist
     *
     * @return mixed
     */
    public function getParameterByName($name, $default = null)
    {
        if (empty($this->parms) || empty($name)) return $default;

        $name .= ':';
        $nlen = strlen($name);

        for ($k = 0; $k < count($this->parms); $k++)
        {
            if (substr($this->parms[$k], 0, $nlen) == $name)
            {
                return substr($this->parms[$k], $nlen);
            }
        }
        return $default;
    }

    /**
     * Gets a parameter by its index position
     *
     * @param int $idx
     * @param mixed $default
     */
    public function getParameterByIndex(int $idx, $default = null)
    {
        return ($idx >= 0 && $idx < count($this->parms)) ? $this->parms[$idx] : $default;
    }

    /**
     * Gets the request parameters as a string
     *
     * @return string|null
     */
    public function getParameterString() : ?string
    {
        if (count($this->parms))
        {
            $result = '';
            foreach($this->parms as $parm)
            {
                $result .= "/$parm";
            }
            return $result;
        }
        return null;
    }

    /**
     * Populates $this->get from $_SERVER['REQUEST_URI'].
     *
     * This is an opt-in functionality. If you don't call this method,
     * $this->get will always be an empty array
     */
    public function populateGetFromRequestUri()
    {
        $this->get = [];
        $uri = $_SERVER['REQUEST_URI'];
        if (!empty($uri))
        {
            $qpos = strpos($uri, '?');
            if ($qpos !== false)
            {
                $qs = substr($uri, $qpos + 1);
                $parts = explode('&', $qs);

                foreach ($parts as $part)
                {
                    $arg = explode('=', $part);
                    $this->get[$arg[0]] = urldecode($arg[1]);
                }
            }
        }
    }

    /**
     * Gets a prepared url, eliminate false controller hit on https:/site.com/?parm=something
     */
    private function getPreparedUrl($url): string
    {
        /* nothing to prepare */
        if (empty($url)) return '';
        /* means we got a root request (no path) with ?parm=something */
        if (strpos($url, '=') !== false) return '';
        /* The ? character never arrives here, nginx */
        return $url;
    }
}
?>