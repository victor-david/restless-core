<?php declare(strict_types=1);
namespace Restless\Core;

/**
* Provides a wrapper around open ssl functions.
*/
class OpenSsl
{
  private $key;
  private $hashMethod = 'sha256';
  private $encryptMethod = 'aes-256-cbc';

  /**
   * Class constructor
   *
   * @param string $encryptionKey key in HEX encoding
   */
  function __construct(string $key)
  {
    $this->key = openssl_digest($key, $this->hashMethod, true);
  }

  /**
  * Encrypts the specified string.
  *
  * @param string $str
  * @return string|null
  */
  public function encrypt(string $str) : ?string
  {
    $iv = random_bytes(openssl_cipher_iv_length($this->encryptMethod));

    if ($encrypted = openssl_encrypt($str, $this->encryptMethod, $this->key, 0, $iv))
    {
      return bin2hex($iv) . ':' . $encrypted;
    }
    return null;
  }

  /**
  * Decrypts the specified string.
  *
  * @param string $str
  * @return string|null
  */
  public function decrypt(string $str) : ?string
  {
    $parts = explode(':', $str);
    $iv = hex2bin($parts[0]);
    $encrypted = $parts[1];

    if ($decrypted = openssl_decrypt($encrypted, $this->encryptMethod, $this->key, 0, $iv))
    {
      return $decrypted;
    }
    return null;
  }

  /**
  * Destroys the key specified in the constructor in accordance
  * with the Paranoid Programmer Act of 2021
  */
  public function destroy()
  {
    $this->key = null;
  }
}
?>