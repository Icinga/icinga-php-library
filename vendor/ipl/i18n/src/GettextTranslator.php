<?php

namespace ipl\I18n;

use Exception;
use FilesystemIterator;
use ipl\Stdlib\Contract\Translator;
use Locale;
use SplFileInfo;

/**
 * Translator using PHP's native [gettext](https://www.php.net/gettext) extension
 *
 * # Example Usage
 *
 *     $translator = (new GettextTranslator())
 *         ->addTranslationDirectory('/path/to/locales')
 *         ->addTranslationDirectory('/path/to/locales-of-domain', 'special') // Can be the same directory as above.
 *         ->setLocale('de_DE');
 *
 *     $translator->translate('user');
 *
 *     printf(
 *         $translator->translatePlural('%d user', '%d user', 42),
 *         42
 *     );
 *
 *     $translator->translateInDomain('special-domain', 'request');
 *
 *     printf(
 *         $translator->translatePluralInDomain('special-domain', '%d request', '%d requests', 42),
 *         42
 *     );
 *
 *     // All translation functions also accept a context as last parameter.
 *     $translator->translate('group', 'a-context');
 *
 */
class GettextTranslator implements Translator
{
    /** @var string Default gettext domain */
    protected string $defaultDomain = 'default';

    /** @var string Default locale code */
    protected string $defaultLocale = 'en_US';

    /** @var array<string, string> Known translation directories as array[$domain] => $directory */
    protected array $translationDirectories = [];

    /** @var array<string, string> Loaded translations as array[$domain] => $directory */
    protected array $loadedTranslations = [];

    /** @var ?string Primary locale code used for translations */
    protected ?string $locale = null;

    /**
     * Get the default domain
     *
     * @return string
     */
    public function getDefaultDomain(): string
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
    public function setDefaultDomain(string $defaultDomain): static
    {
        $this->defaultDomain = $defaultDomain;

        return $this;
    }

    /**
     * Get the default locale
     *
     * @return string
     */
    public function getDefaultLocale(): string
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
    public function setDefaultLocale(string $defaultLocale): static
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Get available translations
     *
     * @return array<string, string> Available translations as array[$domain] => $directory
     */
    public function getTranslationDirectories(): array
    {
        return $this->translationDirectories;
    }

    /**
     * Add a translation directory
     *
     * @param string $directory Path to translation files
     * @param ?string $domain Optional domain of the translation
     *
     * @return $this
     */
    public function addTranslationDirectory(string $directory, ?string $domain = null): static
    {
        $this->translationDirectories[$domain ?: $this->defaultDomain] = $directory;

        return $this;
    }

    /**
     * Get loaded translations
     *
     * @return array<string, string> Loaded translations as array[$domain] => $directory
     */
    public function getLoadedTranslations(): array
    {
        return $this->loadedTranslations;
    }

    /**
     * Load a translation so that gettext is able to locate its message catalogs
     *
     * {@see bindtextdomain()} is called internally for every domain and path
     * that has been added with {@see addTranslationDirectory()}.
     *
     * @return $this
     * @throws Exception If {@see bindtextdomain()} fails for a domain
     */
    public function loadTranslations(): static
    {
        foreach ($this->translationDirectories as $domain => $directory) {
            if (
                isset($this->loadedTranslations[$domain])
                && $this->loadedTranslations[$domain] === $directory
            ) {
                continue;
            }

            if (bindtextdomain($domain, $directory) !== $directory) {
                throw new Exception(sprintf(
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
     * @return ?string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Set up the primary locale code to use for translations
     *
     * Calls {@see loadTranslations()} internally.
     *
     * @param string $locale Locale code
     *
     * @return $this
     * @throws Exception If {@see bindtextdomain()} fails for a domain
     */
    public function setLocale(string $locale): static
    {
        putenv("LANGUAGE=$locale.UTF-8");
        setlocale(LC_ALL, $locale . '.UTF-8');
        Locale::setDefault($locale . '.UTF-8');

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
    public function encodeMessageWithContext(string $message, string $context): string
    {
        // .mo format: context + "\x04" + message (gettext >= 0.15).
        return "{$context}\x04{$message}";
    }

    public function translate(string $message, ?string $context = null): string
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

    public function translateInDomain(string $domain, string $message, ?string $context = null): string
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

    public function translatePlural(string $singular, string $plural, int $number, ?string $context = null): string
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

    public function translatePluralInDomain(
        string $domain,
        string $singular,
        string $plural,
        int $number,
        ?string $context = null
    ): string {
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
     * List available locales by traversing the translation directories from {@see addTranslationDirectory()}
     *
     * @return string[] Array of available locale codes
     */
    public function listLocales(): array
    {
        $locales = [];

        foreach (array_unique($this->getTranslationDirectories()) as $directory) {
            $fs = new FilesystemIterator(
                $directory,
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
            );

            /** @var SplFileInfo $file */
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
