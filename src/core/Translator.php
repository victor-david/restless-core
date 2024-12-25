<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Represents the base class for obtaining localized strings via gettext.
 * This class must be inherited.
 */
abstract class Translator
{
    private $translationPath;
    private $currentLocale;
    private $currentDomain;
    private $supportedLocales;
    private $fallbackLocale;
    private $fallbackDomain;

    public const DEFAULT_LOCALE = 'en_US';
    public const DEFAULT_DOMAIN = 'main';

    public function __construct(string $translationPath)
    {
        $this->translationPath = $translationPath;
        $this->initializeSupportedLocales();
        $this->fallbackLocale = self::DEFAULT_LOCALE;
        $this->fallbackDomain = self::DEFAULT_DOMAIN;
        $this->setLocale(self::DEFAULT_LOCALE);
    }

    /**
     * Gets the current locale.
     *
     * @return string
     */
    public function getLocale() : string
    {
        return $this->currentLocale;
    }

    /**
     * Gets the current domain
     *
     * @return string
     */
    public function getDomain() : string
    {
        return $this->currentDomain;
    }

    /**
     * Gets an array of the supported locales
     *
     * @return array
     */
    public function getSupportedLocales() : array
    {
        return $this->supportedLocales;
    }

    /**
     * Sets the locale and domain
     *
     * @param string $locale
     * @param string $domain
     */
    public function setLocale(string $locale, string $domain = self::DEFAULT_DOMAIN) : bool
    {
        $this->currentLocale = $this->getSupportedLocale($locale);
        $this->currentDomain = strtolower($domain);

        putenv("LANG=" . $this->currentLocale);
        setlocale(LC_ALL, $this->currentLocale);

        bindtextdomain($this->currentDomain, $this->translationPath);
        bind_textdomain_codeset($this->currentDomain, 'UTF-8');
        textdomain($this->currentDomain);
        return true;
    }

    /**
     * Sets the locale and domain to use when a translation does not exist,
     * or when attempting to set a locale that doesn't exist.
     *
     * @param string $fallbackLocale
     * @param string $fallbackDomain
     */
    public function setFallbackLocale(string $fallbackLocale, string $fallbackDomain = self::DEFAULT_DOMAIN)
    {
        $this->fallbackLocale = $this->getSupportedLocale($fallbackLocale);
        $this->fallbackDomain = $fallbackDomain;
    }

    /**
     * Sets the locale to the specified preset locale, if present. If the specified preset locale
     * is null (the default), or the preset locale is not a precise match to a supported locale,
     * this method attempts to set the locale according to the accept-language http header.
     *
     * When evaulating the http header, a fuzzy match may be used to determine the locale.
     * For example if the priority language id in the header is 'es' and 'es_MX' is a supported locale,
     * this method sets the locale to 'es_MX'.
     *
     * If neither the preset locale nor the http header produce a supported locale, this method does nothing.
     *
     * @param string|null $presetLocal
     * @return bool true if locale was set; otherwsie, false.
     */
    public function setAutoLocale(?string $presetLocale = null) : bool
    {
        if ($presetLocale && $this->getTransformedLocal($presetLocale) == $presetLocale)
        {
            return $this->setLocale($presetLocale);
        }

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        if ($acceptLang = $headers['accept-language'])
        {
            $parts = explode(';', $acceptLang, 2);

            $langIds = array_map(function($v)
            {
                return str_replace('-','_', $v);
            }, explode(',', $parts[0]));

            foreach($langIds as $locale)
            {
                if ($candidateLocale = $this->getTransformedLocal($locale))
                {
                    return $this->setLocale($candidateLocale);
                }
            }
        }
        return false;
    }

    /**
     * Get the translation for the specified key with a specified fallback.
     * If the key cannot be translated after trying the current locale and
     * the fallaback locale,returns the specified fallback.
     *
     * If the specifed fallback is null (the default), returns an indicator
     * that the key is missing. If you want the fallback to be empty, pass an empty string.
     *
     * @param mixed $key
     * @param mixed $fallback
     */
    public function getWithFallback($key, $fallback = null)
    {
        $fallback = $fallback ?? "[$key missing]";
        $keyTranslated = $this->$key;
        return ($keyTranslated == $key) ? $fallback : $keyTranslated;
    }

    /**
     * Gets a plural string using an automatic plural key,
     * the specified key with an 's' concatentated.
     *
     * @param string $key The plural key is taken from this value by adding an 's' character
     * @param int $count
     * @return string
     */
    public function getPlural(string $key, int $count)
    {
        return $this->getPlurals($key, "{$key}s", $count);
    }

    /**
     * Gets a plural string with specified singular and plural keys.
     *
     * @param string $key1 Singular key
     * @param string $key2 Plural key
     * @param int $count Number of items
     * @return string
     */
    public function getPlurals(string $key1, string $key2, int $count) : string
    {
        return sprintf(ngettext($key1, $key2, $count), $count);
    }

    /**
     * Gets the translation for the specified key with fallback to the default locale.
     * If the key cannot be translated, returns the key.
     *
     * @param mixed $key
     */
    public function __get($key)
    {
        $keyTranslated = gettext($key);

        if ($keyTranslated == $key && ($this->currentLocale != $this->fallbackLocale || $this->currentDomain != $this->fallbackDomain))
        {
            $locale = $this->currentLocale;
            $domain = $this->currentDomain;
            $this->setLocale($this->fallbackLocale, $this->fallbackDomain);
            $keyTranslated = gettext($key);
            $this->setLocale($locale, $domain);
        }
        return $keyTranslated;
    }

    private function initializeSupportedLocales()
    {
        $this->supportedLocales = [];
        $dirList = scandir($this->translationPath);
        foreach($dirList as $item)
        {
            if ($item[0] != '.' && is_dir($this->translationPath . DIRECTORY_SEPARATOR . $item))
            {
                $this->supportedLocales[] = $item;
            }
        }
    }

    private function getSupportedLocale(string $requestedLocale) : string
    {
        return $this->getTransformedLocal($requestedLocale) ?? $this->fallbackLocale;
    }

    private function getTransformedLocal($requestedLocale) : ?string
    {
        if ($requestedLocale)
        {
            if (in_array($requestedLocale, $this->supportedLocales))
            {
                return $requestedLocale;
            }

            foreach ($this->supportedLocales as $locale)
            {
                if (substr($locale, 0, 2) == strtolower(substr($requestedLocale, 0 , 2)))
                {
                    return $locale;
                }
            }
        }
        return null;
    }
}
?>