<?php

namespace ipl\I18n;

class Locale
{
    /** @var string Default locale code */
    protected $defaultLocale = 'en_US';

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
     * Return the preferred locale based on the given HTTP header and the available translations
     *
     * @param string $header    The HTTP "Accept-Language" header
     * @param array  $available Available translations
     *
     * @return string The browser's preferred locale code
     */
    public function getPreferred($header, array $available)
    {
        $headerValues = explode(',', $header);
        for ($i = 0; $i < count($headerValues); $i++) {
            // In order to accomplish a stable sort we need to take the original
            // index into account as well during element comparison
            $headerValues[$i] = [$headerValues[$i], $i];
        }
        usort( // Sort DESC but keep equal elements ASC
            $headerValues,
            function ($a, $b) {
                $tagA = explode(';', $a[0], 2);
                $tagB = explode(';', $b[0], 2);
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
                $parts = explode(';', $headerValue, 2);
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
     * @return object Output of {@link \Locale::parseLocale()} converted to an object
     */
    public function parseLocale($locale)
    {
        return (object) \Locale::parseLocale($locale);
    }
}
