<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Provides templating services
*
* @author Victor D. Sandiego
*/
class CoreView implements TranslatorAwareInterface
{
  /* The default section key when including another template piece */
  public const DEF_KEY = 'main1';

  /* The default main template file name */
  public const DefaultMainTemplate = 'main.html';

  /* CoreController */
  private $core;

  /* string */
  private $app;

  /* arrays */
  private $script;
  private $obj;
  private $loop;
  private $include;
  private $conditional;

  /* object */
  private $meta;

  /* string */
  private $productTitle;

  /* string, full path to the root of the view directory */
  private $viewRoot;

  /* string, full path to the root of the auto include directory */
  private $autoIncRoot;

  /* string, main template set to self::DefaultMainTemplate in constructor */
  private $mainTemplate;

  /* regex */
  private $rxFile      = '#{%inc:([a-zA-Z0-9]*)%}#';
  private $rxAuto      = '#{%auto:([a-zA-Z0-9\.]*)%}#';
  private $rxCondition = '#{%if:([a-zA-Z]*)%}#';
  private $rxLoop      = '#{%loop:([a-zA-Z0-9]*)%}#';
  private $rxConfig    = '#{%cfg:([a-zA-Z_]*)%}#';
  private $rxMeta      = '#{%meta:([a-zA-Z]*)%}#';
  private $rxLanguage  = '#{%lang:([a-zA-Z0-9_?.]*)%}#';
  private $rxServer    = '#{%svr:([a-zA-Z_]*)%}#';
  private $rxSystem    = '#{%sys:([a-zA-Z_]*)%}#';
  private $rxCookie    = '#{%cook:([a-zA-Z0-9]*)%}#';
  private $rxUserObj   = '#{%obj:([a-zA-Z0-9_]*):?([a-zA-Z0-9_]*)%}#';

  /**
  * {%inc:key%}       include file (by key)
  * {%auto:file%}     include file (fixed name)
  * {%if:key%}        conditional include
  * {%loop:key%}      loop
  * {%cfg:key%}       configuration val
  * {%meta:key%}      meta data val
  * {%svr:key%}       server var
  * {%sys:key%}       system var
  * {%cook:key%}      cookie val
  * {%dom:key%}       domain val
  * {%obj:key:prop%}  user object (named key)
  * {%obj:prop%}      user object (default key)
  */

  /**
  * Gets or sets a truthy value that specifies whether debug mode is active
  *
  * @var int
  */
  public $debug = 0;

  /**
  * Gets or sets a boolean value that determines if the html output will be minified
  *
  * @var bool
  */
  public $minifyHtml = false;

  /**
  * Gets or sets a boolean value that determines if view root
  * appends the name of the app. The default is true
  *
  * @var bool
  */
  public $viewRootPerApp = true;

  /**
  * Gets or sets a boolean value that determines if auto include root
  * appends the name of the app. The default is true
  *
  * @var bool
  */
  public $autoIncRootPerApp = true;

  /**
  * @var \Restless\Core\Translator
  */
  protected $translator;

  /**
  * Class constructor
  *
  * @param CoreController $core
  * @param string $app
  */
  public function __construct(CoreController $core, string $app)
  {
    $this->core = $core;
    $this->app = $app;
    $this->script = [];
    $this->obj = [];
    $this->loop = [];
    $this->include = [];
    $this->conditional = [];
    $this->meta = (object)[];
    /* this must be updated by caller using setRootViewDir(..) */
    $this->viewRoot = '';
    $this->autoIncRoot = '';
    $this->mainTemplate = self::DefaultMainTemplate;
    $this->productTitle = '';
  }

  /**
  * Returns a new CoreView based on this instance with all placeholder properties
  * reset and the template file (if supplied) set to the specified value.
  *
  * This method is a shortcut for obtaining a clone and setting the clone's template.
  *
  * @param string|null $template
  * @return CoreView
  */
  public function clone($template = null) : self
  {
    $view = clone $this;
    if ($template) $view->setTemplateFile($template);
    return $view;
  }

  public function __clone()
  {
    $this->script = [];
    $this->obj = [];
    $this->loop = [];
    $this->include = [];
    $this->conditional = [];
    $this->meta = (object)[];
  }

  /****************** TRANSLATOR *****************/

  /**
  * Sets the specified translator object
  */
  public function setTranslatorObject(Translator $obj)
  {
    $this->translator = $obj;
  }

  /**
  * Gets the translator object, or null if not set.
  *
  * @return Translator|null
  */
  public function getTranslatorObject() : ?Translator
  {
    return $this->translator;
  }

