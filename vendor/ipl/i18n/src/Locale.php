<?php

namespace ipl\I18n;

use ipl\Stdlib\Str;
use stdClass;

class Locale
{
    /** @var string Default locale code */
    protected string $defaultLocale = 'en_US';

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
     * Return the preferred locale based on the given HTTP header and the available translations
     *
     * @param string $header    The HTTP "Accept-Language" header
     * @param array<string> $available Available translations
     *
     * @return string The browser's preferred locale code
     */
    public function getPreferred(string $header, array $available): string
    {
        $headerValues = Str::trimSplit($header, ',');
        for ($i = 0; $i < count($headerValues); $i++) {
            // In order to accomplish a stable sort we need to take the original
            // index into account as well during element comparison
            $headerValues[$i] = [$headerValues[$i], $i];
        }
        usort( // Sort DESC but keep equal elements ASC
            $headerValues,
            function ($a, $b) {
                $tagA = Str::trimSplit($a[0], ';', 2);
                $tagB = Str::trimSplit($b[0], ';', 2);
                $qValA = (float) (strpos($a[0], ';') > 0 ? substr(array_pop($tagA), 2) : 1);
                $qValB = (float) (strpos($b[0], ';') > 0 ? substr(array_pop($tagB), 2) : 1);

                return $qValA < $qValB ? 1 : ($qValA > $qValB ? -1 : ($a[1] > $b[1] ? 1 : ($a[1] < $b[1] ? -1 : 0)));
            }
        );
        for ($i = 0; $i < count($headerValues); $i++) {
            // We need to reset the array to its original structure once it's sorted
            $headerValues[$i] = $headerValues[$i][0];
        }
        $requestedLocales = [];
        foreach ($headerValues as $headerValue) {
            if (strpos($headerValue, ';') > 0) {
                $parts = Str::trimSplit($headerValue, ';', 2);
                $headerValue = $parts[0];
            }
            $requestedLocales[] = str_replace('-', '_', $headerValue);
        }
        $requestedLocales = array_combine(
            array_map('strtolower', array_values($requestedLocales)),
            array_values($requestedLocales)
        );

        $available[] = $this->defaultLocale;
        $availableLocales = array_combine(
            array_map('strtolower', array_values($available)),
            array_values($available)
        );

        $similarMatch = null;

        foreach ($requestedLocales as $requestedLocaleLowered => $requestedLocale) {
            $localeObj = $this->parseLocale($requestedLocaleLowered);

            if (
                isset($availableLocales[$requestedLocaleLowered])
                && (! $similarMatch || $this->parseLocale($similarMatch)->language === $localeObj->language)
            ) {
                // Prefer perfect match only if no similar match has been found yet or the perfect match is more precise
                // than the similar match
                return $availableLocales[$requestedLocaleLowered];
            }

            if (! $similarMatch) {
                foreach ($availableLocales as $availableLocaleLowered => $availableLocale) {
                    if ($this->parseLocale($availableLocaleLowered)->language === $localeObj->language) {
                        $similarMatch = $availableLocaleLowered;
                        break;
                    }
                }
            }
        }

        return $similarMatch ? $availableLocales[$similarMatch] : $this->defaultLocale;
    }

    /**
     * Parse a locale into its subtags
     *
     * Converts to output of {@link \Locale::parseLocale()} to an object and returns it.
     *
     * @param string $locale
     *
     * @return stdClass Output of {@link \Locale::parseLocale()} converted to an object
     */
    public function parseLocale(string $locale): stdClass
    {
        return (object) \Locale::parseLocale($locale);
    }
}
