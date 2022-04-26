<?php

namespace ipl\I18n;

use FilesystemIterator;
use ipl\Stdlib\Contract\Translator;

/**
 * Translator using PHP's native [gettext](https://www.php.net/gettext) extension
 *
 * # Example Usage
 *
 * ```php
 * $translator = (new GettextTranslator())
 *     ->addTranslationDirectory('/path/to/locales')
 *     ->addTranslationDirectory('/path/to/locales-of-domain', 'special') // Could also be the same directory as above
 *     ->setLocale('de_DE');
 *
 * $translator->translate('user');
 *
 * printf(
 *     $translator->translatePlural('%d user', '%d user', 42),
 *     42
 * );
 *
 * $translator->translateInDomain('special-domain', 'request');
 *
 * printf(
 *     $translator->translatePluralInDomain('special-domain', '%d request', '%d requests', 42),
 *     42
 * );
 *
 * // All translation functions also accept a context as last parameter
 * $translator->translate('group', 'a-context');
 * ```
 *
 */
class GettextTranslator implements Translator
{
    /** @var string Default gettext domain */
    protected $defaultDomain = 'default';

    /** @var string Default locale code */
    protected $defaultLocale = 'en_US';

    /** @var array Known translation directories as array[$domain] => $directory */
    protected $translationDirectories = [];

    /** @var array Loaded translations as array[$domain] => $directory */
    protected $loadedTranslations = [];

    /** @var string Primary locale code used for translations */
    protected $locale;

    /**
     * Get the default domain
     *
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->defaultDomain;
    }

    /**
     * Set the default domain
     *
     * @param string $defaultDomain
     *
     * @return $this
     */
    public function setDefaultDomain($defaultDomain)
    {
        $this->defaultDomain = $defaultDomain;

        return $this;
    }

    /**
     * Get the default locale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set the default locale
     *
     * @param string $defaultLocale
     *
     * @return $this
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Get available translations
     *
     * @return array Available translations as array[$domain] => $directory
     */
    public function getTranslationDirectories()
    {
        return $this->translationDirectories;
    }

    /**
     * Add a translation directory
     *
     * @param string $directory Path to translation files
     * @param string $domain    Optional domain of the translation
     *
     * @return $this
     */
    public function addTranslationDirectory($directory, $domain = null)
    {
        $this->translationDirectories[$domain ?: $this->defaultDomain] = $directory;

        return $this;
    }

    /**
     * Get loaded translations
     *
     * @return array Loaded translations as array[$domain] => $directory
     */
    public function getLoadedTranslations()
    {
        return $this->loadedTranslations;
    }

    /**
     * Load a translation so that gettext is able to locate its message catalogs
     *
     * {@link bindtextdomain()} is called internally for every domain and path
     * that has been added with {@link addTranslationDirectory()}.
     *
     * @return $this
     * @throws \Exception If {@link bindtextdomain()} fails for a domain
     */
    public function loadTranslations()
    {
        foreach ($this->translationDirectories as $domain => $directory) {
            if (
                isset($this->loadedTranslations[$domain])
                && $this->loadedTranslations[$domain] === $directory
            ) {
                continue;
            }

            if (bindtextdomain($domain, $directory) !== $directory) {
                throw new \Exception(sprintf(
                    "Can't register domain '%s' with path '%s'",
                    $domain,
                    $directory
                ));
            }

            bind_textdomain_codeset($domain, 'UTF-8');

            $this->loadedTranslations[$domain] = $directory;
        }

        return $this;
    }

    /**
     * Get the primary locale code used for translations
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Setup the primary locale code to use for translations
     *
     * Calls {@link loadTranslations()} internally.
     *
     * @param string $locale Locale code
     *
     * @return $this
     * @throws \Exception If {@link bindtextdomain()} fails for a domain
     */
    public function setLocale($locale)
    {
        putenv("LANGUAGE=$locale.UTF-8");
        setlocale(LC_ALL, $locale . '.UTF-8');

        $this->loadTranslations();

        textdomain($this->getDefaultDomain());

        $this->locale = $locale;

        return $this;
    }

    /**
     * Encode a message with context to the representation used in .mo files
     *
     * @param string $message
     * @param string $context
     *
     * @return string The encoded message as context + "\x04" + message
     */
    public function encodeMessageWithContext($message, $context)
    {
        // The encoding of a context and a message in a .mo file is
        // context + "\x04" + message (gettext version >= 0.15)
        return "{$context}\x04{$message}";
    }

    public function translate($message, $context = null)
    {
        if ($context !== null) {
            $messageForGettext = $this->encodeMessageWithContext($message, $context);
        } else {
            $messageForGettext = $message;
        }

        $translation = gettext($messageForGettext);

        if ($translation === $messageForGettext) {
            return $message;
        }

        return $translation;
    }

    public function translateInDomain($domain, $message, $context = null)
    {
        if ($context !== null) {
            $messageForGettext = $this->encodeMessageWithContext($message, $context);
        } else {
            $messageForGettext = $message;
        }

        $translation = dgettext(
            $domain,
            $messageForGettext
        );

        if ($translation === $messageForGettext) {
            $translation = dgettext(
                $this->getDefaultDomain(),
                $messageForGettext
            );
        }

        if ($translation === $messageForGettext) {
            return $message;
        }

        return $translation;
    }

    public function translatePlural($singular, $plural, $number, $context = null)
    {
        if ($context !== null) {
            $singularForGettext = $this->encodeMessageWithContext($singular, $context);
        } else {
            $singularForGettext = $singular;
        }


        $translation = ngettext(
            $singularForGettext,
            $plural,
            $number
        );

        if ($translation === $singularForGettext) {
            return $number === 1 ? $singular : $plural;
        }

        return $translation;
    }

    public function translatePluralInDomain($domain, $singular, $plural, $number, $context = null)
    {
        if ($context !== null) {
            $singularForGettext = $this->encodeMessageWithContext($singular, $context);
        } else {
            $singularForGettext = $singular;
        }

        $translation = dngettext(
            $domain,
            $singularForGettext,
            $plural,
            $number
        );

        $isSingular = $number === 1;

        if ($translation === ($isSingular ? $singularForGettext : $plural)) {
            $translation = dngettext(
                $this->getDefaultDomain(),
                $singularForGettext,
                $plural,
                $number
            );
        }

        if ($translation === $singularForGettext) {
            return $isSingular ? $singular : $plural;
        }

        return $translation;
    }

    /**
     * List available locales by traversing the translation directories from {@link addTranslationDirectory()}
     *
     * @return string[] Array of available locale codes
     */
    public function listLocales()
    {
        $locales = [];

        foreach (array_unique($this->getTranslationDirectories()) as $directory) {
            $fs = new FilesystemIterator(
                $directory,
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
            );

            foreach ($fs as $file) {
                if (! $file->isDir()) {
                    continue;
                }

                $locales[] = $file->getBasename();
            }
        }

        $locales = array_filter(array_unique($locales));

        sort($locales);

        return $locales;
    }
}
