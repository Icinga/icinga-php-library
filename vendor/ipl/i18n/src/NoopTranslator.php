<?php

namespace ipl\I18n;

use ipl\Stdlib\Contract\Translator;

/**
 * Translator that just returns the original messages
 */
class NoopTranslator implements Translator
{
    public function translate(string $message, ?string $context = null): string
    {
        return $message;
    }

    public function translateInDomain(string $domain, string $message, ?string $context = null): string
    {
        return $message;
    }

    public function translatePlural(string $singular, string $plural, int $number, ?string $context = null): string
    {
        return $number === 1 ? $singular : $plural;
    }

    public function translatePluralInDomain(
        string $domain,
        string $singular,
        string $plural,
        int $number,
        ?string $context = null
    ): string {
        return $number === 1 ? $singular : $plural;
    }
}