  /**
  * Gets a boolean that indicates whether a translator object has been set.
  */
  public function isTranslatorAware() : bool
  {
    return $this->translator instanceof Translator;
  }

  /**** View directories and template files ****/

  /**
  * Sets the full file system path to the root view directory.
  *
  * This method sets the root view dir and the root auto include dir
  * to the same value. If the auto include root must be different than
  * the root view, call setRootAutoIncludeDir AFTER calling this method
  *
  * @param string $value
  *
  * @return \Restless\Core\CoreView
  */
  public function setRootViewDir(string $value) : self
  {
    $this->viewRoot = $value;
    $this->autoIncRoot = $value;
    return $this;
  }

  /**
  * Sets the full file system path to the root auto include directory
  *
  * @param string $value
  *
  * @return \Restless\Core\CoreView
  */
  public function setRootAutoIncludeDir(string $value) : self
  {
    $this->autoIncRoot = $value;
    return $this;
  }

  /**
  * Sets the application name
  *
  * This method is only needed if you want to override the application name
  * that gets set in the constructor
  *
  * @param string $value
  * @return \Restless\Core\CoreView
  */
  public function setApplication(string $value) : self
  {
    $this->app = $value;
    return $this;
  }

  /**
  * Sets the main template file name. Only needed if you want to override the default.
  *
  * @param string $value
  * @return \Restless\Core\CoreView
  */
  public function setTemplateFile(string $value) : self
  {
    $this->mainTemplate = $value;
    return $this;
  }

  /**
  * Gets the full path to view directory for the specified app or (if null) the current app.
  *
  * @param string|null $app
  * @return string
  */
  public function getViewDirectory(?string $app = null): string
  {
    return
      ($this->viewRootPerApp || $app != null) ?
        sprintf('%s/%s', $this->viewRoot, $app ?? $this->app) :
        $this->viewRoot;
  }

  /**
  * Gets the full path to auto include directory for the specified app or (if null) the current app.
  *
  * @param string|null $app
  * @return string
  */
  public function getAutoIncludeDirectory(?string $app = null) : string
  {
    return
      ($this->autoIncRootPerApp || $app != null) ?
        sprintf('%s/%s', $this->autoIncRoot, $app ?? $this->app) :
        $this->autoIncRoot;
  }

  /**
  * Gets the full path to a file in the common application
  *
  * @param string $fileName base file name, no path
  *
  * @return string The fully qualified name
  */
  public function getCommonFileName(string $fileName): string
  {
    return sprintf('%s/%s', $this->getViewDirectory(AppCollection::COMMON_KEY), $fileName);
  }

  /**
  * Gets the full path to a view file of the specified application
  *
  * @param string $app name of app
  * @param string $fileName base file name, no path
  *
  * @return string The fully qualified name
  */
  public function getApplicationFileName(string $app, string $fileName) : string
  {
    return sprintf('%s/%s', $this->getViewDirectory($app), $fileName);
  }

  /**
  * Gets the full path to a view file of the current application
  *
  * @param string $fileName base file name, no path
  * @return string
  */
  public function getCurrentApplicationFileName(string $fileName) : string
  {
    return $this->getApplicationFileName($this->app, $fileName);
  }

  /**
  * Sets the product title that can then be used in the setMetaTitle()
  * and setMetaDescription() methods to provide title substitution.
  *
  * For example, you can call setMetaDescription('Provides something for [title]');
  * the string '[title]' will be replaced with the value set in this method.
  *
  * @param string $value
  * @return \Restless\Core\CoreView
  */
  public function setProductTitle(string $value): self
  {
    $this->productTitle = $value;
    return $this;
  }

  /**
  * Sets the title used in meta data.
  *
  * @param string $value
  * @return \Restless\Core\CoreView
  */
  public function setMetaTitle(string $value): self
  {
    $this->meta->title = str_replace('[title]', $this->productTitle, $value);
    return $this;
  }

  /**
  * Sets the description used in meta data.
  *
  * @param string $value
  * @return \Restless\Core\CoreView
  */
  public function setMetaDescription(string $value): self
  {
    $this->meta->description = str_replace('[title]', $this->productTitle, $value);
    return $this;
  }

  /**
  * Sets the specified meta data key to the specified value
  *
  * @param string $key
  * @param string $value
  */
  public function setMetaValue(string $key, string $value)
  {
    $this->meta->$key = $value;
  }

