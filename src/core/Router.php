<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Main entry point for routing and dispatching
*
* @author Victor D. Sandiego
*/
final class Router
{
  /**
  * Default application namespace
  */
  public const DEFAULT_APP_NAMESPACE = 'App';

  /**
  * Default controller namespace
  */
  public const DEFAULT_CONTROLLER_NAMESPACE = 'Controller';

  private $topLevel;            // top level namespace
  private $app;                 // app name
  private $appNamespace;        // application namespace
  private $controllerNamespace; // controller namespace

  /**
  * Gets the request object
  *
  * @var CoreRequest
  */
  public $request;

  /**
  * Class constructor
  *
  * Controllers are located according to the specifications passed here
  *
  * @param string $topLevelNamespace Top level namespace
  * @param string $app The app name
  * @param string $appNamespace The application namespace
  * @param string $controllerNamespace The controller namespace
  *
  */
  public function __construct(
    string $topLevelNamespace,
    string $app,
    ?string $appNamespace = self::DEFAULT_APP_NAMESPACE,
    ?string $controllerNamespace = self::DEFAULT_CONTROLLER_NAMESPACE)
  {
    $this->topLevel = $topLevelNamespace;
    $this->app = $app;
    $this->appNamespace = $appNamespace;
    $this->controllerNamespace = $controllerNamespace;
  }

  /**
  * Dispatch the route, creating the controller object and running the
  * action method
  *
  * @param string $url The route URL
  *
  * @return void
  */
  public function dispatch($url)
  {
    $this->request = new CoreRequest($this->app, $url);

    $controller = $this->getControllerPath($this->request->app, $this->request->controller);

    if (class_exists($controller))
    {
      $controllerObj = new $controller($this->request);

      // $this->dispatchInitializationObject($controllerObj);

      $action = $this->convertToCamelCase($this->request->action);

      if (preg_match('/action$/i', $action) == 0)
      {
        $controllerObj->$action();
      }
      else
      {
        ControllerException::throwMethodDirectException("Method [$action] in controller [$controller] cannot be called directly", $this->request);
      }
    }
    else
    {
      ControllerException::throwControllerNotFoundException("Controller [$controller] not found", $this->request);
    }
  }

  /**
  * This method enables a initialization object to be invoked
  * that can perform initialization tasks common to all applications
  * as well as those that apply to a particular app.
  *
  * The class must be in the common control path, be named 'Initialize',
  * and have a method named 'init'. This method receives the controller
  * that is about to be executed and $this->request which contains the
  * app, controller name, and action. Initialization can use these
  * properties to make decisions about what to init.
  *
  * The common Initialize class should be a basic class.
  * It should not derive from CoreContoller.
  *
  * @param CoreController $controller
  */
  private function dispatchInitializationObject(CoreController $controller)
  {
    $init = $this->getControllerPath('common', 'initialize');

    if (class_exists($init))
    {
      $initObj = new $init();
      if (method_exists($initObj, 'init'))
      {
        $initObj->init($controller, $this->request);
      }
    }
  }

  private function getControllerPath(string $app, string $controller)
  {
    $app = $this->convertToStudlyCaps($app);
    $controller = $this->convertToStudlyCaps($controller);

    return
      $this->topLevel .
      ((!empty($this->appNamespace)) ? "\\{$this->appNamespace}" : '') .
      "\\$app" .
      ((!empty($this->controllerNamespace)) ? "\\{$this->controllerNamespace}" : '') .
      "\\{$controller}";
  }

  /**
  * Convert the string with hyphens to StudlyCaps,
  * e.g. post-authors => PostAuthors
  *
  * @param string $string The string to convert
  *
  * @return string
  */
  private function convertToStudlyCaps($string)
  {
    return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
  }

  /**
  * Convert the string with hyphens to camelCase,
  * e.g. add-new => addNew
  *
  * @param string $string The string to convert
  *
  * @return string
  */
  private function convertToCamelCase($string)
  {
    return lcfirst($this->convertToStudlyCaps($string));
  }
}
?>