<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Defines methods for an object that implements translation functionality.
*/
interface TranslatorAwareInterface
{
  /**
  * Sets the specified translator object
  */
  public function setTranslatorObject(Translator $obj);

  /**
  * Gets the translator object, or null if not set.
  *
  * @return Translator|null
  */
  public function getTranslatorObject() : ?Translator;

  /**
  * Gets a boolean that indicates whether a translator object has been set.
  *
  * @return bool
  */
  public function isTranslatorAware() : bool;
}
?>