  /**
  * Includes another file.
  *
  * @param string $fileIndex
  * @param string $fileName
  */
  public function includeFile(string $key, string $file): void
  {
    if (!$key || !$file) ViewException::throwViewException('Include: Invalid parameter(s)');
    $this->include[$key] = $file;
  }

  /**
  * Conditions the display of a block.
  *
  * @param string $key
  * @param mixed $condition
  */
  public function makeConditional(string $key, $condition)
  {
    $this->conditional[$key] = $condition;
  }

  /**
  * Inserts an abitrary object (or array) whose properties will
  * be inserted into the template
  *
  * @param array|object $obj
  * @param string $key An optional key. If omitted, defaults to 'def'
  */
  public function insertObj($obj, string $key = 'def')
  {
    if (is_array($obj)) $obj = (object)$obj;

    if (is_object($obj))
    {
      $key = $key ?: 'def';
      $this->obj[$key] = $obj;
    }
  }

  /**
  * Inserts an array of data that provides a looping construct. Before insertion,
  * elements of $data are converted to objects. If a callback is provided, each element
  * of $data is passed to the callback which can modify values or take other action.
  * If the callback returns false, the element is skipped.
  *
  * @param array $data
  * @param string $key
  * @param callable|null $callback
  */
  public function insertLoop(array $data, string $key, $callback = null)
  {
    array_walk($data, function(&$v) { $v = (object)$v;});
    $this->loop[$key] = [$data, $callback];
  }

  /***************************************/
  /* Render                              */
  /***************************************/

  /**
  * Includes the specified file using the default key and presents
  *
  * @param string $file
  */
  public function render(string $file)
  {
    $this->includeFile(self::DEF_KEY, $file);
    $this->present();
  }

  public function present()
  {
    echo $this->getPresent();
  }

  public function getPresent() : string
  {
    $mainPath = $this->getFileName($this->mainTemplate);
    $mainStr = $this->getFileContents($mainPath);

    if ($mainStr === false)
    {
      throw new ViewException("Template file: {$this->getSafeDisplayPath($mainPath)} not found");
    }

    $this->merge($mainStr);
    if ($this->minifyHtml) $this->minify($mainStr);

    return $mainStr;
  }

  /***************************************/
  /* File system. File names, read files */
  /***************************************/

  private function getFileName($baseName) : string
  {
    $fc = substr($_SERVER['DOCUMENT_ROOT'], 0, 1);
    return (strpos($baseName, $fc) === 0) ? $baseName : $this->getCurrentApplicationFileName($baseName);
  }

  private function getSafeDisplayPath(string $path): string
  {
    return ($this->debug) ? $path : basename($path);
  }

  private function getFileContents($fileName)
  {
    return @file_get_contents($fileName);
  }

  /***************************************/
  /* Merge methods                       */
  /***************************************/
  private function merge(&$s)
  {
    $this->mergeKeyedFiles($s);
    $this->mergeAutoFiles($s);

    $this->mergeConditionals($s);
    $this->mergeLoops($s);
    $this->mergeApps($s);

    $this->mergeUserObjects($s);
    $this->mergeObject($this->core->getConfig(), $this->rxConfig, $s);
    $this->mergeObject($this->core->server, $this->rxServer, $s);
    $this->mergeObject($this->core->system, $this->rxSystem, $s);
    $this->mergeObject($this->core->cookie, $this->rxCookie, $s);
    $this->mergeObject($this->meta, $this->rxMeta, $s);

    $this->mergeLanguage($s);
    $this->mergeScript($s);
  }

  /***************************************/
  /* Merge keyed files (dynamic name)    */
  /***************************************/
  private function mergeKeyedFiles(&$s)
  {
    $replace= [];
    $out= [];

    while (preg_match_all($this->rxFile, $s, $out))
    {
      for ($k= 0; $k < count($out[1]); $k++)
      {
        $key = $out[1][$k];
        $fileStr = null;

        if ($this->include[$key])
        {
          $file = $this->include[$key];
          $filePath = $this->getFileName($file);
          $fileStr = $this->getFileContents($filePath);

          if ($fileStr === false)
          {
            $fileStr = "Include file: {$this->getSafeDisplayPath($filePath)} not found for [$key]";
          }
        }
        $replace[$k]= $fileStr;
      }
      $s = str_replace($out[0], $replace, $s);
    }
  }

