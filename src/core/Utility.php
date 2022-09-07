<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Provides static utility methods
*/
class Utility
{
  /**
   * Sets the content type by emitting a Content-Type header
   *
   * @param string $contentType The content type, i.e. 'application/json'
   * @param string $charSet The char set or omit for default of utf-8
  */
  public static function setContentType($contentType, $charSet = 'utf-8')
  {
    if ($contentType)
    {
      @header(sprintf('Content-Type: %s; charset=%s', $contentType, $charSet), true);
    }
  }

  /**
  * Sets content type to application/json
  */
  public static function setJsonContentType()
  {
    $this->setContentType('application/json');
  }

  /**
  * Emits headers to disable client side caching
  */
  public static function disableCache()
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
  public static function enableCache(int $minutes)
  {
    $seconds = max(abs($minutes) * 60, 60);
    @header("cache-control: public, max-age=$seconds, s-maxage=$seconds, immutable");
  }

  /**
   * Emits a header and terminates processing.
   *
   * This method enables you to halt processing and return a header to the browser.
   * Example: Utility::terminate(403, 'Forbidden');
  */
  public static function terminate($code, $msg)
  {
    $header = sprintf('%s %s %s', $_SERVER['SERVER_PROTOCOL'], $code, $msg);
    @header($header, true, $code);
    die($msg);
  }
}
?>