  /***************************************/
  /* Merge auto files (fixed name)       */
  /***************************************/
  private function mergeAutoFiles(&$s)
  {
    $replace= [];
    $out= [];
    while (preg_match_all($this->rxAuto, $s, $out))
    {
      for ($k= 0; $k < count($out[1]); $k++)
      {
        $file = $out[1][$k];
        $fileStr = null;
        $filePath = "{$this->getAutoIncludeDirectory()}/$file";
        $fileStr = $this->getFileContents($filePath);

        if ($fileStr === false)
        {
          $fileStr = "Auto file: {$this->getSafeDisplayPath($filePath)} not found";
        }

        $replace[$k]= $fileStr;
      }
      $s = str_replace($out[0], $replace, $s);
    }
  }

  /***************************************/
  /* Merge conditionals                  */
  /***************************************/
  private function mergeConditionals(&$s)
  {
    $out= [];
    /* no matches, no cry */
    if (!preg_match_all($this->rxCondition, $s, $out, PREG_OFFSET_CAPTURE)) return;

    /* $out[0] = array raw with complete tag, $out[1] = array with captures */
    $raw = $out[0];
    $parms = [];
    $startIdx = 0;
    $slen = strlen($s);

    while ($startIdx < count($raw))
    {
      if (empty($raw[$startIdx][2]))
      {
        $key = $out[1][$startIdx][0];
        $startIdx = $this->createConditionalParms($key, $startIdx, $raw, $parms, $slen);
      }
      else
      {
        $startIdx++;
      }
    }

    /* need to sort by offset before looping through and making replacements */
    $this->sortParms($parms);

    $adj = 0;
    foreach ($parms as $parm)
    {
      $s = substr_replace($s, '', $parm[0] - $adj, $parm[1]);
      $adj += $parm[1];
    }
  }

  private function createConditionalParms(string $key, int $startIdx, array &$raw, array &$parms, int $slen) : int
  {
    $startOffset = $raw[$startIdx][1];

    $endIdx = $this->getMatchingEndIndex($startIdx, $raw);

    if ($endIdx > 0)
    {
      $endOffset = $raw[$endIdx][1];
      $tagLen = strlen($raw[$endIdx][0]);
    }
    else
    {
      /* markup error. no matching end tag. everything from here goes away */
      $endIdx = count($raw) - 1;
      $endOffset = $startOffset;
      $tagLen = $slen;
      $this->conditional[$key] = false;
    }

    if ($this->conditional[$key])
    {
      /* remove the tags */
      $parms[] = [$startOffset, $tagLen, $key];
      $parms[] = [$endOffset, $tagLen, $key];
      $startIdx++;
    }
    else
    {
      /* remove the entire block */
      $len = $endOffset - $startOffset + $tagLen;
      $parms[] = [$startOffset, $len, $key];
      /* skip over all nested; they don't count because the outer block is gone */
      $startIdx = $endIdx + 1;
    }

    /* mark the end index as processed and return the next start index */
    $raw[$endIdx][2] = 1;
    return $startIdx;
  }

  private function getMatchingEndIndex(int $startIdx, array $raw) : int
  {
    for ($idx = $startIdx + 1; $idx < count($raw); $idx++)
    {
      if ($raw[$idx][0] == $raw[$startIdx][0]) return $idx;
    }
    /* no matching end index = error in markup */
    return -1;
  }

  private function sortParms(&$parms)
  {
    /* need to sort by offset before looping through and making replacements */
    usort($parms, function($a, $b)
    {
      if ($a[0] == $b[0]) return 0;
      return ($a[0] < $b[0]) ? -1 : 1;
    });
  }

  /***************************************/
  /* Merge loops                         */
  /***************************************/
  private function mergeLoops(&$s)
  {
    $out= [];
    /* no matches, no cry */
    if (!preg_match_all($this->rxLoop, $s, $out, PREG_OFFSET_CAPTURE)) return;

    /* $out[0] = array raw with complete tag, $out[1] = array with captures */
    $raw = $out[0];
    $parms = [];
    $startIdx = 0;

    while ($startIdx < count($raw))
    {
      if (empty($raw[$startIdx][2]))
      {
        $key = $out[1][$startIdx][0];
        $startIdx = $this->createLoopParms($key, $startIdx, $raw, $parms);
      }
      else
      {
        $startIdx++;
      }
    }

    /* need to sort by offset before looping through and making replacements */
    $this->sortParms($parms);

    $adj = 0;
    foreach ($parms as $parm)
    {
      $key = $parm[2];
      if ($parm[3] == 'tag' || empty($this->loop[$key]))
      {
        $s = substr_replace($s, '', $parm[0] - $adj, $parm[1]);
        $adj += $parm[1];
      }
      else
      {
        $template = $una = substr($s, $parm[0] - $adj, $parm[1]);
        $rx = "#{%$key:([a-zA-Z0-9_]*)%}#";
        $looped = '';

        foreach ($this->loop[$key][0] as $obj)
        {
          $process = true;
          if (is_callable($this->loop[$key][1]))
          {
            $process = call_user_func_array($this->loop[$key][1], [$obj]);
          }
          if ($process !== false)
          {
            $this->mergeObject($obj, $rx, $template);
            $looped .= $template;
            $template = $una;
          }
        }

        $s = substr_replace($s, $looped, $parm[0] - $adj, $parm[1]);
        $adj += $parm[1] - strlen($looped);
      }
    }
  }

  private function createLoopParms(string $key, int $startIdx, array &$raw, array &$parms) : int
  {
    $startOffset = $raw[$startIdx][1];
    $endIdx = $this->getMatchingEndIndex($startIdx, $raw);

    /* markup error. no matching end tag. */
    if ($endIdx == -1) return count($raw);

    $endOffset = $raw[$endIdx][1];
    $tagLen = strlen($raw[$endIdx][0]);

    /* remove the tags */
    $parms[] = [$startOffset, $tagLen, $key, 'tag'];
    $parms[] = [$endOffset, $tagLen, $key, 'tag'];

    /* the block to be looped */
    $len = $endOffset - $startOffset - $tagLen;
    $parms[] = [$startOffset + $tagLen, $len, $key, 'loop'];

    /* mark the end index as processed and return the next start index */
    $raw[$endIdx][2] = 1;

    return $startIdx + 1;
  }

  /***************************************/
  /* Merge apps and objects              */
  /***************************************/
  private function mergeApps(&$s)
  {
    $apps = $this->core->getAppCollection();
    if ($apps instanceof AppCollection)
    {
      $search = [];
      $replace = [];

      foreach ($apps as $key => $app)
      {
        if ($app instanceof App)
        {
          if ($key == 'current') $key = 'curr';

          foreach($app as $prop => $dummy)
          {
            $search[] = "{%app:$key:$prop%}";
            $replace[] = $app->$prop;
          }
        }
      }

      $s = str_replace($search, $replace, $s);
    }
  }

  private function mergeObject(?object $obj, string $rx, &$s) : bool
  {
    if ($obj != null)
    {
      $replace= [];
      $out= [];

      if (preg_match_all($rx, $s, $out))
      {
        for ($k= 0; $k < count($out[1]); $k++)
        {
          $key = $out[1][$k];
          $replace[$k]= $obj->$key;
        }
        $s = str_replace($out[0], $replace, $s);
        return true;
      }
    }
    return false;
  }

  private function mergeLanguage(&$s)
  {
    $out = [];
    /* no matches, no cry */
    if (!preg_match_all($this->rxLanguage, $s, $out)) return;

    $replace = [];

    if ($this->isTranslatorAware())
    {
      for ($k=0; $k < count($out[0]); $k++)
      {
        $key = $out[1][$k];
        $replace[$k] = $this->translator->getWithFallback($key);
      }
    }

    $s = str_replace($out[0], $replace, $s);
  }

  private function mergeUserObjects(&$s)
  {
    $out = [];
    /* no matches, no cry */
    if (!preg_match_all($this->rxUserObj, $s, $out)) return;

    for ($k=0; $k < count($out[0]); $k++)
    {
      if (!$out[2][$k])
      {
        $out[2][$k] = $out[1][$k];
        $out[1][$k] = 'def';
      }
    }

    $replace = [];
    for ($k=0;$k < count($out[0]); $k++)
    {
      $key = $out[1][$k];
      $prop = $out[2][$k];
      $replace[$k] = $this->obj[$key]->$prop;
    }

    $s = str_replace($out[0], $replace, $s);
  }

  private function mergeScript(&$s)
  {
    $js = '';
    foreach ($this->script as $script)
    {
      $js .= "<script src=\"$script\"></script>\r\n";
    }

    if ($js)
    {
      $s = str_replace('</head>', "$js</head>", $s);
    }
  }

  /***************************************/
  /* Minify                              */
  /***************************************/
  private function minify(&$s)
  {
    // main replacments, remove leading and trailing spaces, remove comments
    $replace =
    [
      '/^([\t ])+/m' => '',
      '/([\t ])+$/m' => '',
      '/<!--(.|\s)*?-->/' => '',
    ];
    $s = preg_replace(array_keys($replace), array_values($replace), $s);

    if ($this->minifyHtml > 1)
    {
      $s = str_replace("\n",'', $s);
    }
  }
}
?>